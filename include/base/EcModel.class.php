<?php

declare(strict_types=1);

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：模型类，加载了外部的数据库驱动类和缓存类
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcModel
{
    public mixed $db = null; // 当前数据库操作对象
    public mixed $cache = null; //缓存对象
    public string $sql = ''; //sql语句，主要用于输出构造成的sql语句
    public string $pre = ''; //表前缀，主要用于在其他地方获取表前缀
    public array $config = []; //配置
    protected array $options = []; // 查询表达式参数
    public string|float $queryTime = ''; // sql运行时间
    public int $queryCount = 0; // sql运行次数
    public array $queryLog = [];

    /**
     * 构造函数
     * @param unknown $config
     */
    public function __construct(array|string $config = [])
    {
        $this->config = array_merge(C('DB'), $config); //参数配置
        $this->options['field'] = '*'; //默认查询字段
        $this->pre = $this->config['DB_PREFIX']; //数据表前缀
        $this->connect();
    }

    /**
     * 连接数据库
     */
    public function connect()
    {
        $dbDriver = 'Ec' . ucfirst($this->config['DB_TYPE']);
        require_once(dirname(__FILE__) . '/drivers/db/' . $dbDriver . '.class.php');
        $this->db = new $dbDriver($this->config); //实例化数据库驱动类
    }

    /**
     * 设置表，$$ignore_prefix为true的时候，不加上默认的表前缀
     * @param unknown $table
     * @param string $ignorePre
     * @return EcModel
     */
    public function table(string $table, bool $ignorePre = false): self
    {
        if ($ignorePre) {
            $this->options['table'] = $table;
        } else {
            $this->options['table'] = $this->config['DB_PREFIX'] . $table;
        }
        return $this;
    }

    /**
     * 回调方法，连贯操作的实现
     * @param unknown $method
     * @param unknown $args
     * @throws Exception
     * @return EcModel
     */
    public function __call(string $method, array $args): self
    {
        $method = strtolower($method);
        if (in_array($method, array('field', 'data', 'where', 'group', 'having', 'order', 'limit', 'cache'))) {
            $this->options[$method] = $args[0]; //接收数据
            if ($this->options['field'] == '') {
                $this->options['field'] = '*';
            }
            return $this; //返回对象，连贯查询
        } else {
            throw new Exception($method . '方法在EcModel.class.php类中没有定义');
        }
    }

    /**
     * 执行原生sql语句，如果sql是查询语句，返回二维数组
     * @param unknown $sql
     * @param unknown $params
     * @param string $is_query
     * @return boolean|unknown|Ambigous <multitype:, unknown>
     */
    public function query(string $sql, array $params = [], bool $is_query = false): array|bool
    {
        if (empty($sql)) {
            return false;
        }
        $sql = str_replace('{pre}', $this->pre, $sql); //表前缀替换
        $this->sql = $sql;
        if ($this->queryCount++ <= 99) {
            $this->queryLog[] = $sql;
        }
        if ($this->queryTime == '') {
            $this->queryTime = microtime(true);
        }
        //判断当前的sql是否是查询语句
        if ($is_query || stripos(trim($sql), 'select') === 0) {
            $data = $this->_readCache();
            if (!empty($data)) {
                return $data;
            }
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

    /**
     * 统计行数
     * @return Ambigous <boolean, unknown>|unknown
     */
    public function count(): int
    {
        $table = $this->options['table']; //当前表
        $field = 'count(*)'; //查询的字段
        $where = $this->_parseCondition(); //条件
        $this->sql = "SELECT $field FROM $table $where"; //这不是真正执行的sql，仅作缓存的key使用

        $data = $this->_readCache();
        if (!empty($data)) {
            return $data;
        }

        $data = $this->db->count($table, $where);
        $this->_writeCache($data);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    /**
     * 只查询一条信息，返回一维数组
     * @return boolean
     */
    public function find(): array|false
    {
        $this->options['limit'] = 1; //限制只查询一条数据
        $data = $this->select();
        return isset($data[0]) ? $data[0] : false;
    }

    /**
     * 返回一个字段
     * @return boolean
     */
    public function getOne(): mixed
    {
        $this->options['limit'] = 1; //限制只查询一条数据
        $field = $this->options['field'];
        $data = $this->select();
        return isset($data[0][$field]) ? $data[0][$field] : false;
    }

    /**
     * 返回指定列
     * @return Ambigous <boolean, unknown>
     */
    public function getCol(): array|false
    {
        $field = $this->options['field'];
        $data = $this->select();
        foreach ($data as $vo) {
            $arr[] = $vo[$field];
        }
        return isset($arr) ? $arr : false;
    }
    
    /**
     * 查询多条信息，返回数组
     */
    public function select(): array|bool
    {
        $table = $this->options['table']; //当前表
        $field = $this->options['field']; //查询的字段
        $where = $this->_parseCondition(); //条件
        return $this->query("SELECT $field FROM $table $where", array(), true);
    }

    /**
     * 获取一张表的所有字段
     * @return Ambigous <boolean, unknown>|unknown
     */
    public function getFields(): array|false
    {
        $table = $this->options['table'];
        $this->sql = "SHOW FULL FIELDS FROM {$table}"; //这不是真正执行的sql，仅作缓存的key使用

        $data = $this->_readCache();
        if (!empty($data)) {
            return $data;
        }

        $data = $this->db->getFields($table);
        $this->_writeCache($data);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    /**
     * 插入数据
     * @param string $replace
     * @return unknown|boolean
     */
    public function insert(bool $replace = false): int|bool
    {
        $table = $this->options['table']; //当前表
        $data = $this->_parseData('add'); //要插入的数据
        $INSERT = $replace ? 'REPLACE' : 'INSERT';
        $this->sql = "$INSERT INTO $table $data";
        $query = $this->db->execute($this->sql);
        if ($this->db->affectedRows()) {
            $id = $this->db->lastId();
            return empty($id) ? $this->db->affectedRows() : $id;
        }
        return false;
    }

    /**
     * 替换数据
     * @return Ambigous <unknown, boolean, unknown>
     */
    public function replace(): int|bool
    {
        return $this->insert(true);
    }

    /**
     * 修改更新
     * @return boolean
     */
    public function update(): int|bool
    {
        $table = $this->options['table']; //当前表
        $data = $this->_parseData('save'); //要更新的数据
        $where = $this->_parseCondition(); //更新条件
        if (empty($where)) {
            return false;
        } //修改条件为空时，则返回false，避免不小心将整个表数据修改了

        $this->sql = "UPDATE $table SET $data $where";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    /**
     * 删除
     * @return boolean
     */
    public function delete(): int|bool
    {
        $table = $this->options['table']; //当前表
        $where = $this->_parseCondition(); //条件
        if (empty($where)) {
            return false;
        } //删除条件为空时，则返回false，避免数据不小心被全部删除

        $this->sql = "DELETE FROM $table $where";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    /**
     * 数据过滤
     * @param unknown $value
     */
    public function escape(string $value): string
    {
        return $this->db->escape($value);
    }

    /**
     * 返回sql语句
     * @return string
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * 删除数据库缓存
     * @return boolean
     */
    public function clear(): bool
    {
        if ($this->initCache()) {
            return $this->cache->clear();
        }
        return false;
    }

    /**
     * 初始化缓存类，如果开启缓存，则加载缓存类并实例化
     * @return boolean
     */
    public function initCache(): bool
    {
        if (is_object($this->cache)) {
            return true;
        } elseif ($this->config['DB_CACHE_ON']) {
            require_once(dirname(__FILE__) . '/EcCache.class.php');
            $this->cache = new EcCache($this->config, $this->config['DB_CACHE_TYPE']);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 读取缓存
     * @return boolean|unknown
     */
    private function _readCache(): mixed
    {
        isset($this->options['cache']) or $this->options['cache'] = $this->config['DB_CACHE_TIME'];
        //缓存时间为0，不读取缓存
        if ($this->options['cache'] == 0) {
            return false;
        }
        if ($this->initCache()) {
            $data = $this->cache->get($this->sql);
            if (!empty($data)) {
                unset($this->options['cache']);
                return $data;
            }
        }
        return false;
    }

    /**
     * 写入缓存
     * @param unknown $data
     * @return boolean
     */
    private function _writeCache(mixed $data): bool
    {
        //缓存时间为0，不设置缓存
        if ($this->options['cache'] == 0) {
            return false;
        }
        if ($this->initCache()) {
            $expire = $this->options['cache'];
            unset($this->options['cache']);
            return $this->cache->set($this->sql, $data, $expire);
        }
        return false;
    }

    /**
     * 解析数据
     * @param unknown $type
     * @return unknown
     */
    private function _parseData(string $type): string
    {
        $data = $this->db->parseData($this->options, $type);
        $this->options['data'] = '';
        return $data;
    }

    /**
     * 解析条件
     * @return unknown
     */
    private function _parseCondition(): string
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
    
    /*********************************************/
    
    // 返回最后一次插入数据库的ID号
    public function insert_id(): int
    {
        return $this->db->lastId();
    }
    
    // 返回错误信息
    public function error(): string
    {
        return mysql_error();
    }
    
    // 返回错误代号
    public function errno(): int
    {
        return $this->db->errno;
    }
    
    //输出错误信息
    public function ErrorMsg(string $message = ''): void
    {
        $error = $this->error();
        $errorno = $this->errno();
        if (DEBUG) {
            $str = " {$message}<br>
            <b>SQL</b>: {$this->sql}<br>
            <b>错误详情</b>: {$error}<br>
            <b>错误代码</b>:{$errorno}<br>";
        } else {
            $str = "<b>出错</b>: $message<br>";
        }
        throw new Exception($str);
    }
    
    public function autoReplace(string $table, array $field_values, array $update_values, string $where = '', string $querymode = ''): array|bool
    {
        $query = $this->db->query('DESC ' . $table);
        while ($row = $this->db->fetchArray($query)) {
            $field_descs[] = $row;
        }
        if (!is_array($field_descs)) {
            $field_descs = array();
        }
        $primary_keys = array();
        foreach ($field_descs as $value) {
            $field_names[] = $value['Field'];
            if ($value['Key'] == 'PRI') {
                $primary_keys[] = $value['Field'];
            }
        }
    
        $fields = $values = array();
        foreach ($field_names as $value) {
            if (array_key_exists($value, $field_values) == true) {
                $fields[] = $value;
                $values[] = "'" . $field_values[$value] . "'";
            }
        }
    
        $sets = array();
        foreach ($update_values as $key => $value) {
            if (array_key_exists($key, $field_values) == true) {
                if (is_int($value) || is_float($value)) {
                    $sets[] = $key . ' = ' . $key . ' + ' . $value;
                } else {
                    $sets[] = $key . " = '" . $value . "'";
                }
            }
        }
    
        $sql = '';
        if (empty($primary_keys)) {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
                if (!empty($sets)) {
                    $sql .= 'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                }
            }
        }
        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }
    
    // 返回一条数据
    public function getRow(string $sql, bool $limited = false): array|false
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }
        $res = $this->query($sql);
        if ($res !== false) {
            return $res['0'];
        } else {
            return false;
        }
    }
    
    //  函数返回结果集中一个字段的值
    public function result(mixed $query, int $row): mixed
    {
        return @mysql_result($query, $row);
    }
    
    // 返回结果集的条数
    public function num_rows(mixed $query): int
    {
        return mysql_num_rows($query);
    }
    
    // 返回结果集中的字段数
    public function num_fields(mixed $query): int
    {
        return mysql_num_fields($query);
    }
    
    public function free_result(mixed $query): bool
    {
        return mysql_free_result($query);
    }
    
    // 返回根据从结果集取得的行生成的关联数组
    public function fetchRow(mixed $query): array|false
    {
        return mysql_fetch_assoc($query);
    }
    
    public function fetch_fields(mixed $query): object|false
    {
        return mysql_fetch_field($query);
    }
    
    // 返回mysql版本号
    public function version(): string
    {
        return mysql_get_server_info();
    }
    
    // 如果存在连接，则返回 true。如果失败，则返回 false。
    public function ping(): bool
    {
        return mysql_ping();
    }
    
    public function escape_string(string $unescaped_string): string
    {
        return mysql_real_escape_string($unescaped_string);
    }
}
