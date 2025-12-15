<?php

/**
 * ECTouch Model模型类
 * 待实现了ORM和ActiveRecords模式
 */
class Model extends BaseModel
{
    /**
     * 执行SQL查询返回一个字段
     * @param  string $sql SQL语句
     * @param  array $params 过滤参数
     * @return mixed
     */
    public function queryOne($sql, $params = array())
    {
        $data = $this->query($sql, $params);
        return isset($data[0]) ? current($data[0]) : array();
    }

    public function getOne($sql, $params = array()){
        $this->queryOne($sql, $params);
    }

    /**
     * 执行SQL查询返回一条
     * @param  string $sql SQL语句
     * @param  array $params 过滤参数
     * @return mixed
     */
    public function queryRow($sql, $params = array())
    {
        $data = $this->query($sql, $params);
        return isset($data[0]) ? $data[0] : array();
    }

    /**
     * 执行SQL查询返回多条
     * @param  string $sql SQL语句
     * @param  int $num 偏移数量
     * @param  int $start 开始位置
     * @return mixed
     */
    public function queryLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }
        return $this->query($sql);
    }

    /**
     * 执行SQL查询返回一列
     * @param string $sql SQL语句
     * @param array $params 过滤参数
     * @return array
     */
    public function queryCol($sql, $params = array())
    {
        $res = $this->query($sql, $params);
        if ($res !== false) {
            $arr = array();
            foreach ($res as $row) {
                $arr[] = reset($row);
            }
            return $arr;
        } else {
            return array();
        }
    }

    //只查询一条信息，返回一维数组
    public function getCol()
    {
        $res = $this->select();
        if ($res !== false) {
            $arr = array();
            foreach ($res as $row) {
                $arr[] = reset($row);
            }
            return $arr;
        } else {
            return array();
        }
    }
}

class BaseModel
{
    public $db = NULL; // 当前数据库操作对象
    public $cache = NULL;    //缓存对象
    public $sql = '';    //sql语句，主要用于输出构造成的sql语句
    public $pre = '';    //表前缀，主要用于在其他地方获取表前缀
    public $config = array(); //配置
    protected $options = array(); // 查询表达式参数	

    public function __construct($config = array())
    {
        //参数配置
        if (file_exists(DATA_PATH . 'config.php')) {
            $this->config = require(DATA_PATH . 'config.php');
        }
        if (empty($this->config) || !isset($this->config['DB_TYPE'])) {
            throw new Exception('database config error', 500);
        }
        $this->options['field'] = '*';    //默认查询字段
        $this->pre = $this->config['DB_PREFIX'];    //数据表前缀
        $this->connect();
    }

    //连接数据库
    public function connect()
    {
        $dbDriver = 'Ec' . ucfirst($this->config['DB_TYPE']);
        require_once(BASE_PATH . 'base/drivers/db/' . $dbDriver . '.class.php');
        $this->db = new $dbDriver($this->config);    //实例化数据库驱动类
    }

    //设置表，$$ignore_prefix为true的时候，不加上默认的表前缀
    public function table($table, $ignorePre = false)
    {
        if ($ignorePre) {
            $this->options['table'] = $table;
        } else {
            $this->options['table'] = $this->config['DB_PREFIX'] . $table;
        }
        return $this;
    }

    //回调方法，连贯操作的实现
    public function __call($method, $args)
    {
        $method = strtolower($method);
        if (in_array($method, array('field', 'data', 'where', 'group', 'having', 'order', 'limit', 'cache'))) {
            $this->options[$method] = $args[0];    //接收数据
            if ($this->options['field'] == '') $this->options['field'] = '*';
            return $this;    //返回对象，连贯查询
        } else {
            throw new Exception($method . '方法在Model.php类中没有定义');
        }
    }

    //执行原生sql语句，如果sql是查询语句，返回二维数组
    public function query($sql, $params = array(), $is_query = false)
    {
        if (empty($sql)) return false;
        $sql = str_replace('{pre}', $this->pre, $sql);    //表前缀替换
        $this->sql = $sql;
        //判断当前的sql是否是查询语句
        if ($is_query || strpos(trim(strtolower($sql)), 'select') === 0) {
            $data = $this->_readCache();
            if (!empty($data)) return $data;

            $query = $this->db->query($this->sql, $params);
            while ($row = $this->db->fetchArray($query)) {
                $data[] = $row;
            }
            if (!is_array($data)) {
                $data = array();
            }
            $this->_writeCache($data);
            return $data;
        } else {
            return $this->db->execute($this->sql, $params); //不是查询条件，直接执行
        }
    }

