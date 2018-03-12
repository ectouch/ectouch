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
        $this->data = array(
            'code' => I('get.code'),
            'type' => I('get.type')
        );
    }

    // 发送
    public function index()
    {
        /* 判断是否启用 */
        $condition['pay_code'] = $this->data['code'];
        $condition['enabled'] = 1;
        $enabled = $this->model->table('payment')->where($condition)->count();
        if ($enabled == 0) {
            $msg = L('pay_disabled');
        } else {
            // 微信h5中间页面
            if (isset($_GET['style']) && $this->data['code'] == 'wxpay' && $_GET['style'] == 'wxh5') {
                $log_id = intval($_GET['log_id']);
                $url = url('respond/wxh5', array('code' => 'wxpay', 'log_id' => $log_id));
                $this->redirect($url);
            }

            $plugin_file = ADDONS_PATH.'payment/' . $this->data['code'] . '.php';
            if (file_exists($plugin_file)) {
                include_once($plugin_file);
                $payobj = new $this->data['code']();
                // 处理异步请求
                if ($this->data['type'] == 'notify') {
                    @$payobj->notify($this->data);
                }
                $msg = (@$payobj->callback($this->data)) ? L('pay_success') : L('pay_fail');
            } else {
                $msg = L('pay_not_exist');
            }
        }
        // 根据不同订单类型（普通、充值） 跳转
        if (isset($_GET['log_id']) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = $this->model->table('pay_log')->field('order_type, order_id')->where(array('log_id' => $log_id))->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order_detail', array('order_id' => $pay_log['order_id']));
            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account_detail');
            }
        } else {
            $order_url = url('user/order_list'); // 订单列表
        }
        $order_url = str_replace('respond', 'index', $order_url);
        //显示页面
        $this->assign('message', $msg);
        $this->assign('order_url', $order_url);
        $this->assign('shop_url', __URL__);
        $this->display('respond.dwt');
    }

    /**
     * 微信支付h5同步通知中间页面
     * @return
     */
    public function wxh5()
    {
        //显示页面
        if (isset($_GET) && !empty($_GET['log_id'])) {
            $log_id = intval($_GET['log_id']);
            $pay_log = $this->model->table('pay_log')->field('order_type, order_id')->where(array('log_id' => $log_id))->find(); // order_type 0 普通订单, 1 会员充值订单
            if ($pay_log['order_type'] == 0) {
                $order_url = url('user/order_detail', array('order_id' => $pay_log['order_id']));
            } elseif ($pay_log['order_type'] == 1) {
                $order_url = url('user/account_detail');
            }
            $order_url = str_replace('respond', 'index', $order_url);
            $repond_url = __URL__ . "/respond.php?code=" .$this->data['code']. "&status=1&log_id=".$log_id;
        } else {
            $repond_url = __URL__ . "/respond.php?code=" .$this->data['code']. "&status=0";
        }
        $is_wxh5 = ($this->data['code'] == 'wxpay' && !is_wechat_browser()) ? 1 : 0;
        $this->assign('is_wxh5', $is_wxh5);
        $this->assign('repond_url', $repond_url);
        $this->assign('order_url', $order_url);
        $this->display('respond_wxh5.dwt');
    }
}
