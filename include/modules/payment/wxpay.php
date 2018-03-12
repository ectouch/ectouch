<?php
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = BASE_PATH . 'languages/' .C('lang'). '/payment/wxpay.php';

if (file_exists($payment_lang)) {
    global $_LANG;
    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code'] = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc'] = 'wxpay_desc';
    /* 是否支持货到付款 */
    $modules[$i]['is_cod'] = '0';
    /* 是否支持在线支付 */
    $modules[$i]['is_online'] = '1';
    /* 作者 */
    $modules[$i]['author'] = 'ECTouch TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.ectouch.cn/';
    /* 版本号 */
    $modules[$i]['version'] = '2.5';
    /* 配置信息 */
    $modules[$i]['config'] = array(
        // 微信公众号身份的唯一标识
        array(
            'name' => 'wxpay_appid',
            'type' => 'text',
            'value' => ''
        ),
        // JSAPI接口中获取openid，审核后在公众平台开启开发模式后可查看
        array(
            'name' => 'wxpay_appsecret',
            'type' => 'text',
            'value' => ''
        ),
        // 商户支付密钥Key
        array(
            'name' => 'wxpay_key',
            'type' => 'text',
            'value' => ''
        ),
        // 受理商ID
        array(
            'name' => 'wxpay_mchid',
            'type' => 'text',
            'value' => ''
        )
    );
    
    return;
}
