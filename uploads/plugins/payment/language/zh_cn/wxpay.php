<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wxpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信支付语言包
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

$_LANG['wxpay'] = '微信支付';
$_LANG['wxpay_desc'] = '微信支付，是基于客户端提供的服务功能。同时向商户提供销售经营分析、账户和资金管理的功能支持。用户通过扫描二维码、微信内打开商品页面购买等多种方式调起微信支付模块完成支付。';
$_LANG['wxpay_appid'] = '微信公众号AppId';
$_LANG['wxpay_appsecret'] = '微信公众号AppSecret';
$_LANG['wxpay_paysignkey'] = '微信公众号PaySignKey';
$_LANG['wxpay_partnerid'] = '财付通商户号PartnerId';
$_LANG['wxpay_partnerkey'] = '财付通商户权限密钥PartnerKey';
$_LANG['wxpay_signtype'] = '签名方式';
$_LANG['wxpay_button'] = '立即用微信支付';