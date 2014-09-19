<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * ECSHOP 基础类
 */
class EcsEcshop {

    var $db_name = '';
    var $prefix = 'ecs_';

    /**
     * 构造函数
     *
     * @access  public
     * @param   string      $ver        版本号
     *
     * @return  void
     */
    function __construct($db_name, $prefix) {
        $this->db_name = $db_name;
        $this->prefix = $prefix;
    }

    /**
     * 将指定的表名加上前缀后返回
     *
     * @access  public
     * @param   string      $str        表名
     *
     * @return  string
     */
    function table($str) {
        return '`' . $this->db_name . '`.`' . $this->prefix . $str . '`';
    }

    /**
     * ECSHOP 密码编译方法;
     *
     * @access  public
     * @param   string      $pass       需要编译的原始密码
     *
     * @return  string
     */
    function compile_password($pass) {
        return md5($pass);
    }

    /**
     * 获得 ECSHOP 当前环境的 HTTP 协议方式
     *
     * @access  public
     *
     * @return  void
     */
    function http() {
        return (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) ? 'https://' : 'http://';
    }

    /**
     * 获得数据目录的路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    function data_dir($sid = 0) {
        if (empty($sid)) {
            $s = 'data';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= $sid % 3000;
        }
        return $s;
    }

    /**
     * 获得图片的目录路径
     *
     * @param int $sid
     *
     * @return string 路径
     */
    function image_dir($sid = 0) {
        if (empty($sid)) {
            $s = 'images';
        } else {
            $s = 'user_files/';
            $s .= ceil($sid / 3000) . '/';
            $s .= ($sid % 3000) . '/';
            $s .= 'images';
        }
        return $s;
    }

}

?>