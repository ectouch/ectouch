<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ecmoban.php
 * ----------------------------------------------------------------------------
 * 功能描述：模版堂短信
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ecmoban.com)
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ecmoban
{
    /**
     * @var objcet 短信对象
     */
    public $sms_api = "https://cloud.ecjia.com/sites/api/?url=sms/send";
    public $phones = array();
    public $errorInfo = null;
    public $config = array( 
        'app_key' => '',
        'app_secret' => '');
    /**
     * 构建函数
     * @param array $config 短信配置
     */
    public function __construct()
    {
        $this->config['app_key'] = get_sms_config('ecmoban', 'account');
        $this->config['app_secret'] = get_sms_config('ecmoban', 'key');
    }

    public function send($phones, $msg)
    {
        $post_data =array(
            'app_key' => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
            'mobile' => $phones,
            'content' => $msg);

        $res = Http::doPost($this->sms_api, $post_data);
        $data = json_decode($res, true);

        if ($data['status']['succeed']) {
            return true;
        } else {
            $this->errorInfo = $data['status']['error_desc'];
            logResult($this->errorInfo);
            return false;
        }        
    }

}