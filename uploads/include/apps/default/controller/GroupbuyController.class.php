<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupbuyControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：团购控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GroupbuyController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $sort = 'last_update';
    private $order = 'ASC';

    public function __construct() {
        parent::__construct();

        if (ACTION_NAME == 'list') {
            $this->index();
        }
    }

    /* ------------------------------------------------------ */

    //-- 团购商品 --> 团购活动商品列表
    /* ------------------------------------------------------ */

    public function index() {
        $this->parameter();
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        /* 显示模板 */
        $this->display('group_buy_list.dwt');
    }

    /* ------------------------------------------------------ */

    //--异步加载团购商品列表
    /* ------------------------------------------------------ */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $gb_list = model('Groupbuy')->group_buy_list($this->size, $this->page, $this->sort, $this->order);
        foreach ($gb_list as $key => $value) {
            $this->assign('groupbuy', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /* ------------------------------------------------------ */

    //-- 团购商品 --> 商品详情
    /* ------------------------------------------------------ */
    public function info() {
        /* 取得参数：团购活动id */
        $group_buy_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if ($group_buy_id <= 0) {
            ecs_header("Location: ./\n");
            exit;
        }
        /* 取得团购活动信息 */
        $group_buy = model('GroupBuyBase')->group_buy_info($group_buy_id);
        if (empty($group_buy)) {
            ecs_header("Location: ./\n");
            exit;
        }

        $group_buy['gmt_end_date'] = $group_buy['end_date'];
        $this->assign('group_buy', $group_buy);
        /* 取得团购商品信息 */
        $goods_id = $group_buy['goods_id'];
        $goods = model('Goods')->goods_info($goods_id);
        if (empty($goods)) {
            ecs_header("Location: ./\n");
            exit;
        }
        $goods['url'] = build_uri('goods', array('gid' => $goods_id), $goods['goods_name']);
        $this->assign('gb_goods', $goods);

        /* 取得商品的规格 */
        $properties = model('Goods')->get_goods_properties($goods_id);
        $this->assign('specification', $properties['spe']); // 商品规格
        //模板赋值
        $this->assign('cfg', C('CFG'));
        assign_template();

        //更新团购商品点击次数
        $count = $this->model->table('touch_goods_activity')->field('COUNT(*)')->where('act_id =' . $group_buy_id)->getOne();
        if ($count) {
            $this->model->table('touch_goods_activity')->data('click_num = click_num + 1')->where('act_id = ' . $group_buy_id)->update();
        } else {
            $data['act_id'] = $group_buy_id;
            $data['click_num'] = 1;
            $this->model->table('touch_goods_activity')->data($data)->insert();
        }
        $this->assign('now_time', gmtime());           // 当前系统时间
        $this->assign('goods_id', $group_buy_id);     // 商品的id
        $this->display('group_buy_info.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 团购商品 --> 购买
    /* ------------------------------------------------------ */
    public function buy() {

        /* 查询：判断是否登录 */
        if ($_SESSION['user_id'] <= 0) {
            $this->redirect(url('user/login'));
        }

        /* 查询：取得参数：团购活动id */
        $group_buy_id = isset($_POST['group_buy_id']) ? intval($_POST['group_buy_id']) : 0;
        if ($group_buy_id <= 0) {
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查询：取得数量 */
        $number = isset($_POST['number']) ? intval($_POST['number']) : 1;
        $number = $number < 1 ? 1 : $number;

        /* 查询：取得团购活动信息 */
        $group_buy = model('GroupBuyBase')->group_buy_info($group_buy_id, $number);
        if (empty($group_buy)) {
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查询：检查团购活动是否是进行中 */
        if ($group_buy['status'] != GBS_UNDER_WAY) {
            show_message(L('gb_error_status'), '', '', 'error');
        }

        /* 查询：取得团购商品信息 */
        $goods = model('Goods')->get_goods_info($group_buy['goods_id']);
        if (empty($goods)) {
            ecs_header("Location: ./\n");
            exit;
        }

        /* 查询：判断数量是否足够 */
        if (($group_buy['restrict_amount'] > 0 && $number > ($group_buy['restrict_amount'] - $group_buy['valid_goods'])) || $number > $goods['goods_number']) {
            show_message(L('gb_error_goods_lacking'), '', '', 'error');
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
        if ($specs) {
            $_specs = explode(',', $specs);
            $product_info = model('ProductsBase')->get_products_info($goods['goods_id'], $_specs);
        }

        empty($product_info) ? $product_info = array('product_number' => 0, 'product_id' => 0) : '';

        /* 查询：判断指定规格的货品数量是否足够 */
        if ($specs && $number > $product_info['product_number']) {
            show_message(L('gb_error_goods_lacking'), '', '', 'error');
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = array();
        $sql = "SELECT a.attr_name, g.attr_value " .
                "FROM " . $this->model->pre . "goods_attr AS g, " .
                $this->model->pre . "attribute AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_attr_id " . db_create_in($specs);
        $res = $this->model->query($sql);
        foreach ($res as $row) {
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        /* 更新：清空购物车中所有团购商品 */
        model('Order')->clear_cart(CART_GROUP_BUY_GOODS);

        /* 更新：加入购物车 */
        $goods_price = $group_buy['deposit'] > 0 ? $group_buy['deposit'] : $group_buy['cur_price'];
        $cart = array(
            'user_id' => $_SESSION['user_id'],
            'session_id' => SESS_ID,
            'goods_id' => $group_buy['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $goods_price,
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $specs,
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_GROUP_BUY_GOODS,
            'is_gift' => 0
        );
        $new_cart = model('Common')->filter_field('cart', $cart);
        $this->model->table('cart')->data($new_cart)->insert();

        /* 更新：记录购物流程类型：团购 */
        $_SESSION['flow_type'] = CART_GROUP_BUY_GOODS;
        $_SESSION['extension_code'] = 'group_buy';
        $_SESSION['extension_id'] = $group_buy_id;

        /* 进入收货人页面 */
        $this->redirect(url('flow/consignee_list'));
        exit;
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        // 如果分类ID为0，则返回总分类页
        $page_size = C('page_size');
        $brand = I('request.brand');
        $price_max = I('request.price_max');
        $price_min = I('request.price_min');
        $filter_attr = I('request.filter_attr');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->brand = $brand > 0 ? $brand : 0;

        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'cur_price' : 'last_update');

        $this->sort = (isset($_REQUEST ['sort']) && in_array(trim(strtolower($_REQUEST ['sort'])), array(
                    'goods_id',
                    'cur_price',
                    'last_update',
                    'click_num',
                    'sales_count'
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
        $display = in_array($display, array(
                    'list',
                    'grid',
                    'album'
                )) ? $display : 'album';
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
    }

}

