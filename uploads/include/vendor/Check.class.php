<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 表单验证类
 */
class Check {

    //执行验证规则
    /*
      用法：
      Check::rule(
      array(验证函数1，'错误返回值1'),
      array(验证函数2，'错误返回值2'),
      );
      若有一个验证函数返回false,则返回对应的错误返回值，若全部通过验证，则返回true。
      验证函数，可以是自定义的函数或类方法，返回true表示通过，返回false，表示没有通过
     */
    public static function rule($array = array()) {
        //可以采用数组传参，也可以采用无限个参数方式传参
        if (!isset($array[0][0]))
            $array = func_get_args();

        if (is_array($array)) {
            foreach ($array as $vo) {
                if (is_array($vo) && isset($vo[0]) && isset($vo[1])) {
                    if (!$vo[0])
                        return $vo[1];
                }
            }
        }
        return true;
    }

    /**
     * 检查字符串长度
     * @param type $str
     * @param type $min
     * @param type $max
     * @return boolean
     */
    public static function len($str, $min = 0, $max = 255) {
        $str = trim($str);
        if (empty($str))
            return true;
        $len = strlen($str);
        if (($len >= $min) && ($len <= $max))
            return true;
        else
            return false;
    }

    /**
     * 检查字符串是否为空
     * @param type $str
     * @return type
     */
    public static function must($str) {
        $str = trim($str);
        return empty($str) ? false : true;
    }

    /**
     * 检查两次输入的值是否相同
     * @param type $str1
     * @param type $str2
     * @return type
     */
    public static function same($str1, $str2) {
        return $str1 == $str2;
    }

    /**
     * 检查用户名
     * @param type $str
     * @param type $len_min
     * @param type $len_max
     * @param type $type
     * @return boolean
     */
    public static function userName($str, $len_min = 0, $len_max = 255, $type = 'ALL') {
        if (empty($str))
            return true;
        if (self::len($str, $len_min, $len_max) == false) {
            return false;
        }

        switch ($type) {    //纯英文
            case "EN":$pattern = "/^[a-zA-Z]+$/";
                break;
            //英文数字                           
            case "ENNUM":$pattern = "/^[a-zA-Z0-9]+$/";
                break;
            //允许的符号(|-_字母数字)   
            case "ALL":$pattern = "/^[\-\_a-zA-Z0-9]+$/";
                break;
            //用户自定义正则
            default:$pattern = $type;
                break;
        }

        if (preg_match($pattern, $str))
            return true;
        else
            return false;
    }

    /**
     * 验证邮箱
     * @param type $str
     * @return boolean
     */
    public static function email($str) {
        if (empty($str))
            return true;
        $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
        if (strpos($str, '@') !== false && strpos($str, '.') !== false) {
            if (preg_match($chars, $str)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 验证手机号码
     * @param type $str
     * @return boolean
     */
    public static function mobile($str) {
        if (empty($str)) {
            return true;
        }

        return preg_match('#^13[\d]{9}$|14^[0-9]\d{8}|^15[0-9]\d{8}$|^18[0-9]\d{8}$#', $str);
    }

    /**
     * 验证固定电话
     * @param type $str
     * @return boolean
     */
    public static function tel($str) {
        if (empty($str)) {
            return true;
        }
        return preg_match('/^((\(\d{2,3}\))|(\d{3}\-))?(\(0\d{2,3}\)|0\d{2,3}-)?[1-9]\d{6,7}(\-\d{1,4})?$/', trim($str));
    }

    /**
     * 验证qq号码
     * @param type $str
     * @return boolean
     */
    public static function qq($str) {
        if (empty($str)) {
            return true;
        }

        return preg_match('/^[1-9]\d{4,12}$/', trim($str));
    }

    /**
     * 验证邮政编码
     * @param type $str
     * @return boolean
     */
    public static function zipCode($str) {
        if (empty($str)) {
            return true;
        }

        return preg_match('/^[1-9]\d{5}$/', trim($str));
    }

    /**
     * 验证ip
     * @param type $str
     * @return boolean
     */
    public static function ip($str) {
        if (empty($str))
            return true;

        if (!preg_match('#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$#', $str)) {
            return false;
        }

        $ip_array = explode('.', $str);

        //真实的ip地址每个数字不能大于255（0-255）		
        return ($ip_array[0] <= 255 && $ip_array[1] <= 255 && $ip_array[2] <= 255 && $ip_array[3] <= 255) ? true : false;
    }

    /**
     * 验证身份证(中国)
     * @param type $str
     * @return boolean
     */
    public static function idCard($str) {
        $str = trim($str);
        if (empty($str))
            return true;

        if (preg_match("/^([0-9]{15}|[0-9]{17}[0-9a-z])$/i", $str))
            return true;
        else
            return false;
    }

    /**
     * 验证网址
     * @param type $str
     * @return boolean
     */
    public static function url($str) {
        if (empty($str))
            return true;

        return preg_match('#^(http|https|ftp|ftps)://([\w-]+\.)+[\w-]+(/[\w-./?%&=]*)?#i', $str) ? true : false;
    }

}

?>