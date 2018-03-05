<?php

/**
 * ECTouch E-Commerce Project
 * ============================================================================
 * Copyright (c) 2014-2015 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：tenpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：财付通异步通知文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/license.txt )
 * ----------------------------------------------------------------------------
 */

define('IN_ECTOUCH', true);
define('ROOT_PATH', str_replace('\\', '/', dirname(dirname(dirname(__FILE__)))) . '/');
define('CONTROLLER_NAME', 'Respond');
$_GET['code'] = 'tenpay';
$_GET['type'] = 'notify';
require ROOT_PATH . 'include/bootstrap.php';
