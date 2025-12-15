<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BaseController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：基础函数控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

declare(strict_types=1);

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BaseController extends Controller
{
    protected static ?EcsEcshop $ecs = null;
    protected static ?EcsMysql $db = null;
    protected static ?EcsError $err = null;
    protected array $appConfig = [];
    protected ?Loader $load = null;

    public function __construct()
    {
        parent::__construct();
        $this->appConfig = C('APP');
        if ($this->_readHtmlCache()) {
            $this->appConfig['HTML_CACHE_ON'] = false;
            exit;
        }
        $this->_initialize();
        $this->_common();
        Migrate::init();
    }

    public function __destruct()
    {
        $this->_writeHtmlCache();
    }

    public static function ecs(): ?EcsEcshop
    {
        return self::$ecs;
    }

    public static function db(): ?EcsMysql
    {
        return self::$db;
    }

    public static function err(): ?EcsError
    {
        return self::$err;
    }

    private function _initialize(): void
    {
        //初始化设置
        @ini_set('memory_limit', '512M');
        @ini_set('session.cache_expire', '180');
        @ini_set('session.use_cookies', '1');
        @ini_set('session.auto_start', '0');
        @ini_set('display_errors', '1');
        @ini_set("arg_separator.output", "&amp;");
        @ini_set('include_path', '.;' . BASE_PATH);
        //加载系统常量和函数库
        require(BASE_PATH . 'config/constant.php');
        require(APP_PATH . 'common/helpers/function.php');
        //对用户传入的变量进行转义操作
        if (!empty($_GET)) {
            $_GET = addslashes_deep($_GET);
        }
        if (!empty($_POST)) {
            $_POST = addslashes_deep($_POST);
        }
        $_COOKIE = addslashes_deep($_COOKIE);
        $_REQUEST = addslashes_deep($_REQUEST);
        //创建 ECSHOP 对象
        self::$ecs = new EcsEcshop(C('DB_NAME'), C('DB_PREFIX'));
        //初始化数据库类
        self::$db = new EcsMysql(C('DB_HOST'), C('DB_USER'), C('DB_PWD'), C('DB_NAME'));
        //创建错误处理对象
        self::$err = new EcsError('message.dwt');
        //载入系统参数
        C('CFG', model('Base')->load_config());

        if (C('rewrite') > 0) {
            C('URL_MODEL', 2);
        }

        $this->load = new Loader();
    }

    //载入函数、语言文件
    private function _common(): void
    {
        //加载公共语言
        require(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/common.php');
        //加载控制器语言
        if (file_exists(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/' . strtolower(CONTROLLER_NAME) . '.php')) {
            require(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/' . strtolower(CONTROLLER_NAME) . '.php');
        }
        L($_LANG); //语言包赋值
        if (file_exists(APP_PATH . C('_APP_NAME') . '/helpers/insert.php')) {
            require(APP_PATH . C('_APP_NAME') . '/helpers/insert.php');
        }
        //加载模板解析扩展函数
        require(ROOT_PATH . 'vendor/Template.php');
    }

    //读取静态缓存
    private function _readHtmlCache(): bool
    {
        if (($this->appConfig['HTML_CACHE_ON'] == false) || empty($this->appConfig['HTML_CACHE_RULE'])) {
            $this->appConfig['HTML_CACHE_ON'] = false;
            return false;
        }
        if (isset($this->appConfig['HTML_CACHE_RULE'][APP_NAME][CONTROLLER_NAME][ACTION_NAME])) {
            $expire = $this->appConfig['HTML_CACHE_RULE'][APP_NAME][CONTROLLER_NAME][ACTION_NAME];
        } elseif (isset($this->appConfig['HTML_CACHE_RULE'][APP_NAME][CONTROLLER_NAME]['*'])) {
            $expire = $this->appConfig['HTML_CACHE_RULE'][APP_NAME][CONTROLLER_NAME]['*'];
        } else {
            $this->appConfig['HTML_CACHE_ON'] = false;
            return false;
        }
        return EcHtmlCache::read($this->appConfig['HTML_CACHE_PATH'], $expire);
    }

    //写入静态页面缓存
    private function _writeHtmlCache(): void
    {
        if ($this->appConfig['HTML_CACHE_ON']) {
            EcHtmlCache::write();
        }
    }
}
