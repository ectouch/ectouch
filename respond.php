<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：respond.php
 * ----------------------------------------------------------------------------
 * 功能描述：支付接口通知文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

define('IN_ECTOUCH', true);
define('CONTROLLER_NAME', 'Respond');
if (!isset($_GET['code'])) {
    header('location: index.php?'.$_SERVER['QUERY_STRING']);
    exit();
}
require dirname(__FILE__) . '/include/bootstrap.php';
