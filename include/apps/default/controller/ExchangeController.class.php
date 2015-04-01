<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ExchangeControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：积分商城制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ExchangeController extends CommonController {

    private $cat_id = 0;
    private $ext = '';
    private $children = '';
    private $size = 10;
    private $page = 1;
    private $sort = 'last_update';
    private $order = 'ASC';
    private $integral_max = 0;
    private $integral_min = 0;

    // 构造函数
    public function __construct() {
        parent::__construct();
    }

    /* ------------------------------------------------------ */

    //-- 积分商城 - 商品列表
    /* ------------------------------------------------------ */
    public function index() {
        $this->parameter();
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        $goods_list = model('Exchange')->exchange_get_goods($this->children, $this->integral_min, $this->integral_max, $this->ext, $this->size, $this->page, $this->sort, $this->order);
        $count = model('Exchange')->get_exchange_goods_count($this->children, $this->integral_min, $this->integral_max);
        $this->pageLimit(url('index', array('sort' => $this->sort, 'order' => $this->order)), $this->size);
        $this->assign('goods_list', $goods_list);
        $this->assign('pager', $this->pageShow($count));
        $this->display('exchange_list.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 积分商城 - 商品列表 -异步加载
    /* ------------------------------------------------------ */
    public function asynclist_list() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $list = model('Exchange')->exchange_get_goods($this->children, $this->integral_min, $this->integral_max, $this->ext, $this->size, $this->page, $this->sort, $this->order);
        foreach ($list as $key => $value) {
            $this->assign('exchange', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /* ------------------------------------------------------ */

    //-- 积分商城 - 积分兑换商品详情
    /* ------------------------------------------------------ */
    public function exchange_goods() {

        $goods_id = $id = intval(I('request.gid'));
        if (!$goods_id) {
            ecs_header("Location: ./\n");
        }
        $goods = model('Exchange')->get_exchange_goods_info($goods_id);
        $this->assign('goods', $goods);

        /* 上一个商品下一个商品 */
        $sql = "SELECT eg.goods_id FROM " . $this->model->pre . "exchange_goods AS eg," . $this->model->pre . "goods AS g WHERE eg.goods_id = g.goods_id AND eg.goods_id > " . $goods['goods_id'] . " AND eg.is_exchange = 1 AND g.is_delete = 0 LIMIT 1";

        $prev_gid = $this->model->query($sql);
        if (!empty($prev_gid[0]['goods_id'])) {
            $prev_good['url'] = url('exchange/exchange_goods', array('gid' => $prev_gid));
            $this->assign('prev_good', $prev_good); //上一个商品
        }
        $sql = "SELECT max(eg.goods_id) as max FROM " . $this->model->pre . "exchange_goods AS eg," . $this->model->pre . "goods AS g WHERE eg.goods_id = g.goods_id AND eg.goods_id < " . $goods['goods_id'] . " AND eg.is_exchange = 1 AND g.is_delete = 0";
        $next_gid = $this->model->query($sql);
        if (!empty($next_gid[0]['max'])) {
            $next_good['url'] = url('exchange/exchange_goods', array('gid' => $next_gid));
            $this->assign('next_good', $next_good); //下一个商品
        }
        // 检查是否已经存在于用户的收藏夹
        if ($_SESSION ['user_id']) {
            $where['user_id'] = $_SESSION ['user_id'];
            $where['goods_id'] = $goods['goods_id'];
            $rs = $this->model->table('collect_goods')->where($where)->count();
            if ($rs > 0) {
                $this->assign('sc', 1);
            }
        }
        // 获得商品的规格和属性
        $properties = model('Goods')->get_goods_properties($goods['goods_id']);
        // 商品属性
        $this->assign('properties', $properties ['pro']);
        // 商品规格
        $this->assign('specification', $properties ['spe']);
        $this->assign('goods_id', $goods_id);
        $this->assign('pictures', model('GoodsBase')->get_goods_gallery($goods_id));
        $this->assign('cfg', C('CFG'));
        $this->display('exchange_info.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 积分商城 -  积分兑换
    /* ------------------------------------------------------ */
    public function buy() {
        if (!isset($back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {

            $back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'exchange') ? $GLOBALS['_SERVER']['HTTP_REFERER'] : './index.php';
        }
        /* 查询：判断是否登录 */
        if ($_SESSION['user_id'] <= 0) {
            //直接跳转
            ecs_header("Location: " . url('user/index') . "\n");
            // show_message(L('eg_error_login'), array(L('back_up_page')), array($back_act), 'error');
        }
        /* 查询：取得参数：商品id */
        $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
        if ($goods_id <= 0) {
            ecs_header("Location: ./\n");
            exit;
        }
        /* 查询：取得兑换商品信息 */
        $goods = model('Exchange')->get_exchange_goods_info($goods_id);
        if (empty($goods)) {
            ecs_header("Location: ./\n");
            exit;
        }
        /* 查询：检查兑换商品是否有库存 */
        if ($goods['goods_number'] == 0 && C('use_storage') == 1) {
            show_message(L('eg_error_number'), array(L('back_up_page')), array($back_act), 'error');
        }
        /* 查询：检查兑换商品是否是取消 */
        if ($goods['is_exchange'] == 0) {
            show_message(L('eg_error_status'), array(L('back_up_page')), array($back_act), 'error');
        }

        $user_info = model('Users')->get_user_info($_SESSION['user_id']);
        $user_points = $user_info['pay_points']; // 用户的积分总数
        if ($goods['exchange_integral'] > $user_points) {
            show_message(L('eg_error_integral'), array(L('back_up_page')), array($back_act), 'error');
        }

        /* 查询：取得规格 */
        $specs = '';
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'spec_') !== false) {
                $specs .= ',' . intval($value);
            }
        }
        $specs = trim($specs, ',');

        /* 查询：如果商品有规格则取规格商品信息 配件除外 */
        if (!empty($specs)) {
            $_specs = explode(',', $specs);

            $product_info = model('ProductsBase')->get_products_info($goods_id, $_specs);
        }
        if (empty($product_info)) {
            $product_info = array('product_number' => '', 'product_id' => 0);
        }

        //查询：商品存在规格 是货品 检查该货品库存
        if ((!empty($specs)) && ($product_info['product_number'] == 0) && (C('use_storage') == 1)) {
            show_message(L('eg_error_number'), array(L('back_up_page')), array($back_act), 'error');
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $this->model->pre . "goods_attr AS g, " . $this->model->pre . "attribute AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($specs);
        $res = $this->model->query($sql);
        foreach ($res as $row) {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        /* 更新：清空购物车中所有团购商品 */
        model('Order')->clear_cart(CART_EXCHANGE_GOODS);

        /* 更新：加入购物车 */
        $number = 1;
        $cart = array(
            'user_id' => $_SESSION['user_id'],
            'session_id' => SESS_ID,
            'goods_id' => $goods['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => 0, //$goods['exchange_integral']
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_EXCHANGE_GOODS,
            'is_gift' => 0
        );
        $this->model->table('cart')->data($cart)->insert();

        /* 记录购物流程类型：团购 */
        $_SESSION['flow_type'] = CART_EXCHANGE_GOODS;
        $_SESSION['extension_code'] = 'exchange_goods';
        $_SESSION['extension_id'] = $goods_id;

        /* 进入收货人页面 */
        ecs_header("Location: " . url('flow/consignee') . "\n");
        exit;
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        // 如果分类ID为0，则返回总分类页
        $page_size = C('page_size');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->page = I('request.page') ? intval(I('request.page')) : 1;
        $this->ext = '';
        $this->cat_id = I('request.cat_id');
        $this->integral_max = I('request.integral_max');
        $this->integral_min = I('request.integral_min');
        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');

        $this->sort = (isset($_REQUEST ['sort']) && in_array(trim(strtolower($_REQUEST ['sort'])), array(
                    'goods_id',
                    'exchange_integral',
                    'last_update',
                    'sales_volume',
                    'click_count'
                ))) ? trim($_REQUEST ['sort']) : $default_sort_order_type; // 增加按人气、按销量排序 by wang
        $this->order = (isset($_REQUEST ['order']) && in_array(trim(strtoupper($_REQUEST ['order'])), array(
                    'ASC',
                    'DESC'
                ))) ? trim($_REQUEST ['order']) : $default_sort_order_method;
        $display = (isset($_REQUEST ['display']) && in_array(trim(strtolower($_REQUEST ['display'])), array(
                    'list',
                    'grid',
                    'album'
                ))) ? trim($_REQUEST ['display']) : (isset($_COOKIE ['ECS'] ['display']) ? $_COOKIE ['ECS'] ['display'] : $default_display_type);
        $this->assign('display', $display);
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
        $this->children = get_children($this->cat_id);
    }

}
