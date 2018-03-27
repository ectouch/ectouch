<?php

/**
 * ECTouch E-Commerce Project
 * ============================================================================
 * Copyright (c) 2014-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * This is NOT a freeware, use is subject to license terms
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/license )
 * ----------------------------------------------------------------------------
 */

// 部署模式
define('DEPLOY_MODE', 0);
define('EC_CHARSET', 'utf-8');
define('ADMIN_PATH', 'admin');
define('AUTH_KEY', 'this is a key');
define('OLD_AUTH_KEY', '');
define('API_TIME', '2018-01-30 10:03:17');
define('RUN_ON_ECS', false);
define('DEFAULT_TIMEZONE', 'PRC');
$database = ROOT_PATH . 'data/database.php';
if (!file_exists($database)) {
    header('location: ./install');
    exit();
}
return require $database;
