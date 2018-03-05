<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AdminModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 后台管理模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AdminModel extends BaseModel {

    protected $table = 'admin_user';

    public function getUserInfo($username = '', $password = '') {
        // 获取加密因子
        $ec_salt = $this->field('ec_salt', array('user_name' => $username));
        /* 检查密码是否正确 */
        if (empty($ec_salt)) {
            $condition['password'] = md5($password);
        } else {
            $condition['password'] = md5(md5($password) . $ec_salt);
        }
        $condition['user_name'] = $username;
        //非供应商管理员
        $condition['suppliers_id'] = 0;
        $userInfo = $this->find($condition, 'user_id, user_name, password, email, last_login, ec_salt');
        if ($userInfo) {
            // 登录成功
            if (empty($userInfo['ec_salt'])) {
                $data['ec_salt'] = rand(1, 9999);
                $data['password'] = md5(md5($password) . $data['ec_salt']);
            }
            $data['last_login'] = gmtime();
            $data['last_ip'] = get_client_ip();
            $this->update('user_id = ' . $userInfo['user_id'], $data);
            return $userInfo;
        }
        return false;
    }

    public function getUserInfoNoPwd($username = '', $email = '') {
        // 获取管理员信息
        $condition = array('user_name' => $username, 'email' => $email);
        $userInfo = $this->find($condition, 'user_id, password');
        /* 检查密码是否正确 */
        if ($userInfo) {
            return $userInfo;
        } else {
            return false;
        }
    }

    public function getUserTotal($condition = array()) {
        return $this->count($condition);
    }

    /**
     * 记录管理员的操作内容
     * @access  public
     * @param   string      $sn         数据的唯一值
     * @param   string      $action     操作的类型
     * @param   string      $content    操作的内容
     * @return  void
     */
    public function admin_log($sn = '', $action, $content) {
        $log_info = L('log_action.' . $action) . L('log_action.' . $action) . ': ' . addslashes($sn);

        $sql = 'INSERT INTO ' . $this->pre . 'admin_log (log_time, user_id, log_info, ip_address) ' .
                " VALUES ('" . gmtime() . "', $_SESSION[admin_id], '" . stripslashes($log_info) . "', '" . real_ip() . "')";
        $this->query($sql);
    }

    /**
     * 插入一个配置信息
     * @access  public
     * @param   string      $parent     分组的code
     * @param   string      $code       该配置信息的唯一标识
     * @param   string      $value      该配置信息值
     * @return  void
     */
    public function insert_config($parent, $code, $value) {
        $this->table = 'touch_shop_config';
        $condition['code'] = $parent;
        $condition['type'] = 1;
        $parent_id = $this->field('id', $condition);

        $sql = 'INSERT INTO ' . $this->pre . 'touch_shop_config (parent_id, code, value) ' .
                "VALUES('$parent_id', '$code', '$value')";
        $$this->query($sql);
    }

    /**
     * 将插件library从默认模板中移动到指定模板中
     *
     * @access  public
     * @param   string  $tmp_name   模版名称
     * @param   string  $msg        如果出错，保存错误信息，否则为空
     * @return  Boolen
     */
    function move_plugin_library($tmp_name, &$msg) {
        $sql = 'SELECT code, library FROM ' . $this->pre . "plugins WHERE library > ''";
        $rec =$this->query($sql);
        $return_value = true;
        $target_dir = ROOT_PATH . 'themes/' . $tmp_name;
        $source_dir = ROOT_PATH . 'themes/' . C('template');
        foreach($rec as $key=> $value){
            //先移动，移动失败试则拷贝
            if (!@rename($source_dir . $value['library'], $target_dir . $value['library'])) {
                if (!@copy(ROOT_PATH . 'plugins/' . $value['code'] . '/temp' . $value['library'], $target_dir . $value['library'])) {
                    $return_value = false;
                    $msg .= "\n moving " . $value['library'] . ' failed';
                }
            }
        }
    }

}
