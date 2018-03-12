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

class BrandController extends CommonController
{
    private $brand = 0;
    private $size = 10;
    private $page = 1;
    private $sort = 'last_update';
    private $order = 'ASC';

    // 构造函数
    public function __construct()
    {
        parent::__construct();

        $this->action = ACTION_NAME;
        /* 如果是显示页面，对页面进行相应赋值 */
        assign_template();
        $this->assign('action', $this->action);
    }

    public function index()
    {
        $list = model('Brand')->get_brands_hj();
        if ($list) {
            if ($list['top']) {
                foreach ($list['top'] as $key => $val) {
                    $list['top'][$key]['goods'] = model('Brand')->brand_get_goods_img($val['brand_id'], '', 'goods_id', 'desc', '3', '1');
                }
            }
            if ($list['list1']) {
                foreach ($list['list1'] as $key=>$val) {
                    $list['list1'][$key]['goods'] =  model('Brand')->brand_get_goods_img($val['brand_id'], '', 'goods_id', 'desc', '1', '1');
                }
            }
        }
        $this->assign('list', $list);
        $this->assign('page_title', L('brand_hj'));
        $this->display('brand_show.dwt');
    }

    /* ------------------------------------------------------ */

    // -- 品牌活动 - 异步加载
    /* ------------------------------------------------------ */
    public function asynclist()
    {
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
    public function goods_list()
    {
        $this->parameter();
        $brand_id = I('request.id');
        $brand_info = model('BrandBase')->get_brand_info($brand_id);
        if (empty($brand_info)) {
            $this->redirect(url('index'));
            exit();
        }
        $cat = I('request.cat') ? intval(I('request.cat')) : 0;
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        $this->assign('brand_id', $brand_id);
        $this->assign('cat', $cat);
        $goods_list = model('Brand')->brand_get_goods($brand_id, '', $this->sort, $this->order, $this->size, $this->page);
        $this->assign('goods_list', $goods_list);
        $count = model('Brand')->goods_count_by_brand($brand_id, $this->cat);
        $this->pageLimit(url('goods_list', array('id' => $brand_id, 'sort' => $this->sort, 'order' => $this->order)), $this->size);
        $this->assign('pager', $this->pageShow($count));
        $this->assign('show_marketprice', C('show_marketprice'));

        $this->assign('brand_info', $brand_info);
        // 商品数量
        $this->assign('brand_goods_count', model('Brand')->goods_count_by_brand($brand_id));
        //dump(model('Brand')->goods_count_by_brand($brand_id));exit;
        //新品
        $sql = "SELECT COUNT(*) as count FROM  {pre}goods AS g WHERE brand_id = '$brand_id' AND g.is_new = 1 and g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ";
        $res = $this->model->getrow($sql);
        $this->assign('brand_goods_new', $res['count']);
        //热销
        $sql = "SELECT COUNT(*) as count FROM  {pre}goods AS g WHERE brand_id = '$brand_id' AND g.is_hot = 1 and g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ";
        $res = $this->model->getrow($sql);
        $this->assign('brand_goods_hot', $res['count']);

        $this->assign('page_title', $brand_info['brand_name']);

        // 微信JSSDK分享
        $brand_img = $brand_info['brand_logo'];
        $share_data = array(
            'title' => $brand_info['brand_name'],
            'desc' => $brand_info['brand_desc'],
            'link' => '',
            'img' => $brand_img,
        );
        $this->assign('share_data', $this->get_wechat_share_content($share_data));

        $this->assign('meta_keywords', $brand_info['brand_name']);
        $this->assign('meta_description', $brand_info['brand_desc']);

        $this->display('brand_goods_list.dwt');
    }

    /**
     * 异步加载品牌列表
     */
    public function list_asynclist()
    {
        $this->parameter();
        $this->assign('show_marketprice', C('show_marketprice'));
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
    private function parameter()
    {
        // 初始化分页信息
        $page_size = C('page_size');
        $brand = I('request.brand');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->brand = $brand > 0 ? $brand : 0;
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');

        $this->assign('show_asynclist', C('show_asynclist'));
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
        $this->assign('display', $display);
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
    }

    public function nav()
    {
        $list = model('Brand')->get_brands('brand', 1000, 1);
        $this->assign('list', $list);
        for ($i='A',$a=0;$a<26;$a++,$i++) {
            $nav[]=$i;
        }
        $this->assign('nav', $nav);
        $this->assign('page_title', L('all_brand'));
        $this->display('brand_list.dwt');
    }
}
