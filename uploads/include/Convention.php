<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：convention.php
 * ----------------------------------------------------------------------------
 * 功能描述：常规配置文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/* 版本信息 */
if (file_exists(ROOT_PATH . 'data/version.php')) {
    require (ROOT_PATH . 'data/version.php');
}

/* 数据库配置 */
$db_conf = array();
if (file_exists(ROOT_PATH . 'data/config.php')) {
    $db_conf = require (ROOT_PATH . 'data/config.php');
}

/* 默认配置 */
$conf = array(
    'APP' => array(
        /* 日志和错误调试配置 */
        'DEBUG' => true, // 是否开启调试模式
        'LOG_ON' => false, // 是否开启出错信息保存到文件
        'LOG_PATH' => ROOT_PATH . 'data/cache/log/', // 出错信息存放的目录
        'ERROR_URL' => '', // 出错信息重定向页面，为空采用默认的出错页面
        'URL_HTTP_HOST' => 'http://localhost/', // 设置网址域
        'TIMEZONE' => 'PRC', // 时区设置
        'LANG' => 'zh_cn', // 语言包
        /* 静态页面缓存 */
        'HTML_CACHE_ON' => false, // 是否开启静态页面缓存，true开启.false关闭
        'HTML_CACHE_PATH' => ROOT_PATH . 'data/cache/html_cache/', // 静态页面缓存目录，一般不需要修改
        'HTML_CACHE_RULE' => array(
            'default' => array(
                'index' => array(
                    'index' => 1000
                )
            )
        )
    ),
    /* 数据库配置 */
    'DB' => array(
        'DB_TYPE' => 'mysql', // 数据库类型，一般不需要修改
        'DB_HOST' => 'localhost', // 数据库主机，一般不需要修改
        'DB_USER' => 'root', // 数据库用户名
        'DB_PWD' => '123456', // 数据库密码
        'DB_PORT' => 3306, // 数据库端口，mysql默认是3306，一般不需要修改
        'DB_NAME' => 'ecshop_db', // 数据库名
        'DB_CHARSET' => 'utf8', // 数据库编码，一般不需要修改
        'DB_PREFIX' => 'ecs_', // 数据库前缀
        /* 数据库缓存 */
        'DB_CACHE_ON' => false,
        'DB_CACHE_TYPE' => 'FileCache',
        'DB_CACHE_TIME' => 600,
        'DB_CACHE_PATH' => ROOT_PATH . 'data/cache/db_cache/'
    ),
    /* 模板配置 */
    'TPL' => array(
        'TPL_TEMPLATE_PATH' => BASE_PATH, // 模板目录，一般不需要修改
        'TPL_TEMPLATE_SUFFIX' => '.php', // 模板后缀,一般不需要修改
        'TPL_CACHE_ON' => false, // 是否开启模板缓存，true开启,false不开启
        'TPL_CACHE_TYPE' => '', // 数据缓存类型，为空或Memcache或SaeMemcache，其中为空为普通文件缓存
        /* 普通文件缓存 */
        'TPL_CACHE_PATH' => ROOT_PATH . 'data/cache/tpl_cache/', // 模板缓存目录,一般不需要修改
        'TPL_CACHE_SUFFIX' => '.php', // 模板缓存后缀,一般不需要修改
        /* memcache配置 */
        'MEM_SERVER' => array(
            array(
                '127.0.0.1',
                11211
            ),
            array(
                '127.0.0.2',
                11211
            )
        ),
        'MEM_GROUP' => 'tpl',
        /*SaeMemcache配置*/ 
        'SAE_MEM_GROUP' => 'tpl'
    ),
    'CFG' => array(),
    /* 重写规则 */
    'REWRITE' => array(
        '<app>/<c>/<a>.html' => '<app>/<c>/<a>'
    ),
    /* SESSION设置 */
    'SESSION' => array(
        'SESSION_AUTO_START' => true, // 是否自动开启Session
        'SESSION_OPTIONS' => array(), // session 配置数组 支持name id path expire domain 等参数
        'SESSION_PREFIX' => '' // session 前缀
        ),
    /* Cookie设置 */
    'COOKIE' => array(
        'COOKIE_EXPIRE' => 0, // Cookie有效期
        'COOKIE_DOMAIN' => '', // Cookie有效域名
        'COOKIE_PATH' => '/', // Cookie路径
        'COOKIE_PREFIX' => '', // Cookie前缀 避免冲突
        'COOKIE_HTTPONLY' => '' // Cookie httponly设置
        )
);

$conf['DB'] = array_merge($conf['DB'], $db_conf);
return $conf;
