<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：alipay_wap.php
 * ----------------------------------------------------------------------------
 * 功能描述：手机支付宝支付插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = ROOT_PATH . 'plugins/payment/language/' . C('lang') . '/' . basename(__FILE__);

if (file_exists($payment_lang)) {
    include_once ($payment_lang);
    L($_LANG);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'alipay_wap_desc';
    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';
    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '1';
    /* 作者 */
    $modules[$i]['author'] = 'ECTOUCH TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.ectouch.cn';
    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';
    /* 配置信息 */
    $modules[$i]['config'] = array(
        array(
            'name' => 'alipay_account',
            'type' => 'text',
            'value' => ''
        ),
        array(
            'name' => 'alipay_key',
            'type' => 'text',
            'value' => ''
        ),
        array(
            'name' => 'alipay_partner',
            'type' => 'text',
            'value' => ''
        ),
        array(
            'name' => 'relate_pay',
            'type' => 'select',
            'value' => ''
        )
    );
    
    return;
}

/**
 * 支付插件类
 */
class alipay_wap
{

    /**
     * 生成支付代码
     *
     * @param array $order
     * 订单信息
     * @param array $payment
     * 支付方式信息
     */
    function get_code($order, $payment)
    {
        if (! defined('EC_CHARSET')) {
            $charset = 'utf-8';
        } else {
            $charset = EC_CHARSET;
        }
        
        $gateway = 'http://wappaygw.alipay.com/service/rest.htm?';
        
        // 请求业务数据
        $req_data = '<direct_trade_create_req>' . '<subject>' . $order['order_sn'] . '</subject>' . '<out_trade_no>' . $order['order_sn'] . 'O' . $order['log_id'] . '</out_trade_no>' . '<total_fee>' . $order['order_amount'] . '</total_fee>' . '<seller_account_name>' . $payment['alipay_account'] . '</seller_account_name>' . '<call_back_url>' . return_url(basename(__FILE__, '.php')) . '</call_back_url>' . '<notify_url>' . $this->return_alipay_wap_url() . '</notify_url>' . '<out_user>' . $order['consignee'] . '</out_user>' . '<merchant_url>' . __URL__ . '</merchant_url>' . '<pay_expire>3600</pay_expire>' . '</direct_trade_create_req>';
        
        $parameter = array(
            'service' => 'alipay.wap.trade.create.direct', // 接口名称
            'format' => 'xml', // 请求参数格式
            'v' => '2.0', // 接口版本号
            'partner' => $payment['alipay_partner'], // 合作者身份ID
            'req_id' => date('Ymdhis') . rand(1000, 9999), // 请求号，唯一
            'sec_id' => 'MD5', // 签名方式
            'req_data' => $req_data, // 请求业务数据
            "_input_charset" => $charset
        );
        
        ksort($parameter);
        reset($parameter);
        
        $param = '';
        $sign = '';
        
        foreach ($parameter as $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }
        
        $param = substr($param, 0, - 1);
        $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
        
        // 请求授权接口
        $result = $this->post($gateway, $param . '&sign=' . md5($sign));
        $result = urldecode($result); // URL转码
        $result_array = explode('&', $result); // 根据 & 符号拆分
                                               // 重构数组
        $new_result_array = $temp_item = array();
        if (is_array($result_array)) {
            foreach ($result_array as $vo) {
                $temp_item = explode('=', $vo, 2); // 根据 & 符号拆分
                $new_result_array[$temp_item[0]] = $temp_item[1];
            }
        }
        
        $xml = simplexml_load_string($new_result_array['res_data']);
        $request_token = (array) $xml->request_token;
        // 请求交易接口
        $parameter = array(
            'service' => 'alipay.wap.auth.authAndExecute', // 接口名称
            'format' => 'xml', // 请求参数格式
            'v' => $new_result_array['v'], // 接口版本号
            'partner' => $new_result_array['partner'], // 合作者身份ID
            'sec_id' => $new_result_array['sec_id'],
            'req_data' => '<auth_and_execute_req><request_token>' . $request_token[0] . '</request_token></auth_and_execute_req>',
            'request_token' => $request_token[0],
            '_input_charset' => $charset
        );
        
        ksort($parameter);
        reset($parameter);
        
        $param = '';
        $sign = '';
        
        foreach ($parameter as $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }
        
        $param = substr($param, 0, - 1);
        $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
        
        /* 生成支付按钮 */
        $button = '<div style="text-align:center"><input type="button" onclick="window.open(\'' . $gateway . $param . '&sign=' . md5($sign) . '\')" value="'.l('pay_button') . '" class="c-btn3" /></div>';
        return $button;
    }

    /**
     * 手机支付宝同步响应操作
     * @return boolean
     */
    function respond()
    {
        if (! empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }
        
        $payment = model('Payment')->get_payment($_GET['code']);
        
        $out_trade_no = explode('O', $_GET['out_trade_no']);
        $order_sn = $out_trade_no[1];
        
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        
        $sign = '';
        foreach ($_GET as $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code') {
                $sign .= "$key=$val&";
            }
        }
        
        $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
        if (md5($sign) != $_GET['sign']) {
            return false;
        }
        
        if ($_GET['result'] == 'success') {
            /* 改变订单状态，统一使用异步通知 */
            // model('Payment')->order_paid($order_sn, 2);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取得返回信息地址
     * @return string
     */
    function return_alipay_wap_url()
    {
        return __URL__ . 'plugins/payment/notify/alipay_wap.php';
    }

    /**
     * post请求
     * @param unknown $url
     * @param unknown $curlPost
     * @return Ambigous <boolean, type, mixed, string>
     */
    function post($url, $curlPost)
    {
        return Http::doPost($url, $curlPost);
    }
}

?>