    //统计行数
    public function count()
    {
        $table = $this->options['table'];    //当前表
        $field = 'count(*)';//查询的字段
        $where = $this->_parseCondition();    //条件
        $this->sql = "SELECT $field FROM $table $where";    //这不是真正执行的sql，仅作缓存的key使用

        $data = $this->_readCache();
        if (!empty($data)) return $data;

        $data = $this->db->count($table, $where);
        $this->_writeCache($data);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    //只查询字段信息，返回字段值
    public function getField()
    {
        $field = $this->options['field'];    //查询的字段
        $data = $this->find();
        return isset($data[$field]) ? $data[$field] : false;
    }

    //只查询一条信息，返回一维数组
    public function find()
    {
        $this->options['limit'] = 1;    //限制只查询一条数据
        $data = $this->select();
        return isset($data[0]) ? $data[0] : false;
    }

    //查询多条信息，返回数组
    public function select()
    {
        $table = $this->options['table'];    //当前表
        $field = $this->options['field'];    //查询的字段
        $where = $this->_parseCondition();    //条件
        return $this->query("SELECT $field FROM $table $where", array(), true);
    }

    //获取一张表的所有字段
    public function getFields()
    {
        $table = $this->options['table'];
        $this->sql = "SHOW FULL FIELDS FROM {$table}"; //这不是真正执行的sql，仅作缓存的key使用

        $data = $this->_readCache();
        if (!empty($data)) return $data;

        $data = $this->db->getFields($table);
        $this->_writeCache($data);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    //插入数据
    public function insert($replace = false)
    {
        $table = $this->options['table'];    //当前表
        $data = $this->_parseData('add');    //要插入的数据
        $INSERT = $replace ? 'REPLACE' : 'INSERT';
        $this->sql = "$INSERT INTO $table $data";
        $query = $this->db->execute($this->sql);
        if ($this->db->affectedRows()) {
            $id = $this->db->lastId();
            return empty($id) ? $this->db->affectedRows() : $id;
        }
        return false;
    }

    //替换数据
    public function replace()
    {
        return $this->insert(true);
    }

    //修改更新
    public function update()
    {
        $table = $this->options['table'];    //当前表
        $data = $this->_parseData('save');    //要更新的数据
        $where = $this->_parseCondition();    //更新条件
        if (empty($where)) return false; //修改条件为空时，则返回false，避免不小心将整个表数据修改了

        $this->sql = "UPDATE $table SET $data $where";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    //删除
    public function delete()
    {
        $table = $this->options['table'];    //当前表
        $where = $this->_parseCondition();    //条件
        if (empty($where)) return false; //删除条件为空时，则返回false，避免数据不小心被全部删除

        $this->sql = "DELETE FROM $table $where";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    //数据过滤
    public function escape($value)
    {
        return $this->db->escape($value);
    }

    //返回sql语句
    public function getSql()
    {
        return $this->sql;
    }

    //删除数据库缓存
    public function clear()
    {
        if ($this->initCache()) {
            return $this->cache->clear();
        }
        return false;
    }

    //初始化缓存类，如果开启缓存，则加载缓存类并实例化
    public function initCache()
    {
        if (is_object($this->cache)) {
            return true;
        } else if ($this->config['DB_CACHE_ON']) {
            $this->cache = new Cache($this->config, $this->config['DB_CACHE_TYPE']);
            return true;
        } else {
            return false;
        }
    }

    //读取缓存
    private function _readCache()
    {
        isset($this->options['cache']) or $this->options['cache'] = $this->config['DB_CACHE_TIME'];
        //缓存时间为0，不读取缓存
        if ($this->options['cache'] == 0)
            return false;
        if ($this->initCache()) {
            $data = $this->cache->get($this->sql);
            if (!empty($data)) {
                unset($this->options['cache']);
                return $data;
            }
        }
        return false;
    }

    //写入缓存
    private function _writeCache($data)
    {
        //缓存时间为0，不设置缓存
        if ($this->options['cache'] == 0)
            return false;
        if ($this->initCache()) {
            $expire = $this->options['cache'];
            unset($this->options['cache']);
            return $this->cache->set($this->sql, $data, $expire);
        }
        return false;
    }

    //解析数据
    private function _parseData($type)
    {
        $data = $this->db->parseData($this->options, $type);
        $this->options['data'] = '';
        return $data;
    }

    //解析条件
    private function _parseCondition()
    {
        $condition = $this->db->parseCondition($this->options);
        $this->options['where'] = '';
        $this->options['group'] = '';
        $this->options['having'] = '';
        $this->options['order'] = '';
        $this->options['limit'] = '';
        $this->options['field'] = '*';
        return $condition;
    }
}