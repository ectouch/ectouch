<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：RespondController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 支付应答控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class RespondController extends CommonController
{

    private $data;

    public function __construct()
    {
        parent::__construct();
        // 获取参数
        $code = I('get.code');
        $this->data = unserialize(urlsafe_b64decode($code));
	}

    // 发送
    public function index()
    {
        /* 判断是否启用 */
        $condition['pay_code'] = $this->data['code'];
        $condition['enabled'] = 1;
        $enabled = $this->model->table('touch_payment')->where($condition)->count();
        if ($enabled == 0) {
            $msg = L('pay_disabled');
        } else {
            $plugin_file = ADDONS_PATH.'payment/' . $this->data['code'] . '.php';
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            if (file_exists($plugin_file)) {
                /* 根据支付方式代码创建支付类的对象并调用其响应操作方法 */
                include_once($plugin_file);
                $payobj = new $this->data['code']();
                /* 处理异步请求 */
                if($this->data['type'] == 1){
                    @$payobj->notify($this->data);
                }
                $msg = (@$payobj->callback($this->data)) ? L('pay_success') : L('pay_fail');
            } else {
                $msg = L('pay_not_exist');
            }
        }
        //显示页面
        $this->assign('message', $msg);
        $this->assign('shop_url', __URL__);
        $this->display('respond.dwt');
    }
}