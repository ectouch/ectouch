<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 文件缓存类 基于secache修改
 */
class EcFileCache {

    private $idx_node_size = 40;
    private $data_base_pos = 262588; //40+20+24*16+16*16*16*16*4;
    private $schema_item_size = 24;
    private $header_padding = 20; //保留空间 放置php标记防止下载
    private $info_size = 20; //保留空间 4+16 maxsize|ver
    //40起 添加20字节保留区域
    private $idx_seq_pos = 40; //id 计数器节点地址
    private $dfile_cur_pos = 44; //id 计数器节点地址
    private $idx_free_pos = 48; //id 空闲链表入口地址
    private $idx_base_pos = 444; //40+20+24*16
    private $schema_struct = array('size', 'free', 'lru_head', 'lru_tail', 'hits', 'miss');
    private $ver = '$Rev: 3 $';
    public $config = array();

    public function __construct($config = array()) {
        $this->config = array(
            'DB_CACHE_PATH' => 'data/db_cache/', //缓存目录
            'DB_CACHE_CHECK' => 'false', //是否验证数据
            'DB_CACHE_FILE' => 'cachedata', //缓存的数据文件名
            'DB_CACHE_SIZE' => '15M', //预设的缓存大小
            'DB_CACHE_FLOCK' => 'true', //是否存在文件锁，设置为false，将模拟文件锁									
        );
        $this->config = array_merge($this->config, (array) $config);
        //检查缓存目录
        $this->_checkDir();
        $this->workat($this->config['DB_CACHE_PATH'] . $this->config['DB_CACHE_FILE']);
    }

    //读取缓存
    public function get($key) {
        $key = md5($key); //设置索引值		
        if ($this->fetch($key, $content)) {
            $expire = (int) substr($content, 0, 12);
            if ($expire != -1 && time() >= $expire) {
                return false;
            }
            if ($this->config['DB_CACHE_CHECK']) {
                //开启数据校验
                $check = substr($content, 12, 32);
                $content = substr($content, 44);
                if ($check != md5($content)) {
                    return false; //校验错误
                }
            } else {
                $content = substr($content, 12);
            }
            return unserialize($content); //解序列化数据
        } else {
            return false;
        }
    }

    //设置缓存
    public function set($key, $value, $expire = 1800) {
        $key = md5($key); //设置索引值				
        $value = serialize($value); //将数据序列化
        $expire = ($expire == -1) ? $expire : (time() + $expire); //过期时间
        //是否开启数据校验
        $check = $this->config['DB_CACHE_CHECK'] ? md5($value) : '';
        $value = sprintf('%012d', $expire) . $check . $value;
        return $this->store($key, $value); //存储数据
    }

    //自增1
    public function inc($key, $value = 1) {
        return $this->set($key, intval($this->get($key)) + intval($value), -1);
    }

    //自减1
    public function des($key, $value = 1) {
        return $this->set($key, intval($this->get($key)) - intval($value), -1);
    }

    //删除
    public function del($key) {
        return $this->set($key, '', 0);
    }

    //清空缓存
    public function clear() {
        return $this->_format(true);
    }

    /*     * ******** 下面是缓存算法的具体实现 ********* */

    //检查缓存目录
    private function _checkDir() {
        // 如果缓存目录不存在或者不是目录，则创建缓存目录
        if ((!file_exists($this->config['DB_CACHE_PATH'])) || (!is_dir($this->config['DB_CACHE_PATH']))) {
            //创建缓存目录
            if (!@mkdir($this->config['DB_CACHE_PATH'], 0777, true)) {
                return false;
            }
        }
        //检查缓存目录是否可写，不可写则修改它的属性
        if (!is_writable($this->config['DB_CACHE_PATH'])) {
            return @chmod($this->config['DB_CACHE_PATH'], 0777);
        }
        return true;
    }

