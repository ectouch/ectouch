<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 多语言支持类
 */
class Lang {

    static public $config;
    static private $langArray;

    static public function init($config = array()) {
        self::$config['LANG_DEFAULT'] = isset($config['LANG_DEFAULT']) ? $config['LANG_DEFAULT'] : 'zh'; //默认语言
        self::$config['LANG_PACK_PATH'] = isset($config['LANG_PACK_PATH']) ? $config['LANG_PACK_PATH'] : './lang/'; //语言包目录
        self::$config['LANG_PACK_SUFFIX'] = isset($config['LANG_PACK_SUFFIX']) ? $config['LANG_PACK_SUFFIX'] : '.lang.php'; //语言包后缀
        self::$config['LANG_PACK_COMMON'] = isset($config['LANG_PACK_COMMON']) ? $config['LANG_PACK_COMMON'] : 'common'; //公用语言包，默认会自动加载
        //语言包目录不以“/”结尾，加上“/”
        if (substr(self::$config['LANG_PACK_PATH'], -1) != "/") {
            self::$config['LANG_PACK_PATH'] .= "/";
        }
        //定义当前语言
        if (!defined(__LANG__))
            define('__LANG__', self::getLang(self::$config['LANG_DEFAULT'])); //定义当前选择的语言 
        self::$config['LANG_PACK_PATH'] = self::$config['LANG_PACK_PATH'] . __LANG__ . '/'; //设置当前语言的语言包目录
    }

    /**
     * 获取不同语言的值
     * @param type $key
     * @param type $pack
     * @return type
     */
    static public function get($key, $pack = '') {
        $lang_array = self::getPack($pack);
        return isset($lang_array[$key]) ? $lang_array[$key] : $key;
    }

    /**
     * 获取语言包数组
     * @param type $pack
     * @return type
     */
    static public function getPack($pack = '') {
        $common_pack = self::$config['LANG_PACK_COMMON']; //公用语言包
        $pack = empty($pack) ? $common_pack : $pack; //不指定语言包，自动调用公共语言包
        if (!isset(self::$langArray[$pack])) {
            if ($pack == $common_pack) {
                self::$langArray[$pack] = self::_loadLangPack($common_pack);
            } else {//加载公共语言包和指定的语言包
                self::$langArray[$pack] = @array_merge(self::_loadLangPack($common_pack), self::_loadLangPack($pack)); //两个数组合并
            }
        }
        return self::$langArray[$pack];
    }

    /**
     * 获取当前语言
     * @param type $default
     * @return type
     */
    static private function getLang($default = 'zh') {
        if (isset($_GET['lang'])) {// 获取url中设置了语言变量
            $lang = $_GET['lang'];
        } else if (isset($_COOKIE['ec_language'])) {//获取用户上次选择的语言
            $lang = $_COOKIE['ec_language'];
        } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {//检测浏览器语言
            preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
            $lang = $matches[1];
        } else {
            $lang = $default; //默认语言
        }

        //如果获取到语言为空，则设置为默认语言
        if (empty($lang)) {
            $lang = $default; //默认语言
        }

        $lang = strtolower($lang); //大写转换成小写
        //如果当前语言目录不存在，如en-us,则使用en作为当前语言目录
        if (!is_dir(self::$config['LANG_PACK_PATH'] . $lang . '/')) {
            $lang_2 = explode('-', $lang); //当前语言分割，获取语言大类，如en-us，获取en
            $lang = $lang_2[0];
            if (!is_dir(self::$config['LANG_PACK_PATH'] . $lang . '/'))
                $lang = $default; //默认语言
        }
        setcookie('ec_language', $lang, time() + 3600 * 24 * 365, '/');
        return $lang;
    }

    /**
     * 加载语言包
     * @param type $pack
     * @return array
     */
    static private function _loadLangPack($pack) {
        $lang_array = array();
        $lang_file = self::$config['LANG_PACK_PATH'] . $pack . self::$config['LANG_PACK_SUFFIX'];
        if (file_exists($lang_file)) {
            $lang_array = require_once($lang_file);
            if (!is_array($lang_array)) {//数据不为数组的时候，为了下面的array_merge，赋值为空数组，
                $lang_array = array();
            }
        }
        return $lang_array;
    }

}

?>