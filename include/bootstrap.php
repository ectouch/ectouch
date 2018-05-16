<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：bootstrap.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch公共入口文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
header("Content-Type:text/html;charset=utf-8");
defined('APPNAME') or define('APPNAME', 'ECTouch');
defined('VERSION') or define('VERSION', '2.7.1');
defined('RELEASE') or define('RELEASE', '20180516');
defined('BASE_PATH') or define('BASE_PATH', dirname(__FILE__) . '/');
defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/');
defined('APP_PATH') or define('APP_PATH', BASE_PATH . 'apps/');
defined('ADDONS_PATH') or define('ADDONS_PATH', ROOT_PATH . 'plugins/');
defined('DEFAULT_APP') or define('DEFAULT_APP', 'default');
defined('DEFAULT_CONTROLLER') or define('DEFAULT_CONTROLLER', 'Index');
defined('DEFAULT_ACTION') or define('DEFAULT_ACTION', 'index');
/* 加载vendor */
require ROOT_PATH . 'vendor/autoload.php';
/* 系统函数 */
require(BASE_PATH . 'base/helpers/function.php');
/* 默认配置 */
C(load_file(BASE_PATH . 'config/global.php'));
/* 应用配置 */
C('APP', load_file(BASE_PATH . 'config/app.php'));
/* 数据库配置 */
C('DB', load_file(ROOT_PATH . 'data/config.php'));
/* 设置时区 */
date_default_timezone_set(DEFAULT_TIMEZONE);
/* 调试配置 */
defined('APP_DEBUG') or define('APP_DEBUG', C('DEBUG'));
/* 基于ecshop */
defined('IS_ECSHOP') or define('IS_ECSHOP', RUN_ON_ECS);

/* 错误等级 */
if (APP_DEBUG) {
    // 除了notice提示，其他类型的错误都报告
    error_reporting(E_ALL ^ E_NOTICE);
    $whoops = new \Whoops\Run;
    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
} else {
    @ini_set("display_errors", 0);
    // 把错误报告，全部屏蔽
    error_reporting(0);
}

/* 自动注册类文件 */
spl_autoload_register('autoload');
/* 网址路由解析 */
urlRoute();

try {
    /* 常规URL */
    defined('__HOST__') or define('__HOST__', get_domain());
    defined('__ROOT__') or define('__ROOT__', rtrim(dirname($_SERVER["SCRIPT_NAME"]), '\\/'));
    defined('__URL__') or define('__URL__', __HOST__ . __ROOT__);
    defined('__ADDONS__') or define('__ADDONS__', __ROOT__ . '/plugins');
    defined('__PUBLIC__') or define('__PUBLIC__', __ROOT__ . '/data/assets');
    defined('__ASSETS__') or define('__ASSETS__', __ROOT__ . '/data/assets/' . APP_NAME);
    /* 安装检测 */
    if (! file_exists(ROOT_PATH . 'data/install.lock')) {
        header("Location: ./install/");
        exit();
    }
    /* 控制器和方法 */
    $controller = CONTROLLER_NAME . 'Controller';
    $action = ACTION_NAME;
    /* 控制器类是否存在 */
    if (! class_exists($controller)) {
        E(APP_NAME . '/' . $controller . '.class.php 控制器类不存在', 404);
    }
    $controller = class_exists('MY_'. $controller) ? 'MY_'. $controller : $controller;
    $obj = new $controller();
    /* 是否非法操作 */
    if (! preg_match('/^[A-Za-z](\w)*$/', $action)) {
        E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法不合法', 404);
    }
    /* 控制器类中的方法是否存在 */
    if (! method_exists($obj, $action)) {
        E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法不存在', 404);
    }
    /* 执行当前操作 */
    $method = new ReflectionMethod($obj, $action);
    if ($method->isPublic() && ! $method->isStatic()) {
        $obj->$action();
    } else {
        /* 操作方法不是Public 抛出异常 */
        E(APP_NAME . '/' . $controller . '.class.php的' . $action . '() 方法没有访问权限', 404);
    }
} catch (Exception $e) {
    E($e->getMessage(), $e->getCode());
}
