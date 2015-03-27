<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：FlowModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 购物流程模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class FlowModel extends BaseModel {

    /**
     * 删除购物车中的商品
     *
     * @access public
     * @param integer $id
     * @return void
     */
    function flow_drop_cart_goods($id) {
        /* 取得商品id */
        $sql = "SELECT * FROM " . $this->pre . "cart WHERE rec_id = '$id'";
        $row = $this->row($sql);
        if ($row) {
            // 如果是超值礼包
            if ($row ['extension_code'] == 'package_buy') {
                $sql = "DELETE FROM " . $this->pre . "cart WHERE session_id = '" . SESS_ID . "' " . "AND rec_id = '$id' LIMIT 1";
            }
            // 如果是普通商品，同时删除所有赠品及其配件
            elseif ($row ['parent_id'] == 0 && $row ['is_gift'] == 0) {
                /* 检查购物车中该普通商品的不可单独销售的配件并删除 */
                $sql = "SELECT c.rec_id
				FROM " . $this->pre . "cart AS c, " . $this->pre . "group_goods AS gg, " . $this->pre . "goods AS g
				WHERE gg.parent_id = '" . $row ['goods_id'] . "'
				AND c.goods_id = gg.goods_id
				AND c.parent_id = '" . $row ['goods_id'] . "'
				AND c.extension_code <> 'package_buy'
				AND gg.goods_id = g.goods_id
				AND g.is_alone_sale = 0";
                $res = $this->query($sql);
                $_del_str = $id . ',';
                foreach ($res as $id_alone_sale_goods) {
                    $_del_str .= $id_alone_sale_goods ['rec_id'] . ',';
                }
                $_del_str = trim($_del_str, ',');

                $sql = "DELETE FROM " . $this->pre . "cart WHERE session_id = '" . SESS_ID . "' " . "AND (rec_id IN ($_del_str) OR parent_id = '$row[goods_id]' OR is_gift <> 0)";
            }
            // 如果不是普通商品，只删除该商品即可
            else {
                $sql = "DELETE FROM " . $this->pre . "cart WHERE session_id = '" . SESS_ID . "' " . "AND rec_id = '$id' LIMIT 1";
            }
            $this->query($sql);
        }
        //删除购物车中不能单独销售的商品
        $this->flow_clear_cart_alone();
    }

    /**
     * 删除购物车中不能单独销售的商品
     *
     * @access public
     * @return void
     */
    function flow_clear_cart_alone() {
        /* 查询：购物车中所有不可以单独销售的配件 */
        $sql = "SELECT c.rec_id, gg.parent_id
		FROM " . $this->pre . "cart AS c
		LEFT JOIN " . $this->pre . "group_goods AS gg ON c.goods_id = gg.goods_id
		LEFT JOIN " . $this->pre . "goods AS g ON c.goods_id = g.goods_id
		WHERE c.session_id = '" . SESS_ID . "'
		AND c.extension_code <> 'package_buy'
		AND gg.parent_id > 0
		AND g.is_alone_sale = 0";
        $res = $this->query($sql);
        $rec_id = array();
        foreach ($res as $row) {
            $rec_id [$row ['rec_id']] [] = $row ['parent_id'];
        }
        if (empty($rec_id)) {
            return;
        }

        /* 查询：购物车中所有商品 */
        $sql = "SELECT DISTINCT goods_id
		FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "'
		AND extension_code <> 'package_buy'";
        $res = $this->query($sql);
        $cart_good = array();
        foreach ($res as $row) {
            $cart_good [] = $row ['goods_id'];
        }
        if (empty($cart_good)) {
            return;
        }

        /* 如果购物车中不可以单独销售配件的基本件不存在则删除该配件 */
        $del_rec_id = '';
        foreach ($rec_id as $key => $value) {
            foreach ($value as $v) {
                if (in_array($v, $cart_good)) {
                    continue 2;
                }
            }

            $del_rec_id = $key . ',';
        }
        $del_rec_id = trim($del_rec_id, ',');

        if ($del_rec_id == '') {
            return;
        }

        /* 删除 */
        $sql = "DELETE FROM " . $this->pre .
                "cart WHERE session_id = '" . SESS_ID . "'
			AND rec_id IN ($del_rec_id)";
        $this->query($sql);
    }

    /**
     * 取得购物车中已有的优惠活动及数量
     *
     * @return array
     */
    function cart_favourable() {
        $list = array();
        $sql = "SELECT is_gift, COUNT(*) AS num " . "FROM " . $this->pre . "cart  WHERE session_id = '" . SESS_ID . "'" . " AND rec_type = '" . CART_GENERAL_GOODS . "'" . " AND is_gift > 0" . " GROUP BY is_gift";
        $res = $this->query($sql);
        foreach ($res as $row) {
            $list [$row ['is_gift']] = $row ['num'];
        }
        return $list;
    }

    /**
     * 取得某用户等级当前时间可以享受的优惠活动
     *
     * @param int $user_rank
     *        	用户等级id，0表示非会员
     * @return array
     */
    function favourable_list_flow($user_rank) {
        /* 购物车中已有的优惠活动及数量 */
        $used_list = model('Flow')->cart_favourable();
        /* 当前用户可享受的优惠活动 */
        $favourable_list = array();
        $user_rank = ',' . $user_rank . ',';
        $now = gmtime();
        $sql = "SELECT * " . "FROM " . $this->pre . "favourable_activity WHERE CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" . " AND start_time <= '$now' AND end_time >= '$now'" . " AND act_type = '" . FAT_GOODS . "'" . " ORDER BY sort_order";
        $res = $this->query($sql);
        foreach ($res as $favourable) {
            $favourable ['start_time'] = local_date(C('time_format'), $favourable ['start_time']);
            $favourable ['end_time'] = local_date(C('time_format'), $favourable ['end_time']);
            $favourable ['formated_min_amount'] = price_format($favourable ['min_amount'], false);
            $favourable ['formated_max_amount'] = price_format($favourable ['max_amount'], false);
            $favourable ['gift'] = unserialize($favourable ['gift']);
            foreach ($favourable ['gift'] as $key => $value) {
                $favourable ['gift'] [$key] ['formated_price'] = price_format($value ['price'], false);
                $sql = "SELECT COUNT(*) as count FROM " . $this->pre . "goods WHERE is_on_sale = 1 AND goods_id = " . $value ['id'];
                $res = $this->row($sql);
                $is_sale = $res['count'];
                if (!$is_sale) {
                    unset($favourable ['gift'] [$key]);
                }
            }
            $favourable ['act_range_desc'] = $this->act_range_desc($favourable);
            $favourable ['act_type_desc'] = sprintf(L('fat_ext.' . $favourable ['act_type']), $favourable ['act_type_ext']);
            /* 是否能享受 */
            $favourable ['available'] = $this->favourable_available($favourable);
            if ($favourable ['available']) {
                /* 是否尚未享受 */
                $favourable ['available'] = !$this->favourable_used($favourable, $used_list);
            }
            if (!$favourable ['available']) {
                continue;
            }
            $favourable_list [] = $favourable;
        }
        return $favourable_list;
    }

    /**
     * 比较优惠活动的函数，用于排序（把可用的排在前面）
     *
     * @param array $a
     *        	优惠活动a
     * @param array $b
     *        	优惠活动b
     * @return int 相等返回0，小于返回-1，大于返回1
     */
    static function cmp_favourable($a, $b) {
        if ($a ['available'] == $b ['available']) {
            if ($a ['sort_order'] == $b ['sort_order']) {
                return 0;
            } else {
                return $a ['sort_order'] < $b ['sort_order'] ? - 1 : 1;
            }
        } else {
            return $a ['available'] ? - 1 : 1;
        }
    }

    /**
     * 取得优惠范围描述
     *
     * @param array $favourable
     *        	优惠活动
     * @return string
     */
    function act_range_desc($favourable) {
    
        if ($favourable ['act_range'] == FAR_BRAND) {
            $condition = "brand_id " . db_create_in($favourable ['act_range_ext']);
            $field = 'brand_name';
            $this->table = 'brand';
            $array = $this->gecol($condition, $field);
            $array = $array ? $array : array();
            return join(',', $array);
        } elseif ($favourable ['act_range'] == FAR_CATEGORY) {
            $this->table = 'category';
            $condition = "cat_id " . db_create_in($favourable ['act_range_ext']);
            $field = 'cat_name';
            $array = $this->gecol($condition, $field);
            $array = $array ? $array : array();
            return join(',', $array);
        } elseif ($favourable ['act_range'] == FAR_GOODS) {
            $this->table = 'goods';
            $condition = "goods_id " . db_create_in($favourable ['act_range_ext']);
            $field = 'goods_name';
            $array = $this->gecol($condition, $field);
            $array = $array ? $array : array();
            return join(',', $array);
        } else {
    
            return '';
        }
    }

    /**
     * 根据购物车判断是否可以享受某优惠活动
     *
     * @param array $favourable
     *        	优惠活动信息
     * @return bool
     */
    function favourable_available($favourable) {
        /* 会员等级是否符合 */
        $user_rank = $_SESSION ['user_rank'];
        if (strpos(',' . $favourable ['user_rank'] . ',', ',' . $user_rank . ',') === false) {
            return false;
        }

        /* 优惠范围内的商品总额 */
        $amount = $this->cart_favourable_amount($favourable);

        /* 金额上限为0表示没有上限 */
        return $amount >= $favourable ['min_amount'] && ($amount <= $favourable ['max_amount'] || $favourable ['max_amount'] == 0);
    }

    /**
     * 取得购物车中某优惠活动范围内的总金额
     *
     * @param array $favourable
     *        	优惠活动
     * @return float
     */
    function cart_favourable_amount($favourable) {
        /* 查询优惠范围内商品总额的sql */
        $sql = "SELECT SUM(c.goods_price * c.goods_number) as sum " . "FROM " . $this->pre . "cart AS c, " . $this->pre . "goods AS g " . "WHERE c.goods_id = g.goods_id " . "AND c.session_id = '" . SESS_ID . "' " . "AND c.rec_type = '" . CART_GENERAL_GOODS . "' " . "AND c.is_gift = 0 " . "AND c.goods_id > 0 ";

        /* 根据优惠范围修正sql */
        if ($favourable ['act_range'] == FAR_ALL) {
            // sql do not change
        } elseif ($favourable ['act_range'] == FAR_CATEGORY) {
            /* 取得优惠范围分类的所有下级分类 */
            $id_list = array();
            $cat_list = explode(',', $favourable ['act_range_ext']);
            foreach ($cat_list as $id) {
                $id_list = array_merge($id_list, array_keys(cat_list(intval($id), 0, false)));
            }

            $sql .= "AND g.cat_id " . db_create_in($id_list);
        } elseif ($favourable ['act_range'] == FAR_BRAND) {
            $id_list = explode(',', $favourable ['act_range_ext']);

            $sql .= "AND g.brand_id " . db_create_in($id_list);
        } else {
            $id_list = explode(',', $favourable ['act_range_ext']);

            $sql .= "AND g.goods_id " . db_create_in($id_list);
        }
        $res = $this->row($sql);
        /* 优惠范围内的商品总额 */
        return $res['sum'];
    }

    /**
     * 购物车中是否已经有某优惠
     *
     * @param array $favourable
     *        	优惠活动
     * @param array $cart_favourable购物车中已有的优惠活动及数量
     */
    function favourable_used($favourable, $cart_favourable) {
        if ($favourable ['act_type'] == FAT_GOODS) {
            return isset($cart_favourable [$favourable ['act_id']]) && $cart_favourable [$favourable ['act_id']] >= $favourable ['act_type_ext'] && $favourable ['act_type_ext'] > 0;
        } else {
            return isset($cart_favourable [$favourable ['act_id']]);
        }
    }

    /**
     * 添加优惠活动（赠品）到购物车
     *
     * @param int $act_id
     *        	优惠活动id
     * @param int $id
     *        	赠品id
     * @param float $price
     *        	赠品价格
     */
    function add_gift_to_cart($act_id, $id, $price) {
        $sql = "INSERT INTO " . $this->pre . "cart (" . "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, " . "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) " . "SELECT '$_SESSION[user_id]', '" . SESS_ID . "', goods_id, goods_sn, goods_name, market_price, " . "'$price', 1, is_real, extension_code, 0, '$act_id', '" . CART_GENERAL_GOODS . "' " . "FROM " . $this->pre . "goods WHERE goods_id = '$id'";
        $this->query($sql);
    }

    /**
     * 添加优惠活动（非赠品）到购物车
     * @param   int     $act_id     优惠活动id
     * @param   string  $act_name   优惠活动name
     * @param   float   $amount     优惠金额
     */
    function add_favourable_to_cart($act_id, $act_name, $amount) {
        $sql = "INSERT INTO " . $this->pre . "cart(" .
                "user_id, session_id, goods_id, goods_sn, goods_name, market_price, goods_price, " .
                "goods_number, is_real, extension_code, parent_id, is_gift, rec_type ) " .
                "VALUES('$_SESSION[user_id]', '" . SESS_ID . "', 0, '', '$act_name', 0, " .
                "'" . (-1) * $amount . "', 1, 0, '', 0, '$act_id', '" . CART_GENERAL_GOODS . "')";
        $this->query($sql);
    }

    /**
     * 获得用户的可用积分
     *
     * @access private
     * @return integral
     */
    function flow_available_points() {
        $sql = "SELECT SUM(g.integral * c.goods_number) as sum " . "FROM " . $this->pre . "cart AS c, " . $this->pre . "goods AS g " . "WHERE c.session_id = '" . SESS_ID . "' AND c.goods_id = g.goods_id AND c.is_gift = 0 AND g.integral > 0 " . "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";

        $res = $this->row($sql);
        $val = intval($res['sum']);

        return integral_of_value($val);
    }

    // 增加销量统计
    function add_touch_goods($flow_type, $extension_code) {
        /* 统计时间段 */
        $period = C('top10_time');
        //近一个月（30天）
        if ($period == 1) { // 一年
            $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
        } elseif ($period == 2) { // 半年
            $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
        } elseif ($period == 3) { // 三个月
            $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
        } elseif ($period == 4) { // 一个月
            $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
        } else {
            $ext = '';
        }
        //查询销量统计表中是否有购物车中的商品信息
        $sql = 'select goods_id from ' . $this->pre . 'cart where  session_id = "' . SESS_ID . '" AND rec_type = "' . $flow_type . '"';
        $arrGoodsid = $this->query($sql);
        foreach ($arrGoodsid as $goodsid) {
                /* 查询该商品销量 */
                $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
                        'as count FROM ' . $this->pre . 'order_info AS o, ' .
                        $this->pre . 'order_goods AS g ' .
                        "WHERE o.order_id = g.order_id " .
                        "AND o.extension_code = '$extension_code'  AND g.goods_id = '" . $goodsid['goods_id'] . "' AND o.pay_status = '2' " . $ext;
                $res = $this->row($sql);
                $sales_count = $res['count'];
                if ($flow_type == CART_GENERAL_GOODS) {
                    $nCount = $this->query('select COUNT(*) from ' . $this->pre . 'touch_goods where  goods_id = "' . $goodsid['goods_id'] . '"');
                    if ($nCount[0]['COUNT(*)'] == 0) {
                        $this->query("INSERT INTO " . $this->pre . "touch_goods (`goods_id` ,`sales_volume` ) VALUES ( '" . $goodsid['goods_id'] . "' , '0')");
                    }
                    $sql = 'update ' . $this->pre . 'touch_goods AS a set a.sales_volume = ' . $sales_count . " WHERE goods_id=" . $goodsid['goods_id'];
                    $this->query($sql);
                }
            if ($flow_type == CART_GROUP_BUY_GOODS) {
                /* 查询该商品销量 */
                $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
                        'as count FROM ' . $this->pre . 'order_info AS o LEFT JOIN ' .
                        $this->pre . 'order_goods AS g  ON o.order_id = g.order_id ' .
                        'LEFT JOIN ' . $this->pre . 'goods_activity as ga ON ga.goods_id = g.goods_id ' .
                        "WHERE o.extension_code = '$extension_code'  AND o.pay_status = 2  AND g.goods_id = '" . $goodsid['goods_id'] . "'" . $ext;
                $res = $this->row($sql);
                $nCount = $this->query('select COUNT(*) from ' . $this->pre .
                        'touch_goods_activity tga LEFT JOIN ' . $this->pre . 'goods_activity ga ON tga.act_id =ga.act_id  where  ga.goods_id = "' . $goodsid['goods_id'] . '" ');
                $sql = 'SELECT act_id FROM ' . $this->pre . 'goods_activity WHERE goods_id = "' . $goodsid['goods_id'] . '" ';
                $act_id = $this->row($sql);
                if ($nCount[0]['COUNT(*)'] == 0) {
                    $this->query("INSERT INTO " . $this->pre . "touch_goods_activity (`act_id` ,`sales_count` ) VALUES ( '" . $act_id['act_id'] . "' , '0')");
                }
                $sales_count_group = $res['count'];
                $sql = 'update ' . $this->pre . 'touch_goods_activity set sales_count = ' . $sales_count_group . " WHERE act_id = $act_id[act_id]";
                $this->query($sql);
            }
        }
    }

    /**
     * 检查订单中商品库存
     *
     * @access  public
     * @param   array   $arr
     *
     * @return  void
     */
    function flow_cart_stock($arr) {
        foreach ($arr AS $key => $val) {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key)) {
                continue;
            }

            $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM " . $this->pre .
                    "cart WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
            $goods = $this->row($sql);

            $sql = "SELECT g.goods_name, g.goods_number, c.product_id " .
                    "FROM " . $this->pre . "goods AS g, " .
                    $this->pre . "cart AS c " .
                    "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            $row = $this->row($sql);

            //系统启用了库存，检查输入的商品数量是否有效
            if (intval(C('use_storage')) > 0 && $goods['extension_code'] != 'package_buy') {
                if ($row['goods_number'] < $val) {
                    show_message(sprintf(L('stock_insufficiency'), $row['goods_name'], $row['goods_number'], $row['goods_number']));
                    exit;
                }

                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id'])) {
                    $sql = "SELECT product_number FROM " . $this->pre . "products WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                    $res = $this->row($sql);
                    $product_number = $res['product_number'];
                    if ($product_number < $val) {
                        show_message(sprintf(L('stock_insufficiency'), $row['goods_name'], $row['goods_number'], $row['goods_number']));
                        exit;
                    }
                }
            } elseif (intval(C('use_storage')) > 0 && $goods['extension_code'] == 'package_buy') {
                if (model('Order')->judge_package_stock($goods['goods_id'], $val)) {
                    show_message(L('package_stock_insufficiency'));
                    exit;
                }
            }
        }
    }

}
