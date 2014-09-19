<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcHtmlCache.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：静态缓存类
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcHtmlCache {

    static private $cacheFile = "";

    /**
     * 检查规则，看是否满足生成静态页面的条件
     * @param unknown $cachePath
     * @return string
     */ 
    static public function getCacheFile($cachePath) {
        if (isset($_SERVER['PATH_INFO'])) {
            $url = $_SERVER['PATH_INFO'];
        } else {
            $script_name = $_SERVER["SCRIPT_NAME"]; //获取当前文件的路径
            $url = $_SERVER["REQUEST_URI"]; //获取完整的路径，包含"?"之后的字符串
            //去除url包含的当前文件的路径信息
            if ($url && @strpos($url, $script_name, 0) !== false) {
                $url = substr($url, strlen($script_name));
            } else {
                $script_name = str_replace(basename($_SERVER["SCRIPT_NAME"]), '', $_SERVER["SCRIPT_NAME"]);
                if ($url && @strpos($url, $script_name, 0) !== false) {
                    $url = substr($url, strlen($script_name));
                }
            }
        }
        //第一个字符是'/'，则去掉
        if ($url[0] == '/') {
            $url = substr($url, 1);
        }

        if (empty($url)) { //首页
            $file = 'index.html';
        } else if (empty($_SERVER['QUERY_STRING']) && preg_match("#^[a-z0-9_\-\/%]+\.(shtml|html|htm)$#i", $url)) { //静态页面
            $file = $url;
        } else { //静态缓存
            $url_md5 = md5($url);
            $file = $url_md5{0} . '/' . $url_md5{1} . '/' . $url_md5 . '.html';
        }
        $file = $cachePath . $file;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $file;
    }

    /**
     * 读取静态缓存文件
     * @param string $cachePath
     * @param number $expire
     * @return number|boolean
     */
    static public function read($cachePath = "", $expire = 1800) {
        self::$cacheFile = self::getCacheFile($cachePath);
        //静态缓存文件存在，且没有过期，则直接读取
        if (file_exists(self::$cacheFile) && (time() < ( filemtime(self::$cacheFile) + $expire ))) {
            return readfile(self::$cacheFile);
        } else {
            ob_start();
            return false;
        }
    }

    /**
     * 写入静态缓存文件
     */
    static public function write() {
        $contents = ob_get_contents();
        if (strlen($contents) > 0) {
            file_put_contents(self::$cacheFile, $contents);
        }
        ob_end_flush();
        flush();
    }

}
