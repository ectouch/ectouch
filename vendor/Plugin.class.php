<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 插件类
 */
class Plugin {

    static public $config = array(); //插件配置
    static private $_plugins = array(); //存放所有插件的方法
    static private $_instance = array(); //存放已经实例化的插件

    /**
     * 插件初始化，为空表示操作前台插件模块，为'Admin'表示操作后台插件模块
     * @param type $suffix
     * @param type $config
     * @return type
     */
    static public function init($suffix = '', $config = array()) {
        if (!empty(self::$config))
            return;
        self::$config['PLUGIN_PATH'] = isset($config['PLUGIN_PATH']) ? $config['PLUGIN_PATH'] : './plugins/'; //插件目录
        self::$config['PLUGIN_SUFFIX'] = isset($config['PLUGIN_SUFFIX']) ? $config['PLUGIN_SUFFIX'] : 'Plugin.class.php'; //插件模块后缀
        //插件目录不以“/”结尾，加上“/”
        if (substr(self::$config['PLUGIN_PATH'], -1) != "/") {
            self::$config['PLUGIN_PATH'] .= "/";
        }
        self::$config['PLUGIN_SUFFIX'] = $suffix . self::$config['PLUGIN_SUFFIX'];

        $suffix_arr = explode('.', self::$config['PLUGIN_SUFFIX'], 2);
        self::$config['PLUGIN_CLASS_SUFFIX'] = $suffix_arr[0];
    }

    /**
     * 遍历获取所有插件
     * @return boolean
     */
    static public function get() {
        if (!empty(self::$_plugins))
            return self::$_plugins;

        //打开插件目录失败，返回false
        if (!($handle = opendir(self::$config['PLUGIN_PATH'])))
            return false;
        //遍历插件目录
        while (false !== ($file = readdir($handle))) {
            //如果是目录且不是".",".."
            if (is_dir(self::$config['PLUGIN_PATH'] . $file) && $file != '.' && $file != '..') {
                $plugin_name = $file;
                //检查插件文件和插件类是否存在，不存在continue;
                if (self::_check($plugin_name) == false)
                    continue;
                $class_name = $plugin_name . self::$config['PLUGIN_CLASS_SUFFIX']; //插件类名
                $class_methods = get_class_methods($class_name);
                if (!is_array($class_methods))
                    continue;
                //遍历插件方法	
                foreach ($class_methods as $action) {  //过滤魔术方法
                    if (substr($action, 0, 2) != '__') {
                        self::$_plugins[$plugin_name][] = $action;
                    }
                }
            }
        }
        closedir($handle);
        return self::$_plugins;
    }

    /**
     * 运行插件
     * @param type $plugin_name
     * @param type $action_name
     * @param type $data
     * @return boolean
     */
    static public function run($plugin_name, $action_name, $data = NULL) {
        if (!isset(self::$_instance[$plugin_name])) {
            //检查插件文件和插件类是否存在，不存在返回false
            if (self::_check($plugin_name) == false)
                return false;
            $class_name = $plugin_name . self::$config['PLUGIN_CLASS_SUFFIX']; //插件类名
            self::$_instance[$plugin_name] = new $class_name(); //实例化对象
        }

        //指定插件模块的方法和魔术回调方法__call都不存在，返回false
        if ((!method_exists(self::$_instance[$plugin_name], $action_name)) && (!method_exists(self::$_instance[$plugin_name], '__call')))
            return false;
        if (is_null($data))
            self::$_instance[$plugin_name]->$action_name(); //执行插件的方法，无参数
        else
            self::$_instance[$plugin_name]->$action_name($data); //执行插件的方法,有参数
        return true;
    }

    /**
     * 设定钩子，运行符合条件的插件
     * @param type $action_name 方法名
     * @param type $plugin_name 插件名，可以不指定
     * @param type $data 数据参数，可以不指定
     */
    static public function hook($action_name, $plugin_name = '', $data = NULL) {
        if (empty(self::$_plugins))
            self::$_plugins = self::get(); //遍历获取插件

        if (is_array(self::$_plugins)) {
            //如果指定插件，则直接执行指定插件
            if (!empty($plugin_name) && isset(self::$_plugins[$plugin_name][$action_name])) {
                self::run($plugin_name, $action_name, $data); //运行符合条件的插件
            } else {
                //若插件的操作方法中和预定的操作方法相同则被执行
                foreach (self::$_plugins as $plugin_name => $action_array) {
                    if (in_array($action_name, $action_array)) {
                        self::run($plugin_name, $action_name, $data); //运行符合条件的插件
                    }
                }
            }
        }
    }

    /**
     * 检查插件
     * @param type $plugin_name
     * @return boolean
     */
    static private function _check($plugin_name) {
        $plugin_file = self::$config['PLUGIN_PATH'] . $plugin_name . '/' . $plugin_name . self::$config['PLUGIN_SUFFIX'];
        //插件文件不存在，返回
        if (!is_file($plugin_file))
            return false;

        require_once($plugin_file); //加载模插件文件
        $class_name = $plugin_name . self::$config['PLUGIN_CLASS_SUFFIX']; //插件类名
        //插件模块类不存在，返回
        if (!class_exists($class_name))
            return false;
        return true;
    }

}

?>