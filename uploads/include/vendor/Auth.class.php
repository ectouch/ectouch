<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 权限认证类
 */
class Auth {

    static public $model = NULL; //数据库模型
    static public $config;

    static public function init($config = array()) {
        if (!isset($_SESSION))
            session_start();
        //登录地址
        self::$config['AUTH_LOGIN_URL'] = isset($config['AUTH_LOGIN_URL']) ? $config['AUTH_LOGIN_URL'] : __APP__ . '?a=login';
        //不需要认证的模块，对后台认证有效
        self::$config['AUTH_LOGIN_NO'] = isset($config['AUTH_LOGIN_NO']) ? $config['AUTH_LOGIN_NO'] : array('index' => array('login', 'verify'), 'common' => '*');
        //session前缀
        self::$config['AUTH_SESSION_PREFIX'] = isset($config['AUTH_SESSION_PREFIX']) ? $config['AUTH_SESSION_PREFIX'] : 'auth_';
        //是否缓存权限信息，如果设置为false，每次都需要从数据库读取数据
        self::$config['AUTH_POWER_CACHE'] = isset($config['AUTH_POWER_CACHE']) ? $config['AUTH_POWER_CACHE'] : false;
        //数据库表和字段映射	
        self::$config['AUTH_TABLE'] = isset($config['AUTH_TABLE']) ? $config['AUTH_TABLE'] : array(
            'group' => array(
                'name' => 'group',
                'field' => array('id' => 'id', 'power' => 'power_value'),
            ),
            'resource' => array(
                'name' => 'resource',
                'field' => array('id' => 'id', 'pid' => 'pid', 'operate' => 'operate'),
            ),
        );
    }

    /**
     * 设置认证用户组id
     * @param type $groupid
     */
    static public function set($groupid) {
        $_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid'] = $groupid;
    }

    /**
     * 清空认证
     */
    static public function clear() {
        unset($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid']);
        unset($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'power']);
    }

