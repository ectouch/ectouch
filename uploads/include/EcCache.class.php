<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcCache.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：缓存类
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcCache
{

    protected $cache = NULL;

    /**
     * 构造函数
     * 
     * @param unknown $config            
     * @param string $type            
     */
    public function __construct($config = array(), $type = 'FileCache')
    {
        $cacheDriver = 'Ec' . $type;
        require_once (dirname(__FILE__) . '/driver/cache/' . $cacheDriver . '.class.php');
        $this->cache = new $cacheDriver($config);
    }

    /**
     * 读取缓存
     * 
     * @param unknown $key            
     */
    public function get($key)
    {
        return $this->cache->get($key);
    }

    /**
     * 设置缓存
     * 
     * @param unknown $key            
     * @param unknown $value            
     * @param number $expire            
     */
    public function set($key, $value, $expire = 1800)
    {
        return $this->cache->set($key, $value, $expire);
    }

    /**
     * 自增1
     * 
     * @param unknown $key            
     * @param number $value            
     */
    public function inc($key, $value = 1)
    {
        return $this->cache->inc($key, $value);
    }

    /**
     * 自减1
     * 
     * @param unknown $key            
     * @param number $value            
     */
    public function des($key, $value = 1)
    {
        return $this->cache->des($key, $value);
    }

    /**
     * 删除
     * 
     * @param unknown $key            
     */
    public function del($key)
    {
        return $this->cache->del($key);
    }

    /**
     * 清空缓存
     */
    public function clear()
    {
        return $this->cache->clear();
    }
}