    public function workat($file) {

        $this->_file = $file . '.php';
        $this->_bsize_list = array(
            512 => 10,
            3 << 10 => 10,
            8 << 10 => 10,
            20 << 10 => 4,
            30 << 10 => 2,
            50 << 10 => 2,
            80 << 10 => 2,
            96 << 10 => 2,
            128 << 10 => 2,
            224 << 10 => 2,
            256 << 10 => 2,
            512 << 10 => 1,
            1024 << 10 => 1,
        );

        $this->_node_struct = array(
            'next' => array(0, 'V'),
            'prev' => array(4, 'V'),
            'data' => array(8, 'V'),
            'size' => array(12, 'V'),
            'lru_right' => array(16, 'V'),
            'lru_left' => array(20, 'V'),
            'key' => array(24, 'H*'),
        );

        if (!file_exists($this->_file)) {
            $this->create();
        } else {
            $this->_rs = fopen($this->_file, 'rb+') or $this->trigger_error('Can\'t open the cachefile: ' . realpath($this->_file), E_USER_ERROR);
            $this->_seek($this->header_padding);
            $info = unpack('V1max_size/a*ver', fread($this->_rs, $this->info_size));
            if ($info['ver'] != $this->ver) {
                $this->_format(true);
            } else {
                $this->max_size = $info['max_size'];
            }
        }

        $this->idx_node_base = $this->data_base_pos + $this->max_size;
        $this->_block_size_list = array_keys($this->_bsize_list);
        sort($this->_block_size_list);
        return true;
    }

    public function fetch($key, &$return) {

        if ($this->lock(false)) {
            $locked = true;
        }

        if ($this->search($key, $offset)) {
            $info = $this->_get_node($offset);
            $schema_id = $this->_get_size_schema_id($info['size']);
            if ($schema_id === false) {
                if ($locked)
                    $this->unlock();
                return false;
            }

            $this->_seek($info['data']);
            //去除反序列化数据
            // $data = fread($this->_rs,$info['size']);
            // $return = unserialize($data);
            $return = fread($this->_rs, $info['size']);
            if ($return === false) {
                if ($locked)
                    $this->unlock();
                return false;
            }

            if ($locked) {
                $this->_lru_push($schema_id, $info['offset']);
                $this->_set_schema($schema_id, 'hits', $this->_get_schema($schema_id, 'hits') + 1);
                return $this->unlock();
            } else {
                return true;
            }
        } else {
            if ($locked)
                $this->unlock();
            return false;
        }
    }

    public function store($key, $data) {

        if ($this->lock(true)) {
            //save data
            //去除序列化数据
            //$data = serialize($value);
            $size = strlen($data);

            //get list_idx
            $has_key = $this->search($key, $list_idx_offset);
            $schema_id = $this->_get_size_schema_id($size);
            if ($schema_id === false) {
                $this->unlock();
                return false;
            }
            if ($has_key) {
                $hdseq = $list_idx_offset;

                $info = $this->_get_node($hdseq);
                if ($schema_id == $this->_get_size_schema_id($info['size'])) {
                    $dataoffset = $info['data'];
                } else {
                    //破掉原有lru
                    $this->_lru_delete($info);
                    if (!($dataoffset = $this->_dalloc($schema_id))) {
                        $this->unlock();
                        return false;
                    }
                    $this->_free_dspace($info['size'], $info['data']);
                    $this->_set_node($hdseq, 'lru_left', 0);
                    $this->_set_node($hdseq, 'lru_right', 0);
                }

                $this->_set_node($hdseq, 'size', $size);
                $this->_set_node($hdseq, 'data', $dataoffset);
            } else {

                if (!($dataoffset = $this->_dalloc($schema_id))) {
                    $this->unlock();
                    return false;
                }
                $hdseq = $this->_alloc_idx(array(
                    'next' => 0,
                    'prev' => $list_idx_offset,
                    'data' => $dataoffset,
                    'size' => $size,
                    'lru_right' => 0,
                    'lru_left' => 0,
                    'key' => $key,
                ));

                if ($list_idx_offset > 0) {
                    $this->_set_node($list_idx_offset, 'next', $hdseq);
                } else {
                    $this->_set_node_root($key, $hdseq);
                }
            }

            if ($dataoffset > $this->max_size) {
                $this->trigger_error('alloc datasize:' . $dataoffset, E_USER_WARNING);
                return false;
            }
            $this->_puts($dataoffset, $data);

            $this->_set_schema($schema_id, 'miss', $this->_get_schema($schema_id, 'miss') + 1);

            $this->_lru_push($schema_id, $hdseq);
            $this->unlock();
            return true;
        } else {
            $this->trigger_error("Couldn't lock the file !", E_USER_WARNING);
            return false;
        }
    }

