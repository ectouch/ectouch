<?php

/**
 * MYSQL 公用类库
 */

class mysql
{
    public $link_id    = null;

    public $settings   = array();

    public $queryCount = 0;
    public $queryTime  = '';
    public $queryLog   = array();

    public $max_cache_time = 300; // 最大的缓存时间，以秒为单位

    public $cache_data_dir = 'data/caches/query_caches/';
    public $root_path      = '';

    public $error_message  = array();
    public $platform       = '';
    public $version        = '';
    public $dbhash         = '';
    public $starttime      = 0;
    public $timeline       = 0;
    public $timezone       = 0;

    public $mysql_config_cache_file_time = 0;

    public $mysql_disable_cache_tables = array(); // 不允许被缓存的表，遇到将不会进行缓存

    public function __construct($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'gbk', $pconnect = 0, $quiet = 0)
    {
        if (defined('CHARSET')) {
            $charset = strtolower(str_replace('-', '', CHARSET));
        }

        if (defined('ROOT_PATH') && !$this->root_path) {
            $this->root_path = ROOT_PATH;
        }

        if ($quiet) {
            $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
        } else {
            $this->settings = array(
                                    'dbhost'   => $dbhost,
                                    'dbuser'   => $dbuser,
                                    'dbpw'     => $dbpw,
                                    'dbname'   => $dbname,
                                    'charset'  => $charset,
                                    'pconnect' => $pconnect
                                    );
        }
    }

