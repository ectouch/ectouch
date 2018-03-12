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

/**
 * 支付插件类
 */
class alipay
{

    /**
     * 生成支付代码
     *
     * @param array $order
     *            订单信息
     * @param array $payment
     *            支付方式信息
     */
    public function get_code($order, $payment)
    {
        if (! defined('EC_CHARSET')) {
            $charset = 'utf-8';
        } else {
            $charset = EC_CHARSET;
        }
        
        $gateway = 'http://wappaygw.alipay.com/service/rest.htm?';
        // 请求业务数据
        $req_data = '<direct_trade_create_req>' . '<subject>' . $order['order_sn'] . 'B' . $order['log_id'] . '</subject>' . '<out_trade_no>' . $order['order_sn'] . '</out_trade_no>' . '<total_fee>' . $order['order_amount'] . '</total_fee>' . '<seller_account_name>' . $payment['alipay_account'] . '</seller_account_name>' . '<call_back_url>' . return_url(basename(__FILE__, '.php')) . '</call_back_url>' . '<notify_url>' . notify_url(basename(__FILE__, '.php'), true) . '</notify_url>' . '<out_user>' . $order['consignee'] . '</out_user>' . '<merchant_url>' . __URL__ . '</merchant_url>' . '<pay_expire>3600</pay_expire>' . '</direct_trade_create_req>';
        $parameter = array(
            'service' => 'alipay.wap.trade.create.direct', // 接口名称
            'format' => 'xml', // 请求参数格式
            'v' => '2.0', // 接口版本号
            'partner' => $payment['alipay_partner'], // 合作者身份ID
            'req_id' => $order['order_sn'] . $order['log_id'], // 请求号，唯一
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
        $result = Http::doPost($gateway, $param . '&sign=' . md5($sign));
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
        $button = '<script type="text/javascript" src="'.__PUBLIC__.'/js/ap.js"></script><div><input type="button" class="btn btn-info ect-btn-info ect-colorf ect-bg" onclick="javascript:_AP.pay(\'' . $gateway . $param . '&sign=' . md5($sign) . '\')" value="立即付款" class="c-btn3" /></div>';
        return $button;
    }

    /**
     * 手机支付宝同步响应操作
     *
     * @return boolean
     */
    public function callback($data)
    {
        if (! empty($_GET)) {
            $out_trade_no = explode('B', $_GET['subject']);
            $log_id = $out_trade_no[1];
            $payment = model('Payment')->get_payment($data['code']);

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
                /* 改变订单状态 */
                model('Payment')->order_paid($log_id, 2);
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 手机支付宝异步通知
     *
     * @return string
     */
    public function notify($data)
    {
        if (! empty($_POST)) {
            $payment = model('Payment')->get_payment($data['code']);
            // 支付宝系统通知待签名数据构造规则比较特殊，为固定顺序。
            $parameter['service'] = $_POST['service'];
            $parameter['v'] = $_POST['v'];
            $parameter['sec_id'] = $_POST['sec_id'];
            $parameter['notify_data'] = $_POST['notify_data'];
            // 生成签名字符串
            $sign = '';
            foreach ($parameter as $key => $val) {
                $sign .= "$key=$val&";
            }
            $sign = substr($sign, 0, - 1) . $payment['alipay_key'];
            // 验证签名
            if (md5($sign) != $_POST['sign']) {
                exit("fail");
            }
            // 解析notify_data
            $data = (array) simplexml_load_string($parameter['notify_data']);
            // 交易状态
            $trade_status = $data['trade_status'];
            // 获取支付订单号log_id
            $out_trade_no = explode('B', $data['subject']);
            $log_id = $out_trade_no[1]; // 订单号log_id
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                /* 改变订单状态 */
                model('Payment')->order_paid($log_id, 2);
                exit("success");
            } else {
                exit("fail");
            }
        } else {
            exit("fail");
        }
    }
}