    //锁定文件
    protected function lock($is_block, $whatever = false) {

        if ($this->config['DB_CACHE_FLOCK'])
            return flock($this->_rs, $is_block ? LOCK_EX : LOCK_EX + LOCK_NB);

        ignore_user_abort(true);
        $support_usleep = version_compare(PHP_VERSION, 5, '>=') ? 20 : 1;
        $lockfile = $this->_file . '.lck';

        if (file_exists($lockfile)) {
            if (time() - filemtime($lockfile) > 0) {
                unlink($lockfile);
            } elseif (!$is_block) {
                return false;
            }
        }

        $lock_ex = @fopen($lockfile, 'x');
        for ($i = 0; ($lock_ex === false) && ($whatever || $i < 10); $i++) {
            clearstatcache();
            if ($support_usleep == 1) {
                usleep(rand(9, 999));
            } else {
                sleep(1);
            }
            $lock_ex = @fopen($lockfile, 'x');
        }
        return ($lock_ex !== false);
    }

    //解除文件锁定
    protected function unlock() {
        if ($this->config['DB_CACHE_FLOCK'])
            return flock($this->_rs, LOCK_UN);

        ignore_user_abort(false);
        return @unlink($this->_file . '.lck');
    }

    protected function create() {
        $this->_rs = @fopen($this->_file, 'wb+') or $this->trigger_error('创建缓存文件失败: ' . $this->_file, E_USER_ERROR);
        ;
        fseek($this->_rs, 0);
        fputs($this->_rs, '<' . '?php exit()?' . '>');
        return $this->_format();
    }

    private function _puts($offset, $data) {
        if ($offset < $this->max_size * 1.5) {
            $this->_seek($offset);
            return fputs($this->_rs, $data);
        } else {
            $this->trigger_error('Offset over quota:' . $offset, E_USER_ERROR);
        }
    }

    private function _seek($offset) {
        return fseek($this->_rs, $offset);
    }

    protected function delete($key, $pos = false) {
        if ($pos || $this->search($key, $pos)) {
            if ($info = $this->_get_node($pos)) {
                //删除data区域
                if ($info['prev']) {
                    $this->_set_node($info['prev'], 'next', $info['next']);
                    $this->_set_node($info['next'], 'prev', $info['prev']);
                } else { //改入口位置
                    $this->_set_node($info['next'], 'prev', 0);
                    $this->_set_node_root($key, $info['next']);
                }
                $this->_free_dspace($info['size'], $info['data']);
                $this->_lru_delete($info);
                $this->_free_node($pos);
                return $info['prev'];
            }
        }
        return false;
    }

    /**
     * search 
     * 查找指定的key
     * 如果找到节点则$pos=节点本身 返回true
     * 否则 $pos=树的末端 返回false
     * 
     * @param mixed $key 
     * @access public
     * @return void
     */
    protected function search($key, &$pos) {
        return $this->_get_pos_by_key($this->_get_node_root($key), $key, $pos);
    }

    private function _get_size_schema_id($size) {
        foreach ($this->_block_size_list as $k => $block_size) {
            if ($size <= $block_size) {
                return $k;
            }
        }
        return false;
    }