    /**
     * 检测用户是否登录
     * @return boolean
     */
    static public function checkLogin() {
        if (isset($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid']) && !empty($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid']))
            return true;
        else
            return false;
    }

    /**
     * 权限认证检查
     * @param type $model
     * @param type $config
     * @return boolean
     */
    static public function check($model, $config = array()) {
        self::$model = $model;
        self::init($config);
        $module = $_GET['_module']; //获取当前执行的模块
        $action = $_GET['_action']; //获取当前执行的操作	
        //print_r($_SESSION);
        //不需要认证的模块，则放行
        if (isset(self::$config['AUTH_LOGIN_NO'][$module]) && ((self::$config['AUTH_LOGIN_NO'][$module] == '*') || in_array($action, self::$config['AUTH_LOGIN_NO'][$module]))) {
            return true;
        }
        //没有登录，跳转到登录页面
        if (false == self::checkLogin()) {
            self::noLogin();
        }

        $power = self::getGroupPower($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid']);

        if (($power == -1) || ($power[$module][$action] == -1)) {
            return true; //认证通过
        } else {
            self::noPower();
        }
    }

    /**
     * 检查模块和操作权限
     * @param type $module
     * @param type $action
     * @return boolean
     */
    static public function checkPower($module, $action = NULL) {
        if (empty($module))
            return false;
        $power = self::getGroupPower($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'groupid']);
        if (empty($power))
            return false;
        if ($power == -1)
            return true;

        if (empty($action) && isset($power[$module]) && !empty($power[$module])) {
            return true;
        } else if (isset($power[$module][$action])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 没有登录的时候调用
     */
    static public function noLogin() {
        header('location:' . self::$config['AUTH_LOGIN_URL']);
        exit;
    }

    /**
     * 没有权限的时候调用
     * @throws Exception
     */
    static public function noPower() {
        throw new Exception('您没有权限操作');
    }

    /**
     * 自动获取模块信息
     * @param type $model
     * @param type $config
     * @param type $module_path
     * @param type $module_suffix
     * @return type
     */
    static public function getModule($model, $config = array(), $module_path = '', $module_suffix = '') {
        self::$model = $model;
        self::init($config);
        //完成数据库表名和字段名映射	
        $table_group = self::$config['AUTH_TABLE']['group']['name']; //用户组表
        $table_resource = self::$config['AUTH_TABLE']['resource']['name']; //资源表

        $group_field_id = self::$config['AUTH_TABLE']['group']['field']['id']; //用户组表 id字段
        $group_field_power = self::$config['AUTH_TABLE']['group']['field']['power']; //用户组表 权限power字段

        $resource_field_id = self::$config['AUTH_TABLE']['resource']['field']['id']; //资源表 id字段
        $resource_field_pid = self::$config['AUTH_TABLE']['resource']['field']['pid']; //资源表 pid字段
        $resource_field_operate = self::$config['AUTH_TABLE']['resource']['field']['operate']; //资源表 operate字段
        //如果没有用户组和资源数据表，则自动创建
        $sql_group = "CREATE TABLE IF NOT EXISTS `" . self::$model->pre . $table_group . "` (
  `{$group_field_id}` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `{$group_field_power}` varchar(1000) NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  `status` int(1) unsigned NOT NULL,
  PRIMARY KEY (`{$group_field_id}`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        $sql_resource = "CREATE TABLE IF NOT EXISTS `" . self::$model->pre . $table_resource . "` (
  `{$resource_field_id}` int(10) unsigned NOT NULL auto_increment,
  `{$resource_field_pid}` int(10) unsigned NOT NULL,
  `{$resource_field_operate}` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `menu` int(1) unsigned NOT NULL,
  `sort` int(10) unsigned NOT NULL,
  `status` int(1) unsigned NOT NULL,
  PRIMARY KEY  (`{$resource_field_id}`),
  KEY `pid` (`{$resource_field_pid}`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
        self::$model->query($sql_group);
        self::$model->query($sql_resource);

        $module_path = empty($module_path) ? './module/' : $module_path;
        $module_suffix = empty($module_suffix) ? 'Mod.class.php' : $module_suffix;
        $return_data = array();
        if ($dir = opendir($module_path)) {
            while ($filename = readdir($dir)) {
                if (!is_dir($filename)) {
                    $module_suffix_array = explode('.', $module_suffix, 2);
                    $module = str_replace($module_suffix, '', $filename);
                    $class_name = $module . $module_suffix_array[0];
                    $class_methods = get_class_methods($class_name);
                    if (is_array($class_methods)) {
                        foreach ($class_methods as $action) {
                            //过滤魔术方法
                            if (substr($action, 0, 2) != '__') {
                                $return_data[$module][$action] = -1;
                            }
                        }
                    }
                }
            }
            closedir($dir);
        }
        if (!empty($return_data)) {
            $data = array();
            foreach ($return_data as $key => $value) {
                $data[$resource_field_pid] = $condition[$resource_field_pid] = 0;
                $data[$resource_field_operate] = $condition[$resource_field_operate] = $key;

                $info = self::$model->table($table_resource)->where($condition)->find();
                if (empty($info)) {
                    $pid = self::$model->table($table_resource)->data($data)->insert();
                } else {
                    $pid = $info[$resource_field_id];
                }
                if (is_array($value)) {
                    foreach ($value as $key => $vo) {

                        $data[$resource_field_pid] = $condition[$resource_field_pid] = $pid;
                        $data[$resource_field_operate] = $condition[$resource_field_operate] = $key;

                        $info = self::$model->table($table_resource)->where($condition)->find();
                        if (empty($info)) {
                            self::$model->table($table_resource)->data($data)->insert();
                        }
                    }
                }
            }
        }
        return $return_data;
    }

    /**
     * 获取用户组权限信息
     * @param type $group_id
     * @return boolean
     */
    static public function getGroupPower($group_id = '') {

        //如果可以获取到权限值Session信息直接返回
        if (self::$config['AUTH_POWER_CACHE'] && !empty($_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'power']))
            return $_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'power'];

        //完成数据库表名和字段名映射	
        $table_group = self::$config['AUTH_TABLE']['group']['name']; //用户组表
        $table_resource = self::$config['AUTH_TABLE']['resource']['name']; //资源表

        $group_field_id = self::$config['AUTH_TABLE']['group']['field']['id']; //用户组表 id字段
        $group_field_power = self::$config['AUTH_TABLE']['group']['field']['power']; //用户组表 权限power字段

        $resource_field_id = self::$config['AUTH_TABLE']['resource']['field']['id']; //资源表 id字段
        $resource_field_pid = self::$config['AUTH_TABLE']['resource']['field']['pid']; //资源表 pid字段
        $resource_field_operate = self::$config['AUTH_TABLE']['resource']['field']['operate']; //资源表 operate字段
        //获取当前用户组信息	
        $condition = array();
        $condition[$group_field_id] = $group_id; //等价于$condition['id']=$group_id;
        $group = self::$model->table($table_group)->where($condition)->find();
        //用户组或用户组的权限值为空，则返回false
        if (empty($group) || empty($group[$group_field_power]))
            return false;

        //power_value=-1表示超级管理员，拥有所以权限
        if ($group[$group_field_power] == -1) {
            $power = -1;
        } else {
            //获取所有的资源信息	
            $resource = self::$model->table($table_resource)->select();
            //资源为空，则返回false
            if (empty($resource))
                return false;

            //权限值，分割成数组
            $power_value = explode(',', $group[$group_field_power]);
            $power = $resource2 = array();

            //将资源数组的下标转成id
            foreach ($resource as $vo) {
                $resource2[$vo[$resource_field_id]] = $vo;
            }

            foreach ($resource2 as $vo) {
                //筛选该用户组拥有的权限信息
                if ($vo[$resource_field_pid] != 0 && in_array($vo[$resource_field_id], $power_value)) {
                    $module = $resource2[$vo[$resource_field_pid]][$resource_field_operate]; //模块
                    $action = $vo[$resource_field_operate]; //操作方法
                    $power[$module][$action] = -1;
                }
            }
        }
        $_SESSION[self::$config['AUTH_SESSION_PREFIX'] . 'power'] = $power; //设置session
        return $power; //返回权限值
    }

}

?>