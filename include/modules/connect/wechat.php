<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2015 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wechat.php
 * ----------------------------------------------------------------------------
 * 功能描述：wechat登录插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = ROOT_PATH . 'plugins/connect/languages/' . C('lang') . '/' . basename(__FILE__);

if (file_exists($payment_lang)) {
    include_once($payment_lang);
    L($_LANG);
}
/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 类名 */
    $modules[$i]['name'] = 'wechat';
    // 文件名，不包含后缀
    $modules[$i]['type'] = 'wechat';

    $modules[$i]['className'] = 'wechat';
    // 作者信息
    $modules[$i]['author'] = 'ECTouch';

    // 作者QQ
    $modules[$i]['qq'] = '10000';

    // 作者邮箱
    $modules[$i]['email'] = 'support@ectouch.cn';

    // 申请网址
    $modules[$i]['website'] = 'http://mp.weixin.qq.com';

    // 版本号
    $modules[$i]['version'] = '1.0';

    // 更新日期
    $modules[$i]['date'] = '2014-10-03';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('type' => 'text', 'name' => 'app_key', 'value' => ''),
        array('type' => 'text', 'name' => 'app_secret', 'value' => ''),
    );
    return;
}
