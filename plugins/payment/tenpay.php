<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：tenpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：财付通wap支付插件
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
    $modules[$i]['desc'] = 'tenpay_desc';
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
            'name' => 'bargainor_id',
            'type' => 'text',
            'value' => ''
        ),
        array(
            'name' => 'tenpay_key',
            'type' => 'text',
            'value' => ''
        )
    );

    return;
}

/**
 * 支付插件类
 */
class tenpay
{

    /**
     * 生成支付代码
     *
     * @param array $order 订单信息
     * @param array $payment 支付方式信息
     */
    function get_code($order, $payment)
    {
        $gateway = 'https://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_init.cgi';
        // 初始化
        $data = array(
            'ver' => '2.0', // 必填 版本号,ver 默认值是 1.0。目前版本 ver 取值应为 2.0
            'charset' => 1, // 可选 1 UTF-8, 2 GB2312, 默认为 1 UTF-8
            'bank_type' => 0, // 必填 银行类型:财付通支付填 0;目前只能为 0
            'desc' => $order['order_sn'], // 必填 商品描述,32 个字符以内
            'purchaser_id' => '', // 可选 用户(买方)的财付通帐户(QQ 或 EMAIL)。若商户没有传该参数则在财付通支付页面;买家需要输入其财付通帐户。
            'bargainor_id' => $payment['bargainor_id'], // 必填 商户号,由财付通统一分配的 10 位正整数(120XXXXXXX)号
            'sp_billno' => $order['order_sn'] . 'O' . $order['log_id'], // 必填 商户系统内部的定单号,32 个字符内、可包含字母
            'total_fee' => $order['order_amount'] * 100, // 必填 总金额,以分为单位,不允许包含任何字母、符号
            'fee_type' => 1, // 可选 现金支付币种,目前只支持人民币,默认值是 1-人民币
            'notify_url' => return_url(basename(__FILE__, '.php'), array('type'=>0)), // 必填 接收财付通通知的 URL
            'callback_url' => return_url(basename(__FILE__, '.php'), array('type'=>1)), //必填 交易完成后跳转的 URL
            // 'attach' => , // 可选 商户附加信息,可做扩展参数255 字符内
            // 'time_start' => , // 可选 订单生成时间 格式为 yyyymmddhhmmss 如 2009 年 12 月 25日 9 点 10 分 10 秒表示为 20091225091010。时区为 GMT+8 beijing。该时间取自商户服务器
            // 'time_expire' => , // 可选 订单失效时间 格式为 yyyymmddhhmmss 如 2009 年 12 月 27日 9 点 10 分 10 秒表示为 20091227091010。时区为 GMT+8beijing。该时间取自商户服务器
            // 'sign' => , // 必填 MD5 签名结果,详见“第二章 MD5 签名规则”
        );
        // 字典排序
        ksort($data);
        reset($data);
        // 生成签名
        $sign = '';
        foreach ($data as $key => $vo) {
            if ($vo !== '') {
                $sign .= $key . '=' . $vo . '&';
            }
        }
        $sign .= 'key=' . $payment['tenpay_key'];
        $data['sign'] = strtoupper(md5($sign));
        // 交易初始化
        $result = Http::doPost($gateway, $data);
        $xml = (array)simplexml_load_string($result);
        if (isset($xml['err_info'])) {
            return '<div style="color:red; text-align: center">错误信息：'.$xml['err_info'].'</div>';
        }
        /* 生成支付按钮 */
        $button = '<div><input type="button" class="btn btn-info ect-btn-info ect-colorf ect-bg" onclick="window.open(\'' . $gateway . '?token_id=' . $xml['token_id'] . '\')" value="' . L('pay_button') . '" class="c-btn3" /></div>';
        return $button;
    }

    /**
     * 同步响应操作
     *
     * @return boolean
     */
    public function callback($data)
    {
        if (! empty($_GET)) {
            $payment = model('Payment')->get_payment($data['code']);
            $record_data = in($_GET);
            // 字典排序
            ksort($record_data);
            reset($record_data);
            // 生成签名
            $sign = '';
            foreach ($record_data as $key => $vo) {
                if ($vo !== '' && $key != 'sign') {
                    $sign .= $key . '=' . $vo . '&';
                }
            }
            $sign .= 'key=' . $payment['tenpay_key'];
            $sign = strtoupper(md5($sign));
            // 验证签名
            if ($sign != $record_data['sign']) {
                return false;
            }
            // 订单号和支付log_id
            $sp_billno = explode('O', $record_data['sp_billno']);
            $log_id = $sp_billno[1];
            if ($record_data['pay_result'] == 0) {
                /* 改变订单状态 */
                model('Payment')->order_paid($log_id, 2);
                return true;
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 异步通知
     *
     * @return string
     */
    public function notify($data)
    {
        if (! empty($_GET)) {
            $payment = model('Payment')->get_payment($data['code']);
            $record_data = in($_GET);
            // 字典排序
            ksort($record_data);
            reset($record_data);
            // 生成签名
            $sign = '';
            foreach ($record_data as $key => $vo) {
                if ($vo !== '' && $key != 'sign') {
                    $sign .= $key . '=' . $vo . '&';
                }
            }
            $sign .= 'key=' . $payment['tenpay_key'];
            $sign = strtoupper(md5($sign));
            // 验证签名
            if ($sign != $record_data['sign']) {
                exit("fail");
            }
            // 交易状态
            $pay_result = $record_data['pay_result'];
            // 获取支付订单号log_id
            $sp_billno = explode('O', $record_data['sp_billno']);
            $log_id = $sp_billno[1]; // 订单号log_id
            if ($pay_result == 0) {
                /* 改变订单状态 */
                model('Payment')->order_paid($log_id, 2);
                if(method_exists('WechatController', 'do_oauth')){
                    /* 如果需要，微信通知 wanglu */
                    $order_id = model('Base')->model->table('order_info')->field('order_id')->where('order_sn = "'.$sp_billno[0].'"')->getOne();
                    $order_url = __HOST__ . url('user/order_detail', array('order_id'=>$order_id));
                    $order_url = urlencode(base64_encode($order_url));
                    send_wechat_message('pay_remind', '', $sp_billno[0].' 订单已支付', $order_url, $sp_billno[0]);
                }
                exit("success");
            } else {
                exit("fail");
            }
        } else {
            exit("fail");
        }
    }
}
