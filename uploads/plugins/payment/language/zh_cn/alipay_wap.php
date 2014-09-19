<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：alipay_wap.php
 * ----------------------------------------------------------------------------
 * 功能描述：手机支付宝支付插件语言包
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

$_LANG['alipay_wap'] = '支付宝（手机版）';
$_LANG['alipay_wap_desc'] = '支付宝（手机版）网站(www.alipay.com) 是国内先进的网上支付平台。';
$_LANG['alipay_account'] = '支付宝帐户';
$_LANG['alipay_key'] = '交易安全校验码';
$_LANG['alipay_partner'] = '合作者身份ID';
$_LANG['pay_button'] = '立即使用支付宝支付';


$_LANG['relate_pay'] = '关联电脑支付方式';
$_LANG['relate_pay_desc'] = '请选择关联电脑版支付方式，用于电脑版支付；不关联则使用默认的支付方式。';
$_LANG['relate_pay_range'] = $pc_pay_type;