    private function _parse_str_size($str_size, $default) {
        if (preg_match('/^([0-9]+)\s*([gmk]|)$/i', $str_size, $match)) {
            switch (strtolower($match[2])) {
                case 'g':
                    if ($match[1] > 1) {
                        $this->trigger_error('Max cache size 1G', E_USER_ERROR);
                    }
                    $size = $match[1] << 30;
                    break;
                case 'm':
                    $size = $match[1] << 20;
                    break;
                case 'k':
                    $size = $match[1] << 10;
                    break;
                default:
                    $size = $match[1];
            }
            if ($size <= 0) {
                $this->trigger_error('Error cache size ' . $this->max_size, E_USER_ERROR);
                return false;
            } elseif ($size < 10485760) {
                return 10485760;
            } else {
                return $size;
            }
        } else {
            return $default;
        }
    }

    private function _format($truncate = false) {
        if ($this->lock(true, true)) {

            if ($truncate) {
                $this->_seek(0);
                ftruncate($this->_rs, $this->idx_node_base);
            }

            $this->max_size = $this->_parse_str_size($this->config['DB_CACHE_SIZE'], 15728640); //default:15m
            $this->_puts($this->header_padding, pack('V1a*', $this->max_size, $this->ver));

            ksort($this->_bsize_list);
            $ds_offset = $this->data_base_pos;
            $i = 0;
            foreach ($this->_bsize_list as $size => $count) {

                //将预分配的空间注册到free链表里
                $count *= min(3, floor($this->max_size / 10485760));
                $next_free_node = 0;
                for ($j = 0; $j < $count; $j++) {
                    $this->_puts($ds_offset, pack('V', $next_free_node));
                    $next_free_node = $ds_offset;
                    $ds_offset+=intval($size);
                }

                $code = pack(str_repeat('V1', count($this->schema_struct)), $size, $next_free_node, 0, 0, 0, 0);

                $this->_puts(60 + $i * $this->schema_item_size, $code);
                $i++;
            }
            $this->_set_dcur_pos($ds_offset);

            $this->_puts($this->idx_base_pos, str_repeat("\0", 262144));
            $this->_puts($this->idx_seq_pos, pack('V', 1));
            $this->unlock();
            return true;
        } else {
            $this->trigger_error("Couldn't lock the file !", E_USER_ERROR);
            return false;
        }
    }

    private function _get_node_root($key) {
        $this->_seek(hexdec(substr($key, 0, 4)) * 4 + $this->idx_base_pos);
        $a = fread($this->_rs, 4);
        list(, $offset) = unpack('V', $a);
        return $offset;
    }

    private function _set_node_root($key, $value) {
        return $this->_puts(hexdec(substr($key, 0, 4)) * 4 + $this->idx_base_pos, pack('V', $value));
    }

    private function _set_node($pos, $key, $value) {

        if (!$pos) {
            return false;
        }

        if (isset($this->_node_struct[$key])) {
            return $this->_puts($pos * $this->idx_node_size + $this->idx_node_base + $this->_node_struct[$key][0], pack($this->_node_struct[$key][1], $value));
        } else {
            return false;
        }
    }

    private function _get_pos_by_key($offset, $key, &$pos) {
        if (!$offset) {
            $pos = 0;
            return false;
        }

        $info = $this->_get_node($offset);

        if ($info['key'] == $key) {
            $pos = $info['offset'];
            return true;
        } elseif ($info['next'] && $info['next'] != $offset) {
            return $this->_get_pos_by_key($info['next'], $key, $pos);
        } else {
            $pos = $offset;
            return false;
        }
    }

    private function _lru_delete($info) {

        if ($info['lru_right']) {
            $this->_set_node($info['lru_right'], 'lru_left', $info['lru_left']);
        } else {
            $this->_set_schema($this->_get_size_schema_id($info['size']), 'lru_tail', $info['lru_left']);
        }

        if ($info['lru_left']) {
            $this->_set_node($info['lru_left'], 'lru_right', $info['lru_right']);
        } else {
            $this->_set_schema($this->_get_size_schema_id($info['size']), 'lru_head', $info['lru_right']);
        }

        return true;
    }