    public function connect($dbhost, $dbuser, $dbpw, $dbname = '', $charset = 'utf8', $pconnect = 0, $quiet = 0)
    {
        if ($pconnect) {
            if (!($this->link_id = @mysqli_connect($dbhost, $dbuser, $dbpw))) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't pConnect MySQL Server($dbhost)!");
                }

                return false;
            }
        } else {
            $this->link_id = mysqli_connect($dbhost, $dbuser, $dbpw);
            if (!$this->link_id) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't Connect MySQL Server($dbhost)!");
                }

                return false;
            }
        }

        $this->dbhash  = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version = mysqli_get_server_info($this->link_id);

        if ($charset != 'latin1') {
            mysqli_query($this->link_id, "SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary");
        }
        if ($this->version > '5.0.1') {
            mysqli_query($this->link_id, "SET sql_mode=''");
        }

        $sqlcache_config_file = $this->root_path . $this->cache_data_dir . 'sqlcache_config_file_' . $this->dbhash . '.php';

        @include($sqlcache_config_file);

        $this->starttime = time();

        if ($this->max_cache_time && $this->starttime > $this->mysql_config_cache_file_time + $this->max_cache_time) {
            if ($dbhost != '.') {
                $result = mysqli_query($this->link_id, "SHOW VARIABLES LIKE 'basedir'");
                $row    = mysqli_fetch_assoc($result);
                if (!empty($row['Value']{1}) && $row['Value']{1} == ':' && !empty($row['Value']{2}) && $row['Value']{2} == "\\") {
                    $this->platform = 'WINDOWS';
                } else {
                    $this->platform = 'OTHER';
                }
            } else {
                $this->platform = 'WINDOWS';
            }

            if ($this->platform == 'OTHER' &&
                ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') ||
                (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC')) {
                $result = mysqli_query($this->link_id, "SELECT UNIX_TIMESTAMP() AS timeline, UNIX_TIMESTAMP('" . date('Y-m-d H:i:s', $this->starttime) . "') AS timezone");
                $row    = mysqli_fetch_assoc($result);

                if ($dbhost != '.' && strtolower($dbhost) != 'localhost:3306' && $dbhost != '127.0.0.1:3306') {
                    $this->timeline = $this->starttime - $row['timeline'];
                }

                if (PHP_VERSION >= '5.1' && date_default_timezone_get() == 'UTC') {
                    $this->timezone = $this->starttime - $row['timezone'];
                }
            }

            $content = '<' . "?php\r\n" .
                       '$this->mysql_config_cache_file_time = ' . $this->starttime . ";\r\n" .
                       '$this->timeline = ' . $this->timeline . ";\r\n" .
                       '$this->timezone = ' . $this->timezone . ";\r\n" .
                       '$this->platform = ' . "'" . $this->platform . "';\r\n?" . '>';

            @file_put_contents($sqlcache_config_file, $content);
        }

        /* 选择数据库 */
        if ($dbname) {
            if (mysqli_select_db($this->link_id, $dbname) === false) {
                if (!$quiet) {
                    $this->ErrorMsg("Can't select MySQL database($dbname)!");
                }

                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public function select_database($dbname)
    {
        return mysqli_select_db($this->link_id, $dbname);
    }

    public function set_mysql_charset($charset)
    {
        /* 如果mysql 版本是 4.1+ 以上，需要对字符集进行初始化 */
        if ($this->version > '4.1') {
            if (in_array(strtolower($charset), array('gbk', 'big5', 'utf-8', 'utf8'))) {
                $charset = str_replace('-', '', $charset);
            }
            if ($charset != 'latin1') {
                mysqli_query($this->link_id, "SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary");
            }
        }
    }

    public function fetch_array($query, $result_type = MYSQLI_ASSOC)
    {
        return mysqli_fetch_array($query, $result_type);
    }

    public function query($sql, $type = '')
    {
        if ($this->link_id === null) {
            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            $this->settings = array();
        }

        if ($this->queryCount++ <= 99) {
            $this->queryLog[] = $sql;
        }
        if ($this->queryTime == '') {
            $this->queryTime = microtime(true);
        }

        /* 当当前的时间大于类初始化时间的时候，自动执行 ping 这个自动重新连接操作 */
        if (time() > $this->starttime + 1) {
            mysqli_ping($this->link_id);
        }

        if (!($query = mysqli_query($this->link_id, $sql)) && $type != 'SILENT') {
            $this->error_message[]['message'] = 'MySQL Query Error';
            $this->error_message[]['sql'] = $sql;
            $this->error_message[]['error'] = mysqli_error($this->link_id);
            $this->error_message[]['errno'] = mysqli_errno($this->link_id);

            $this->ErrorMsg();

            return false;
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            $logfilename = $this->root_path . 'data/caches/logs/mysql_query_' . $this->dbhash . '_' . date('Y_m_d') . '.log';
            $str = $sql . "\n\n";
            if (!is_dir(dirname($logfilename))) {
                mkdir(dirname($logfilename));
            }

            file_put_contents($logfilename, $str, FILE_APPEND);
        }

        return $query;
    }

    public function affected_rows()
    {
        return mysqli_affected_rows($this->link_id);
    }

    public function error()
    {
        return mysqli_error($this->link_id);
    }

    public function errno()
    {
        return mysqli_errno($this->link_id);
    }

    public function result($query, $row)
    {
        return @mysqli_result($query, $row);
    }

    public function num_rows($query)
    {
        return mysqli_num_rows($query);
    }

    public function num_fields($query)
    {
        return mysqli_num_fields($query);
    }

    public function free_result($query)
    {
        return mysqli_free_result($query);
    }

    public function insert_id()
    {
        return mysqli_insert_id($this->link_id);
    }

    public function fetchRow($query)
    {
        return mysqli_fetch_assoc($query);
    }

    public function fetch_fields($query)
    {
        return mysqli_fetch_field($query);
    }

    public function version()
    {
        return $this->version;
    }

    public function ping()
    {
        return mysqli_ping($this->link_id);
    }

    public function escape_string($unescaped_string)
    {
        return mysqli_real_escape_string($this->link_id, $unescaped_string);
    }

    public function close()
    {
        return mysqli_close($this->link_id);
    }

    public function ErrorMsg($message = '', $sql = '')
    {
        if ($message) {
            echo "<b>ECTouch info</b>: $message\n\n<br /><br />";
        } else {
            echo "<b>MySQL server error report:";
            print_r($this->error_message);
        }

        exit;
    }

    /* 仿真 Adodb 函数 */
    public function selectLimit($sql, $num, $start = 0)
    {
        if ($start == 0) {
            $sql .= ' LIMIT ' . $num;
        } else {
            $sql .= ' LIMIT ' . $start . ', ' . $num;
        }

        return $this->query($sql);
    }

    public function getOne($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            $row = mysqli_fetch_row($res);

            if ($row !== false) {
                return $row[0];
            } else {
                return '';
            }
        } else {
            return false;
        }
    }

    public function getOneCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getOne($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getOne($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getAll($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $arr[] = $row;
            }

            return $arr;
        } else {
            return false;
        }
    }

    public function getAllCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getAll($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getAll($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getRow($sql, $limited = false)
    {
        if ($limited == true) {
            $sql = trim($sql . ' LIMIT 1');
        }

        $res = $this->query($sql);
        if ($res !== false) {
            return mysqli_fetch_assoc($res);
        } else {
            return false;
        }
    }

    public function getRowCached($sql, $cached = 'FILEFIRST')
    {
        $sql = trim($sql . ' LIMIT 1');

        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getRow($sql, true);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getRow($sql, true);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function getCol($sql)
    {
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = array();
            while ($row = mysqli_fetch_row($res)) {
                $arr[] = $row[0];
            }

            return $arr;
        } else {
            return false;
        }
    }

    public function getColCached($sql, $cached = 'FILEFIRST')
    {
        $cachefirst = ($cached == 'FILEFIRST' || ($cached == 'MYSQLFIRST' && $this->platform != 'WINDOWS')) && $this->max_cache_time;
        if (!$cachefirst) {
            return $this->getCol($sql);
        } else {
            $result = $this->getSqlCacheData($sql, $cached);
            if (empty($result['storecache']) == true) {
                return $result['data'];
            }
        }

        $arr = $this->getCol($sql);

        if ($arr !== false && $cachefirst) {
            $this->setSqlCacheData($result, $arr);
        }

        return $arr;
    }

    public function autoExecute($table, $field_values, $mode = 'INSERT', $where = '', $querymode = '')
    {
        $field_names = $this->getCol('DESC ' . $table);

        $sql = '';
        if ($mode == 'INSERT') {
            $fields = $values = array();
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $fields[] = $value;
                    $values[] = "'" . $field_values[$value] . "'";
                }
            }

            if (!empty($fields)) {
                $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
            }
        } else {
            $sets = array();
            foreach ($field_names as $value) {
                if (array_key_exists($value, $field_values) == true) {
                    $sets[] = $value . " = '" . $field_values[$value] . "'";
                }
            }

            if (!empty($sets)) {
                $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE ' . $where;
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    public function autoReplace($table, $field_values, $update_values, $where = '', $querymode = '')
    {
        $field_descs = $this->getAll('DESC ' . $table);

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
                    $sql .=  'ON DUPLICATE KEY UPDATE ' . implode(', ', $sets);
                }
            }
        }

        if ($sql) {
            return $this->query($sql, $querymode);
        } else {
            return false;
        }
    }

    public function setMaxCacheTime($second)
    {
        $this->max_cache_time = $second;
    }

    public function getMaxCacheTime()
    {
        return $this->max_cache_time;
    }

    public function getSqlCacheData($sql, $cached = '')
    {
        $sql = trim($sql);

        $result = array();
        $result['filename'] = $this->root_path . $this->cache_data_dir . 'sqlcache_' . abs(crc32($this->dbhash . $sql)) . '_' . md5($this->dbhash . $sql) . '.php';

        $data = @file_get_contents($result['filename']);
        if (isset($data{23})) {
            $filetime = substr($data, 13, 10);
            $data     = substr($data, 23);

            if (($cached == 'FILEFIRST' && time() > $filetime + $this->max_cache_time) || ($cached == 'MYSQLFIRST' && $this->table_lastupdate($this->get_table_name($sql)) > $filetime)) {
                $result['storecache'] = true;
            } else {
                $result['data'] = @unserialize($data);
                if ($result['data'] === false) {
                    $result['storecache'] = true;
                } else {
                    $result['storecache'] = false;
                }
            }
        } else {
            $result['storecache'] = true;
        }

        return $result;
    }

    public function setSqlCacheData($result, $data)
    {
        if ($result['storecache'] === true && $result['filename']) {
            @file_put_contents($result['filename'], '<?php exit;?>' . time() . serialize($data));
            clearstatcache();
        }
    }

    /* 获取 SQL 语句中最后更新的表的时间，有多个表的情况下，返回最新的表的时间 */
    public function table_lastupdate($tables)
    {
        if ($this->link_id === null) {
            $this->connect($this->settings['dbhost'], $this->settings['dbuser'], $this->settings['dbpw'], $this->settings['dbname'], $this->settings['charset'], $this->settings['pconnect']);
            $this->settings = array();
        }

        $lastupdatetime = '0000-00-00 00:00:00';

        $tables = str_replace('`', '', $tables);
        $this->mysql_disable_cache_tables = str_replace('`', '', $this->mysql_disable_cache_tables);

        foreach ($tables as $table) {
            if (in_array($table, $this->mysql_disable_cache_tables) == true) {
                $lastupdatetime = '2037-12-31 23:59:59';

                break;
            }

            if (strstr($table, '.') != null) {
                $tmp = explode('.', $table);
                $sql = 'SHOW TABLE STATUS FROM `' . trim($tmp[0]) . "` LIKE '" . trim($tmp[1]) . "'";
            } else {
                $sql = "SHOW TABLE STATUS LIKE '" . trim($table) . "'";
            }
            $result = mysqli_query($this->link_id, $sql);

            $row = mysqli_fetch_assoc($result);
            if ($row['Update_time'] > $lastupdatetime) {
                $lastupdatetime = $row['Update_time'];
            }
        }
        $lastupdatetime = strtotime($lastupdatetime) - $this->timezone + $this->timeline;

        return $lastupdatetime;
    }

    public function get_table_name($query_item)
    {
        $query_item = trim($query_item);
        $table_names = array();

        /* 判断语句中是不是含有 JOIN */
        if (stristr($query_item, ' JOIN ') == '') {
            /* 解析一般的 SELECT FROM 语句 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?(?:\s*,\s*(?:`?\w+`?\s*\.\s*)?`?\w+`?(?:(?:\s*AS)?\s*`?\w+`?)?)*)/is', $query_item, $table_names)) {
                $table_names = preg_replace('/((?:`?\w+`?\s*\.\s*)?`?\w+`?)[^,]*/', '\1', $table_names[1]);

                return preg_split('/\s*,\s*/', $table_names);
            }
        } else {
            /* 对含有 JOIN 的语句进行解析 */
            if (preg_match('/^SELECT.*?FROM\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)(?:(?:\s*AS)?\s*`?\w+`?)?.*?JOIN.*$/is', $query_item, $table_names)) {
                $other_table_names = array();
                preg_match_all('/JOIN\s*((?:`?\w+`?\s*\.\s*)?`?\w+`?)\s*/i', $query_item, $other_table_names);

                return array_merge(array($table_names[1]), $other_table_names[1]);
            }
        }

        return $table_names;
    }

    /* 设置不允许进行缓存的表 */
    public function set_disable_cache_tables($tables)
    {
        if (!is_array($tables)) {
            $tables = explode(',', $tables);
        }

        foreach ($tables as $table) {
            $this->mysql_disable_cache_tables[] = $table;
        }

        array_unique($this->mysql_disable_cache_tables);
    }
}
