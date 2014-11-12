<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticleController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $cat_id = 0;
    private $keywords = '';

    public function __construct() {
        parent::__construct();
        $this->cat_id = intval(I('get.id'));
    }

    /* ------------------------------------------------------ */

    //-- 文章分类
    /* ------------------------------------------------------ */
    public function index() {
        $cat_id = intval(I('get.id'));
        $this->assign('article_categories', model('Article')->article_categories_tree($cat_id)); //文章分类树
        $this->display('article_cat.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 文章列表
    /* ------------------------------------------------------ */
    public function art_list() {
        $this->parameter();
        $this->assign('keywords', $this->keywords);
        $this->assign('id', $this->cat_id);
        $this->display('article_list.dwt');
    }

    /**
     * 文章列表异步加载
     */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('ArticleBase')->get_cat_articles($this->cat_id, $this->page, $this->size, $this->keywords);
        foreach ($list as $key => $value) {
            $this->assign('article', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /* ------------------------------------------------------ */

    //-- 文章详情
    /* ------------------------------------------------------ */
    public function info() {
        /* 文章详情 */
        $article_id = intval(I('get.aid'));
        $article = model('Article')->get_article_info($article_id);
        $this->assign('article', $article);
        $this->display('article_info.dwt', $cache_id);
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        // 如果分类ID为0，则返回总分类页
        $page_size = C('page_size');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->cat_id = intval(I('request.id'));
        $this->keywords = I('request.keywords');
        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
    }

}