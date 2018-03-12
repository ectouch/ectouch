<?php
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = BASE_PATH . 'languages/' .C('lang'). '/payment/cod.php';

if (file_exists($payment_lang)) {
    global $_LANG;
    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'cod_desc';
    /* 是否支持货到付款 */
    $modules[$i]['is_cod']  = '1';
    /* 是否支持在线支付 */
    $modules[$i]['is_online']  = '0';
    /* 支付费用，由配送决定 */
    $modules[$i]['pay_fee'] = '0';
    /* 作者 */
    $modules[$i]['author']  = 'ECTouch TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.ectouch.cn';
    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';
    /* 配置信息 */
    $modules[$i]['config']  = array();
    return;
}
