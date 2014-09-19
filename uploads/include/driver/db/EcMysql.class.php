<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcMysql {

    private $_writeLink = NULL; //主
    private $_readLink = NULL; //从
    private $_replication = false; //标志是否支持主从
    private $dbConfig = array();
    public $sql = "";

    public function __construct($dbConfig = array()) {
        $this->dbConfig = $dbConfig;
        //判断是否支持主从				
        $this->_replication = isset($this->dbConfig['DB_SLAVE']) && !empty($this->dbConfig['DB_SLAVE']);
    }

    //执行sql查询	
    public function query($sql, $params = array()) {
        foreach ($params as $k => $v) {
            $sql = str_replace(':' . $k, $this->escape($v), $sql);
        }
        $this->sql = $sql;
        if ($query = mysql_query($sql, $this->_getReadLink()))
            return $query;
        else
            $this->error('MySQL Query Error');
    }

    //执行sql命令
    public function execute($sql, $params = array()) {
        foreach ($params as $k => $v) {
            $sql = str_replace(':' . $k, $this->escape($v), $sql);
        }
        $this->sql = $sql;
        if ($query = mysql_query($sql, $this->_getWriteLink()))
            return $query;
        else
            $this->error('MySQL Query Error');
    }

    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有 
    public function fetchArray($query, $result_type = MYSQL_ASSOC) {
        return $this->unEscape(mysql_fetch_array($query, $result_type));
    }

    //取得前一次 MySQL 操作所影响的记录行数
    public function affectedRows() {
        return mysql_affected_rows($this->_getWriteLink());
    }

    //获取上一次插入的id
    public function lastId() {
        return ($id = mysql_insert_id($this->_getWriteLink())) >= 0 ? $id : mysql_result($this->execute("SELECT last_insert_id()"), 0);
    }

    //获取表结构
    public function getFields($table) {
        $this->sql = "SHOW FULL FIELDS FROM {$table}";
        $query = $this->query($this->sql);
        $data = array();
        while ($row = $this->fetchArray($query)) {
            $data[] = $row;
        }
        return $data;
    }

    //获取行数
    public function count($table, $where) {
        $this->sql = "SELECT count(*) FROM $table $where";
        $query = $this->query($this->sql);
        $data = $this->fetchArray($query);
        return $data['count(*)'];
    }

    //数据过滤
    public function escape($value) {
        if (isset($this->_readLink)) {
            $link = $this->_readLink;
        } elseif (isset($this->_writeLink)) {
            $link = $this->_writeLink;
        } else {
            $link = $this->_getReadLink();
        }

        if (is_array($value)) {
            return array_map(array($this, 'escape'), $value);
        } else {
            if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            return "'" . mysql_real_escape_string($value, $link) . "'";
        }
    }

    //数据过滤
    public function unEscape($value) {
        if (is_array($value)) {
            return array_map('stripslashes', $value);
        } else {
            return stripslashes($value);
        }
    }

    //解析待添加或修改的数据
    public function parseData($options, $type) {
        //如果数据是字符串，直接返回
        if (is_string($options['data'])) {
            return $options['data'];
        }
        if (is_array($options) && !empty($options)) {
            switch ($type) {
                case 'add':
                    $data = array();
                    $data['fields'] = array_keys($options['data']);
                    $data['values'] = $this->escape(array_values($options['data']));
                    return " (`" . implode("`,`", $data['fields']) . "`) VALUES (" . implode(",", $data['values']) . ") ";
                case 'save':
                    $data = array();
                    foreach ($options['data'] as $key => $value) {
                        $data[] = " `$key` = " . $this->escape($value);
                    }
                    return implode(',', $data);
                default:return false;
            }
        }
        return false;
    }

    //解析查询条件
    public function parseCondition($options) {
        $condition = "";
        if (!empty($options['where'])) {
            $condition = " WHERE ";
            if (is_string($options['where'])) {
                $condition .= $options['where'];
            } else if (is_array($options['where'])) {
                foreach ($options['where'] as $key => $value) {
                    $condition .= " `$key` = " . $this->escape($value) . " AND ";
                }
                $condition = substr($condition, 0, -4);
            } else {
                $condition = "";
            }
        }

        if (!empty($options['group']) && is_string($options['group'])) {
            $condition .= " GROUP BY " . $options['group'];
        }
        if (!empty($options['having']) && is_string($options['having'])) {
            $condition .= " HAVING " . $options['having'];
        }
        if (!empty($options['order']) && is_string($options['order'])) {
            $condition .= " ORDER BY " . $options['order'];
        }
        if (!empty($options['limit']) && (is_string($options['limit']) || is_numeric($options['limit']))) {
            $condition .= " LIMIT " . $options['limit'];
        }
        if (empty($condition))
            return "";
        return $condition;
    }

    //输出错误信息
    public function error($message = '') {
        $error = mysql_error();
        $errorno = mysql_errno();
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

    /*     * ******** 兼容以前的版本 ********* */

    //选择数据库
    public function select_db($dbname) {
        return mysql_select_db($dbname, $this->_getWriteLink());
    }

    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有 
    public function fetch_array($query, $result_type = MYSQL_ASSOC) {
        return $this->fetchArray($query, $result_type);
    }

    //获取上一次插入的id
    public function insert_id() {
        return $this->lastId();
    }

    //取得前一次 MySQL 操作所影响的记录行数
    public function affected_rows() {
        return $this->affectedRows();
    }

    //取得结果集中行的数目
    public function num_rows($query) {
        return mysql_num_rows($query);
    }

    /*     * ******** 兼容以前的版本 ********* */

    //获取从服务器连接
    private function _getReadLink() {
        if (isset($this->_readLink)) {
            return $this->_readLink;
        } else {
            if (!$this->_replication) {
                return $this->_getWriteLink();
            } else {
                $this->_readLink = $this->_connect(false);
                return $this->_readLink;
            }
        }
    }

    //获取主服务器连接
    private function _getWriteLink() {
        if (isset($this->_writeLink)) {
            return $this->_writeLink;
        } else {
            $this->_writeLink = $this->_connect(true);
            return $this->_writeLink;
        }
    }

    //数据库链接
    private function _connect($is_master = true) {
        if (($is_master == false) && $this->_replication) {
            $slave_count = count($this->dbConfig['DB_SLAVE']);
            //遍历所有从机
            for ($i = 0; $i < $slave_count; $i++) {
                $db_all[] = array_merge($this->dbConfig, $this->dbConfig['DB_SLAVE'][$i]);
            }
            $db_all[] = $this->dbConfig; //如果所有从机都连接不上，连接到主机
            //随机选择一台从机连接
            $rand = mt_rand(0, $slave_count - 1);
            $db = array_unshift($db_all, $db_all[$rand]);
        } else {
            $db_all[] = $this->dbConfig; //直接连接到主机
        }

        foreach ($db_all as $db) {
            if ($link = @mysql_connect($db['DB_HOST'] . ':' . $db['DB_PORT'], $db['DB_USER'], $db['DB_PWD'])) {
                break;
            }
        }

        if (!$link) {
            $this->error('无法连接到数据库服务器');
        }

        $version = mysql_get_server_info($link);
        if ($version > '4.1') {
            mysql_query("SET character_set_connection = " . $db['DB_CHARSET'] . ", character_set_results = " . $db['DB_CHARSET'] . ", character_set_client = binary", $link);

            if ($version > '5.0.1') {
                mysql_query("SET sql_mode = ''", $link);
            }
        }
        mysql_select_db($db['DB_NAME'], $link);
        return $link;
    }

    //关闭数据库
    public function __destruct() {
        if ($this->_writeLink) {
            @mysql_close($this->_writeLink);
        }
        if ($this->_readLink) {
            @mysql_close($this->_readLink);
        }
    }

}