    private function _lru_push($schema_id, $offset) {
        $lru_head = $this->_get_schema($schema_id, 'lru_head');
        $lru_tail = $this->_get_schema($schema_id, 'lru_tail');

        if ((!$offset) || ($lru_head == $offset))
            return;

        $info = $this->_get_node($offset);

        $this->_set_node($info['lru_right'], 'lru_left', $info['lru_left']);
        $this->_set_node($info['lru_left'], 'lru_right', $info['lru_right']);

        $this->_set_node($offset, 'lru_right', $lru_head);
        $this->_set_node($offset, 'lru_left', 0);

        $this->_set_node($lru_head, 'lru_left', $offset);
        $this->_set_schema($schema_id, 'lru_head', $offset);

        if ($lru_tail == 0) {
            $this->_set_schema($schema_id, 'lru_tail', $offset);
        } elseif ($lru_tail == $offset && $info['lru_left']) {
            $this->_set_schema($schema_id, 'lru_tail', $info['lru_left']);
        }
        return true;
    }

    private function _get_node($offset) {
        $this->_seek($offset * $this->idx_node_size + $this->idx_node_base);
        $info = unpack('V1next/V1prev/V1data/V1size/V1lru_right/V1lru_left/H*key', fread($this->_rs, $this->idx_node_size));
        $info['offset'] = $offset;
        return $info;
    }

    private function _lru_pop($schema_id) {
        if ($node = $this->_get_schema($schema_id, 'lru_tail')) {
            $info = $this->_get_node($node);
            if (!$info['data']) {
                return false;
            }
            $this->delete($info['key'], $info['offset']);
            if (!$this->_get_schema($schema_id, 'free')) {
                $this->trigger_error('pop lru,But nothing free...', E_USER_ERROR);
            }
            return $info;
        } else {
            return false;
        }
    }

    private function _dalloc($schema_id, $lru_freed = false) {

        if ($free = $this->_get_schema($schema_id, 'free')) { //如果lru里有链表
            $this->_seek($free);
            list(, $next) = unpack('V', fread($this->_rs, 4));
            $this->_set_schema($schema_id, 'free', $next);
            return $free;
        } elseif ($lru_freed) {
            $this->trigger_error('Bat lru poped freesize', E_USER_ERROR);
            return false;
        } else {
            $ds_offset = $this->_get_dcur_pos();
            $size = $this->_get_schema($schema_id, 'size');

            if ($size + $ds_offset > $this->max_size) {
                if ($info = $this->_lru_pop($schema_id)) {
                    return $this->_dalloc($schema_id, $info);
                } else {
                    $this->trigger_error('Can\'t alloc dataspace', E_USER_ERROR);
                    return false;
                }
            } else {
                $this->_set_dcur_pos($ds_offset + $size);
                return $ds_offset;
            }
        }
    }

    private function _get_dcur_pos() {
        $this->_seek($this->dfile_cur_pos);
        list(, $ds_offset) = unpack('V', fread($this->_rs, 4));
        return $ds_offset;
    }

    private function _set_dcur_pos($pos) {
        return $this->_puts($this->dfile_cur_pos, pack('V', $pos));
    }

    private function _free_dspace($size, $pos) {

        if ($pos > $this->max_size) {
            $this->trigger_error('free dspace over quota:' . $pos, E_USER_ERROR);
            return false;
        }

        $schema_id = $this->_get_size_schema_id($size);
        if ($free = $this->_get_schema($schema_id, 'free')) {
            $this->_puts($free, pack('V1', $pos));
        } else {
            $this->_set_schema($schema_id, 'free', $pos);
        }
        $this->_puts($pos, pack('V1', 0));
    }

