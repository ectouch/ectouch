<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ApiController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch接口控制器
 * 调用说明：url('api/index', array('openid'=>$openid, 'title'=>$title, 'msg'=>$msg, 'url'=>$url));
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ApiController extends CommonController
{
    private $weObj = '';
    private $wechat_id = 0;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $wxConf = $this->getConfig();
        $this->weObj = new Wechat($wxConf);

        $this->wechat_id = $wxConf['id'];
    }

    /**
     * PC后台发送发货通知模板消息接口方法
     *
     */
    public function index()
    {
        $user_id = I('get.user_id', 0, 'intval');
        $code = I('get.code', '', 'trim');
        $pushData = I('get.pushData', '', 'trim');
        $url = I('get.url', '');
        $url = $url ? base64_decode(urldecode($url)) : '';

        if ($user_id && $code) {
            $pushData = stripslashes(urldecode($pushData));
            //转换成数组
            $pushData = unserialize($pushData);
            // 发送微信通模板消息
            pushTemplate($code, $pushData, $url, $user_id);
        }
    }

    /**
     * JSSDK 参数
     * @return
     */
    public function jssdk()
    {
        $url = I('url', '', 'addslashes');
        if (!empty($url)) {
            $sdk = $this->weObj->getJsSign($url);
            $data = array('status' => '200', 'data' => $sdk);
        } else {
            $data = array('status' => '100', 'message' => '缺少参数');
        }
        exit(json_encode($data));
    }



    public function qrcode()
    {
        $userid = I('userid', '0', 'intval');
        echo call_user_func(array('WechatController', 'rec_qrcode'), $userid);
    }


    /**
     * 获取公众号配置
     *
     * @return array
     */
    private function getConfig()
    {
        $config = $this->model->table('wechat')
                ->field('id, token, appid, appsecret')
                ->where(array('status' => 1, 'default_wx' => 1))
                ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }
}
