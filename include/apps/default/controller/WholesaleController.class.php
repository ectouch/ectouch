<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WholesaleControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：批发控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class WholesaleController extends CommonController {

    private $size = 10;
    private $page = 1;

    public function __construct() {
        parent::__construct();

        if (ACTION_NAME == 'list') {
            $this->index();
        }
    }

    /* ------------------------------------------------------ */

    //-- 批发列表
    /* ------------------------------------------------------ */

    public function index() {
        $this->parameter();
        /* 查询条件：当前用户的会员等级（搜索关键字） */
        $where = " WHERE g.goods_id = w.goods_id
               AND w.enabled = 1
               AND CONCAT(',', w.rank_ids, ',') LIKE '" . '%,' . $_SESSION['user_rank'] . ',%' . "' ";
        $search_category = empty($_REQUEST['search_category']) ? 0 : intval(I('request.search_category'));
        $search_keywords = isset($_REQUEST['search_keywords']) ? trim(I('request.search_keywords')) : '';
        /* 搜索 */
        /* 搜索类别 */
        if ($search_category) {
            $where .= " AND g.cat_id = '$search_category' ";
            $param['search_category'] = $search_category;
            $this->assign('search_category', $search_category);
        }
        /* 搜索商品名称和关键字 */
        if ($search_keywords) {
            $where .= " AND (g.keywords LIKE '%$search_keywords%'
                    OR g.goods_name LIKE '%$search_keywords%') ";
            $param['search_keywords'] = $search_keywords;
            $this->assign('search_keywords', $search_keywords);
        }

        $count = model('Wholesale')->wholesale_count($search_category, $search_keywords, $where);
        if ($count > 0) {
            $this->pageLimit(url('index'), $this->size);
            $this->assign('pager', $this->pageShow($count));
            /* 取得当前页的批发商品 */
            $this->assign('wholesale_list', model('Wholesale')->wholesale_list($this->size, $this->page, $where));
        }
        /* 模板赋值 */
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->display('wholesale_list.dwt');
    }

    /* ------------------------------------------------------ */

    //--异步加载团购商品列表
    /* ------------------------------------------------------ */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        /* 查询条件：当前用户的会员等级（搜索关键字） */
        $where = " WHERE g.goods_id = w.goods_id
               AND w.enabled = 1
               AND CONCAT(',', w.rank_ids, ',') LIKE '" . '%,' . $_SESSION['user_rank'] . ',%' . "' ";
        $search_category = empty($_REQUEST['search_category']) ? 0 : intval(I('request.search_category'));
        $search_keywords = isset($_REQUEST['search_keywords']) ? trim(I('request.search_keywords')) : '';
        /* 搜索 */
        /* 搜索类别 */
        if ($search_category) {
            $where .= " AND g.cat_id = '$search_category' ";
            $param['search_category'] = $search_category;
            $this->assign('search_category', $search_category);
        }
        /* 搜索商品名称和关键字 */
        if ($search_keywords) {
            $where .= " AND (g.keywords LIKE '%$search_keywords%'
                    OR g.goods_name LIKE '%$search_keywords%') ";
            $param['search_keywords'] = $search_keywords;
            $this->assign('search_keywords', $search_keywords);
        }

        $wholesale_list = model('Wholesale')->wholesale_list($this->size, $this->page, $where);
        foreach ($wholesale_list as $key => $value) {
            $this->assign('wholesale', $value);
            $sayList [] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /* ------------------------------------------------------ */

    //-- 批发详情
    /* ------------------------------------------------------ */
    public function info() {
        $id = isset($_REQUEST['id']) ? intval(I('request.id')) : 0;
        if ($id <= 0) {
            $this->redirect(url('index'));
            exit;
        }
        $this->assign('id', $id);
        $this->assign('wholesale', model('Wholesale')->wholesale_info($id));
        $this->assign('pictures', model('GoodsBase')->get_goods_gallery($id));
        // 获得商品的规格和属性
        $properties = model('Goods')->get_goods_properties($id);
        // 商品属性
        $this->assign('properties', $properties ['pro']);
        // 商品规格
        $this->assign('specification', $properties ['spe']);
        /* 批发商品购物车 */
        $this->assign('cart_goods', isset($_SESSION['wholesale_goods']) ? $_SESSION['wholesale_goods'] : array());
        $comments = model('Comment')->get_comment_info($id,0);
        $this->assign('comments', $comments);
        $this->assign('title',L('wholesale_goods_info'));
        $this->display('wholesale.dwt');
    }

    /* ------------------------------------------------------ */

    //-- 团购商品 --> 购买
    /* ------------------------------------------------------ */
    public function add_to_cart() {
        /* 取得参数 */
        $act_id = intval($_POST['act_id']);
        $goods_number = $_POST['goods_number'][$act_id];
        $attr_id = isset($_POST['attr_id']) ? $_POST['attr_id'] : array();
        $id = $_POST['id'];
        if (isset($attr_id[$act_id])) {
            $goods_attr = $attr_id[$act_id];
        }
        /* 用户提交必须全部通过检查，才能视为完成操作 */
        /* 检查数量 */
        if (empty($goods_number) || (is_array($goods_number) && array_sum($goods_number) <= 0)) {
            show_message(L('ws_invalid_goods_number'));
        }
        /* 确定购买商品列表 */
        $goods_list = array();
        if (is_array($goods_number)) {
            foreach ($goods_number as $key => $value) {
                if (!$value) {
                    unset($goods_number[$key], $goods_attr[$key]);
                    continue;
                }

                $goods_list[] = array('number' => $goods_number[$key], 'goods_attr' => $goods_attr[$key]);
            }
        } else {
            $goods_list[0] = array('number' => $goods_number, 'goods_attr' => '');
        }

        /* 取批发相关数据 */
        $wholesale = model('GoodsBase')->wholesale_info($act_id);

        /* 检查session中该商品，该属性是否存在 */
        if (isset($_SESSION['wholesale_goods'])) {
            foreach ($_SESSION['wholesale_goods'] as $goods) {
                if ($goods['goods_id'] == $wholesale['goods_id']) {
                    if (empty($goods_attr)) {
                        show_message(L('ws_goods_attr_exists'));
                    } elseif (in_array($goods['goods_attr_id'], $goods_attr)) {
                        show_message(L('ws_goods_attr_exists'));
                    }
                }
            }
        }
        /* 获取购买商品的批发方案的价格阶梯 （一个方案多个属性组合、一个属性组合、一个属性、无属性） */
        $attr_matching = false;
        foreach ($wholesale['price_list'] as $attr_price) {
            // 没有属性
            if (empty($attr_price['attr'])) {
                $attr_matching = true;
                $goods_list[0]['qp_list'] = $attr_price['qp_list'];
                break;
            } // 有属性
            elseif (($key = model('wholesale')->is_attr_matching($goods_list, $attr_price['attr'])) !== false) {
                $attr_matching = true;
                $goods_list[$key]['qp_list'] = $attr_price['qp_list'];
            }
        }
        if (!$attr_matching) {
            show_message(L('ws_attr_not_matching'));
        }
        /* 检查数量是否达到最低要求 */
        foreach ($goods_list as $goods_key => $goods) {
            if ($goods['number'] < $goods['qp_list'][0]['quantity']) {
                show_message(L('ws_goods_number_not_enough'));
            } else {
                $goods_price = 0;
                foreach ($goods['qp_list'] as $qp) {
                    if ($goods['number'] >= $qp['quantity']) {
                        $goods_list[$goods_key]['goods_price'] = $qp['price'];
                    } else {
                        break;
                    }
                }
            }
        }
        /* 写入session */
        foreach ($goods_list as $goods_key => $goods) {
            // 属性名称
            $goods_attr_name = '';
            if (!empty($goods['goods_attr'])) {
                foreach ($goods['goods_attr'] as $key => $attr) {
                    $attr['attr_name'] = htmlspecialchars($attr['attr_name']);
                    $goods['goods_attr'][$key]['attr_name'] = $attr['attr_name'];
                    $attr['attr_val'] = htmlspecialchars($attr['attr_val']);
                    $goods['goods_attr'][$key]['attr_name'] = $attr['attr_name'];
                    $goods_attr_name .= $attr['attr_name'] . '：' . $attr['attr_val'] . '&nbsp;';
                }
            }
            // 总价
            $total = $goods['number'] * $goods['goods_price'];

            $_SESSION['wholesale_goods'][] = array(
                'goods_id' => $wholesale['goods_id'],
                'goods_name' => $wholesale['goods_name'],
                'goods_attr_id' => $goods['goods_attr'],
                'goods_attr' => $goods_attr_name,
                'goods_number' => $goods['number'],
                'goods_price' => $goods['goods_price'],
                'subtotal' => $total,
                'formated_goods_price' => price_format($goods['goods_price'], false),
                'formated_subtotal' => price_format($total, false),
                'goods_url' => url('goods/index', array('id' => $wholesale['goods_id'])),
            );
        }
        unset($goods_attr, $attr_id, $goods_list, $wholesale, $goods_attr_name);
        /* 刷新页面 */
        $this->redirect(url('info', array('id' => $id)));
        exit;
    }

    /**
     * 提交订单
     */
    public function submit_order() {
        /* 检查购物车中是否有商品 */
        if (count($_SESSION['wholesale_goods']) == 0) {
            show_message(L('no_goods_in_cart'));
        }
        /* 检查备注信息 */
        if (empty($_POST['remark'])) {
            show_message(L('ws_remark'));
        }

        /* 计算商品总额 */
        $goods_amount = 0;
        foreach ($_SESSION['wholesale_goods'] as $goods) {
            $goods_amount += $goods['subtotal'];
        }

        $order = array(
            'postscript' => htmlspecialchars($_POST['remark']),
            'user_id' => $_SESSION['user_id'],
            'add_time' => gmtime(),
            'order_status' => OS_UNCONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'goods_amount' => $goods_amount,
            'order_amount' => $goods_amount,
        );

        /* 插入订单表 */
        $error_no = 0;
        do {
            $order['order_sn'] = get_order_sn(); //获取新订单号
            $this->model->table('order_info')->data($order)->insert();
            $error_no = $this->model->errno();

            if ($error_no > 0 && $error_no != 1062) {
                die($this->model->errorMsg());
            }
        } while ($error_no == 1062); //如果是订单号重复则重新提交数据
        $new_order_id = $this->model->insert_id();
        $order['order_id'] = $new_order_id;

        /* 插入订单商品 */
        foreach ($_SESSION['wholesale_goods'] as $goods) {
            //如果存在货品
            $product_id = 0;
            if (!empty($goods['goods_attr_id'])) {
                $goods_attr_id = array();
                foreach ($goods['goods_attr_id'] as $value) {
                    $goods_attr_id[$value['attr_id']] = $value['attr_val_id'];
                }

                ksort($goods_attr_id);
                $goods_attr = implode('|', $goods_attr_id);

                $res = $this->model->table('products')->field('product_id')->where("goods_attr = '$goods_attr' AND goods_id = '" . $goods['goods_id'] . "'")->find();
                $product_id = $res['product_id'];
            }

            $sql = "INSERT INTO " . $this->model->pre . "order_goods( " .
                    "order_id, goods_id, goods_name, goods_sn, product_id, goods_number, market_price, " .
                    "goods_price, goods_attr, is_real, extension_code, parent_id, is_gift) " .
                    " SELECT '$new_order_id', goods_id, goods_name, goods_sn, '$product_id','$goods[goods_number]', market_price, " .
                    "'$goods[goods_price]', '$goods[goods_attr]', is_real, extension_code, 0, 0 " .
                    " FROM " . $this->model->pre .
                    "goods WHERE goods_id = '$goods[goods_id]'";
            $this->model->query($sql);
        }

        /* 给商家发邮件 */
        if (C('service_email') != '') {
            $tpl = get_mail_template('remind_of_new_order');
            $this->assign('order', $order);
            $this->assign('shop_name', C('shop_name'));
            $this->assign('send_date', date(C('time_format')));
            $content = ECTouch::view()->fetch('str:' . $tpl['template_content']);
            send_mail(C('shop_name'), C('service_email'), $tpl['template_subject'], $content, $tpl['is_html']);
        }

        /* 如果需要，发短信 */
        if (C('sms_order_placed') == '1' && C('sms_shop_mobile') != '') {
            $sms = new EcsSms();
            $msg = L('order_placed_sms');
            $sms->send(C('sms_shop_mobile'), sprintf($msg, $order ['consignee'], $order ['mobile']), '', 13, 1);
        }

        /* 清空购物车 */
        unset($_SESSION['wholesale_goods']);

        /* 提示 */
        show_message(sprintf(L('ws_order_submitted'), $order['order_sn']), L('ws_return_home'), url('index'));
    }

    /**
     * /-- 从购物车删除
     */
    public function drop_goods() {
        $key = intval(I('request.key'));
        if (isset($_SESSION['wholesale_goods'][$key])) {
            unset($_SESSION['wholesale_goods'][$key]);
        }
        /* 刷新页面 */
        $this->redirect(url('index'));
        exit;
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        /* 如果没登录，提示登录 */
        if ($_SESSION['user_rank'] <= 0) {
            show_message(L('ws_user_rank'), L('ws_return_home'), 'index.php');
        }
        $this->assign('show_asynclist', C('show_asynclist'));
        $page_size = C('page_size');
        $page = I('request.page');
        $this->page = $page ? $page : 1;
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;

        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : 'text';
        $display = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array('list', 'text'))) ? trim($_REQUEST['display']) : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
        $display = in_array($display, array('list', 'text')) ? $display : 'text';
        /* 排序、显示方式以及类型 */
        $this->assign('display', $display);
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
    }

}

