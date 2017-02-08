<?php

/**
 * ECTouch E-Commerce Project
 *
 * @package  ECTouch
 * @author   carson <docxcn@gmail.com>
 */

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    die('require PHP > 5.4.0 !');
}

/*
|--------------------------------------------------------------------------
| Setting Version
|--------------------------------------------------------------------------
|
*/

defined('APPNAME') or define('APPNAME', 'ECTouch');
defined('VERSION') or define('VERSION', '2.0.0-dev');
defined('RELEASE') or define('RELEASE', '20170108');
defined('CHARSET') or define('CHARSET', 'utf-8');

/*
|--------------------------------------------------------------------------
| Setting Debuger
|--------------------------------------------------------------------------
|
*/

if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', '192.168.1.92'])) {
    defined('YII_DEBUG') or define('YII_DEBUG', false);
    defined('YII_ENV') or define('YII_ENV', 'prod');
} else {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
}

/*
|--------------------------------------------------------------------------
| Loading Kernel
|--------------------------------------------------------------------------
|
*/

require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

/*
|--------------------------------------------------------------------------
| Load Configuration
|--------------------------------------------------------------------------
|
*/

$config = require(__DIR__ . '/../config/web.php');

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return new yii\web\Application($config);
