<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BrandControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：品牌控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BrandController extends CommonController {

    private $brand = 0;
    private $size = 10;
    private $page = 1;
    private $sort = 'last_update';
    private $order = 'ASC';

    // 构造函数
    public function __construct() {
        parent::__construct();

        $this->action = ACTION_NAME;
        /* 如果是显示页面，对页面进行相应赋值 */
        assign_template();
        $this->assign('action', $this->action);
    }

    public function index() {
        $this->parameter();
        $this->assign('brand_id', $this->brand);
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        $this->display('brand_list.dwt');
    }

    /* ------------------------------------------------------ */

    // -- 品牌活动 - 异步加载
    /* ------------------------------------------------------ */
    public function asynclist() {
        // 开始工作
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('Brand')->get_brands('brand', $this->size, $this->page);
        foreach ($list as $key => $value) {
            $this->assign('brand', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /**
     * 品牌商品列表
     */
    public function goods_list() {
        $this->parameter();
        $brand_id = I('request.id');
        $brand_info = model('BrandBase')->get_brand_info($brand_id);
        if (empty($brand_info)) {
            ecs_header("Location: ./\n");
            exit();
        }
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        $this->assign('brand_id', $brand_id);
        $this->display('brand_goods_list.dwt', $cache_id);
    }

    /**
     * 异步加载品牌列表
     */
    public function list_asynclist() {
        $this->parameter();
        $brand_id = I('request.brand');
        $brand_info = model('BrandBase')->get_brand_info($brand_id);
        if (empty($brand_info)) {
            ecs_header("Location: ./\n");
            exit();
        }
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('Brand')->brand_get_goods($brand_id, '', $this->sort, $this->order, $this->size, $this->page);
        foreach ($list as $key => $value) {
            $this->assign('brand_goods', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /**
     * 处理参数便于搜索商品信息
     */
    public function parameter() {
        // 初始化分页信息
        $page_size = C('page_size');
        $brand = I('request.brand');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->brand = $brand > 0 ? $brand : 0;
        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');

        $this->sort = (isset($_REQUEST['sort']) && in_array(trim(strtolower($_REQUEST['sort'])), array(
                    'goods_id',
                    'shop_price',
                    'last_update',
                    'click_count',
                    'sales_volume'
                ))) ? trim($_REQUEST['sort']) : $default_sort_order_type; // 增加按人气、按销量排序 by wang
        $this->order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array(
                    'ASC',
                    'DESC'
                ))) ? trim($_REQUEST['order']) : $default_sort_order_method;
        $display = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array(
                    'list',
                    'grid',
                    'album'
                ))) ? trim($_REQUEST['display']) : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
        $display = in_array($display, array(
                    'list',
                    'grid',
                    'album'
                )) ? $display : 'album';
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
    }

}
