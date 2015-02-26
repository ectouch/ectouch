<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：OrderModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 订单模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class OrderModel extends BaseModel {

    /**
     * 取得包装列表
     * @return  array   包装列表
     */
    function pack_list() {
        $sql = 'SELECT * FROM ' . $this->pre . "pack";
        $res = $this->query($sql);
        $list = array();
        foreach ($res as $key => $row) {
            $row['format_pack_fee'] = price_format($row['pack_fee'], false);
            $row['format_free_money'] = price_format($row['free_money'], false);
            $list[] = $row;
        }
        return $list;
    }

    /**
     * 取得包装信息
     * @param   int     $pack_id    包装id
     * @return  array   包装信息
     */
    function pack_info($pack_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "pack WHERE pack_id = '$pack_id'";

        return $this->row($sql);
    }

    /**
     * 取得可用的支付方式列表
     * @param   bool    $support_cod        配送方式是否支持货到付款
     * @param   int     $cod_fee            货到付款手续费（当配送方式支持货到付款时才传此参数）
     * @param   int     $is_online          是否支持在线支付
     * @return  array   配送方式数组
     */
    function available_payment_list($support_cod, $cod_fee = 0, $is_online = false) {
        $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod' .
                ' FROM ' . $this->pre .
                'touch_payment WHERE enabled = 1 ';
        if (!$support_cod) {
            $sql .= 'AND is_cod = 0 '; // 如果不支持货到付款
        }
        if ($is_online) {
            $sql .= "AND is_online = '1' ";
        }
        $sql .= 'ORDER BY pay_order'; // 排序
        $res = $this->query($sql);

        $pay_list = array();
        foreach ($res as $key => $row) {
            if ($row['is_cod'] == '1') {
                $row['pay_fee'] = $cod_fee;
            }
            $row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
                    price_format($row['pay_fee'], false);
            $modules[] = $row;
        }
        if (isset($modules)) {
            return $modules;
        }
    }

    /**
     * 取得贺卡列表
     * @return  array   贺卡列表
     */
    function card_list() {
        $sql = "SELECT * FROM " . $this->pre . 'card';
        $res = $this->query($sql);

        $list = array();
        foreach ($res as $row) {
            $row['format_card_fee'] = price_format($row['card_fee'], false);
            $row['format_free_money'] = price_format($row['free_money'], false);
            $list[] = $row;
        }
        return $list;
    }

    /**
     * 取得贺卡信息
     * @param   int     $card_id    贺卡id
     * @return  array   贺卡信息
     */
    function card_info($card_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "card WHERE card_id = '$card_id'";

        return $this->row($sql);
    }

    /**
     * 取得已安装的支付方式列表
     * @return  array   已安装的配送方式列表
     */
    function payment_list() {
        $sql = 'SELECT pay_id, pay_name ' .
                'FROM ' . $this->pre .
                'payment WHERE enabled = 1';

        return $this->query($sql);
    }

    /**
     * 取得支付方式信息
     * @param   int     $pay_id     支付方式id
     * @return  array   支付方式信息
     */
    function payment_info($pay_id) {
        $sql = 'SELECT * FROM ' . $this->pre .
                "touch_payment WHERE pay_id = '$pay_id' AND enabled = 1";

        return $this->row($sql);
    }

    /**
     * 取得订单信息
     * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
     * @param   string  $order_sn   订单号
     * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
     */
    function order_info($order_id, $order_sn = '') {
        /* 计算订单各种费用之和的语句 */
        $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
        $order_id = intval($order_id);
        if ($order_id > 0) {
            $sql = "SELECT *, " . $total_fee . " FROM " . $this->pre .
                    "order_info WHERE order_id = '$order_id'";
        } else {
            $sql = "SELECT *, " . $total_fee . "  FROM " . $this->pre .
                    "order_info WHERE order_sn = '$order_sn'";
        }
        $order = $this->row($sql);

        /* 格式化金额字段 */
        if ($order) {
            $order['formated_goods_amount'] = price_format($order['goods_amount'], false);
            $order['formated_discount'] = price_format($order['discount'], false);
            $order['formated_tax'] = price_format($order['tax'], false);
            $order['formated_shipping_fee'] = price_format($order['shipping_fee'], false);
            $order['formated_insure_fee'] = price_format($order['insure_fee'], false);
            $order['formated_pay_fee'] = price_format($order['pay_fee'], false);
            $order['formated_pack_fee'] = price_format($order['pack_fee'], false);
            $order['formated_card_fee'] = price_format($order['card_fee'], false);
            $order['formated_total_fee'] = price_format($order['total_fee'], false);
            $order['formated_money_paid'] = price_format($order['money_paid'], false);
            $order['formated_bonus'] = price_format($order['bonus'], false);
            $order['formated_integral_money'] = price_format($order['integral_money'], false);
            $order['formated_surplus'] = price_format($order['surplus'], false);
            $order['formated_order_amount'] = price_format(abs($order['order_amount']), false);
            $order['formated_add_time'] = local_date(C('time_format'), $order['add_time']);
        }

        return $order;
    }

    /**
     * 取得订单商品
     * @param   int     $order_id   订单id
     * @return  array   订单商品数组
     */
    function order_goods($order_id) {

        $sql = "SELECT og.rec_id, og.goods_id, og.goods_name, og.goods_sn, og.market_price, og.goods_number, " .
                "og.goods_price, og.goods_attr, og.is_real, og.parent_id, og.is_gift, " .
                "og.goods_price * og.goods_number AS subtotal, og.extension_code, g.goods_thumb " .
                "FROM " . $this->pre . "order_goods as og left join " . $this->pre . "goods g on og.goods_id = g.goods_id" .
                " WHERE og.order_id = '$order_id'";

        $res = $this->query($sql);
        foreach ($res as $row) {
            if ($row['extension_code'] == 'package_buy') {
                $row['package_goods_list'] = model('PackageBase')->get_package_goods($row['goods_id']);
            }
            $goods_list[] = $row;
        }
        return $goods_list;
    }

    /**
     * 取得订单应该发放的红包
     * @param   int     $order_id   订单id
     * @return  array
     */
    function order_bonus($order_id) {
        /* 查询按商品发的红包 */
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $sql = "SELECT b.type_id, b.type_money, SUM(o.goods_number) AS number " .
                "FROM " . $this->pre . "order_goods AS o, " .
                $this->pre . "goods AS g, " .
                $this->pre . "bonus_type AS b " .
                " WHERE o.order_id = '$order_id' " .
                " AND o.is_gift = 0 " .
                " AND o.goods_id = g.goods_id " .
                " AND g.bonus_type_id = b.type_id " .
                " AND b.send_type = '" . SEND_BY_GOODS . "' " .
                " AND b.send_start_date <= '$today' " .
                " AND b.send_end_date >= '$today' " .
                " GROUP BY b.type_id ";
        $list = $this->query($sql);

        /* 查询订单中非赠品总金额 */
        $amount = $this->order_amount($order_id, false);

        /* 查询订单日期 */
        $sql = "SELECT add_time " .
                " FROM " . $this->pre .
                "order_info WHERE order_id = '$order_id' LIMIT 1";
        $res = $this->row($sql);
        $order_time = $res['add_time'];
        /* 查询按订单发的红包 */
        $sql = "SELECT type_id, type_money, IFNULL(FLOOR('$amount' / min_amount), 1) AS number " .
                "FROM " . $this->pre .
                "bonus_type WHERE send_type = '" . SEND_BY_ORDER . "' " .
                "AND send_start_date <= '$order_time' " .
                "AND send_end_date >= '$order_time' ";
        $list = array_merge($list, $this->query($sql));

        return $list;
    }

    /**
     * 取得订单总金额
     * @param   int     $order_id   订单id
     * @param   bool    $include_gift   是否包括赠品
     * @return  float   订单总金额
     */
    function order_amount($order_id, $include_gift = true) {
        $sql = "SELECT SUM(goods_price * goods_number) " .
                "as amount FROM " . $this->pre .
                "order_goods WHERE order_id = '$order_id'";
        if (!$include_gift) {
            $sql .= " AND is_gift = 0";
        }
        $res = $this->row($sql);
        return floatval($res['amount']);
    }

    /**
     * 取得某订单商品总重量和总金额（对应 cart_weight_price）
     * @param   int     $order_id   订单id
     * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
     */
    function order_weight_price($order_id) {
        $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
                "SUM(o.goods_price * o.goods_number) AS amount ," .
                "SUM(o.goods_number) AS number " .
                "FROM " . $this->pre . "order_goods AS o, " .
                $this->pre . "goods AS g " .
                "WHERE o.order_id = '$order_id' " .
                "AND o.goods_id = g.goods_id";

        $row = $this->row($sql);
        $row['weight'] = floatval($row['weight']);
        $row['amount'] = floatval($row['amount']);
        $row['number'] = intval($row['number']);

        /* 格式化重量 */
        $row['formated_weight'] = formated_weight($row['weight']);

        return $row;
    }

    /**
     * 取得购物车商品
     * @param   int     $type   类型：默认普通商品
     * @return  array   购物车商品数组
     */
    function cart_goods($type = CART_GENERAL_GOODS) {
        $sql = "SELECT rec_id, user_id, goods_id, goods_name, goods_sn, goods_number, " .
                "market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, " .
                "goods_price * goods_number AS subtotal " .
                "FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' " .
                "AND rec_type = '$type'";

        $arr = $this->query($sql);

        /* 格式化价格及礼包商品 */
        foreach ($arr as $key => $value) {
            $arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
            $arr[$key]['formated_goods_price'] = price_format($value['goods_price'], false);
            $arr[$key]['formated_subtotal'] = price_format($value['subtotal'], false);

            if ($value['extension_code'] == 'package_buy') {
                $arr[$key]['package_goods_list'] = model('PackageBase')->get_package_goods($value['goods_id']);
            }
        }

        return $arr;
    }

    /**
     * 取得购物车总金额
     * @params  boolean $include_gift   是否包括赠品
     * @param   int     $type           类型：默认普通商品
     * @return  float   购物车总金额
     */
    function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS) {
        $sql = "SELECT SUM(goods_price * goods_number) " .
                " as amount FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' " .
                "AND rec_type = '$type' ";

        if (!$include_gift) {
            $sql .= ' AND is_gift = 0 AND goods_id > 0';
        }
        $res = $this->row($sql);
        return floatval($res['amount']);
    }

    /**
     * 检查某商品是否已经存在于购物车
     *
     * @access  public
     * @param   integer     $id
     * @param   array       $spec
     * @param   int         $type   类型：默认普通商品
     * @return  boolean
     */
    function cart_goods_exists($id, $spec, $type = CART_GENERAL_GOODS) {
        /* 检查该商品是否已经存在在购物车中 */
        $sql = "SELECT COUNT(*) as count FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' AND goods_id = '$id' " .
                "AND parent_id = 0 AND goods_attr = '" . $this->get_goods_attr_info($spec) . "' " .
                "AND rec_type = '$type'";
        $res = $this->row($sql);
        return ($res['count'] > 0);
    }

    /**
     * 获得购物车中商品的总重量、总价格、总数量
     *
     * @access  public
     * @param   int     $type   类型：默认普通商品
     * @return  array
     */
    function cart_weight_price($type = CART_GENERAL_GOODS) {
        $package_row['weight'] = 0;
        $package_row['amount'] = 0;
        $package_row['number'] = 0;

        $packages_row['free_shipping'] = 1;

        /* 计算超值礼包内商品的相关配送参数 */
        $sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $this->pre . "cart WHERE extension_code = 'package_buy' AND session_id = '" . SESS_ID . "'";
        $row = $this->query($sql);

        if ($row) {
            $packages_row['free_shipping'] = 0;
            $free_shipping_count = 0;

            foreach ($row as $val) {
                // 如果商品全为免运费商品，设置一个标识变量
                $sql = 'SELECT count(*) as count FROM ' .
                        $this->pre . 'package_goods AS pg, ' .
                        $this->pre . 'goods AS g ' .
                        "WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '" . $val['goods_id'] . "'";
                $res = $this->row($sql);
                $shipping_count = $res['count'];

                if ($shipping_count > 0) {
                    // 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
                    $sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
                            'SUM(pg.goods_number) AS number FROM ' .
                            $this->pre . 'package_goods AS pg, ' .
                            $this->pre . 'goods AS g ' .
                            "WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '" . $val['goods_id'] . "'";

                    $goods_row = $this->row($sql);
                    $package_row['weight'] += floatval($goods_row['weight']) * $val['goods_number'];
                    $package_row['amount'] += floatval($val['goods_price']) * $val['goods_number'];
                    $package_row['number'] += intval($goods_row['number']) * $val['goods_number'];
                } else {
                    $free_shipping_count++;
                }
            }

            $packages_row['free_shipping'] = $free_shipping_count == count($row) ? 1 : 0;
        }

        /* 获得购物车中非超值礼包商品的总重量 */
        $sql = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
                'SUM(c.goods_price * c.goods_number) AS amount, ' .
                'SUM(c.goods_number) AS number ' .
                'FROM ' . $this->pre . 'cart AS c ' .
                'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = c.goods_id ' .
                "WHERE c.session_id = '" . SESS_ID . "' " .
                "AND rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
        $row = $this->row($sql);

        $packages_row['weight'] = floatval($row['weight']) + $package_row['weight'];
        $packages_row['amount'] = floatval($row['amount']) + $package_row['amount'];
        $packages_row['number'] = intval($row['number']) + $package_row['number'];
        /* 格式化重量 */
        $packages_row['formated_weight'] = formated_weight($packages_row['weight']);

        return $packages_row;
    }

    /**
     * 添加商品到购物车
     *
     * @access  public
     * @param   integer $goods_id   商品编号
     * @param   integer $num        商品数量
     * @param   array   $spec       规格值对应的id数组
     * @param   integer $parent     基本件
     * @return  boolean
     */
    function addto_cart($goods_id, $num = 1, $spec = array(), $parent = 0) {
        ECTouch::err()->clean();
        $_parent_id = $parent;

        /* 取得商品信息 */
        $sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, " .
                "g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, " .
                "g.promote_end_date, g.goods_weight, g.integral, g.extension_code, " .
                "g.goods_number, g.is_alone_sale, g.is_shipping," .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price " .
                " FROM " . $this->pre . "goods AS g " .
                " LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                " WHERE g.goods_id = '$goods_id'" .
                " AND g.is_delete = 0";
        $goods = $this->row($sql);

        if (empty($goods)) {
            ECTouch::err()->add(L('goods_not_exists'), ERR_NOT_EXISTS);

            return false;
        }

        /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
        if ($parent > 0) {
            $sql = "SELECT COUNT(*) as count FROM " . $this->pre .
                    "cart WHERE goods_id='$parent' AND session_id='" . SESS_ID . "' AND extension_code <> 'package_buy'";
            $res = $this->row($sql);
            if ($res['count'] == 0) {
                ECTouch::err()->add(L('no_basic_goods'), ERR_NO_BASIC_GOODS);

                return false;
            }
        }

        /* 是否正在销售 */
        if ($goods['is_on_sale'] == 0) {
            ECTouch::err()->add(L('not_on_sale'), ERR_NOT_ON_SALE);

            return false;
        }

        /* 不是配件时检查是否允许单独销售 */
        if (empty($parent) && $goods['is_alone_sale'] == 0) {
            ECTouch::err()->add(L('cannt_alone_sale'), ERR_CANNT_ALONE_SALE);

            return false;
        }

        /* 如果商品有规格则取规格商品信息 配件除外 */
        $sql = "SELECT * FROM " . $this->pre . "products WHERE goods_id = '$goods_id' LIMIT 0, 1";
        $prod = $this->row($sql);

        if (model('GoodsBase')->is_spec($spec) && !empty($prod)) {
            $product_info = model('ProductsBase')->get_products_info($goods_id, $spec);
        }
        if (empty($product_info)) {
            $product_info = array('product_number' => '', 'product_id' => 0);
        }

        /* 检查：库存 */
        if (C('use_storage') == 1) {
            //检查：商品购买数量是否大于总库存
            if ($num > $goods['goods_number']) {
                ECTouch::err()->add(sprintf(L('shortage'), $goods['goods_number']), ERR_OUT_OF_STOCK);

                return false;
            }

            //商品存在规格 是货品 检查该货品库存
            if (model('GoodsBase')->is_spec($spec) && !empty($prod)) {
                if (!empty($spec)) {
                    /* 取规格的货品库存 */
                    if ($num > $product_info['product_number']) {
                        ECTouch::err()->add(sprintf(L('shortage'), $product_info['product_number']), ERR_OUT_OF_STOCK);

                        return false;
                    }
                }
            }
        }

        /* 计算商品的促销价格 */
        $spec_price = model('Goods')->spec_price($spec);
        $goods_price = model('GoodsBase')->get_final_price($goods_id, $num, true, $spec);
        $goods['market_price'] += $spec_price;
        $goods_attr = $this->get_goods_attr_info($spec);
        $goods_attr_id = join(',', $spec);

        /* 初始化要插入购物车的基本件数据 */
        $parent = array(
            'user_id' => $_SESSION['user_id'],
            'session_id' => SESS_ID,
            'goods_id' => $goods_id,
            'goods_sn' => addslashes($goods['goods_sn']),
            'product_id' => $product_info['product_id'],
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'extension_code' => $goods['extension_code'],
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'rec_type' => CART_GENERAL_GOODS
        );

        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $basic_list = array();
        $sql = "SELECT parent_id, goods_price " .
                "FROM " . $this->pre .
                "group_goods WHERE goods_id = '$goods_id'" .
                " AND goods_price < '$goods_price'" .
                " AND parent_id = '$_parent_id'" .
                " ORDER BY goods_price";
        $res = $this->query($sql);
        foreach ($res as $row) {
            $basic_list[$row['parent_id']] = $row['goods_price'];
        }
        /* 取得购物车中该商品每个基本件的数量 */
        $basic_count_list = array();
        if ($basic_list) {
            $sql = "SELECT goods_id, SUM(goods_number) AS count " .
                    "FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "'" .
                    " AND parent_id = 0" .
                    " AND extension_code <> 'package_buy' " .
                    " AND goods_id " . db_create_in(array_keys($basic_list)) .
                    " GROUP BY goods_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $basic_count_list[$row['goods_id']] = $row['count'];
            }
        }

        /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
        /* 一个基本件对应一个该商品配件 */
        if ($basic_count_list) {
            $sql = "SELECT parent_id, SUM(goods_number) AS count " .
                    "FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "'" .
                    " AND goods_id = '$goods_id'" .
                    " AND extension_code <> 'package_buy' " .
                    " AND parent_id " . db_create_in(array_keys($basic_count_list)) .
                    " GROUP BY parent_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $basic_count_list[$row['parent_id']] -= $row['count'];
            }
        }

        /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
        foreach ($basic_list as $parent_id => $fitting_price) {
            /* 如果已全部插入，退出 */
            if ($num <= 0) {
                break;
            }

            /* 如果该基本件不再购物车中，执行下一个 */
            if (!isset($basic_count_list[$parent_id])) {
                continue;
            }

            /* 如果该基本件的配件数量已满，执行下一个基本件 */
            if ($basic_count_list[$parent_id] <= 0) {
                continue;
            }

            /* 作为该基本件的配件插入 */
            $parent['goods_price'] = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
            $parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
            $parent['parent_id'] = $parent_id;

            /* 添加 */
            $this->table = 'cart';
            $this->insert($parent);
            /* 改变数量 */
            $num -= $parent['goods_number'];
        }

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */
            $sql = "SELECT goods_number FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "' AND goods_id = '$goods_id' " .
                    " AND parent_id = 0 AND goods_attr = '" . $this->get_goods_attr_info($spec) . "' " .
                    " AND extension_code <> 'package_buy' " .
                    " AND rec_type = 'CART_GENERAL_GOODS'";

            $row = $this->row($sql);

            if ($row) { //如果购物车已经有此物品，则更新
                $num += $row['goods_number'];
                if (model('GoodsBase')->is_spec($spec) && !empty($prod)) {
                    $goods_storage = $product_info['product_number'];
                } else {
                    $goods_storage = $goods['goods_number'];
                }
                if (C('use_storage') == 0 || $num <= $goods_storage) {
                    $goods_price = model('GoodsBase')->get_final_price($goods_id, $num, true, $spec);
                    $sql = "UPDATE " . $this->pre . "cart SET goods_number = '$num'" .
                            " , goods_price = '$goods_price'" .
                            " WHERE session_id = '" . SESS_ID . "' AND goods_id = '$goods_id' " .
                            " AND parent_id = 0 AND goods_attr = '" . $this->get_goods_attr_info($spec) . "' " .
                            " AND extension_code <> 'package_buy' " .
                            "AND rec_type = 'CART_GENERAL_GOODS'";
                    $this->query($sql);
                } else {
                    ECTouch::err()->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);

                    return false;
                }
            } else { //购物车没有此物品，则插入
                $goods_price = model('GoodsBase')->get_final_price($goods_id, $num, true, $spec);
                $parent['goods_price'] = max($goods_price, 0);
                $parent['goods_number'] = $num;
                $parent['parent_id'] = 0;
                $this->table = 'cart';
                $this->insert($parent);
            }
        }

        /* 把赠品删除 */
        $sql = "DELETE FROM " . $this->pre . "cart WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
        $this->query($sql);

        return true;
    }

    /**
     * 清空购物车
     * @param   int     $type   类型：默认普通商品
     */
    function clear_cart($type = CART_GENERAL_GOODS) {
        $sql = "DELETE FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' AND rec_type = '$type'";
        $this->query($sql);
    }

    /**
     * 获得指定的商品属性
     *
     * @access      public
     * @param       array       $arr        规格、属性ID数组
     * @param       type        $type       设置返回结果类型：pice，显示价格，默认；no，不显示价格
     *
     * @return      string
     */
    function get_goods_attr_info($arr, $type = 'pice') {
        $attr = '';

        if (!empty($arr)) {
            $fmt = "%s:%s[%s] \n";

            $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price " .
                    "FROM " . $this->pre . "goods_attr AS ga, " .
                    $this->pre . "attribute AS a " .
                    "WHERE " . db_create_in($arr, 'ga.goods_attr_id') . " AND a.attr_id = ga.attr_id";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $attr_price = round(floatval($row['attr_price']), 2);
                $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
            }
            $attr = str_replace('[0]', '', $attr);
        }

        return $attr;
    }

    /**
     * 取得用户信息
     * @param   int     $user_id    用户id
     * @return  array   用户信息
     */
    function user_info($user_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "users WHERE user_id = '$user_id'";
        $user = $this->row($sql);

        unset($user['question']);
        unset($user['answer']);

        /* 格式化帐户余额 */
        if ($user) {
            $user['formated_user_money'] = price_format($user['user_money'], false);
            $user['formated_frozen_money'] = price_format($user['frozen_money'], false);
        }
        return $user;
    }

    /**
     * 修改用户
     * @param   int     $user_id   订单id
     * @param   array   $user      key => value
     * @return  bool
     */
    function update_user($user_id, $user) {
        $this->talbe = 'users';
        return $this->update("user_id = '$user_id'", $user);
    }

    /**
     * 取得用户地址列表
     * @param   int     $user_id    用户id
     * @return  array
     */
    function address_list($user_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "user_address WHERE user_id = '$user_id'";
        return $this->query($sql);
    }

    /**
     * 取得用户地址信息
     * @param   int     $address_id     地址id
     * @return  array
     */
    function address_info($address_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "user_address WHERE address_id = '$address_id'";
        return $this->row($sql);
    }

    /**
     * 取得用户当前可用红包
     * @param   int     $user_id        用户id
     * @param   float   $goods_amount   订单商品金额
     * @return  array   红包数组
     */
    function user_bonus($user_id, $goods_amount = 0) {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        $sql = "SELECT t.type_id, t.type_name, t.type_money, b.bonus_id " .
                "FROM " . $this->pre . "bonus_type AS t," .
                $this->pre . "user_bonus AS b " .
                "WHERE t.type_id = b.bonus_type_id " .
                "AND t.use_start_date <= '$today' " .
                "AND t.use_end_date >= '$today' " .
                "AND t.min_goods_amount <= '$goods_amount' " .
                "AND b.user_id<>0 " .
                "AND b.user_id = '$user_id' " .
                "AND b.order_id = 0";
        return $this->query($sql);
    }

    /**
     * 取得红包信息
     * @param   int     $bonus_id   红包id
     * @param   string  $bonus_sn   红包序列号
     * @param   array   红包信息
     */
    function bonus_info($bonus_id, $bonus_sn = '') {
        $sql = "SELECT t.*, b.* " .
                "FROM " . $this->pre . "bonus_type AS t," .
                $this->pre . "user_bonus AS b " .
                "WHERE t.type_id = b.bonus_type_id ";
        if ($bonus_id > 0) {
            $sql .= "AND b.bonus_id = '$bonus_id'";
        } else {
            $sql .= "AND b.bonus_sn = '$bonus_sn'";
        }

        return $this->row($sql);
    }

    /**
     * 检查红包是否已使用
     * @param   int $bonus_id   红包id
     * @return  bool
     */
    function bonus_used($bonus_id) {
        $sql = "SELECT order_id FROM " . $this->pre .
                "user_bonus WHERE bonus_id = '$bonus_id'";
        $res = $this->row($sql);
        return $res['order_id'] > 0;
    }

    /**
     * 设置红包为已使用
     * @param   int     $bonus_id   红包id
     * @param   int     $order_id   订单id
     * @return  bool
     */
    function use_bonus($bonus_id, $order_id) {
        $sql = "UPDATE " . $this->pre .
                "user_bonus SET order_id = '$order_id', used_time = '" . gmtime() . "' " .
                "WHERE bonus_id = '$bonus_id' LIMIT 1";

        return $this->query($sql);
    }

    /**
     * 设置红包为未使用
     * @param   int     $bonus_id   红包id
     * @param   int     $order_id   订单id
     * @return  bool
     */
    function unuse_bonus($bonus_id) {
        $sql = "UPDATE " . $this->pre .
                "user_bonus SET order_id = 0, used_time = 0 " .
                "WHERE bonus_id = '$bonus_id' LIMIT 1";

        return $this->query($sql);
    }

    /**
     * 订单退款
     * @param   array   $order          订单
     * @param   int     $refund_type    退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
     * @param   string  $refund_note    退款说明
     * @param   float   $refund_amount  退款金额（如果为0，取订单已付款金额）
     * @return  bool
     */
    function order_refund($order, $refund_type, $refund_note, $refund_amount = 0) {
        /* 检查参数 */
        $user_id = $order['user_id'];
        if ($user_id == 0 && $refund_type == 1) {
            die('anonymous, cannot return to account balance');
        }

        $amount = $refund_amount > 0 ? $refund_amount : $order['money_paid'];
        if ($amount <= 0) {
            return true;
        }

        if (!in_array($refund_type, array(1, 2, 3))) {
            die('invalid params');
        }

        /* 备注信息 */
        if ($refund_note) {
            $change_desc = $refund_note;
        } else {
            include_once(ROOT_PATH . 'languages/' . C('lang') . '/admin/order.php');
            $change_desc = sprintf(L('order_refund'), $order['order_sn']);
        }

        /* 处理退款 */
        if (1 == $refund_type) {
            model('ClipsBase')->log_account_change($user_id, $amount, 0, 0, 0, $change_desc);

            return true;
        } elseif (2 == $refund_type) {
            /* 如果非匿名，退回余额 */
            if ($user_id > 0) {
                model('ClipsBase')->log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
            }

            /* user_account 表增加提款申请记录 */
            $account = array(
                'user_id' => $user_id,
                'amount' => (-1) * $amount,
                'add_time' => gmtime(),
                'user_note' => $refund_note,
                'process_type' => SURPLUS_RETURN,
                'admin_user' => $_SESSION['admin_name'],
                'admin_note' => sprintf(L('order_refund'), $order['order_sn']),
                'is_paid' => 0
            );
            $this->table = 'user_account';
            $this->insert($account);

            return true;
        } else {
            return true;
        }
    }

    /**
     * 获得购物车中的商品
     *
     * @access  public
     * @return  array
     */
    function get_cart_goods() {
        /* 初始化 */
        $goods_list = array();
        $total = array(
            'goods_price' => 0, // 本店售价合计（有格式）
            'market_price' => 0, // 市场售价合计（有格式）
            'saving' => 0, // 节省金额（有格式）
            'save_rate' => 0, // 节省百分比
            'goods_amount' => 0, // 本店售价合计（无格式）
            'total_number' => 0,
        );

        /* 循环、统计 */
        $sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
                " FROM " . $this->pre . "cart " .
                " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" .
                " ORDER BY pid, parent_id";
        $res = $this->query($sql);

        /* 用于统计购物车中实体商品和虚拟商品的个数 */
        $virtual_goods_count = 0;
        $real_goods_count = 0;
        foreach ($res as $row) {
            $total['total_number']+=$row['goods_number'];
            $total['goods_price'] += $row['goods_price'] * $row['goods_number'];
            $total['market_price'] += $row['market_price'] * $row['goods_number'];

            $row['subtotal'] = price_format($row['goods_price'] * $row['goods_number'], false);
            $row['goods_price'] = price_format($row['goods_price'], false);
            $row['market_price'] = price_format($row['market_price'], false);

            /* 统计实体商品和虚拟商品的个数 */
            if ($row['is_real']) {
                $real_goods_count++;
            } else {
                $virtual_goods_count++;
            }

            /* 查询规格 */
            if (trim($row['goods_attr']) != '') {
                $row['goods_attr'] = addslashes($row['goods_attr']);
                $sql = "SELECT attr_value FROM " . $this->pre . "goods_attr WHERE goods_attr_id " .
                        db_create_in($row['goods_attr_id']);
                $attr_list = $this->query($sql);
                foreach ($attr_list as $attr) {
                    $row['goods_name'] .= ' [' . $attr['attr_value'] . '] ';
                }
            }
            /* 增加是否在购物车里显示商品图 */
            if ((C('show_goods_in_cart') == "2" || C('show_goods_in_cart') == "3") && $row['extension_code'] != 'package_buy') {
                $res = $this->row("SELECT `goods_thumb` FROM " . $this->pre . "goods WHERE `goods_id`='{$row['goods_id']}'");
                $goods_thumb = $res['goods_thumb'];
                $row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
            }
            if ($row['extension_code'] == 'package_buy') {
                $row['package_goods_list'] = model('PackageBase')->get_package_goods($row['goods_id']);
            }
            //获取库存
            $res = $this->row("SELECT `goods_number` FROM " . $this->pre . "goods WHERE `goods_id`='{$row['goods_id']}'");
            $row['goods_max_number'] = $res['goods_number'];
            $goods_list[] = $row;
        }
        $total['goods_amount'] = $total['goods_price'];
        $total['saving'] = price_format($total['market_price'] - $total['goods_price'], false);
        if ($total['market_price'] > 0) {
            $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
                            100 / $total['market_price']) . '%' : 0;
        }
        $total['goods_price'] = price_format($total['goods_price'], false);
        $total['market_price'] = price_format($total['market_price'], false);
        $total['real_goods_count'] = $real_goods_count;
        $total['virtual_goods_count'] = $virtual_goods_count;

        return array('goods_list' => $goods_list, 'total' => $total);
    }

    /**
     * 取得收货人信息
     * @param   int     $user_id    用户编号
     * @return  array
     */
    function get_consignee($user_id) {
        if (isset($_SESSION['flow_consignee'])) {
            /* 如果存在session，则直接返回session中的收货人信息 */

            return $_SESSION['flow_consignee'];
        } else {
            /* 如果不存在，则取得用户的默认收货人信息 */
            $arr = array();

            if ($user_id > 0) {
                /* 取默认地址 */
                $sql = "SELECT ua.*" .
                        " FROM " . $this->pre . "user_address AS ua, " . $this->pre . 'users AS u ' .
                        " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";

                $arr = $this->row($sql);
            }

            return $arr;
        }
    }

    /**
     * 查询购物车（订单id为0）或订单中是否有实体商品
     * @param   int     $order_id   订单id
     * @param   int     $flow_type  购物流程类型
     * @return  bool
     */
    function exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS) {
        if ($order_id <= 0) {
            $sql = "SELECT COUNT(*) as count FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "' AND is_real = 1 " .
                    "AND rec_type = '$flow_type'";
        } else {
            $sql = "SELECT COUNT(*) as count FROM " . $this->pre .
                    "order_goods WHERE order_id = '$order_id' AND is_real = 1";
        }
        $res = $this->row($sql);
        return $res['count'] > 0;
    }

    /**
     * 检查收货人信息是否完整
     * @param   array   $consignee  收货人信息
     * @param   int     $flow_type  购物流程类型
     * @return  bool    true 完整 false 不完整
     */
    function check_consignee_info($consignee, $flow_type) {
        if (model('Order')->exist_real_goods(0, $flow_type)) {
            /* 如果存在实体商品 */
            $res = !empty($consignee['consignee']) &&
                    !empty($consignee['country']) &&
                    !empty($consignee['mobile']);

            if ($res) {
                if (empty($consignee['province'])) {
                    /* 没有设置省份，检查当前国家下面有没有设置省份 */
                    $pro = model('RegionBase')->get_regions(1, $consignee['country']);
                    $res = empty($pro);
                } elseif (empty($consignee['city'])) {
                    /* 没有设置城市，检查当前省下面有没有城市 */
                    $city = model('RegionBase')->get_regions(2, $consignee['province']);
                    $res = empty($city);
                } elseif (empty($consignee['district'])) {
                    $dist = model('RegionBase')->get_regions(3, $consignee['city']);
                    $res = empty($dist);
                }
            }

            return $res;
        } else {
            /* 如果不存在实体商品 */
            return !empty($consignee['consignee']);
        }
    }

    /**
     * 获得上一次用户采用的支付和配送方式
     *
     * @access  public
     * @return  void
     */
    function last_shipping_and_payment() {
        $sql = "SELECT shipping_id, pay_id " .
                " FROM " . $this->pre .
                "order_info WHERE user_id = '$_SESSION[user_id]' " .
                " ORDER BY order_id DESC LIMIT 1";
        $row = $this->row($sql);

        if (empty($row)) {
            /* 如果获得是一个空数组，则返回默认值 */
            $row = array('shipping_id' => 0, 'pay_id' => 0);
        }

        return $row;
    }

    /**
     * 取得当前用户应该得到的红包总额
     */
    function get_total_bonus() {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

        /* 按商品发的红包 */
        $sql = "SELECT SUM(c.goods_number * t.type_money)" .
                " as count FROM " . $this->pre . "cart AS c, "
                . $this->pre . "bonus_type AS t, "
                . $this->pre . "goods AS g " .
                "WHERE c.session_id = '" . SESS_ID . "' " .
                "AND c.is_gift = 0 " .
                "AND c.goods_id = g.goods_id " .
                "AND g.bonus_type_id = t.type_id " .
                "AND t.send_type = '" . SEND_BY_GOODS . "' " .
                "AND t.send_start_date <= '$today' " .
                "AND t.send_end_date >= '$today' " .
                "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
        $res = $this->row($sql);
        $goods_total = floatval($res['count']);

        /* 取得购物车中非赠品总金额 */
        $sql = "SELECT SUM(goods_price * goods_number) " .
                " as count FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "' " .
                " AND is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $res = $this->row($sql);
        $amount = floatval($res['count']);

        /* 按订单发的红包 */
        $sql = "SELECT FLOOR('$amount' / min_amount) * type_money " .
                "as count FROM " . $this->pre .
                "bonus_type WHERE send_type = '" . SEND_BY_ORDER . "' " .
                " AND send_start_date <= '$today' " .
                "AND send_end_date >= '$today' " .
                "AND min_amount > 0 ";
        $res = $this->row($sql);
        $order_total = floatval($res['count']);

        return $goods_total + $order_total;
    }

    /**
     * 处理红包（下订单时设为使用，取消（无效，退货）订单时设为未使用
     * @param   int     $bonus_id   红包编号
     * @param   int     $order_id   订单号
     * @param   int     $is_used    是否使用了
     */
    function change_user_bonus($bonus_id, $order_id, $is_used = true) {
        if ($is_used) {
            $sql = 'UPDATE ' . $this->pre . 'user_bonus SET ' .
                    'used_time = ' . gmtime() . ', ' .
                    "order_id = '$order_id' " .
                    "WHERE bonus_id = '$bonus_id'";
        } else {
            $sql = 'UPDATE ' . $this->pre . 'user_bonus SET ' .
                    'used_time = 0, ' .
                    'order_id = 0 ' .
                    "WHERE bonus_id = '$bonus_id'";
        }
        $this->query($sql);
    }

    /**
     * 获得订单信息
     *
     * @access  private
     * @return  array
     */
    function flow_order_info() {
        $order = isset($_SESSION['flow_order']) ? $_SESSION['flow_order'] : array();

        /* 初始化配送和支付方式 */
        if (!isset($order['shipping_id']) || !isset($order['pay_id'])) {
            /* 如果还没有设置配送和支付 */
            if ($_SESSION['user_id'] > 0) {
                /* 用户已经登录了，则获得上次使用的配送和支付 */
                $arr = model('Order')->last_shipping_and_payment();

                if (!isset($order['shipping_id'])) {
                    $order['shipping_id'] = $arr['shipping_id'];
                }
                if (!isset($order['pay_id'])) {
                    $order['pay_id'] = $arr['pay_id'];
                }
            } else {
                if (!isset($order['shipping_id'])) {
                    $order['shipping_id'] = 0;
                }
                if (!isset($order['pay_id'])) {
                    $order['pay_id'] = 0;
                }
            }
        }

        if (!isset($order['pack_id'])) {
            $order['pack_id'] = 0;  // 初始化包装
        }
        if (!isset($order['card_id'])) {
            $order['card_id'] = 0;  // 初始化贺卡
        }
        if (!isset($order['bonus'])) {
            $order['bonus'] = 0;    // 初始化红包
        }
        if (!isset($order['integral'])) {
            $order['integral'] = 0; // 初始化积分
        }
        if (!isset($order['surplus'])) {
            $order['surplus'] = 0;  // 初始化余额
        }

        /* 扩展信息 */
        if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) != CART_GENERAL_GOODS) {
            $order['extension_code'] = $_SESSION['extension_code'];
            $order['extension_id'] = $_SESSION['extension_id'];
        }

        return $order;
    }

    /**
     * 合并订单
     * @param   string  $from_order_sn  从订单号
     * @param   string  $to_order_sn    主订单号
     * @return  成功返回true，失败返回错误信息
     */
    function merge_order($from_order_sn, $to_order_sn) {
        /* 订单号不能为空 */
        if (trim($from_order_sn) == '' || trim($to_order_sn) == '') {
            return L('order_sn_not_null');
        }

        /* 订单号不能相同 */
        if ($from_order_sn == $to_order_sn) {
            return L('two_order_sn_same');
        }

        /* 取得订单信息 */
        $from_order = model('Order')->order_info(0, $from_order_sn);
        $to_order = model('Order')->order_info(0, $to_order_sn);

        /* 检查订单是否存在 */
        if (!$from_order) {
            return sprintf(L('order_not_exist'), $from_order_sn);
        } elseif (!$to_order) {
            return sprintf(L('order_not_exist'), $to_order_sn);
        }

        /* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
        if ($from_order['extension_code'] != '' || $to_order['extension_code'] != 0) {
            return L('merge_invalid_order');
        }

        /* 检查订单状态是否是已确认或未确认、未付款、未发货 */
        if ($from_order['order_status'] != OS_UNCONFIRMED && $from_order['order_status'] != OS_CONFIRMED) {
            return sprintf(L('os_not_unconfirmed_or_confirmed'), $from_order_sn);
        } elseif ($from_order['pay_status'] != PS_UNPAYED) {
            return sprintf(L('ps_not_unpayed'), $from_order_sn);
        } elseif ($from_order['shipping_status'] != SS_UNSHIPPED) {
            return sprintf(L('ss_not_unshipped'), $from_order_sn);
        }

        if ($to_order['order_status'] != OS_UNCONFIRMED && $to_order['order_status'] != OS_CONFIRMED) {
            return sprintf(L('os_not_unconfirmed_or_confirmed'), $to_order_sn);
        } elseif ($to_order['pay_status'] != PS_UNPAYED) {
            return sprintf(L('ps_not_unpayed'), $to_order_sn);
        } elseif ($to_order['shipping_status'] != SS_UNSHIPPED) {
            return sprintf(L('ss_not_unshipped'), $to_order_sn);
        }

        /* 检查订单用户是否相同 */
        if ($from_order['user_id'] != $to_order['user_id']) {
            return L('order_user_not_same');
        }

        /* 合并订单 */
        $order = $to_order;
        $order['order_id'] = '';
        $order['add_time'] = gmtime();

        // 合并商品总额
        $order['goods_amount'] += $from_order['goods_amount'];

        // 合并折扣
        $order['discount'] += $from_order['discount'];

        if ($order['shipping_id'] > 0) {
            // 重新计算配送费用
            $weight_price = model('Order')->order_weight_price($to_order['order_id']);
            $from_weight_price = model('Order')->order_weight_price($from_order['order_id']);
            $weight_price['weight'] += $from_weight_price['weight'];
            $weight_price['amount'] += $from_weight_price['amount'];
            $weight_price['number'] += $from_weight_price['number'];

            $region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
            $shipping_area = model('Shipping')->shipping_area_info($order['shipping_id'], $region_id_list);

            $order['shipping_fee'] = shipping_fee($shipping_area['shipping_code'], unserialize($shipping_area['configure']), $weight_price['weight'], $weight_price['amount'], $weight_price['number']);

            // 如果保价了，重新计算保价费
            if ($order['insure_fee'] > 0) {
                $order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
            }
        }

        // 重新计算包装费、贺卡费
        if ($order['pack_id'] > 0) {
            $pack = $this->pack_info($order['pack_id']);
            $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
        }
        if ($order['card_id'] > 0) {
            $card = model('Order')->card_info($order['card_id']);
            $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
        }

        // 红包不变，合并积分、余额、已付款金额
        $order['integral'] += $from_order['integral'];
        $order['integral_money'] = value_of_integral($order['integral']);
        $order['surplus'] += $from_order['surplus'];
        $order['money_paid'] += $from_order['money_paid'];

        // 计算应付款金额（不包括支付费用）
        $order['order_amount'] = $order['goods_amount'] - $order['discount'] + $order['shipping_fee'] + $order['insure_fee'] + $order['pack_fee'] + $order['card_fee'] - $order['bonus'] - $order['integral_money'] - $order['surplus'] - $order['money_paid'];

        // 重新计算支付费
        if ($order['pay_id'] > 0) {
            // 货到付款手续费
            $cod_fee = $shipping_area ? $shipping_area['pay_fee'] : 0;
            $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

            // 应付款金额加上支付费
            $order['order_amount'] += $order['pay_fee'];
        }

        /* 插入订单表 */
        do {
            $order['order_sn'] = get_order_sn();
            $this->talbe = 'order_info';
            if ($this->insert(addslashes_deep($order))) {
                break;
            } else {
                if (M()->errno() != 1062) {
                    die(M()->errorMsg());
                }
            }
        } while (true); // 防止订单号重复

        /* 订单号 */
        $order_id = M()->insert_id();

        /* 更新订单商品 */
        $sql = 'UPDATE ' . $this->pre .
                "order_goods SET order_id = '$order_id' " .
                "WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
        $this->query($sql);

        include_once(ROOT_PATH . 'includes/lib_clips.php');
        /* 插入支付日志 */
        model('ClipsBase')->insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

        /* 删除原订单 */
        $sql = 'DELETE FROM ' . $this->pre .
                "order_info WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
        $this->query($sql);

        /* 删除原订单支付日志 */
        $sql = 'DELETE FROM ' . $this->pre .
                "pay_log WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
        $this->query($sql);

        /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
        if ($from_order['bonus_id'] > 0) {
            model('Order')->unuse_bonus($from_order['bonus_id']);
        }

        /* 返回成功 */
        return true;
    }

    /**
     * 查询配送区域属于哪个办事处管辖
     * @param   array   $regions    配送区域（1、2、3、4级按顺序）
     * @return  int     办事处id，可能为0
     */
    function get_agency_by_regions($regions) {
        if (!is_array($regions) || empty($regions)) {
            return 0;
        }

        $arr = array();
        $sql = "SELECT region_id, agency_id " .
                "FROM " . $this->pre .
                "region WHERE region_id " . db_create_in($regions) .
                " AND region_id > 0 AND agency_id > 0";
        $res = $this->query($sql);
        foreach ($res as $row) {
            $arr[$row['region_id']] = $row['agency_id'];
        }
        if (empty($arr)) {
            return 0;
        }

        $agency_id = 0;
        for ($i = count($regions) - 1; $i >= 0; $i--) {
            if (isset($arr[$regions[$i]])) {
                return $arr[$regions[$i]];
            }
        }
    }

    /**
     * 改变订单中商品库存
     * @param   int     $order_id   订单号
     * @param   bool    $is_dec     是否减少库存
     * @param   bool    $storage     减库存的时机，1，下订单时；0，发货时；
     */
    function change_order_goods_storage($order_id, $is_dec = true, $storage = 0) {
        /* 查询订单商品信息 */
        switch ($storage) {
            case 0 :
                $sql = "SELECT goods_id, SUM(send_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $this->pre .
                        "order_goods WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                break;

            case 1 :
                $sql = "SELECT goods_id, SUM(goods_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $this->pre .
                        "order_goods WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                break;
        }

        $res = $this->query($sql);
        foreach ($res as $row) {
            if ($row['extension_code'] != "package_buy") {
                if ($is_dec) {
                    $this->change_goods_storage($row['goods_id'], $row['product_id'], - $row['num']);
                } else {
                    $this->change_goods_storage($row['goods_id'], $row['product_id'], $row['num']);
                }
                M()->query($sql);
            } else {
                $sql = "SELECT goods_id, goods_number" .
                        " FROM " . $this->pre .
                        "package_goods WHERE package_id = '" . $row['goods_id'] . "'";
                $row_goods = $this->row($sql);
                $sql = "SELECT is_real" .
                        " FROM " . $this->pre .
                        "goods WHERE goods_id = '" . $row_goods['goods_id'] . "'";
                $is_goods = $this->row($sql);

                if ($is_dec) {
                    $this->change_goods_storage($row_goods['goods_id'], $row['product_id'], - ($row['num'] * $row_goods['goods_number']));
                } elseif ($is_goods['is_real']) {
                    $this->change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']));
                }
            }
        }
    }

    /**
     * 商品库存增与减 货品库存增与减
     *
     * @param   int    $good_id         商品ID
     * @param   int    $product_id      货品ID
     * @param   int    $number          增减数量，默认0；
     *
     * @return  bool               true，成功；false，失败；
     */
    function change_goods_storage($good_id, $product_id, $number = 0) {
        if ($number == 0) {
            return true; // 值为0即不做、增减操作，返回true
        }

        if (empty($good_id) || empty($number)) {
            return false;
        }

        $number = ($number > 0) ? '+ ' . $number : $number;

        /* 处理货品库存 */
        $products_query = true;
        if (!empty($product_id)) {
            $sql = "UPDATE " . $this->pre .
                    "products SET product_number = product_number $number
                WHERE goods_id = '$good_id'
                AND product_id = '$product_id'
                LIMIT 1";
            $products_query = $this->query($sql);
        }

        /* 处理商品库存 */
        $sql = "UPDATE " . $this->pre .
                "goods SET goods_number = goods_number $number
            WHERE goods_id = '$good_id'
            LIMIT 1";
        $query = $this->query($sql);

        if ($query && $products_query) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取得支付方式id列表
     * @param   bool    $is_cod 是否货到付款
     * @return  array
     */
    function payment_id_list($is_cod) {
        $sql = "SELECT pay_id FROM " . $this->pre . 'payment';
        if ($is_cod) {
            $sql .= " WHERE is_cod = 1";
        } else {
            $sql .= " WHERE is_cod = 0";
        }
        $list = $this->query($sql);
        $merge = array();
        foreach ($list as $key => $value) {

            $merge[] = $value['order_sn'];
        }
        return $merge;
    }

    /**
     * 计算折扣：根据购物车和优惠活动
     * @return  float   折扣
     */
    function compute_discount() {
        /* 查询优惠活动 */
        $now = gmtime();
        $user_rank = ',' . $_SESSION['user_rank'] . ',';
        $sql = "SELECT *" .
                "FROM " . $this->pre .
                "favourable_activity WHERE start_time <= '$now'" .
                " AND end_time >= '$now'" .
                " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
                " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
        $favourable_list = $this->query($sql);
        if (!$favourable_list) {
            return 0;
        }

        /* 查询购物车商品 */
        $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
                "FROM " . $this->pre . "cart AS c, " . $this->pre . "goods AS g " .
                "WHERE c.goods_id = g.goods_id " .
                "AND c.session_id = '" . SESS_ID . "' " .
                "AND c.parent_id = 0 " .
                "AND c.is_gift = 0 " .
                "AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $goods_list = $this->query($sql);
        if (!$goods_list) {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = array();

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable) {
            $total_amount = 0;
            if ($favourable['act_range'] == FAR_ALL) {
                foreach ($goods_list as $goods) {
                    $total_amount += $goods['subtotal'];
                }
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 找出分类id的子分类id */
                $id_list = array();
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id) {
                    $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                }
                $ids = join(',', array_unique($id_list));

                foreach ($goods_list as $goods) {
                    if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } elseif ($favourable['act_range'] == FAR_BRAND) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } elseif ($favourable['act_range'] == FAR_GOODS) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } else {
                continue;
            }

            /* 如果金额满足条件，累计折扣 */
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
                if ($favourable['act_type'] == FAT_DISCOUNT) {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);

                    $favourable_name[] = $favourable['act_name'];
                } elseif ($favourable['act_type'] == FAT_PRICE) {
                    $discount += $favourable['act_type_ext'];

                    $favourable_name[] = $favourable['act_name'];
                }
            }
        }

        return array('discount' => $discount, 'name' => $favourable_name);
    }

    /**
     * 取得购物车该赠送的积分数
     * @return  int     积分数
     */
    function get_give_integral() {
        $sql = "SELECT SUM(c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price))" .
                " as count FROM " . $this->pre . "cart AS c, " .
                $this->pre . "goods AS g " .
                "WHERE c.goods_id = g.goods_id " .
                "AND c.session_id = '" . SESS_ID . "' " .
                "AND c.goods_id > 0 " .
                "AND c.parent_id = 0 " .
                "AND c.rec_type = 0 " .
                "AND c.is_gift = 0";
        $res = $this->row($sql);
        return intval($res['count']);
    }

    /**
     * 取得某订单应该赠送的积分数
     * @param   array   $order  订单
     * @return  int     积分数
     */
    function integral_to_give($order) {
        /* 判断是否团购 */
        if ($order['extension_code'] == 'group_buy') {
            include_once(ROOT_PATH . 'includes/lib_goods.php');
            $group_buy = model('GroupBuyBase')->group_buy_info(intval($order['extension_id']));

            return array('custom_points' => $group_buy['gift_integral'], 'rank_points' => $order['goods_amount']);
        } else {
            $sql = "SELECT SUM(og.goods_number * IF(g.give_integral > -1, g.give_integral, og.goods_price)) AS custom_points, SUM(og.goods_number * IF(g.rank_integral > -1, g.rank_integral, og.goods_price)) AS rank_points " .
                    "FROM " . $this->pre . "order_goods AS og, " .
                    $this->pre . "goods AS g " .
                    "WHERE og.goods_id = g.goods_id " .
                    "AND og.order_id = '$order[order_id]' " .
                    "AND og.goods_id > 0 " .
                    "AND og.parent_id = 0 " .
                    "AND og.is_gift = 0 AND og.extension_code != 'package_buy'";

            return $this->row($sql);
        }
    }

    /**
     * 发红包：发货时发红包
     * @param   int     $order_id   订单号
     * @return  bool
     */
    function send_order_bonus($order_id) {
        /* 取得订单应该发放的红包 */
        $bonus_list = model('Order')->order_bonus($order_id);

        /* 如果有红包，统计并发送 */
        if ($bonus_list) {
            /* 用户信息 */
            $sql = "SELECT u.user_id, u.user_name, u.email " .
                    "FROM " . $this->pre . "order_info AS o, " .
                    $this->pre . "users AS u " .
                    "WHERE o.order_id = '$order_id' " .
                    "AND o.user_id = u.user_id ";
            $user = $this->row($sql);

            /* 统计 */
            $count = 0;
            $money = '';
            foreach ($bonus_list AS $bonus) {
                $count += $bonus['number'];
                $money .= price_format($bonus['type_money']) . ' [' . $bonus['number'] . '], ';

                /* 修改用户红包 */
                $sql = "INSERT INTO " . $this->pre . "user_bonus (bonus_type_id, user_id) " .
                        "VALUES('$bonus[type_id]', '$user[user_id]')";
                for ($i = 0; $i < $bonus['number']; $i++) {
                    if (!$this->query($sql)) {
                        return M()->errorMsg();
                    }
                }
            }

            /* 如果有红包，发送邮件 */
            if ($count > 0) {
                $tpl = model('Base')->get_mail_template('send_bonus');
                ECTouch::view()->assign('user_name', $user['user_name']);
                ECTouch::view()->assign('count', $count);
                ECTouch::view()->assign('money', $money);
                ECTouch::view()->assign('shop_name', C('shop_name'));
                ECTouch::view()->assign('send_date', local_date(C('date_format')));
                ECTouch::view()->assign('sent_date', local_date(C('date_format')));
                $content = ECTouch::view()->fetch('str:' . $tpl['template_content']);
                send_mail($user['user_name'], $user['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }
        }

        return true;
    }

    /**
     * 返回订单发放的红包
     * @param   int     $order_id   订单id
     */
    function return_order_bonus($order_id) {
        /* 取得订单应该发放的红包 */
        $bonus_list = model('Order')->order_bonus($order_id);

        /* 删除 */
        if ($bonus_list) {
            /* 取得订单信息 */
            $order = model('Order')->order_info($order_id);
            $user_id = $order['user_id'];

            foreach ($bonus_list AS $bonus) {
                $sql = "DELETE FROM " . $this->pre .
                        "user_bonus WHERE bonus_type_id = '$bonus[type_id]' " .
                        "AND user_id = '$user_id' " .
                        "AND order_id = '0' LIMIT " . $bonus['number'];
                $this->query($sql);
            }
        }
    }

    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @return  float   享受红包支付的总额
     */
    function compute_discount_amount() {
        /* 查询优惠活动 */
        $now = gmtime();
        $user_rank = ',' . $_SESSION['user_rank'] . ',';
        $sql = "SELECT *" .
                "FROM " . $this->pre .
                "favourable_activity WHERE start_time <= '$now'" .
                " AND end_time >= '$now'" .
                " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
                " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
        $favourable_list = $this->query($sql);
        if (!$favourable_list) {
            return 0;
        }

        /* 查询购物车商品 */
        $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
                "FROM " . $this->pre . "cart AS c, " . $this->pre . "goods AS g " .
                "WHERE c.goods_id = g.goods_id " .
                "AND c.session_id = '" . SESS_ID . "' " .
                "AND c.parent_id = 0 " .
                "AND c.is_gift = 0 " .
                "AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $goods_list = $this->query($sql);
        if (!$goods_list) {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = array();

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable) {
            $total_amount = 0;
            if ($favourable['act_range'] == FAR_ALL) {
                foreach ($goods_list as $goods) {
                    $total_amount += $goods['subtotal'];
                }
            } elseif ($favourable['act_range'] == FAR_CATEGORY) {
                /* 找出分类id的子分类id */
                $id_list = array();
                $raw_id_list = explode(',', $favourable['act_range_ext']);
                foreach ($raw_id_list as $id) {
                    $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                }
                $ids = join(',', array_unique($id_list));

                foreach ($goods_list as $goods) {
                    if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } elseif ($favourable['act_range'] == FAR_BRAND) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['brand_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } elseif ($favourable['act_range'] == FAR_GOODS) {
                foreach ($goods_list as $goods) {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false) {
                        $total_amount += $goods['subtotal'];
                    }
                }
            } else {
                continue;
            }
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0)) {
                if ($favourable['act_type'] == FAT_DISCOUNT) {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                } elseif ($favourable['act_type'] == FAT_PRICE) {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }


        return $discount;
    }

    /**
     * 添加礼包到购物车
     *
     * @access  public
     * @param   integer $package_id   礼包编号
     * @param   integer $num          礼包数量
     * @return  boolean
     */
    function add_package_to_cart($package_id, $num = 1) {
        ECTouch::err()->clean();

        /* 取得礼包信息 */
        $package = get_package_info($package_id);

        if (empty($package)) {
            ECTouch::err()->add(L('goods_not_exists'), ERR_NOT_EXISTS);

            return false;
        }

        /* 是否正在销售 */
        if ($package['is_on_sale'] == 0) {
            ECTouch::err()->add(L('not_on_sale'), ERR_NOT_ON_SALE);

            return false;
        }

        /* 现有库存是否还能凑齐一个礼包 */
        if (C('use_storage') == '1' && model('Order')->judge_package_stock($package_id)) {
            ECTouch::err()->add(sprintf(L('shortage'), 1), ERR_OUT_OF_STOCK);

            return false;
        }

        /* 检查库存 */
//    if (C('use_storage') == 1 && $num > $package['goods_number'])
//    {
//        $num = $goods['goods_number'];
//        ECTouch::err()->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);
//
//        return false;
//    }

        /* 初始化要插入购物车的基本件数据 */
        $parent = array(
            'user_id' => $_SESSION['user_id'],
            'session_id' => SESS_ID,
            'goods_id' => $package_id,
            'goods_sn' => '',
            'goods_name' => addslashes($package['package_name']),
            'market_price' => $package['market_package'],
            'goods_price' => $package['package_price'],
            'goods_number' => $num,
            'goods_attr' => '',
            'goods_attr_id' => '',
            'is_real' => $package['is_real'],
            'extension_code' => 'package_buy',
            'is_gift' => 0,
            'rec_type' => CART_GENERAL_GOODS
        );

        /* 如果数量不为0，作为基本件插入 */
        if ($num > 0) {
            /* 检查该商品是否已经存在在购物车中 */
            $sql = "SELECT goods_number FROM " . $this->pre .
                    "cart WHERE session_id = '" . SESS_ID . "' AND goods_id = '" . $package_id . "' " .
                    " AND parent_id = 0 AND extension_code = 'package_buy' " .
                    " AND rec_type = '" . CART_GENERAL_GOODS . "'";

            $row = $this->row($sql);

            if ($row) { //如果购物车已经有此物品，则更新
                $num += $row['goods_number'];
                if (C('use_storage') == 0 || $num > 0) {
                    $sql = "UPDATE " . $this->pre . "cart SET goods_number = '" . $num . "'" .
                            " WHERE session_id = '" . SESS_ID . "' AND goods_id = '$package_id' " .
                            " AND parent_id = 0 AND extension_code = 'package_buy' " .
                            " AND rec_type = '" . CART_GENERAL_GOODS . "'";
                    $this->query($sql);
                } else {
                    ECTouch::err()->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);
                    return false;
                }
            } else { //购物车没有此物品，则插入
                $this->table = 'cart';
                $this->insert($parent);
            }
        }

        /* 把赠品删除 */
        $sql = "DELETE FROM " . $this->pre . "cart WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
        $this->query($sql);

        return true;
    }

    /**
     * 检查礼包内商品的库存
     * @return  boolen
     */
    function judge_package_stock($package_id, $package_num = 1) {
        $sql = "SELECT goods_id, product_id, goods_number
            FROM " . $this->pre .
                "package_goods WHERE package_id = '" . $package_id . "'";
        $row = $this->query($sql);
        if (empty($row)) {
            return true;
        }

        /* 分离货品与商品 */
        $goods = array('product_ids' => '', 'goods_ids' => '');
        foreach ($row as $value) {
            if ($value['product_id'] > 0) {
                $goods['product_ids'] .= ',' . $value['product_id'];
                continue;
            }

            $goods['goods_ids'] .= ',' . $value['goods_id'];
        }

        /* 检查货品库存 */
        if ($goods['product_ids'] != '') {
            $sql = "SELECT p.product_id
                FROM " . $this->pre . "products AS p, " . $this->pre . "package_goods AS pg
                WHERE pg.product_id = p.product_id
                AND pg.package_id = '$package_id'
                AND pg.goods_number * $package_num > p.product_number
                AND p.product_id IN (" . trim($goods['product_ids'], ',') . ")";
            $row = $this->query($sql);

            if (!empty($row)) {
                return true;
            }
        }

        /* 检查商品库存 */
        if ($goods['goods_ids'] != '') {
            $sql = "SELECT g.goods_id
                FROM " . $this->pre . "goods AS g, " . $this->pre . "package_goods AS pg
                WHERE pg.goods_id = g.goods_id
                AND pg.goods_number * $package_num > g.goods_number
                AND pg.package_id = '" . $package_id . "'
                AND pg.goods_id IN (" . trim($goods['goods_ids'], ',') . ")";
            $row = $this->query($sql);

            if (!empty($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取订单第一个商品的缩略图
     * @param type $order_id
     * @return type
     */
    function get_order_thumb($order_id) {

        $arr = $this->model->query("SELECT g.goods_thumb FROM " . $this->model->pre . "order_goods as og left join " . $this->model->pre . "goods g on og.goods_id = g.goods_id WHERE og.order_id = " . $order_id . " limit 1");
        return $arr[0]['goods_thumb'];
    }

}
