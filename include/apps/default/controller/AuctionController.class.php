<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ActivityControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：拍卖活动控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AuctionController extends CommonController {

    private $size = 10;
    private $page = 1;
    private $sort = 'last_update';
    private $order = 'ASC';

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * 拍卖活动列表
     */
    public function index() {
        $this->parameter();
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        /* 取得拍卖活动总数 */
        $count = model('Auction')->auction_count();
        /* 如果没有缓存，生成缓存 */
        if ($count > 0) {
            /* 取得当前页的拍卖活动 */
            $auction_list = model('Auction')->auction_list($this->size, $this->page, $this->sort, $this->order);
            $this->assign('auction_list', $auction_list);
            /* 设置分页链接 */
            $this->pageLimit(url('index', array('sort' => $this->sort, 'order' => $this->order)), $this->size);
            $this->assign('pager', $this->pageShow($count));
        }
        $this->display('auction_list.dwt');
    }
    /**
     * 怕卖活动异步加载列表
     */
    public function asynclist(){
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        
        $list = model('Auction')->auction_list($this->size, $this->page, $this->sort, $this->order);
        foreach ($list as $key => $auction) {
            $this->assign('auction', $auction);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }
    /**
     * 拍卖 详情
     */
    public function info() {
        /* 取得参数：拍卖活动id */
        $id = isset($_REQUEST['id']) ? intval(I('request.id')) : 0;
        if ($id <= 0) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 取得拍卖活动信息 */
        $auction = model('Auction')->auction_info($id);
        if (empty($auction)) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 缓存id：语言，拍卖活动id，状态，如果是进行中，还要最后出价的时间（如果有的话） */
        $cache_id = C('lang') . '-' . $id . '-' . $auction['status_no'];
        if ($auction['status_no'] == UNDER_WAY) {
            if (isset($auction['last_bid'])) {
                $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'];
            }
        } elseif ($auction['status_no'] == FINISHED && $auction['last_bid']['bid_user'] == $_SESSION['user_id'] && $auction['order_count'] == 0) {
            $auction['is_winner'] = 1;
            $cache_id = $cache_id . '-' . $auction['last_bid']['bid_time'] . '-1';
        }

        $cache_id = sprintf('%X', crc32($cache_id));

        /* 如果没有缓存，生成缓存 */
        if (!ECTouch::view()->is_cached('auction.dwt', $cache_id)) {
            //取货品信息
            if ($auction['product_id'] > 0) {
                $goods_specifications = model('goodsBase')->get_specifications_list($auction['goods_id']);

                $good_products = model('ProductsBase')->get_good_products($auction['goods_id'], 'AND product_id = ' . $auction['product_id']);

                $_good_products = explode('|', $good_products[0]['goods_attr']);
                $products_info = '';
                foreach ($_good_products as $value) {
                    $products_info .= ' ' . $goods_specifications[$value]['attr_name'] . '：' . $goods_specifications[$value]['attr_value'];
                }
                $this->assign('products_info', $products_info);
                unset($goods_specifications, $good_products, $_good_products, $products_info);
            }

            $auction['gmt_end_time'] = local_strtotime($auction['end_time']);
            $this->assign('auction', $auction);
            //print_r($auction );

            /* 取得拍卖商品信息 */
            $goods_id = $auction['goods_id'];
            $goods = model('Goods')->goods_info($goods_id);
            if (empty($goods)) {
                $this->redirect(url('Auction/index'));
                exit;
            }
            $goods['url'] = url('goods/index', array('id' => $goods_id));
            $this->assign('auction_goods', $goods);
            // 商品相册
            $this->assign('pictures', model('GoodsBase')->get_goods_gallery($goods_id));
            // print_r($goods );
        }
        //更新商品点击次数
        $sql = 'UPDATE ' . $this->model->pre . 'goods SET click_count = click_count + 1 ' .
                "WHERE goods_id = '" . $auction['goods_id'] . "'";
        $this->model->query($sql);
        $this->assign('now_time', gmtime());           // 当前系统时间
        $this->assign('title', L('auction_goods_info'));
        $this->display('aution.dwt');
    }

    /**
     * 出家记录
     */
    public function record() {
        /* 取得参数：拍卖活动id */
        $id = isset($_REQUEST['id']) ? intval(I('request.id')) : 0;
        if ($id <= 0) {
            $this->redirect(url('Auction/index'));
            exit;
        }
        /* 取得拍卖活动信息 */
        $auction = model('Auction')->auction_info($id);
        if (empty($auction)) {
            $this->redirect(url('Auction/index'));
            exit;
        }
        $goods_id = $auction['goods_id'];
        $goods = model('Goods')->goods_info($goods_id);
        $this->assign('goods', $goods);
        /* 出价记录 */
        $this->assign('auction_log', model('Auction')->auction_log($id));
        $this->assign('title', L('detail_intro'));
        $this->display('aution_record.dwt');
    }

    /**
     * 拍卖商品 --> 拍卖出价
     */
    public function bid() {
        /* 取得参数：拍卖活动id */
        $id = isset($_REQUEST['id']) ? intval(I('request.id')) : 0;
        if ($id <= 0) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 取得拍卖活动信息 */
        $auction = model('Auction')->auction_info($id);
        if (empty($auction)) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 活动是否正在进行 */
        if ($auction['status_no'] != UNDER_WAY) {
            show_message(L('au_not_under_way'), '', '', 'error');
        }

        /* 是否登录 */
        $user_id = $_SESSION['user_id'];
        if ($user_id <= 0) {
            show_message(L('au_bid_after_login'));
        }
        $user = model('Order')->user_info($user_id);

        /* 取得出价 */
        $bid_price = isset($_POST['price']) ? round(floatval($_POST['price']), 2) : 0;
        if ($bid_price <= 0) {
            show_message(L('au_bid_price_error'), '', '', 'error');
        }

        /* 如果有一口价且出价大于等于一口价，则按一口价算 */
        $is_ok = false; // 出价是否ok
        if ($auction['end_price'] > 0) {
            if ($bid_price >= $auction['end_price']) {
                $bid_price = $auction['end_price'];
                $is_ok = true;
            }
        }

        /* 出价是否有效：区分第一次和非第一次 */
        if (!$is_ok) {
            if ($auction['bid_user_count'] == 0) {
                /* 第一次要大于等于起拍价 */
                $min_price = $auction['start_price'];
            } else {
                /* 非第一次出价要大于等于最高价加上加价幅度，但不能超过一口价 */
                $min_price = $auction['last_bid']['bid_price'] + $auction['amplitude'];
                if ($auction['end_price'] > 0) {
                    $min_price = min($min_price, $auction['end_price']);
                }
            }

            if ($bid_price < $min_price) {
                show_message(sprintf(L('au_your_lowest_price'), price_format($min_price, false)), '', '', 'error');
            }
        }

        /* 检查联系两次拍卖人是否相同 */
        if ($auction['last_bid']['bid_user'] == $user_id && $bid_price != $auction['end_price']) {
            show_message(L('au_bid_repeat_user'), '', '', 'error');
        }

        /* 是否需要保证金 */
        if ($auction['deposit'] > 0) {
            /* 可用资金够吗 */
            if ($user['user_money'] < $auction['deposit']) {
                show_message(L('au_user_money_short'), '', '', 'error');
            }

            /* 如果不是第一个出价，解冻上一个用户的保证金 */
            if ($auction['bid_user_count'] > 0) {
                model('ClipsBase')->log_account_change($auction['last_bid']['bid_user'], $auction['deposit'], (-1) * $auction['deposit'], 0, 0, sprintf(L('au_unfreeze_deposit'), $auction['act_name']));
            }

            /* 冻结当前用户的保证金 */
            model('ClipsBase')->log_account_change($user_id, (-1) * $auction['deposit'], $auction['deposit'], 0, 0, sprintf(L('au_freeze_deposit'), $auction['act_name']));
        }

        /* 插入出价记录 */
        $auction_log = array(
            'act_id' => $id,
            'bid_user' => $user_id,
            'bid_price' => $bid_price,
            'bid_time' => gmtime()
        );
        $this->model->table('auction_log')->data($auction_log)->insert();

        /* 出价是否等于一口价 */
        if ($bid_price == $auction['end_price']) {
            /* 结束拍卖活动 */
            $this->model->table('goods_activity')->data(array('is_finished' => 1))->where('act_id = ' . $id)->update();
        }

        /* 跳转到活动详情页 */
        $this->redirect(url('Auction/info', array('id' => $id)));
        exit;
    }

    /**
     * 拍卖商品 --> 购买
     */
    public function buy() {
        /* 取得参数：拍卖活动id */
        $id = isset($_REQUEST['id']) ? intval(I('request.id')) : 0;
        if ($id <= 0) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 取得拍卖活动信息 */
        $auction = model('Auction')->auction_info($id);
        if (empty($auction)) {
            $this->redirect(url('Auction/index'));
            exit;
        }

        /* 查询：活动是否已结束 */
        if ($auction['status_no'] != FINISHED) {
            show_message(L('au_not_finished'), '', '', 'error');
        }

        /* 查询：有人出价吗 */
        if ($auction['bid_user_count'] <= 0) {
            show_message(L('au_no_bid'), '', '', 'error');
        }

        /* 查询：是否已经有订单 */
        if ($auction['order_count'] > 0) {
            show_message(L('au_order_placed'));
        }

        /* 查询：是否登录 */
        $user_id = $_SESSION['user_id'];
        if ($user_id <= 0) {
            show_message(L('au_buy_after_login'));
        }

        /* 查询：最后出价的是该用户吗 */
        if ($auction['last_bid']['bid_user'] != $user_id) {
            show_message(L('au_final_bid_not_you'), '', '', 'error');
        }

        /* 查询：取得商品信息 */
        $goods = model('Goods')->goods_info($auction['goods_id']);
        /* 查询：处理规格属性 */
        $goods_attr = '';
        $goods_attr_id = '';
        if ($auction['product_id'] > 0) {
            $product_info = model('ProductsBase')->get_good_products($auction['goods_id'], 'AND product_id = ' . $auction['product_id']);

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
            $goods_attr = join(chr(13) . chr(10), $attr_list);
        } else {
            $auction['product_id'] = 0;
        }

        /* 清空购物车中所有拍卖商品 */
        model('Order')->clear_cart(CART_AUCTION_GOODS);

        /* 加入购物车 */
        $cart = array(
            'user_id' => $user_id,
            'session_id' => SESS_ID,
            'goods_id' => $auction['goods_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $auction['last_bid']['bid_price'],
            'goods_number' => 1,
            'goods_attr' => $goods_attr,
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'extension_code' => addslashes($goods['extension_code']),
            'parent_id' => 0,
            'rec_type' => CART_AUCTION_GOODS,
            'is_gift' => 0
        );
        $this->model->table('cart')->data($cart)->insert();
        /* 记录购物流程类型：团购 */
        $_SESSION['flow_type'] = CART_AUCTION_GOODS;
        $_SESSION['extension_code'] = 'auction';
        $_SESSION['extension_id'] = $id;

        /* 进入收货人页面 */
        $this->redirect(url('flow/consignee'));
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
        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');

        $this->assign('show_asynclist', C('show_asynclist'));
        $this->sort = (isset($_REQUEST['sort']) && in_array(trim(strtolower($_REQUEST['sort'])), array(
                    'goods_id',
                    'sales_count',
                    'click_num',
                    'cur_price'
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

}
