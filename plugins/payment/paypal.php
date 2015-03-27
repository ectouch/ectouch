<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：paypal.php
 * ----------------------------------------------------------------------------
 * 功能描述：Paypal支付插件
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
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'paypal_desc';
    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '0';
    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '1';
    /* 作者 */
    $modules[$i]['author']  = 'ECTOUCH TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.paypal.com';
    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';
    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('name' => 'paypal_account', 'type' => 'text', 'value' => ''),
        array('name' => 'paypal_currency', 'type' => 'select', 'value' => 'USD')
    );
    return;
}

/**
 * 类
 */
class paypal
{
    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        $data_order_id      = $order['log_id'];
        $data_amount        = $order['order_amount'];
        $data_return_url    = return_url(basename(__FILE__, '.php'), array('type'=>0));
        $data_pay_account   = $payment['paypal_account'];
        $currency_code      = $payment['paypal_currency'];
        $data_notify_url    = return_url(basename(__FILE__, '.php'), array('type'=>1));
        $cancel_return      = __URL__;

        $def_url  = '<br /><form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">' .   // 不能省略
            "<input type='hidden' name='cmd' value='_xclick'>" .                             // 不能省略
            "<input type='hidden' name='business' value='$data_pay_account'>" .                 // 贝宝帐号
            "<input type='hidden' name='item_name' value='$order[order_sn]'>" .                 // payment for
            "<input type='hidden' name='amount' value='$data_amount'>" .                        // 订单金额
            "<input type='hidden' name='currency_code' value='$currency_code'>" .            // 货币
            "<input type='hidden' name='return' value='$data_return_url'>" .                    // 付款后页面
            "<input type='hidden' name='invoice' value='$data_order_id'>" .                      // 订单号
            "<input type='hidden' name='charset' value='utf-8'>" .                              // 字符集
            "<input type='hidden' name='no_shipping' value='1'>" .                              // 不要求客户提供收货地址
            "<input type='hidden' name='no_note' value=''>" .                                  // 付款说明
            "<input type='hidden' name='notify_url' value='$data_notify_url'>" .
            "<input type='hidden' name='rm' value='2'>" .
            "<input type='hidden' name='cancel_return' value='$cancel_return'>" .
            "<input type='submit' value='" . L('paypal_button') . "' class='btn btn-info' style='padding:0.8rem'>" .                      // 按钮
            "</form><br />";
        return $def_url;
    }

    /**
     * 响应操作
     */
    function callback($data)
    {
        $payment        = model('Payment')->get_payment($data['code']);
        $merchant_id    = $payment['paypal_account'];               ///获取商户编号

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value)
        {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        // post back to PayPal system to validate
        $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) ."\r\n\r\n";
        $fp = stream_socket_client("tcp://www.paypal.com:80", $errno, $errstr, 5);

        // assign posted variables to local variables
        $item_name = $_POST['item_name'];
        $item_number = $_POST['item_number'];
        $payment_status = $_POST['payment_status'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $order_sn = $_POST['invoice'];
        $memo = !empty($_POST['memo']) ? $_POST['memo'] : '';
        $action_note = $txn_id . '（' . L('paypal_txn_id') . '）' . $memo;
		
		// check that txn_id has not been previously processed
		$count = model('Base')->model->table('order_action')->where("action_note LIKE '" . mysql_like_quote($txn_id) . "%'")->count();
		if($count > 0){
			fclose($fp);
			return true;
		}

        if ($fp) {
            fputs($fp, $header . $req);
            while (!feof($fp)) {
                $res = fgets($fp, 1024);
                if (strcmp($res, 'VERIFIED') == 0) {
                    // check the payment_status is Completed
                    if ($payment_status != 'Completed' && $payment_status != 'Pending') {
						fclose($fp);
                        return false;
                    }
                    // check that receiver_email is your Primary PayPal email
                    if ($receiver_email != $merchant_id) {
						fclose($fp);
                        return false;
                    }
                    // check that payment_amount/payment_currency are correct
					$order_amount = model('Base')->model->table('pay_log')->field('order_amount')->where("log_id = '$order_sn'")->getOne();
                    if ($order_amount != $payment_amount){
						fclose($fp);
                        return false;
                    }
                    if ($payment['paypal_currency'] != $payment_currency) {
						fclose($fp);
                        return false;
                    }
                    // process payment
					model('Payment')->order_paid($order_sn, PS_PAYED, $action_note);
					fclose($fp);
                    return true;
                } elseif (strcmp($res, 'INVALID') == 0) {
                    // log for manual investigation
					fclose($fp);
                    return false;
                }
            }
        }else{
			fclose($fp);
            return false;
		}
    }
    
    /**
     * Paypal异步通知
     * 
     * @return string
     */
    public function notify($data)
    {
        $this->callback($data);
    }
}

?>