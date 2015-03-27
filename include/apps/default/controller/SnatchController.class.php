<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SnatchControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：夺宝奇兵控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SnatchController extends CommonController {

    protected $id;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->id = isset($_REQUEST ['id']) ? intval($_REQUEST ['id']) : model('Snatch')->get_last_snatch();
    }

    /**
     * 夺宝骑兵列表
     */
    public function index() {
        $this->assign('goods_list', model('Snatch')->get_snatch_list());     //所有有效的夺宝奇兵列表
        $this->assign('show_asynclist', C('show_asynclist'));
        $this->assign('tile', L('snatch_list'));
        $this->display('snatch_list.dwt');
    }

    /**
     * 夺宝奇兵详情
     */
    public function info() {
        $goods = model('Snatch')->get_snatch($this->id);
        if ($goods) {
            if ($goods['is_end']) {
                //如果活动已经结束,获取活动结果
                $this->assign('result', model('ActivityBase')->get_snatch_result($this->id));
            }
            $this->assign('id', $this->id);
            $this->assign('snatch_goods', $goods); // 竞价商品
            $this->assign('pictures', model('GoodsBase')->get_goods_gallery($goods['goods_id']));
            $this->assign('myprice', model('Snatch')->get_myprice($this->id));
            if ($goods['product_id'] > 0) {
                $goods_specifications = model('goodsBase')->get_specifications_list($goods['goods_id']);

                $good_products = model('ProductsBase')->get_good_products($goods['goods_id'], 'AND product_id = ' . $goods['product_id']);

                $_good_products = explode('|', $good_products[0]['goods_attr']);
                $products_info = '';
                foreach ($_good_products as $value) {
                    $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
                }
                $this->assign('products_info', $products_info);
                unset($goods_specifications, $good_products, $_good_products, $products_info);
            }
        } else {
            show_message(L('now_not_snatch'));
        }
        /* 调查 */
        $vote = get_vote();
        if (!empty($vote)) {
            $this->assign('vote_id', $vote['id']);
            $this->assign('vote', $vote['content']);
        }
        $this->assign('price_list', model('Snatch')->get_price_list($this->id));
        $this->assign('promotion_info', model('GoodsBase')->get_promotion_info());
        $this->assign('feed_url', (C('rewrite') == 1) ? "feed-typesnatch.xml" : 'feed.php?type=snatch'); // RSS URL
        $this->display('snatch.dwt');
    }

    /**
     * 最新出价列表
     */
    public function new_price_list() {
        $id = I('get.id');
        $this->assign('price_list', model('Snatch')->get_price_list($id));
        $this->display('library/snatch_price.lbi');
        exit;
    }

    /**
     * 用户出价处理
     */
    public function bid() {
        $json = new EcsJson;
        $result = array('error' => 0, 'content' => '');

        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        $price = round($price, 2);
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        /* 测试是否登陆 */
        if (empty($_SESSION['user_id'])) {
            $result['error'] = 1;
            $result['content'] = L('not_login');
            die($json->encode($result));
        }
        /* 获取活动基本信息用于校验 */

        $row = $this->model->table('goods_activity')->field('act_name,end_time,ext_info')->where('act_id =' . $id)->find();
        if ($row) {
            $info = unserialize($row['ext_info']);
            if ($info) {
                foreach ($info as $key => $val) {
                    $row[$key] = $val;
                }
            }
        }
        if (empty($row)) {
            $result['error'] = 1;
            $result['content'] = L('now_not_snatch');
            die($json->encode($result));
        }
        if ($row['end_time'] < gmtime()) {
            $result['error'] = 1;
            $result['content'] = L('snatch_is_end');
            die($json->encode($result));
        }
        /* 检查出价是否合理 */
        if ($price < $row['start_price'] || $price > $row['end_price']) {
            $result['error'] = 1;
            $result['content'] = sprintf($GLOBALS['_LANG']['not_in_range'], $row['start_price'], $row['end_price']);
            die($json->encode($result));
        }
        /* 检查用户是否已经出同一价格 */
        $count = $this->model->table('snatch_log')->where("snatch_id = '" . $id . "' AND user_id = '$_SESSION[user_id]' AND bid_price = '.$price.'")->count();
        if ($count > 0) {
            $result['error'] = 1;
            $result['content'] = sprintf(L('also_bid'), price_format($price, false));
            die($json->encode($result));
        }
        /* 检查用户积分是否足够 */
        $pay_points = $this->model->table('users')->field('pay_points')->where("user_id = '$_SESSION[user_id]'")->find();

        if ($row['cost_points'] > $pay_points['pay_points']) {
            $result['error'] = 1;
            $result['content'] = L('lack_pay_points');
            die($json->encode($result));
        }

        model('ClipsBase')->log_account_change($_SESSION['user_id'], 0, 0, 0, 0 - $row['cost_points'], sprintf(L('snatch_log'), $row['snatch_name'])); //扣除用户积分

        $snatch_log['snatch_id'] = $id;
        $snatch_log['user_id'] = $_SESSION['user_id'];
        $snatch_log['bid_price'] = $price;
        $snatch_log['bid_time'] = gmtime();
        $this->model->table('snatch_log')->data($snatch_log)->insert();

        $this->assign('myprice', model('Snatch')->get_myprice($id));
        $this->assign('id', $id);
        $result['content'] = ECTouch::view()->fetch('library/snatch.lbi');
        die($json->encode($result));
    }

    /**
     * 购买商品
     */
    public function buy() {
        if (empty($this->id)) {
            $this->redirect(url('Snatch/info', array('id' => $this->id)));
            exit;
        }
        if (empty($_SESSION['user_id'])) {
            show_message(L('not_login'));
        }
        $snatch = model('Snatch')->get_snatch($this->id);

        if (empty($snatch)) {
            $this->redirect(url('index', array('id' => $this->id)));
            exit;
        }

        /* 未结束，不能购买 */
        if (empty($snatch['is_end'])) {
            $this->redirect(url('index', array('id' => $this->id)));
            exit;
        }

        $result = model('ActivityBase')->get_snatch_result($this->id);

        if ($_SESSION['user_id'] != $result['user_id']) {
            show_message(L('not_for_you'));
        }

        //检查是否已经购买过
        if ($result['order_count'] > 0) {
            show_message(L('order_placed'));
        }

        /* 处理规格属性 */
        $goods_attr = '';
        $goods_attr_id = '';
        if ($snatch['product_id'] > 0) {
            $product_info = get_good_products($snatch['goods_id'], 'AND product_id = ' . $snatch['product_id']);

            $goods_attr_id = str_replace('|', ',', $product_info[0]['goods_attr']);

            $attr_list = array();
            $sql = "SELECT a.attr_name, g.attr_value " .
                    "FROM " . $this->model->pre . "goods_attr AS g, " .
                    $this->model->pre . "attribute AS a " .
                    "WHERE g.attr_id = a.attr_id " .
                    "AND g.goods_attr_id " . db_create_in($goods_attr_id);
            $res = $this->model->query($sql);
            foreach ($res as $row) {
                $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
            }
            $goods_attr = join('', $attr_list);
        } else {
            $snatch['product_id'] = 0;
        }
        /* 清空购物车中所有商品 */
        model('Order')->clear_cart(CART_SNATCH_GOODS);

        /* 加入购物车 */
        $cart = array(
            'user_id' => $_SESSION['user_id'],
            'session_id' => SESS_ID,
            'goods_id' => $snatch['goods_id'],
            'product_id' => $snatch['product_id'],
            'goods_sn' => addslashes($snatch['goods_sn']),
            'goods_name' => addslashes($snatch['goods_name']),
            'market_price' => $snatch['market_price'],
            'goods_price' => $result['buy_price'],
            'goods_number' => 1,
            'goods_attr' => $goods_attr,
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $snatch['is_real'],
            'extension_code' => addslashes($snatch['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_SNATCH_GOODS,
            'is_gift' => 0
        );
        $this->model->table('cart')->data($cart)->insert();

        /* 记录购物流程类型：夺宝奇兵 */
        $_SESSION['flow_type'] = CART_SNATCH_GOODS;
        $_SESSION['extension_code'] = 'snatch';
        $_SESSION['extension_id'] = $this->id;

        /* 进入收货人页面 */
        $this->redirect(url('flow/consignee'));
        exit;
    }

}