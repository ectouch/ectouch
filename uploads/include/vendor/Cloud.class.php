<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：Cloud.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch云平台
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

// =====Api 说明======
// get.license 获取授权信息，参数 domain=网站域名
// get.competence 判断帐号权限是否正常
// get.latestversion 获取最新版本号，无参数
// get.notice 远程通知
// get.sms.abc 
// post.record 记录站点信息

class Cloud {

    //错误信息
    private $error = '出现未知错误 Cloud ！';
    //需要发送的数据
    private $data = array();
    //接口
    private $act = NULL;
    private $token = NULL;

    //服务器地址
    const serverHot = 'http://www.ectouch.cn/api';

    /**
     * 连接云平台系统
     * @access public
     * @return void
     */
    static public function getInstance() {
        static $systemHandier;
        if (empty($systemHandier)) {
            $systemHandier = new Cloud();
        }
        return $systemHandier;
    }

    /**
     * 获取错误信息
     * @return type
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 需要发送的数据
     * @param type $data
     * @return Cloud
     */
    public function data($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * 执行对应命令
     * @param type $act 例如 version.detection
     * @return type
     */
    public function act($act) {
        if (empty($this->data)) {
            $data = null;
        } else {
            $data = $this->data;
            //重置，以便下一次服务请求
            $this->data = array();
        }
        $this->act = $act;
        return $this->run($data);
    }

    /**
     * 检测当前站点授权文件是否正常
     * @return boolean
     */
    public function competence() {
        $key = $this->getTokenKey();
        $token = S($key);
        if (empty($token)) {
            $this->act('get.token');
            $token = S($key);
        }
        $this->token = $token;
        return true;
    }

    /**
     * 请求
     * @param type $data
     * @return type
     */
    private function run($data) {
        $fields = array(
            'data' => json_encode($data),
            'version' => VERSION,
            'release' => RELEASE,
            'act' => $this->act,
            'identity' => $data['appid'],
            'token' => $this->token,
        );
        //请求
        $status = Http::doPost(self::serverHot, $fields);
        if (false == $status) {
            $this->error = '无法联系服务器，请稍后再试！';
            return false;
        }
        return $this->returnResolve($status);
    }

    /**
     * 解析服务器返回的数据
     * @param type $data
     * @return type
     */
    private function returnResolve($data) {
        if (empty($data)) {
            return array();
        }
        $data = json_decode(base64_decode($data), true);
        if (!is_array($data) || !isset($data['status'])) {
            $this->error = '服务器返回信息错误！';
            return false;
        }
        if (!$data['status']) {
            $this->error = $data['error'];
            return false;
        }
        return $data['data'];
    }

    /**
     * 获取token Key
     * @return type
     */
    public function getTokenKey() {
        return md5(date('Y-m-d H') . 'cloud_token');
    }

}