    private function _dfollow($pos, &$c) {
        $c++;
        $this->_seek($pos);
        list(, $next) = unpack('V1', fread($this->_rs, 4));
        if ($next) {
            return $this->_dfollow($next, $c);
        } else {
            return $pos;
        }
    }

    private function _free_node($pos) {
        $this->_seek($this->idx_free_pos);
        list(, $prev_free_node) = unpack('V', fread($this->_rs, 4));
        $this->_puts($pos * $this->idx_node_size + $this->idx_node_base, pack('V', $prev_free_node) . str_repeat("\0", $this->idx_node_size - 4));
        return $this->_puts($this->idx_free_pos, pack('V', $pos));
    }

    private function _alloc_idx($data) {
        $this->_seek($this->idx_free_pos);
        list(, $list_pos) = unpack('V', fread($this->_rs, 4));
        if ($list_pos) {

            $this->_seek($list_pos * $this->idx_node_size + $this->idx_node_base);
            list(, $prev_free_node) = unpack('V', fread($this->_rs, 4));
            $this->_puts($this->idx_free_pos, pack('V', $prev_free_node));
        } else {
            $this->_seek($this->idx_seq_pos);
            list(, $list_pos) = unpack('V', fread($this->_rs, 4));
            $this->_puts($this->idx_seq_pos, pack('V', $list_pos + 1));
        }
        return $this->_create_node($list_pos, $data);
    }

    private function _create_node($pos, $data) {
        $this->_puts($pos * $this->idx_node_size + $this->idx_node_base
                , pack('V1V1V1V1V1V1H*', $data['next'], $data['prev'], $data['data'], $data['size'], $data['lru_right'], $data['lru_left'], $data['key']));
        return $pos;
    }

    private function _set_schema($schema_id, $key, $value) {
        $info = array_flip($this->schema_struct);
        return $this->_puts(60 + $schema_id * $this->schema_item_size + $info[$key] * 4, pack('V', $value));
    }

    private function _get_schema($id, $key) {
        $info = array_flip($this->schema_struct);

        $this->_seek(60 + $id * $this->schema_item_size);
        unpack('V1' . implode('/V1', $this->schema_struct), fread($this->_rs, $this->schema_item_size));

        $this->_seek(60 + $id * $this->schema_item_size + $info[$key] * 4);
        list(, $value) = unpack('V', fread($this->_rs, 4));
        return $value;
    }

    private function _all_schemas() {
        $schema = array();
        for ($i = 0; $i < 16; $i++) {
            $this->_seek(60 + $i * $this->schema_item_size);
            $info = unpack('V1' . implode('/V1', $this->schema_struct), fread($this->_rs, $this->schema_item_size));
            if ($info['size']) {
                $info['id'] = $i;
                $schema[$i] = $info;
            } else {
                return $schema;
            }
        }
    }

    protected function schemaStatus() {
        $return = array();
        foreach ($this->_all_schemas() as $k => $schemaItem) {
            if ($schemaItem['free']) {
                $this->_dfollow($schemaItem['free'], $schemaItem['freecount']);
            }
            $return[] = $schemaItem;
        }
        return $return;
    }

    protected function status(&$curBytes, &$totalBytes) {
        $totalBytes = $curBytes = 0;
        $hits = $miss = 0;

        $schemaStatus = $this->schemaStatus();
        $totalBytes = $this->max_size;
        $freeBytes = $this->max_size - $this->_get_dcur_pos();

        foreach ($schemaStatus as $schema) {
            $freeBytes+=$schema['freecount'] * $schema['size'];
            $miss += $schema['miss'];
            $hits += $schema['hits'];
        }
        $curBytes = $totalBytes - $freeBytes;

        $return[] = array('name' => '缓存命中', 'value' => $hits);
        $return[] = array('name' => '缓存未命中', 'value' => $miss);
        return $return;
    }

    protected function trigger_error($errstr, $errno) {
        throw new Exception($errstr); //输出错误信息
    }

}
