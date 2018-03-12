<?php

/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param   string       $cfg
 * @return  void
 */
function unserialize_config($cfg)
{
    if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
        $config = array();

        foreach ($arr as $key => $val) {
            $config[$val['name']] = $val['value'];
        }

        return $config;
    } else {
        return false;
    }
}
/**
 * 取得已安装的配送方式
 * @return  array   已安装的配送方式
 */
function shipping_list()
{
    $global = getInstance();
    $sql = 'SELECT shipping_id, shipping_name ' .
            'FROM ' . $global->ecs->table('shipping') .
            ' WHERE enabled = 1';

    return $global->db->getAll($sql);
}

/**
 * 取得配送方式信息
 * @param   int     $shipping_id    配送方式id
 * @return  array   配送方式信息
 */
function shipping_info($shipping_id)
{
    $global = getInstance();
    $sql = 'SELECT * FROM ' . $global->ecs->table('shipping') .
            " WHERE shipping_id = '$shipping_id' " .
            'AND enabled = 1';

    return $global->db->getRow($sql);
}

/**
 * 取得可用的配送方式列表
 * @param   array   $region_id_list     收货人地区id数组（包括国家、省、市、区）
 * @return  array   配送方式数组
 */
function available_shipping_list($region_id_list)
{
    $global = getInstance();
    $sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, s.support_cod, a.configure ' .
            'FROM ' . $global->ecs->table('shipping') . ' AS s, ' .
                $global->ecs->table('shipping_area') . ' AS a, ' .
                $global->ecs->table('area_region') . ' AS r ' .
            'WHERE r.region_id ' . db_create_in($region_id_list) .
            ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1 ORDER BY s.shipping_order';

    return $global->db->getAll($sql);
}

/**
 * 取得某配送方式对应于某收货地址的区域信息
 * @param   int     $shipping_id        配送方式id
 * @param   array   $region_id_list     收货人地区id数组
 * @return  array   配送区域信息（config 对应着反序列化的 configure）
 */
function shipping_area_info($shipping_id, $region_id_list)
{
    $global = getInstance();
    $sql = 'SELECT s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, s.support_cod, a.configure ' .
            'FROM ' . $global->ecs->table('shipping') . ' AS s, ' .
                $global->ecs->table('shipping_area') . ' AS a, ' .
                $global->ecs->table('area_region') . ' AS r ' .
            "WHERE s.shipping_id = '$shipping_id' " .
            'AND r.region_id ' . db_create_in($region_id_list) .
            ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1';
    $row = $global->db->getRow($sql);

    if (!empty($row)) {
        $shipping_config = unserialize_config($row['configure']);
        if (isset($shipping_config['pay_fee'])) {
            if (strpos($shipping_config['pay_fee'], '%') !== false) {
                $row['pay_fee'] = floatval($shipping_config['pay_fee']) . '%';
            } else {
                $row['pay_fee'] = floatval($shipping_config['pay_fee']);
            }
        } else {
            $row['pay_fee'] = 0.00;
        }
    }

    return $row;
}

/**
 * 计算运费
 * @param   string  $shipping_code      配送方式代码
 * @param   mix     $shipping_config    配送方式配置信息
 * @param   float   $goods_weight       商品重量
 * @param   float   $goods_amount       商品金额
 * @param   float   $goods_number       商品数量
 * @return  float   运费
 */
function shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount, $goods_number='')
{
    if (!is_array($shipping_config)) {
        $shipping_config = unserialize($shipping_config);
    }

    $filename = BASE_PATH . 'modules/shipping/' . $shipping_code . '.php';
    if (file_exists($filename)) {
        include_once($filename);

        $obj = new $shipping_code($shipping_config);

        return $obj->calculate($goods_weight, $goods_amount, $goods_number);
    } else {
        return 0;
    }
}

/**
 * 获取指定配送的保价费用
 *
 * @access  public
 * @param   string      $shipping_code  配送方式的code
 * @param   float       $goods_amount   保价金额
 * @param   mix         $insure         保价比例
 * @return  float
 */
function shipping_insure_fee($shipping_code, $goods_amount, $insure)
{
    if (strpos($insure, '%') === false) {
        /* 如果保价费用不是百分比则直接返回该数值 */
        return floatval($insure);
    } else {
        $path = BASE_PATH . 'modules/shipping/' . $shipping_code . '.php';

        if (file_exists($path)) {
            include_once($path);

            $shipping = new $shipping_code;
            $insure   = floatval($insure) / 100;

            if (method_exists($shipping, 'calculate_insure')) {
                return $shipping->calculate_insure($goods_amount, $insure);
            } else {
                return ceil($goods_amount * $insure);
            }
        } else {
            return false;
        }
    }
}

/**
 * 取得已安装的支付方式列表
 * @return  array   已安装的配送方式列表
 */
function payment_list()
{
    $global = getInstance();
    $sql = 'SELECT pay_id, pay_name ' .
            'FROM ' . $global->ecs->table('payment') .
            ' WHERE enabled = 1';

    return $global->db->getAll($sql);
}

/**
 * 取得支付方式信息
 * @param   int     $pay_id     支付方式id
 * @return  array   支付方式信息
 */
function payment_info($pay_id)
{
    $global = getInstance();
    $sql = 'SELECT * FROM ' . $global->ecs->table('payment') .
            " WHERE pay_id = '$pay_id' AND enabled = 1";

    return $global->db->getRow($sql);
}

/**
 * 获得订单需要支付的支付费用
 *
 * @access  public
 * @param   integer $payment_id
 * @param   float   $order_amount
 * @param   mix     $cod_fee
 * @return  float
 */
function pay_fee($payment_id, $order_amount, $cod_fee=null)
{
    $pay_fee = 0;
    $payment = payment_info($payment_id);
    $rate    = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

    if (strpos($rate, '%') !== false) {
        /* 支付费用是一个比例 */
        $val     = floatval($rate) / 100;
        $pay_fee = $val > 0 ? $order_amount * $val /(1- $val) : 0;
    } else {
        $pay_fee = floatval($rate);
    }

    return round($pay_fee, 2);
}

/**
 * 取得可用的支付方式列表
 * @param   bool    $support_cod        配送方式是否支持货到付款
 * @param   int     $cod_fee            货到付款手续费（当配送方式支持货到付款时才传此参数）
 * @param   int     $is_online          是否支持在线支付
 * @return  array   配送方式数组
 */
function available_payment_list($support_cod, $cod_fee = 0, $is_online = false)
{
    $global = getInstance();
    $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod' .
            ' FROM ' . $global->ecs->table('payment') .
            ' WHERE enabled = 1 ';
    if (!$support_cod) {
        $sql .= 'AND is_cod = 0 '; // 如果不支持货到付款
    }
    if ($is_online) {
        $sql .= "AND is_online = '1' ";
    }
    $sql .= 'ORDER BY pay_order'; // 排序
    $res = $global->db->query($sql);

    $pay_list = array();
    while ($row = $global->db->fetchRow($res)) {
        if ($row['is_cod'] == '1') {
            $row['pay_fee'] = $cod_fee;
        }

        $row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
        price_format($row['pay_fee'], false);
        $modules[] = $row;
    }

    include_once(BASE_PATH.'helpers/compositor.php');

    if (isset($modules)) {
        return $modules;
    }
}

/**
 * 取得包装列表
 * @return  array   包装列表
 */
function pack_list()
{
    $global = getInstance();
    $sql = 'SELECT * FROM ' . $global->ecs->table('pack') .' ORDER BY pack_fee ASC';
    $res = $global->db->query($sql);

    $list = array();
    while ($row = $global->db->fetchRow($res)) {
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
function pack_info($pack_id)
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('pack') .
            " WHERE pack_id = '$pack_id'";

    return $global->db->getRow($sql);
}

/**
 * 根据订单中的商品总额来获得包装的费用
 *
 * @access  public
 * @param   integer $pack_id
 * @param   float   $goods_amount
 * @return  float
 */
function pack_fee($pack_id, $goods_amount)
{
    $pack = pack_info($pack_id);

    $val = (floatval($pack['free_money']) <= $goods_amount && $pack['free_money'] > 0) ? 0 : floatval($pack['pack_fee']);

    return $val;
}

/**
 * 取得贺卡列表
 * @return  array   贺卡列表
 */
function card_list()
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('card') . ' ORDER BY card_fee ASC';
    $res = $global->db->query($sql);

    $list = array();
    while ($row = $global->db->fetchRow($res)) {
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
function card_info($card_id)
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('card') .
            " WHERE card_id = '$card_id'";

    return $global->db->getRow($sql);
}

/**
 * 根据订单中商品总额获得需要支付的贺卡费用
 *
 * @access  public
 * @param   integer $card_id
 * @param   float   $goods_amount
 * @return  float
 */
function card_fee($card_id, $goods_amount)
{
    $card = card_info($card_id);

    return ($card['free_money'] <= $goods_amount && $card['free_money'] > 0) ? 0 : $card['card_fee'];
}

/**
 * 取得订单信息
 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
 * @param   string  $order_sn   订单号
 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function order_info($order_id, $order_sn = '')
{
    $global = getInstance();
    /* 计算订单各种费用之和的语句 */
    $total_fee = " (goods_amount - discount + tax + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
    $order_id = intval($order_id);
    if ($order_id > 0) {
        $sql = "SELECT *, " . $total_fee . " FROM " . $global->ecs->table('order_info') .
                " WHERE order_id = '$order_id'";
    } else {
        $sql = "SELECT *, " . $total_fee . "  FROM " . $global->ecs->table('order_info') .
                " WHERE order_sn = '$order_sn'";
    }
    $order = $global->db->getRow($sql);

    /* 格式化金额字段 */
    if ($order) {
        $order['formated_goods_amount']   = price_format($order['goods_amount'], false);
        $order['formated_discount']       = price_format($order['discount'], false);
        $order['formated_tax']            = price_format($order['tax'], false);
        $order['formated_shipping_fee']   = price_format($order['shipping_fee'], false);
        $order['formated_insure_fee']     = price_format($order['insure_fee'], false);
        $order['formated_pay_fee']        = price_format($order['pay_fee'], false);
        $order['formated_pack_fee']       = price_format($order['pack_fee'], false);
        $order['formated_card_fee']       = price_format($order['card_fee'], false);
        $order['formated_total_fee']      = price_format($order['total_fee'], false);
        $order['formated_money_paid']     = price_format($order['money_paid'], false);
        $order['formated_bonus']          = price_format($order['bonus'], false);
        $order['formated_integral_money'] = price_format($order['integral_money'], false);
        $order['formated_surplus']        = price_format($order['surplus'], false);
        $order['formated_order_amount']   = price_format(abs($order['order_amount']), false);
        $order['formated_add_time']       = local_date(C('time_format'), $order['add_time']);
    }

    return $order;
}

/**
 * 判断订单是否已完成
 * @param   array   $order  订单信息
 * @return  bool
 */
function order_finished($order)
{
    return $order['order_status']  == OS_CONFIRMED &&
        ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
        ($order['pay_status']      == PS_PAYED   || $order['pay_status'] == PS_PAYING);
}

/**
 * 取得订单商品
 * @param   int     $order_id   订单id
 * @return  array   订单商品数组
 */
function order_goods($order_id)
{
    $global = getInstance();
    $sql = "SELECT rec_id, goods_id, goods_name, goods_sn, market_price, goods_number, " .
            "goods_price, goods_attr, is_real, parent_id, is_gift, " .
            "goods_price * goods_number AS subtotal, extension_code " .
            "FROM " . $global->ecs->table('order_goods') .
            " WHERE order_id = '$order_id'";

    $res = $global->db->getAll($sql);
    foreach ($res as $row) {
        if ($row['extension_code'] == 'package_buy') {
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }
        $goods_list[] = $row;
    }
    return $goods_list;
}

/**
 * 取得订单总金额
 * @param   int     $order_id   订单id
 * @param   bool    $include_gift   是否包括赠品
 * @return  float   订单总金额
 */
function order_amount($order_id, $include_gift = true)
{
    $global = getInstance();
    $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $global->ecs->table('order_goods') .
            " WHERE order_id = '$order_id'";
    if (!$include_gift) {
        $sql .= " AND is_gift = 0";
    }

    return floatval($global->db->getOne($sql));
}

/**
 * 取得某订单商品总重量和总金额（对应 cart_weight_price）
 * @param   int     $order_id   订单id
 * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
 */
function order_weight_price($order_id)
{
    $global = getInstance();
    $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
                "SUM(o.goods_price * o.goods_number) AS amount ," .
                "SUM(o.goods_number) AS number " .
            "FROM " . $global->ecs->table('order_goods') . " AS o, " .
                $global->ecs->table('goods') . " AS g " .
            "WHERE o.order_id = '$order_id' " .
            "AND o.goods_id = g.goods_id";

    $row = $global->db->getRow($sql);
    $row['weight'] = floatval($row['weight']);
    $row['amount'] = floatval($row['amount']);
    $row['number'] = intval($row['number']);

    /* 格式化重量 */
    $row['formated_weight'] = formated_weight($row['weight']);

    return $row;
}

/**
 * 获得订单中的费用信息
 *
 * @access  public
 * @param   array   $order
 * @param   array   $goods
 * @param   array   $consignee
 * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
 * @return  array
 */
function order_fee($order, $goods, $consignee)
{
    $global = getInstance();
    /* 初始化订单的扩展code */
    if (!isset($order['extension_code'])) {
        $order['extension_code'] = '';
    }

    if ($order['extension_code'] == 'group_buy') {
        $group_buy = group_buy_info($order['extension_id']);
    }

    $total  = array('real_goods_count' => 0,
                    'gift_amount'      => 0,
                    'goods_price'      => 0,
                    'market_price'     => 0,
                    'discount'         => 0,
                    'pack_fee'         => 0,
                    'card_fee'         => 0,
                    'shipping_fee'     => 0,
                    'shipping_insure'  => 0,
                    'integral_money'   => 0,
                    'bonus'            => 0,
                    'surplus'          => 0,
                    'cod_fee'          => 0,
                    'pay_fee'          => 0,
                    'tax'              => 0);
    $weight = 0;

    /* 商品总价 */
    foreach ($goods as $val) {
        /* 统计实体商品的个数 */
        if ($val['is_real']) {
            $total['real_goods_count']++;
        }

        $total['goods_price']  += $val['goods_price'] * $val['goods_number'];
        $total['market_price'] += $val['market_price'] * $val['goods_number'];
    }

    $total['saving']    = $total['market_price'] - $total['goods_price'];
    $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

    $total['goods_price_formated']  = price_format($total['goods_price'], false);
    $total['market_price_formated'] = price_format($total['market_price'], false);
    $total['saving_formated']       = price_format($total['saving'], false);

    /* 折扣 */
    if ($order['extension_code'] != 'group_buy') {
        $discount = compute_discount();
        $total['discount'] = $discount['discount'];
        if ($total['discount'] > $total['goods_price']) {
            $total['discount'] = $total['goods_price'];
        }
    }
    $total['discount_formated'] = price_format($total['discount'], false);

    /* 税额 */
    if (!empty($order['need_inv']) && $order['inv_type'] != '') {
        /* 查税率 */
        $rate = 0;
        $invoice_type = C('invoice_type');
        foreach ($invoice_type['type'] as $key => $type) {
            if ($type == $order['inv_type']) {
                $rate = floatval($invoice_type['rate'][$key]) / 100;
                break;
            }
        }
        if ($rate > 0) {
            $total['tax'] = $rate * $total['goods_price'];
        }
    }
    $total['tax_formated'] = price_format($total['tax'], false);

    /* 包装费用 */
    if (!empty($order['pack_id'])) {
        $total['pack_fee']      = pack_fee($order['pack_id'], $total['goods_price']);
    }
    $total['pack_fee_formated'] = price_format($total['pack_fee'], false);

    /* 贺卡费用 */
    if (!empty($order['card_id'])) {
        $total['card_fee']      = card_fee($order['card_id'], $total['goods_price']);
    }
    $total['card_fee_formated'] = price_format($total['card_fee'], false);

    /* 红包 */

    if (!empty($order['bonus_id'])) {
        $bonus          = bonus_info($order['bonus_id']);
        $total['bonus'] = $bonus['type_money'];
    }
    $total['bonus_formated'] = price_format($total['bonus'], false);

    /* 线下红包 */
    if (!empty($order['bonus_kill'])) {
        $bonus          = bonus_info(0, $order['bonus_kill']);
        $total['bonus_kill'] = $order['bonus_kill'];
        $total['bonus_kill_formated'] = price_format($total['bonus_kill'], false);
    }



    /* 配送费用 */
    $shipping_cod_fee = null;

    if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0) {
        $region['country']  = $consignee['country'];
        $region['province'] = $consignee['province'];
        $region['city']     = $consignee['city'];
        $region['district'] = $consignee['district'];
        $shipping_info = shipping_area_info($order['shipping_id'], $region);

        if (!empty($shipping_info)) {
            if ($order['extension_code'] == 'group_buy') {
                $weight_price = cart_weight_price(CART_GROUP_BUY_GOODS);
            } else {
                $weight_price = cart_weight_price();
            }

            // 查看购物车中是否全为免运费商品，若是则把运费赋为零
            $sql = 'SELECT count(*) FROM ' . $global->ecs->table('cart') . " WHERE  `session_id` = '" . SESS_ID. "' AND `extension_code` != 'package_buy' AND `is_shipping` = 0";
            $shipping_count = $global->db->getOne($sql);

            $total['shipping_fee'] = ($shipping_count == 0 and $weight_price['free_shipping'] == 1) ?0 :  shipping_fee($shipping_info['shipping_code'], $shipping_info['configure'], $weight_price['weight'], $total['goods_price'], $weight_price['number']);

            if (!empty($order['need_insure']) && $shipping_info['insure'] > 0) {
                $total['shipping_insure'] = shipping_insure_fee(
                    $shipping_info['shipping_code'],
                    $total['goods_price'],
                    $shipping_info['insure']
                );
            } else {
                $total['shipping_insure'] = 0;
            }

            if ($shipping_info['support_cod']) {
                $shipping_cod_fee = $shipping_info['pay_fee'];
            }
        }
    }

    $total['shipping_fee_formated']    = price_format($total['shipping_fee'], false);
    $total['shipping_insure_formated'] = price_format($total['shipping_insure'], false);

    // 购物车中的商品能享受红包支付的总额
    $bonus_amount = compute_discount_amount();
    // 红包和积分最多能支付的金额为商品总额
    $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;

    /* 计算订单总额 */
    if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0) {
        $total['amount'] = $total['goods_price'];
    } else {
        $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +
            $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];

        // 减去红包金额
        $use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
        if (isset($total['bonus_kill'])) {
            $use_bonus_kill   = min($total['bonus_kill'], $max_amount);
            $total['amount'] -=  $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
        }

        $total['bonus']   = $use_bonus;
        $total['bonus_formated'] = price_format($total['bonus'], false);

        $total['amount'] -= $use_bonus; // 还需要支付的订单金额
        $max_amount      -= $use_bonus; // 积分最多还能支付的金额
    }

    /* 余额 */
    $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
    if ($total['amount'] > 0) {
        if (isset($order['surplus']) && $order['surplus'] > $total['amount']) {
            $order['surplus'] = $total['amount'];
            $total['amount']  = 0;
        } else {
            $total['amount'] -= floatval($order['surplus']);
        }
    } else {
        $order['surplus'] = 0;
        $total['amount']  = 0;
    }
    $total['surplus'] = $order['surplus'];
    $total['surplus_formated'] = price_format($order['surplus'], false);

    /* 积分 */
    $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
    if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0) {
        $integral_money = value_of_integral($order['integral']);

        // 使用积分支付
        $use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
        $total['amount']        -= $use_integral;
        $total['integral_money'] = $use_integral;
        $order['integral']       = integral_of_value($use_integral);
    } else {
        $total['integral_money'] = 0;
        $order['integral']       = 0;
    }
    $total['integral'] = $order['integral'];
    $total['integral_formated'] = price_format($total['integral_money'], false);

    /* 保存订单信息 */
    $_SESSION['flow_order'] = $order;

    $se_flow_type = isset($_SESSION['flow_type']) ? $_SESSION['flow_type'] : '';
    
    /* 支付费用 */
    if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS)) {
        $total['pay_fee']      = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
    }

    $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

    $total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
    $total['amount_formated']  = price_format($total['amount'], false);

    /* 取得可以得到的积分和红包 */
    if ($order['extension_code'] == 'group_buy') {
        $total['will_get_integral'] = $group_buy['gift_integral'];
    } elseif ($order['extension_code'] == 'exchange_goods') {
        $total['will_get_integral'] = 0;
    } else {
        $total['will_get_integral'] = get_give_integral($goods);
    }
    $total['will_get_bonus']        = $order['extension_code'] == 'exchange_goods' ? 0 : price_format(get_total_bonus(), false);
    $total['formated_goods_price']  = price_format($total['goods_price'], false);
    $total['formated_market_price'] = price_format($total['market_price'], false);
    $total['formated_saving']       = price_format($total['saving'], false);

    if ($order['extension_code'] == 'exchange_goods') {
        $sql = 'SELECT SUM(eg.exchange_integral) '.
               'FROM ' . $global->ecs->table('cart') . ' AS c,' . $global->ecs->table('exchange_goods') . 'AS eg '.
               "WHERE c.goods_id = eg.goods_id AND c.session_id= '" . SESS_ID . "' " .
               "  AND c.rec_type = '" . CART_EXCHANGE_GOODS . "' " .
               '  AND c.is_gift = 0 AND c.goods_id > 0 ' .
               'GROUP BY eg.goods_id';
        $exchange_integral = $global->db->getOne($sql);
        $total['exchange_integral'] = $exchange_integral;
    }

    return $total;
}

/**
 * 修改订单
 * @param   int     $order_id   订单id
 * @param   array   $order      key => value
 * @return  bool
 */
function update_order($order_id, $order)
{
    $global = getInstance();
    return $global->db->autoExecute(
        $global->ecs->table('order_info'),
        $order,
        'UPDATE',
        "order_id = '$order_id'"
    );
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return date('YmdHis') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 取得购物车商品
 * @param   int     $type   类型：默认普通商品
 * @return  array   购物车商品数组
 */
function cart_goods($type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    $sql = "SELECT rec_id, user_id, goods_id, goods_name, goods_sn, goods_number, " .
            "market_price, goods_price, goods_attr, is_real, extension_code, parent_id, is_gift, is_shipping, " .
            "goods_price * goods_number AS subtotal " .
            "FROM " . $global->ecs->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '$type'";

    $arr = $global->db->getAll($sql);

    /* 格式化价格及礼包商品 */
    foreach ($arr as $key => $value) {
        $arr[$key]['formated_market_price'] = price_format($value['market_price'], false);
        $arr[$key]['formated_goods_price']  = price_format($value['goods_price'], false);
        $arr[$key]['formated_subtotal']     = price_format($value['subtotal'], false);

        if ($value['extension_code'] == 'package_buy') {
            $arr[$key]['package_goods_list'] = get_package_goods($value['goods_id']);
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
function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    $sql = "SELECT SUM(goods_price * goods_number) " .
            " FROM " . $global->ecs->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '$type' ";

    if (!$include_gift) {
        $sql .= ' AND is_gift = 0 AND goods_id > 0';
    }

    return floatval($global->db->getOne($sql));
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
function cart_goods_exists($id, $spec, $type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    /* 检查该商品是否已经存在在购物车中 */
    $sql = "SELECT COUNT(*) FROM " .$global->ecs->table('cart').
            "WHERE session_id = '" .SESS_ID. "' AND goods_id = '$id' ".
            "AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
            "AND rec_type = '$type'";

    return ($global->db->getOne($sql) > 0);
}

/**
 * 获得购物车中商品的总重量、总价格、总数量
 *
 * @access  public
 * @param   int     $type   类型：默认普通商品
 * @return  array
 */
function cart_weight_price($type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    $package_row['weight'] = 0;
    $package_row['amount'] = 0;
    $package_row['number'] = 0;

    $packages_row['free_shipping'] = 1;

    /* 计算超值礼包内商品的相关配送参数 */
    $sql = 'SELECT goods_id, goods_number, goods_price FROM ' . $global->ecs->table('cart') . " WHERE extension_code = 'package_buy' AND session_id = '" . SESS_ID . "'";
    $row = $global->db->getAll($sql);

    if ($row) {
        $packages_row['free_shipping'] = 0;
        $free_shipping_count = 0;

        foreach ($row as $val) {
            // 如果商品全为免运费商品，设置一个标识变量
            $sql = 'SELECT count(*) FROM ' .
                    $global->ecs->table('package_goods') . ' AS pg, ' .
                    $global->ecs->table('goods') . ' AS g ' .
                    "WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";
            $shipping_count = $global->db->getOne($sql);

            if ($shipping_count > 0) {
                // 循环计算每个超值礼包商品的重量和数量，注意一个礼包中可能包换若干个同一商品
                $sql = 'SELECT SUM(g.goods_weight * pg.goods_number) AS weight, ' .
                    'SUM(pg.goods_number) AS number FROM ' .
                    $global->ecs->table('package_goods') . ' AS pg, ' .
                    $global->ecs->table('goods') . ' AS g ' .
                    "WHERE g.goods_id = pg.goods_id AND g.is_shipping = 0 AND pg.package_id = '"  . $val['goods_id'] . "'";

                $goods_row = $global->db->getRow($sql);
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
    $sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
                    'SUM(c.goods_price * c.goods_number) AS amount, ' .
                    'SUM(c.goods_number) AS number '.
                'FROM ' . $global->ecs->table('cart') . ' AS c '.
                'LEFT JOIN ' . $global->ecs->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
                "WHERE c.session_id = '" . SESS_ID . "' " .
                "AND rec_type = '$type' AND g.is_shipping = 0 AND c.extension_code != 'package_buy'";
    $row = $global->db->getRow($sql);

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
function addto_cart($goods_id, $num = 1, $spec = array(), $parent = 0)
{
    $global = getInstance();
    $global->err->clean();
    $_parent_id = $parent;

    /* 取得商品信息 */
    $sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, g.is_real, ".
                "g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start_date, ".
                "g.promote_end_date, g.goods_weight, g.integral, g.extension_code, ".
                "g.goods_number, g.is_alone_sale, g.is_shipping,".
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price ".
            " FROM " .$global->ecs->table('goods'). " AS g ".
            " LEFT JOIN " . $global->ecs->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            " WHERE g.goods_id = '$goods_id'" .
            " AND g.is_delete = 0";
    $goods = $global->db->getRow($sql);

    if (empty($goods)) {
        $global->err->add(L('goods_not_exists'), ERR_NOT_EXISTS);

        return false;
    }

    /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件 */
    if ($parent > 0) {
        $sql = "SELECT COUNT(*) FROM " . $global->ecs->table('cart') .
                " WHERE goods_id='$parent' AND session_id='" . SESS_ID . "' AND extension_code <> 'package_buy'";
        if ($global->db->getOne($sql) == 0) {
            $global->err->add(L('no_basic_goods'), ERR_NO_BASIC_GOODS);

            return false;
        }
    }

    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0) {
        $global->err->add(L('not_on_sale'), ERR_NOT_ON_SALE);

        return false;
    }

    /* 不是配件时检查是否允许单独销售 */
    if (empty($parent) && $goods['is_alone_sale'] == 0) {
        $global->err->add(L('cannt_alone_sale'), ERR_CANNT_ALONE_SALE);

        return false;
    }

    /* 如果商品有规格则取规格商品信息 配件除外 */
    $sql = "SELECT * FROM " .$global->ecs->table('products'). " WHERE goods_id = '$goods_id' LIMIT 0, 1";
    $prod = $global->db->getRow($sql);

    if (is_spec($spec) && !empty($prod)) {
        $product_info = get_products_info($goods_id, $spec);
    }
    if (empty($product_info)) {
        $product_info = array('product_number' => '', 'product_id' => 0);
    }

    /* 检查：库存 */
    if (C('use_storage') == 1) {
        //检查：商品购买数量是否大于总库存
        if ($num > $goods['goods_number']) {
            $global->err->add(sprintf(L('shortage'), $goods['goods_number']), ERR_OUT_OF_STOCK);

            return false;
        }

        //商品存在规格 是货品 检查该货品库存
        if (is_spec($spec) && !empty($prod)) {
            if (!empty($spec)) {
                /* 取规格的货品库存 */
                if ($num > $product_info['product_number']) {
                    $global->err->add(sprintf(L('shortage'), $product_info['product_number']), ERR_OUT_OF_STOCK);
    
                    return false;
                }
            }
        }
    }

    /* 计算商品的促销价格 */
    $spec_price             = spec_price($spec);
    $goods_price            = get_final_price($goods_id, $num, true, $spec);
    $goods['market_price'] += $spec_price;
    $goods_attr             = get_goods_attr_info($spec);
    $goods_attr_id          = join(',', $spec);

    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($goods['goods_sn']),
        'product_id'    => $product_info['product_id'],
        'goods_name'    => addslashes($goods['goods_name']),
        'market_price'  => $goods['market_price'],
        'goods_attr'    => addslashes($goods_attr),
        'goods_attr_id' => $goods_attr_id,
        'is_real'       => $goods['is_real'],
        'extension_code'=> $goods['extension_code'],
        'is_gift'       => 0,
        'is_shipping'   => $goods['is_shipping'],
        'rec_type'      => CART_GENERAL_GOODS
    );

    /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
    /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
    /* 受此优惠 */
    $basic_list = array();
    $sql = "SELECT parent_id, goods_price " .
            "FROM " . $global->ecs->table('group_goods') .
            " WHERE goods_id = '$goods_id'" .
            " AND goods_price < '$goods_price'" .
            " AND parent_id = '$_parent_id'" .
            " ORDER BY goods_price";
    $res = $global->db->query($sql);
    while ($row = $global->db->fetchRow($res)) {
        $basic_list[$row['parent_id']] = $row['goods_price'];
    }

    /* 取得购物车中该商品每个基本件的数量 */
    $basic_count_list = array();
    if ($basic_list) {
        $sql = "SELECT goods_id, SUM(goods_number) AS count " .
                "FROM " . $global->ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND parent_id = 0" .
                " AND extension_code <> 'package_buy' " .
                " AND goods_id " . db_create_in(array_keys($basic_list)) .
                " GROUP BY goods_id";
        $res = $global->db->query($sql);
        while ($row = $global->db->fetchRow($res)) {
            $basic_count_list[$row['goods_id']] = $row['count'];
        }
    }

    /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
    /* 一个基本件对应一个该商品配件 */
    if ($basic_count_list) {
        $sql = "SELECT parent_id, SUM(goods_number) AS count " .
                "FROM " . $global->ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "'" .
                " AND goods_id = '$goods_id'" .
                " AND extension_code <> 'package_buy' " .
                " AND parent_id " . db_create_in(array_keys($basic_count_list)) .
                " GROUP BY parent_id";
        $res = $global->db->query($sql);
        while ($row = $global->db->fetchRow($res)) {
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
        $parent['goods_price']  = max($fitting_price, 0) + $spec_price; //允许该配件优惠价格为0
        $parent['goods_number'] = min($num, $basic_count_list[$parent_id]);
        $parent['parent_id']    = $parent_id;

        /* 添加 */
        $global->db->autoExecute($global->ecs->table('cart'), $parent, 'INSERT');

        /* 改变数量 */
        $num -= $parent['goods_number'];
    }

    /* 如果数量不为0，作为基本件插入 */
    if ($num > 0) {
        /* 检查该商品是否已经存在在购物车中 */
        $sql = "SELECT goods_number FROM " .$global->ecs->table('cart').
                " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
                " AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
                " AND extension_code <> 'package_buy' " .
                " AND rec_type = 'CART_GENERAL_GOODS'";

        $row = $global->db->getRow($sql);

        if ($row) { //如果购物车已经有此物品，则更新
            $num += $row['goods_number'];
            if (is_spec($spec) && !empty($prod)) {
                $goods_storage=$product_info['product_number'];
            } else {
                $goods_storage=$goods['goods_number'];
            }
            if (C('use_storage') == 0 || $num <= $goods_storage) {
                $goods_price = get_final_price($goods_id, $num, true, $spec);
                $sql = "UPDATE " . $global->ecs->table('cart') . " SET goods_number = '$num'" .
                       " , goods_price = '$goods_price'".
                       " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$goods_id' ".
                       " AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
                       " AND extension_code <> 'package_buy' " .
                       "AND rec_type = 'CART_GENERAL_GOODS'";
                $global->db->query($sql);
            } else {
                $global->err->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);

                return false;
            }
        } else { //购物车没有此物品，则插入
            $goods_price = get_final_price($goods_id, $num, true, $spec);
            $parent['goods_price']  = max($goods_price, 0);
            $parent['goods_number'] = $num;
            $parent['parent_id']    = 0;
            $global->db->autoExecute($global->ecs->table('cart'), $parent, 'INSERT');
        }
    }

    /* 把赠品删除 */
    $sql = "DELETE FROM " . $global->ecs->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
    $global->db->query($sql);

    return true;
}

/**
 * 清空购物车
 * @param   int     $type   类型：默认普通商品
 */
function clear_cart($type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    $sql = "DELETE FROM " . $global->ecs->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND rec_type = '$type'";
    $global->db->query($sql);
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
function get_goods_attr_info($arr, $type = 'pice')
{
    $global = getInstance();
    $attr   = '';

    if (!empty($arr)) {
        $fmt = "%s:%s[%s] \n";

        $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price ".
                "FROM ".$global->ecs->table('goods_attr')." AS ga, ".
                    $global->ecs->table('attribute')." AS a ".
                "WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";
        $res = $global->db->query($sql);

        while ($row = $global->db->fetchRow($res)) {
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
function user_info($user_id)
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('users') .
            " WHERE user_id = '$user_id'";
    $user = $global->db->getRow($sql);

    unset($user['question']);
    unset($user['answer']);

    /* 格式化帐户余额 */
    if ($user) {
//        if ($user['user_money'] < 0)
//        {
//            $user['user_money'] = 0;
//        }
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
function update_user($user_id, $user)
{
    $global = getInstance();
    return $global->db->autoExecute(
        $global->ecs->table('users'),
        $user,
        'UPDATE',
        "user_id = '$user_id'"
    );
}

/**
 * 取得用户地址列表
 * @param   int     $user_id    用户id
 * @return  array
 */
function address_list($user_id)
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('user_address') .
            " WHERE user_id = '$user_id'";

    return $global->db->getAll($sql);
}

/**
 * 取得用户地址信息
 * @param   int     $address_id     地址id
 * @return  array
 */
function address_info($address_id)
{
    $global = getInstance();
    $sql = "SELECT * FROM " . $global->ecs->table('user_address') .
            " WHERE address_id = '$address_id'";

    return $global->db->getRow($sql);
}

/**
 * 取得用户当前可用红包
 * @param   int     $user_id        用户id
 * @param   float   $goods_amount   订单商品金额
 * @return  array   红包数组
 */
function user_bonus($user_id, $goods_amount = 0)
{
    $global = getInstance();
    $day    = getdate();
    $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    $sql = "SELECT t.type_id, t.type_name, t.type_money, b.bonus_id " .
            "FROM " . $global->ecs->table('bonus_type') . " AS t," .
                $global->ecs->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id " .
            "AND t.use_start_date <= '$today' " .
            "AND t.use_end_date >= '$today' " .
            "AND t.min_goods_amount <= '$goods_amount' " .
            "AND b.user_id<>0 " .
            "AND b.user_id = '$user_id' " .
            "AND b.order_id = 0";
    return $global->db->getAll($sql);
}

/**
 * 取得红包信息
 * @param   int     $bonus_id   红包id
 * @param   string  $bonus_sn   红包序列号
 * @param   array   红包信息
 */
function bonus_info($bonus_id, $bonus_sn = '')
{
    $global = getInstance();
    $sql = "SELECT t.*, b.* " .
            "FROM " . $global->ecs->table('bonus_type') . " AS t," .
                $global->ecs->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id ";
    if ($bonus_id > 0) {
        $sql .= "AND b.bonus_id = '$bonus_id'";
    } else {
        $sql .= "AND b.bonus_sn = '$bonus_sn'";
    }

    return $global->db->getRow($sql);
}

/**
 * 检查红包是否已使用
 * @param   int $bonus_id   红包id
 * @return  bool
 */
function bonus_used($bonus_id)
{
    $global = getInstance();
    $sql = "SELECT order_id FROM " . $global->ecs->table('user_bonus') .
            " WHERE bonus_id = '$bonus_id'";

    return  $global->db->getOne($sql) > 0;
}

/**
 * 设置红包为已使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 */
function use_bonus($bonus_id, $order_id)
{
    $global = getInstance();
    $sql = "UPDATE " . $global->ecs->table('user_bonus') .
            " SET order_id = '$order_id', used_time = '" . gmtime() . "' " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $global->db->query($sql);
}

/**
 * 设置红包为未使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 */
function unuse_bonus($bonus_id)
{
    $global = getInstance();
    $sql = "UPDATE " . $global->ecs->table('user_bonus') .
            " SET order_id = 0, used_time = 0 " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $global->db->query($sql);
}

/**
 * 计算积分的价值（能抵多少钱）
 * @param   int     $integral   积分
 * @return  float   积分价值
 */
function value_of_integral($integral)
{
    $scale = floatval(C('integral_scale'));

    return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
}

/**
 * 计算指定的金额需要多少积分
 *
 * @access  public
 * @param   integer $value  金额
 * @return  void
 */
function integral_of_value($value)
{
    $scale = floatval(C('integral_scale'));

    return $scale > 0 ? round($value / $scale * 100) : 0;
}

/**
 * 订单退款
 * @param   array   $order          订单
 * @param   int     $refund_type    退款方式 1 到帐户余额 2 到退款申请（先到余额，再申请提款） 3 不处理
 * @param   string  $refund_note    退款说明
 * @param   float   $refund_amount  退款金额（如果为0，取订单已付款金额）
 * @return  bool
 */
function order_refund($order, $refund_type, $refund_note, $refund_amount = 0)
{
    $global = getInstance();
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
        include_once(BASE_PATH . 'languages/' .C('lang'). '/admin/order.php');
        $change_desc = sprintf(L('order_refund'), $order['order_sn']);
    }

    /* 处理退款 */
    if (1 == $refund_type) {
        log_account_change($user_id, $amount, 0, 0, 0, $change_desc);

        return true;
    } elseif (2 == $refund_type) {
        /* 如果非匿名，退回余额 */
        if ($user_id > 0) {
            log_account_change($user_id, $amount, 0, 0, 0, $change_desc);
        }

        /* user_account 表增加提款申请记录 */
        $account = array(
            'user_id'      => $user_id,
            'amount'       => (-1) * $amount,
            'add_time'     => gmtime(),
            'user_note'    => $refund_note,
            'process_type' => SURPLUS_RETURN,
            'admin_user'   => $_SESSION['admin_name'],
            'admin_note'   => sprintf(L('order_refund'), $order['order_sn']),
            'is_paid'      => 0
        );
        $global->db->autoExecute($global->ecs->table('user_account'), $account, 'INSERT');

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
function get_cart_goods()
{
    $global = getInstance();
    /* 初始化 */
    $goods_list = array();
    $total = array(
        'goods_price'  => 0, // 本店售价合计（有格式）
        'market_price' => 0, // 市场售价合计（有格式）
        'saving'       => 0, // 节省金额（有格式）
        'save_rate'    => 0, // 节省百分比
        'goods_amount' => 0, // 本店售价合计（无格式）
        'total_number' => 0
    );

    /* 循环、统计 */
    $sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
            " FROM " . $global->ecs->table('cart') . " " .
            " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " ORDER BY pid, parent_id";
    $res = $global->db->query($sql);

    /* 用于统计购物车中实体商品和虚拟商品的个数 */
    $virtual_goods_count = 0;
    $real_goods_count    = 0;

    while ($row = $global->db->fetchRow($res)) {
        $total['goods_price']  += $row['goods_price'] * $row['goods_number'];
        $total['market_price'] += $row['market_price'] * $row['goods_number'];

        $row['subtotal']     = price_format($row['goods_price'] * $row['goods_number'], false);
        $row['goods_price']  = price_format($row['goods_price'], false);
        $row['market_price'] = price_format($row['market_price'], false);

        /* 统计实体商品和虚拟商品的个数 */
        if ($row['is_real']) {
            $real_goods_count++;
        } else {
            $virtual_goods_count++;
        }

        /* 查询规格 */
        if (trim($row['goods_attr']) != '') {
            $row['goods_attr']=addslashes($row['goods_attr']);
            $sql = "SELECT attr_value FROM " . $global->ecs->table('goods_attr') . " WHERE goods_attr_id " .
            db_create_in($row['goods_attr']);
            $attr_list = $global->db->getCol($sql);
            foreach ($attr_list as $attr) {
                $row['goods_name'] .= ' [' . $attr . '] ';
            }
        }
        /* 增加是否在购物车里显示商品图 */
        if ((C('show_goods_in_cart') == "2" || C('show_goods_in_cart') == "3") && $row['extension_code'] != 'package_buy') {
            $goods_thumb = $global->db->getOne("SELECT `goods_thumb` FROM " . $global->ecs->table('goods') . " WHERE `goods_id`='{$row['goods_id']}'");
            $row['goods_thumb'] = get_image_path($row['goods_id'], $goods_thumb, true);
        }
        if ($row['extension_code'] == 'package_buy') {
            $row['package_goods_list'] = get_package_goods($row['goods_id']);
        }
        $goods_list[] = $row;
    }
    $total['goods_amount'] = $total['goods_price'];
    $total['saving']       = price_format($total['market_price'] - $total['goods_price'], false);
    if ($total['market_price'] > 0) {
        $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
        100 / $total['market_price']).'%' : 0;
    }
    $total['goods_price']  = price_format($total['goods_price'], false);
    $total['market_price'] = price_format($total['market_price'], false);
    $total['real_goods_count']    = $real_goods_count;
    $total['virtual_goods_count'] = $virtual_goods_count;

    return array('goods_list' => $goods_list, 'total' => $total);
}

/**
 * 取得收货人信息
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee($user_id)
{
    $global = getInstance();

    if (isset($_SESSION['flow_consignee'])) {
        /* 如果存在session，则直接返回session中的收货人信息 */

        return $_SESSION['flow_consignee'];
    } else {
        /* 如果不存在，则取得用户的默认收货人信息 */
        $arr = array();

        if ($user_id > 0) {
            /* 取默认地址 */
            $sql = "SELECT ua.*".
                    " FROM " . $global->ecs->table('user_address') . "AS ua, ".$global->ecs->table('users').' AS u '.
                    " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";

            $arr = $global->db->getRow($sql);
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
function exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS)
{
    $global = getInstance();
    if ($order_id <= 0) {
        $sql = "SELECT COUNT(*) FROM " . $global->ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' AND is_real = 1 " .
                "AND rec_type = '$flow_type'";
    } else {
        $sql = "SELECT COUNT(*) FROM " . $global->ecs->table('order_goods') .
                " WHERE order_id = '$order_id' AND is_real = 1";
    }

    return $global->db->getOne($sql) > 0;
}

/**
 * 检查收货人信息是否完整
 * @param   array   $consignee  收货人信息
 * @param   int     $flow_type  购物流程类型
 * @return  bool    true 完整 false 不完整
 */
function check_consignee_info($consignee, $flow_type)
{
    if (exist_real_goods(0, $flow_type)) {
        /* 如果存在实体商品 */
        $res = !empty($consignee['consignee']) &&
            !empty($consignee['country']) &&
            !empty($consignee['email']) &&
            !empty($consignee['tel']);

        if ($res) {
            if (empty($consignee['province'])) {
                /* 没有设置省份，检查当前国家下面有没有设置省份 */
                $pro = get_regions(1, $consignee['country']);
                $res = empty($pro);
            } elseif (empty($consignee['city'])) {
                /* 没有设置城市，检查当前省下面有没有城市 */
                $city = get_regions(2, $consignee['province']);
                $res = empty($city);
            } elseif (empty($consignee['district'])) {
                $dist = get_regions(3, $consignee['city']);
                $res = empty($dist);
            }
        }

        return $res;
    } else {
        /* 如果不存在实体商品 */
        return !empty($consignee['consignee']) &&
            !empty($consignee['email']) &&
            !empty($consignee['tel']);
    }
}

/**
 * 获得上一次用户采用的支付和配送方式
 *
 * @access  public
 * @return  void
 */
function last_shipping_and_payment()
{
    $global = getInstance();
    $sql = "SELECT shipping_id, pay_id " .
            " FROM " . $global->ecs->table('order_info') .
            " WHERE user_id = '$_SESSION[user_id]' " .
            " ORDER BY order_id DESC LIMIT 1";
    $row = $global->db->getRow($sql);

    if (empty($row)) {
        /* 如果获得是一个空数组，则返回默认值 */
        $row = array('shipping_id' => 0, 'pay_id' => 0);
    }

    return $row;
}

/**
 * 取得当前用户应该得到的红包总额
 */
function get_total_bonus()
{
    $global = getInstance();
    $day    = getdate();
    $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    /* 按商品发的红包 */
    $sql = "SELECT SUM(c.goods_number * t.type_money)" .
            "FROM " . $global->ecs->table('cart') . " AS c, "
                    . $global->ecs->table('bonus_type') . " AS t, "
                    . $global->ecs->table('goods') . " AS g " .
            "WHERE c.session_id = '" . SESS_ID . "' " .
            "AND c.is_gift = 0 " .
            "AND c.goods_id = g.goods_id " .
            "AND g.bonus_type_id = t.type_id " .
            "AND t.send_type = '" . SEND_BY_GOODS . "' " .
            "AND t.send_start_date <= '$today' " .
            "AND t.send_end_date >= '$today' " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
    $goods_total = floatval($global->db->getOne($sql));

    /* 取得购物车中非赠品总金额 */
    $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $global->ecs->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            " AND is_gift = 0 " .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $amount = floatval($global->db->getOne($sql));

    /* 按订单发的红包 */
    $sql = "SELECT FLOOR('$amount' / min_amount) * type_money " .
            "FROM " . $global->ecs->table('bonus_type') .
            " WHERE send_type = '" . SEND_BY_ORDER . "' " .
            " AND send_start_date <= '$today' " .
            "AND send_end_date >= '$today' " .
            "AND min_amount > 0 ";
    $order_total = floatval($global->db->getOne($sql));

    return $goods_total + $order_total;
}

/**
 * 处理红包（下订单时设为使用，取消（无效，退货）订单时设为未使用
 * @param   int     $bonus_id   红包编号
 * @param   int     $order_id   订单号
 * @param   int     $is_used    是否使用了
 */
function change_user_bonus($bonus_id, $order_id, $is_used = true)
{
    $global = getInstance();
    if ($is_used) {
        $sql = 'UPDATE ' . $global->ecs->table('user_bonus') . ' SET ' .
                'used_time = ' . gmtime() . ', ' .
                "order_id = '$order_id' " .
                "WHERE bonus_id = '$bonus_id'";
    } else {
        $sql = 'UPDATE ' . $global->ecs->table('user_bonus') . ' SET ' .
                'used_time = 0, ' .
                'order_id = 0 ' .
                "WHERE bonus_id = '$bonus_id'";
    }
    $global->db->query($sql);
}

/**
 * 获得订单信息
 *
 * @access  private
 * @return  array
 */
function flow_order_info()
{
    $order = isset($_SESSION['flow_order']) ? $_SESSION['flow_order'] : array();

    /* 初始化配送和支付方式 */
    if (!isset($order['shipping_id']) || !isset($order['pay_id'])) {
        /* 如果还没有设置配送和支付 */
        if ($_SESSION['user_id'] > 0) {
            /* 用户已经登录了，则获得上次使用的配送和支付 */
            $arr = last_shipping_and_payment();

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
function merge_order($from_order_sn, $to_order_sn)
{
    $global = getInstance();
    /* 订单号不能为空 */
    if (trim($from_order_sn) == '' || trim($to_order_sn) == '') {
        return L('order_sn_not_null');
    }

    /* 订单号不能相同 */
    if ($from_order_sn == $to_order_sn) {
        return L('two_order_sn_same');
    }

    /* 取得订单信息 */
    $from_order = order_info(0, $from_order_sn);
    $to_order   = order_info(0, $to_order_sn);

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
    $order['order_id']  = '';
    $order['add_time']  = gmtime();

    // 合并商品总额
    $order['goods_amount'] += $from_order['goods_amount'];

    // 合并折扣
    $order['discount'] += $from_order['discount'];

    if ($order['shipping_id'] > 0) {
        // 重新计算配送费用
        $weight_price       = order_weight_price($to_order['order_id']);
        $from_weight_price  = order_weight_price($from_order['order_id']);
        $weight_price['weight'] += $from_weight_price['weight'];
        $weight_price['amount'] += $from_weight_price['amount'];
        $weight_price['number'] += $from_weight_price['number'];

        $region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
        $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);

        $order['shipping_fee'] = shipping_fee(
            $shipping_area['shipping_code'],
            unserialize($shipping_area['configure']),
            $weight_price['weight'],
            $weight_price['amount'],
            $weight_price['number']
        );

        // 如果保价了，重新计算保价费
        if ($order['insure_fee'] > 0) {
            $order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
        }
    }

    // 重新计算包装费、贺卡费
    if ($order['pack_id'] > 0) {
        $pack = pack_info($order['pack_id']);
        $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
    }
    if ($order['card_id'] > 0) {
        $card = card_info($order['card_id']);
        $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
    }

    // 红包不变，合并积分、余额、已付款金额
    $order['integral']      += $from_order['integral'];
    $order['integral_money'] = value_of_integral($order['integral']);
    $order['surplus']       += $from_order['surplus'];
    $order['money_paid']    += $from_order['money_paid'];

    // 计算应付款金额（不包括支付费用）
    $order['order_amount'] = $order['goods_amount'] - $order['discount']
                           + $order['shipping_fee']
                           + $order['insure_fee']
                           + $order['pack_fee']
                           + $order['card_fee']
                           - $order['bonus']
                           - $order['integral_money']
                           - $order['surplus']
                           - $order['money_paid'];

    // 重新计算支付费
    if ($order['pay_id'] > 0) {
        // 货到付款手续费
        $cod_fee          = $shipping_area ? $shipping_area['pay_fee'] : 0;
        $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

        // 应付款金额加上支付费
        $order['order_amount'] += $order['pay_fee'];
    }

    /* 插入订单表 */
    do {
        $order['order_sn'] = get_order_sn();
        if ($global->db->autoExecute($global->ecs->table('order_info'), addslashes_deep($order), 'INSERT')) {
            break;
        } else {
            if ($global->db->errno() != 1062) {
                die($global->db->errorMsg());
            }
        }
    } while (true); // 防止订单号重复

    /* 订单号 */
    $order_id = $global->db->insert_id();

    /* 更新订单商品 */
    $sql = 'UPDATE ' . $global->ecs->table('order_goods') .
            " SET order_id = '$order_id' " .
            "WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $global->db->query($sql);

    include_once(BASE_PATH . 'helpers/clips_helper.php');
    /* 插入支付日志 */
    insert_pay_log($order_id, $order['order_amount'], PAY_ORDER);

    /* 删除原订单 */
    $sql = 'DELETE FROM ' . $global->ecs->table('order_info') .
            " WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $global->db->query($sql);

    /* 删除原订单支付日志 */
    $sql = 'DELETE FROM ' . $global->ecs->table('pay_log') .
            " WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $global->db->query($sql);

    /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
    if ($from_order['bonus_id'] > 0) {
        unuse_bonus($from_order['bonus_id']);
    }

    /* 返回成功 */
    return true;
}

/**
 * 查询配送区域属于哪个办事处管辖
 * @param   array   $regions    配送区域（1、2、3、4级按顺序）
 * @return  int     办事处id，可能为0
 */
function get_agency_by_regions($regions)
{
    $global = getInstance();
    if (!is_array($regions) || empty($regions)) {
        return 0;
    }

    $arr = array();
    $sql = "SELECT region_id, agency_id " .
            "FROM " . $global->ecs->table('region') .
            " WHERE region_id " . db_create_in($regions) .
            " AND region_id > 0 AND agency_id > 0";
    $res = $global->db->query($sql);
    while ($row = $global->db->fetchRow($res)) {
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
 * 获取配送插件的实例
 * @param   int   $shipping_id    配送插件ID
 * @return  object     配送插件对象实例
 */
function &get_shipping_object($shipping_id)
{
    $shipping  = shipping_info($shipping_id);
    if (!$shipping) {
        $object = new stdClass();
        return $object;
    }

    $file_path = BASE_PATH.'modules/shipping/' . $shipping['shipping_code'] . '.php';

    include_once($file_path);

    $object = new $shipping['shipping_code'];
    return $object;
}

/**
 * 改变订单中商品库存
 * @param   int     $order_id   订单号
 * @param   bool    $is_dec     是否减少库存
 * @param   bool    $storage     减库存的时机，1，下订单时；0，发货时；
 */
function change_order_goods_storage($order_id, $is_dec = true, $storage = 0)
{
    $global = getInstance();
    /* 查询订单商品信息 */
    switch ($storage) {
        case 0:
            $sql = "SELECT goods_id, SUM(send_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $global->ecs->table('order_goods') .
                    " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
        break;

        case 1:
            $sql = "SELECT goods_id, SUM(goods_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $global->ecs->table('order_goods') .
                    " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
        break;
    }

    $res = $global->db->query($sql);
    while ($row = $global->db->fetchRow($res)) {
        if ($row['extension_code'] != "package_buy") {
            if ($is_dec) {
                change_goods_storage($row['goods_id'], $row['product_id'], - $row['num']);
            } else {
                change_goods_storage($row['goods_id'], $row['product_id'], $row['num']);
            }
            $global->db->query($sql);
        } else {
            $sql = "SELECT goods_id, goods_number" .
                   " FROM " . $global->ecs->table('package_goods') .
                   " WHERE package_id = '" . $row['goods_id'] . "'";
            $res_goods = $global->db->query($sql);
            while ($row_goods = $global->db->fetchRow($res_goods)) {
                $sql = "SELECT is_real" .
                   " FROM " . $global->ecs->table('goods') .
                   " WHERE goods_id = '" . $row_goods['goods_id'] . "'";
                $real_goods = $global->db->query($sql);
                $is_goods = $global->db->fetchRow($real_goods);

                if ($is_dec) {
                    change_goods_storage($row_goods['goods_id'], $row['product_id'], - ($row['num'] * $row_goods['goods_number']));
                } elseif ($is_goods['is_real']) {
                    change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']));
                }
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
function change_goods_storage($good_id, $product_id, $number = 0)
{
    $global = getInstance();
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
        $sql = "UPDATE " . $global->ecs->table('products') ."
                SET product_number = product_number $number
                WHERE goods_id = '$good_id'
                AND product_id = '$product_id'
                LIMIT 1";
        $products_query = $global->db->query($sql);
    }

    /* 处理商品库存 */
    $sql = "UPDATE " . $global->ecs->table('goods') ."
            SET goods_number = goods_number $number
            WHERE goods_id = '$good_id'
            LIMIT 1";
    $query = $global->db->query($sql);

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
function payment_id_list($is_cod)
{
    $global = getInstance();
    $sql = "SELECT pay_id FROM " . $global->ecs->table('payment');
    if ($is_cod) {
        $sql .= " WHERE is_cod = 1";
    } else {
        $sql .= " WHERE is_cod = 0";
    }

    return $global->db->getCol($sql);
}

/**
 * 生成查询订单的sql
 * @param   string  $type   类型
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_query_sql($type = 'finished', $alias = '')
{
    /* 已完成订单 */
    if ($type == 'finished') {
        return " AND {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
               " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) .
               " AND {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " ";
    }
    /* 待发货订单 */
    elseif ($type == 'await_ship') {
        return " AND   {$alias}order_status " .
                 db_create_in(array(OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART)) .
               " AND   {$alias}shipping_status " .
                 db_create_in(array(SS_UNSHIPPED, SS_PREPARING, SS_SHIPPED_ING)) .
               " AND ( {$alias}pay_status " . db_create_in(array(PS_PAYED, PS_PAYING)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(true)) . ") ";
    }
    /* 待付款订单 */
    elseif ($type == 'await_pay') {
        return " AND   {$alias}order_status " . db_create_in(array(OS_CONFIRMED, OS_SPLITED)) .
               " AND   {$alias}pay_status = '" . PS_UNPAYED . "'" .
               " AND ( {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " OR {$alias}pay_id " . db_create_in(payment_id_list(false)) . ") ";
    }
    /* 未确认订单 */
    elseif ($type == 'unconfirmed') {
        return " AND {$alias}order_status = '" . OS_UNCONFIRMED . "' ";
    }
    /* 未处理订单：用户可操作 */
    elseif ($type == 'unprocessed') {
        return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
               " AND {$alias}shipping_status = '" . SS_UNSHIPPED . "'" .
               " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
    }
    /* 未付款未发货订单：管理员可操作 */
    elseif ($type == 'unpay_unship') {
        return " AND {$alias}order_status " . db_create_in(array(OS_UNCONFIRMED, OS_CONFIRMED)) .
               " AND {$alias}shipping_status " . db_create_in(array(SS_UNSHIPPED, SS_PREPARING)) .
               " AND {$alias}pay_status = '" . PS_UNPAYED . "' ";
    }
    /* 已发货订单：不论是否付款 */
    elseif ($type == 'shipped') {
        return " AND {$alias}order_status = '" . OS_CONFIRMED . "'" .
               " AND {$alias}shipping_status " . db_create_in(array(SS_SHIPPED, SS_RECEIVED)) . " ";
    } else {
        die('函数 order_query_sql 参数错误');
    }
}

/**
 * 生成查询订单总金额的字段
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_amount_field($alias = '')
{
    return "   {$alias}goods_amount + {$alias}tax + {$alias}shipping_fee" .
           " + {$alias}insure_fee + {$alias}pay_fee + {$alias}pack_fee" .
           " + {$alias}card_fee ";
}

/**
 * 生成计算应付款金额的字段
 * @param   string  $alias  order表的别名（包括.例如 o.）
 * @return  string
 */
function order_due_field($alias = '')
{
    return order_amount_field($alias) .
            " - {$alias}money_paid - {$alias}surplus - {$alias}integral_money" .
            " - {$alias}bonus - {$alias}discount ";
}

/**
 * 计算折扣：根据购物车和优惠活动
 * @return  float   折扣
 */
function compute_discount()
{
    $global = getInstance();
    /* 查询优惠活动 */
    $now = gmtime();
    $user_rank = ',' . $_SESSION['user_rank'] . ',';
    $sql = "SELECT *" .
            "FROM " . $global->ecs->table('favourable_activity') .
            " WHERE start_time <= '$now'" .
            " AND end_time >= '$now'" .
            " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
    $favourable_list = $global->db->getAll($sql);
    if (!$favourable_list) {
        return 0;
    }

    /* 查询购物车商品 */
    $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
            "FROM " . $global->ecs->table('cart') . " AS c, " . $global->ecs->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND c.session_id = '" . SESS_ID . "' " .
            "AND c.parent_id = 0 " .
            "AND c.is_gift = 0 " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $goods_list = $global->db->getAll($sql);
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
function get_give_integral()
{
    $global = getInstance();
    $sql = "SELECT SUM(c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price))" .
                "FROM " . $global->ecs->table('cart') . " AS c, " .
                          $global->ecs->table('goods') . " AS g " .
                "WHERE c.goods_id = g.goods_id " .
                "AND c.session_id = '" . SESS_ID . "' " .
                "AND c.goods_id > 0 " .
                "AND c.parent_id = 0 " .
                "AND c.rec_type = 0 " .
                "AND c.is_gift = 0";

    return intval($global->db->getOne($sql));
}

/**
 * 取得某订单应该赠送的积分数
 * @param   array   $order  订单
 * @return  int     积分数
 */
function integral_to_give($order)
{
    $global = getInstance();
    /* 判断是否团购 */
    if ($order['extension_code'] == 'group_buy') {
        include_once(BASE_PATH . 'helpers/goods_helper.php');
        $group_buy = group_buy_info(intval($order['extension_id']));

        return array('custom_points' => $group_buy['gift_integral'], 'rank_points' => $order['goods_amount']);
    } else {
        $sql = "SELECT SUM(og.goods_number * IF(g.give_integral > -1, g.give_integral, og.goods_price)) AS custom_points, SUM(og.goods_number * IF(g.rank_integral > -1, g.rank_integral, og.goods_price)) AS rank_points " .
                "FROM " . $global->ecs->table('order_goods') . " AS og, " .
                          $global->ecs->table('goods') . " AS g " .
                "WHERE og.goods_id = g.goods_id " .
                "AND og.order_id = '$order[order_id]' " .
                "AND og.goods_id > 0 " .
                "AND og.parent_id = 0 " .
                "AND og.is_gift = 0 AND og.extension_code != 'package_buy'";

        return $global->db->getRow($sql);
    }
}

/**
 * 发红包：发货时发红包
 * @param   int     $order_id   订单号
 * @return  bool
 */
function send_order_bonus($order_id)
{
    $global = getInstance();
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);

    /* 如果有红包，统计并发送 */
    if ($bonus_list) {
        /* 用户信息 */
        $sql = "SELECT u.user_id, u.user_name, u.email " .
                "FROM " . $global->ecs->table('order_info') . " AS o, " .
                          $global->ecs->table('users') . " AS u " .
                "WHERE o.order_id = '$order_id' " .
                "AND o.user_id = u.user_id ";
        $user = $global->db->getRow($sql);

        /* 统计 */
        $count = 0;
        $money = '';
        foreach ($bonus_list as $bonus) {
            $count += $bonus['number'];
            $money .= price_format($bonus['type_money']) . ' [' . $bonus['number'] . '], ';

            /* 修改用户红包 */
            $sql = "INSERT INTO " . $global->ecs->table('user_bonus') . " (bonus_type_id, user_id) " .
                    "VALUES('$bonus[type_id]', '$user[user_id]')";
            for ($i = 0; $i < $bonus['number']; $i++) {
                if (!$global->db->query($sql)) {
                    return $global->db->errorMsg();
                }
            }
        }

        /* 如果有红包，发送邮件 */
        if ($count > 0) {
            $tpl = get_mail_template('send_bonus');
            $global->tpl->assign('user_name', $user['user_name']);
            $global->tpl->assign('count', $count);
            $global->tpl->assign('money', $money);
            $global->tpl->assign('shop_name', C('shop_name'));
            $global->tpl->assign('send_date', local_date(C('date_format')));
            $global->tpl->assign('sent_date', local_date(C('date_format')));
            $content = $global->tpl->fetch('str:' . $tpl['template_content']);
            send_mail($user['user_name'], $user['email'], $tpl['template_subject'], $content, $tpl['is_html']);
        }
    }

    return true;
}

/**
 * 返回订单发放的红包
 * @param   int     $order_id   订单id
 */
function return_order_bonus($order_id)
{
    $global = getInstance();
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);

    /* 删除 */
    if ($bonus_list) {
        /* 取得订单信息 */
        $order = order_info($order_id);
        $user_id = $order['user_id'];

        foreach ($bonus_list as $bonus) {
            $sql = "DELETE FROM " . $global->ecs->table('user_bonus') .
                    " WHERE bonus_type_id = '$bonus[type_id]' " .
                    "AND user_id = '$user_id' " .
                    "AND order_id = '0' LIMIT " . $bonus['number'];
            $global->db->query($sql);
        }
    }
}

/**
 * 取得订单应该发放的红包
 * @param   int     $order_id   订单id
 * @return  array
 */
function order_bonus($order_id)
{
    $global = getInstance();
    /* 查询按商品发的红包 */
    $day    = getdate();
    $today  = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    $sql = "SELECT b.type_id, b.type_money, SUM(o.goods_number) AS number " .
            "FROM " . $global->ecs->table('order_goods') . " AS o, " .
                      $global->ecs->table('goods') . " AS g, " .
                      $global->ecs->table('bonus_type') . " AS b " .
            " WHERE o.order_id = '$order_id' " .
            " AND o.is_gift = 0 " .
            " AND o.goods_id = g.goods_id " .
            " AND g.bonus_type_id = b.type_id " .
            " AND b.send_type = '" . SEND_BY_GOODS . "' " .
            " AND b.send_start_date <= '$today' " .
            " AND b.send_end_date >= '$today' " .
            " GROUP BY b.type_id ".
            "order by min_amount desc ";
    $list = $global->db->getAll($sql);

    /* 查询定单中非赠品总金额 */
    $amount = order_amount($order_id, false);

    /* 查询订单日期 */
    $sql = "SELECT add_time " .
            " FROM " . $global->ecs->table('order_info') .
            " WHERE order_id = '$order_id' LIMIT 1";
    $order_time = $global->db->getOne($sql);

    /* 查询按订单发的红包 */
    $sql = "SELECT type_id, type_money,min_amount " .
            "FROM " . $global->ecs->table('bonus_type') .
            "WHERE send_type = '" . SEND_BY_ORDER . "' " .
            "AND send_start_date <= '$order_time' " .
            "AND send_end_date >= '$order_time' order by min_amount desc";
    $list1 = $global->db->getAll($sql);
    $sum=$amount;
    $arrary = array();
    foreach ($list1 as $key=>$value) {
        if ($sum < $amount) {
            $array[$key]['number']=floatval(0);
            break;
        }
        $min_amount=$value['min_amount'];
        $num=floor($sum/$min_amount);
        $sum=$sum-$min_amount*$num;
        $array[$key]['number']=$num;
        unset($unm);
    }
    $arra = array();
    foreach ($list1 as $k=>$r) {
        $arra[] = array_merge($r, $array[$k]);
    }
    $list = array_merge($list, $arra);
    return $list;
}

/**
 * 计算购物车中的商品能享受红包支付的总额
 * @return  float   享受红包支付的总额
 */
function compute_discount_amount()
{
    $global = getInstance();
    /* 查询优惠活动 */
    $now = gmtime();
    $user_rank = ',' . $_SESSION['user_rank'] . ',';
    $sql = "SELECT *" .
            "FROM " . $global->ecs->table('favourable_activity') .
            " WHERE start_time <= '$now'" .
            " AND end_time >= '$now'" .
            " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
            " AND act_type " . db_create_in(array(FAT_DISCOUNT, FAT_PRICE));
    $favourable_list = $global->db->getAll($sql);
    if (!$favourable_list) {
        return 0;
    }

    /* 查询购物车商品 */
    $sql = "SELECT c.goods_id, c.goods_price * c.goods_number AS subtotal, g.cat_id, g.brand_id " .
            "FROM " . $global->ecs->table('cart') . " AS c, " . $global->ecs->table('goods') . " AS g " .
            "WHERE c.goods_id = g.goods_id " .
            "AND c.session_id = '" . SESS_ID . "' " .
            "AND c.parent_id = 0 " .
            "AND c.is_gift = 0 " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $goods_list = $global->db->getAll($sql);
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
function add_package_to_cart($package_id, $num = 1)
{
    $global = getInstance();
    $global->err->clean();

    /* 取得礼包信息 */
    $package = get_package_info($package_id);

    if (empty($package)) {
        $global->err->add(L('goods_not_exists'), ERR_NOT_EXISTS);

        return false;
    }

    /* 是否正在销售 */
    if ($package['is_on_sale'] == 0) {
        $global->err->add(L('not_on_sale'), ERR_NOT_ON_SALE);

        return false;
    }

    /* 现有库存是否还能凑齐一个礼包 */
    if (C('use_storage') == '1' && judge_package_stock($package_id)) {
        $global->err->add(sprintf(L('shortage'), 1), ERR_OUT_OF_STOCK);

        return false;
    }

    /* 检查库存 */
//    if (C('use_storage') == 1 && $num > $package['goods_number'])
//    {
//        $num = $goods['goods_number'];
//        $global->err->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);
//
//        return false;
//    }

    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_id'      => $package_id,
        'goods_sn'      => '',
        'goods_name'    => addslashes($package['package_name']),
        'market_price'  => $package['market_package'],
        'goods_price'   => $package['package_price'],
        'goods_number'  => $num,
        'goods_attr'    => '',
        'goods_attr_id' => '',
        'is_real'       => $package['is_real'],
        'extension_code'=> 'package_buy',
        'is_gift'       => 0,
        'rec_type'      => CART_GENERAL_GOODS
    );

    /* 如果数量不为0，作为基本件插入 */
    if ($num > 0) {
        /* 检查该商品是否已经存在在购物车中 */
        $sql = "SELECT goods_number FROM " .$global->ecs->table('cart').
                " WHERE session_id = '" .SESS_ID. "' AND goods_id = '" . $package_id . "' ".
                " AND parent_id = 0 AND extension_code = 'package_buy' " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";

        $row = $global->db->getRow($sql);

        if ($row) { //如果购物车已经有此物品，则更新
            $num += $row['goods_number'];
            if (C('use_storage') == 0 || $num > 0) {
                $sql = "UPDATE " . $global->ecs->table('cart') . " SET goods_number = '" . $num . "'" .
                       " WHERE session_id = '" .SESS_ID. "' AND goods_id = '$package_id' ".
                       " AND parent_id = 0 AND extension_code = 'package_buy' " .
                       " AND rec_type = '" . CART_GENERAL_GOODS . "'";
                $global->db->query($sql);
            } else {
                $global->err->add(sprintf(L('shortage'), $num), ERR_OUT_OF_STOCK);
                return false;
            }
        } else { //购物车没有此物品，则插入
            $global->db->autoExecute($global->ecs->table('cart'), $parent, 'INSERT');
        }
    }

    /* 把赠品删除 */
    $sql = "DELETE FROM " . $global->ecs->table('cart') . " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0";
    $global->db->query($sql);

    return true;
}

/**
 * 得到新发货单号
 * @return  string
 */
function get_delivery_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * 检查礼包内商品的库存
 * @return  boolen
 */
function judge_package_stock($package_id, $package_num = 1)
{
    $global = getInstance();
    $sql = "SELECT goods_id, product_id, goods_number
            FROM " . $global->ecs->table('package_goods') . "
            WHERE package_id = '" . $package_id . "'";
    $row = $global->db->getAll($sql);
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
                FROM " . $global->ecs->table('products') . " AS p, " . $global->ecs->table('package_goods') . " AS pg
                WHERE pg.product_id = p.product_id
                AND pg.package_id = '$package_id'
                AND pg.goods_number * $package_num > p.product_number
                AND p.product_id IN (" . trim($goods['product_ids'], ',') . ")";
        $row = $global->db->getAll($sql);

        if (!empty($row)) {
            return true;
        }
    }

    /* 检查商品库存 */
    if ($goods['goods_ids'] != '') {
        $sql = "SELECT g.goods_id
                FROM " . $global->ecs->table('goods') . "AS g, " . $global->ecs->table('package_goods') . " AS pg
                WHERE pg.goods_id = g.goods_id
                AND pg.goods_number * $package_num > g.goods_number
                AND pg.package_id = '" . $package_id . "'
                AND pg.goods_id IN (" . trim($goods['goods_ids'], ',') . ")";
        $row = $global->db->getAll($sql);

        if (!empty($row)) {
            return true;
        }
    }

    return false;
}


/**退换货原因
 * by ECTouch Leah
 * @param $cause_id
 */
function get_service_cause_name($cause_id)
{
    $sql = 'SELECT cause_id,cause_name ,parent_id,is_show ' .
        'FROM ' . $GLOBALS['ecs']->table('return_cause') .
        "WHERE cause_id = '$cause_id' AND is_show = 1";
    $res = $GLOBALS['db']->getRow($sql);
    return $res['cause_name'];
}

/**获取退换货原因 后期升级
 * by ECTouch Leah
 * @param $causer_id
 * @return mixed
 */
function get_service_cause($cause_id)
{
    if ($cause_id > 0) {
        $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('return_cause') . " WHERE cause_id = '$cause_id'";
        $parent_id = $GLOBALS['db']->getOne($sql);
    } else {
        $parent_id = 0;
    }
    /*
     判断当前分类中全是是否是底级分类，
     如果是取出底级分类上级分类，
     如果不是取当前分类及其下的子分类
    */
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('return_cause') . " WHERE parent_id = '$parent_id' AND is_show = 1 ";
    if ($GLOBALS['db']->getOne($sql) || $parent_id == 0) {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT cause_id,cause_name ,parent_id,is_show ' .
            'FROM ' . $GLOBALS['ecs']->table('return_cause') .
            "WHERE parent_id = '$parent_id' AND is_show = 1";
        $res = $GLOBALS['db']->getRow($sql);

        $cat_arr[$res['cause_id']]['id'] = $res['cause_id'];
        $cat_arr[$res['cause_id']]['name'] = $res['cause_name'];
        $cat_arr[$res['cause_id']]['cause_id'] = get_child_cause($res['cause_id']);
    }
    if (isset($cat_arr)) {
        return $cat_arr;
    }
}

/**
 *获取服务订单详情
 * by ECTouch Leah
 * @param $ret_id
 * @return mix
 */
function aftermarket_info($ret_id, $order_sn = '')
{
    $ret_id = intval($ret_id);
    if ($ret_id > 0) {
        $sql = "SELECT r.ret_id, r.rec_id, r.service_sn, r.goods_id, r.user_id, r.order_id, r.order_sn, r.cause_id, r.service_id, r.add_time,r.remark ,
            r.return_status, r.refund_status, r.seller_id, r.is_check, r.should_return, r.back_shipping_name, r.back_other_shipping, r.back_invoice_no, r.out_shipping_name, r.out_invoice_no, r.to_buyer,
            g.goods_thumb, g.goods_name, o.order_sn ,o.add_time,r.addressee,r.phone, r.address, d.delivery_sn, d.update_time, d.how_oos ,d.shipping_fee, d.insure_fee , d.invoice_no,
            d.email, d.sign_building , d.best_time , d.country , d.province , d.city , d.district, rg.back_num " .
            "  FROM " . $GLOBALS['ecs']->table('order_return') .
            " as r LEFT JOIN  " . $GLOBALS['ecs']->table('goods_attr') . " as ga ON r.goods_id = ga.goods_id " .
            " LEFT JOIN ". $GLOBALS['ecs']->table('return_goods'). " as rg ON r.rec_id = rg.rec_id ".
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON g.goods_id=r.goods_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " as o ON o.order_id = r.order_id" .
            " LEFT JOIN " . $GLOBALS['ecs']->table('delivery_order') . " as d ON d.order_id = o.order_id " .
            " WHERE r.ret_id = '$ret_id'";
    } else {
        $sql = "SELECT *  FROM " . $GLOBALS['ecs']->table('order_return') .
            " WHERE order_sn = '$order_sn'";
    }
    $order = $GLOBALS['db']->getRow($sql);
    $order['apply_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['apply_time']);
    $order['service_id'] = $order['service_id'];
    $order['service_name'] = get_service_type_name($order['service_id']);
    $order['return_cause'] = get_service_cause_name($order['cause_id']);
    $order['seller_name'] = get_suppliers_name($order['seller_id']);
    $order['cert_img'] = get_cert_img($order['rec_id']);
    $order['should_return'] = $order['should_return'];
    $order['return_status'] = $order['return_status'];
    $order['refund_status'] = $order['refund_status'];
    $order['back_num'] = $order['back_num'];
    $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
    $order['formated_should_return'] = price_format($order['should_return'], false);
    $order['formated_refund_status'] = $GLOBALS['_LANG']['ff'][$order['refund_status']];
    $order['formated_check_status'] = $GLOBALS['_LANG']['rc'][$order['is_check']];
    $order['region'] = get_consignee_info($order['ret_id']);
    ;

    /* 服务订单 订单状态 退款状态 审核状态 语言项 */
    if ($order['return_status'] == RF_APPLICATION && $order['is_check'] == RC_APPLY_FALSE) {
        /* 状态 ： 待审核 */
        $order['formated_return_status'] = $GLOBALS['_LANG']['wait_check'];
    } elseif ($order['return_status'] == RF_APPLICATION && $order['is_check'] == RC_APPLY_SUCCESS) {
        /* 状态 ： 审核成功 */
        $order['formated_return_status'] = $GLOBALS['_LANG']['check_success'];
    } elseif ($order['return_status'] == RF_APPLY_FALSE && $order['is_check'] == RC_APPLY_FALSE) {
        /* 状态 ： 审核失败 */
        $order['formated_return_status'] = $GLOBALS['_LANG']['check_false'];
    } elseif ($order['return_status'] == RF_CANCELED) {
        /* 状态 ： 撤销申请 */
        $order['formated_return_status'] = $GLOBALS['_LANG']['cancel'];
    } elseif ($order['refund_status'] == FF_REFUND && $order['is_check'] == RC_APPLY_SUCCESS) {
        /* 状态 ： 已退款 */
        $order['formated_return_status'] = $GLOBALS['_LANG']['refund_success'];
    } else {
        $order['formated_return_status'] = $GLOBALS['_LANG']['rf'][$order['return_status']];
    }
    return $order;
}

/**
 * 获取地址 by ECTouch Leah
 * @param type $country
 * @param type $province
 * @param type $city
 * @param type $district
 * @return type
 */
function get_consignee_info($ret_id)
{
    /* 取得区域名 */
    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
            "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
            "FROM " . $GLOBALS['ecs']->table('order_return') . " AS o " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS c ON o.country = c.region_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS p ON o.province = p.region_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS t ON o.city = t.region_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('region') . " AS d ON o.district = d.region_id " .
            "WHERE o.ret_id = '" . $ret_id . "'";
    $address_detail = $GLOBALS['db']->getOne($sql);
    
    return $address_detail;
}

/**
 * 取的退换货表单里的商品
 * by ECTouch Leah
 * @param type $rec_id
 * @return type
 */
function get_return_order_goods_list($rec_id)
{
    $sql = "select * FROM " . $GLOBALS['ecs']->table('order_goods') . " WHERE rec_id =" . $rec_id;
    $goods_list = $GLOBALS['db']->getAll($sql);

    return $goods_list;
}

/**
 * 取的订单上商品中的某一商品
 * by ECTouch Leah
 * @param type $rec_id
 */
function get_return_order_goods($rec_id)
{
    $sql = "select * FROM " . $GLOBALS['ecs']->table('return_goods') . " WHERE rec_id =" . $rec_id;
    $goods_list = $GLOBALS['db']->getRow($sql);

    return $goods_list;
}

/**
 * 获得退换货商品
 * by ECTouch Leah
 */
function get_return_goods($ret_id)
{
    $ret_id = intval($ret_id);
    $sql = "SELECT rg.* FROM " . $GLOBALS['ecs']->table('return_goods') .
        " as rg  LEFT JOIN " . $GLOBALS['ecs']->table('order_return') . "as r ON rg.rec_id = r.rec_id " .
        " WHERE r.ret_id = " . $ret_id;

    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res)) {
        if ($row['return_type'] == ST_RETURN_GOODS) {
            //退货退款
            $row['service_type'] = $GLOBALS['_LANG']['st'][$row['return_type']];
            $row['subtotal'] = price_format(($row['goods_price'] * $row['back_num']), false);
            $goods_list = array('return_goods' => $row);
        } elseif ($row['return_type'] == ST_EXCHANGE) {
            //换货
            $row['service_type'] = $GLOBALS['_LANG']['st'][$row['return_type']];
            $row['subtotal'] = price_format(($row['goods_price'] * $row['back_num']), false);
            $goods_list = array('exchange' => $row);
        }
    }
    return $goods_list;
}

/**
 * 获得退换货操作log
 * by ECTouch Leah
 * @param type $ret_id
 */
function get_return_action($ret_id)
{
    $act_list = array();
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('return_action') . " WHERE ret_id = '" . $ret_id . "'  ORDER BY log_time DESC,ret_id DESC";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        if ($row['return_status'] == RF_APPLICATION && $row['is_check'] == RC_APPLY_FALSE) {
            /* 状态 ： 待审核 */
            $row['return_status'] = $GLOBALS['_LANG']['wait_check'];
        } elseif ($row['return_status'] == RF_APPLICATION && $row['is_check'] == RC_APPLY_SUCCESS) {
            /* 状态 ： 审核成功 */
            $row['return_status'] = $GLOBALS['_LANG']['check_success'];
        } elseif ($row['return_status'] == RF_APPLY_FALSE && $row['is_check'] == RC_APPLY_FALSE) {
            /* 状态 ： 审核失败 */
            $row['return_status'] = $GLOBALS['_LANG']['check_false'];
        } elseif ($row['return_status'] == RF_CANCELED) {
            /* 状态 ： 撤销申请 */
            $row['return_status'] = $GLOBALS['_LANG']['cancel'];
        } elseif ($row['refund_status'] == FF_REFUND && $row['is_check'] == RC_APPLY_SUCCESS) {
            /* 状态 ： 已退款 */
            $row['return_status'] = $GLOBALS['_LANG']['refund_success'];
        } else {
            $row['return_status'] = $GLOBALS['_LANG']['rf'][$row['return_status']];
        }
        $row['refund_status'] = $GLOBALS['_LANG']['ff'][$row['refund_status']];
        $row['is_check'] = ($row['return_status'] == RF_APPLICATION && $row['is_check'] == RC_APPLY_FALSE) ? $GLOBALS['_LANG']['wait_check'] : $GLOBALS['_LANG']['rc'][$row['is_check']];
        $row['action_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['log_time']);
        $act_list[] = $row;
    }
    return $act_list;
}

/**
 * 相同商品退换货单 by ECTouch leah
 * @param type $ret_id
 * @param type $order_sn
 */
function return_order_info_byId($order_id, $refund = true)
{
    if (!$refund) {
        //获得唯一一个订单下申请了全部退换货的退换货订单
        $sql = " SELECT count(*) FROM " . $GLOBALS['ecs']->table('order_return') . " WHERE order_id=" . $order_id . " AND refund_status = 0";
        $res = $GLOBALS['db']->getOne($sql);
    } else {
        $sql = " SELECT * FROM " . $GLOBALS['ecs']->table('order_return') . " WHERE order_id=" . $order_id;
        $res = $GLOBALS['db']->getAll($sql);
    }

    return $res;
}


/**
 * 退货单信息
 * by  leah
 */
function return_order_info($ret_id, $order_sn = '')
{
    $ret_id = intval($ret_id);
    if ($ret_id > 0) {
        $sql = "SELECT r.* , g.goods_thumb , g.goods_name ,rg.back_num , rg.out_num, o.order_sn ,o.add_time ,  d.delivery_sn , d.update_time , d.how_oos ,d.shipping_fee, d.insure_fee , d.invoice_no,
                     d.email, d.sign_building , d.best_time , d.country , d.province , d.city , d.district " .
            "  FROM" . $GLOBALS['ecs']->table('order_return') .
            " as r LEFT JOIN  " . $GLOBALS['ecs']->table('goods_attr') . " as ga ON r.goods_id = ga.goods_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('return_goods') . " as rg ON rg.rec_id = r.rec_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " as g ON g.goods_id=r.goods_id " .
            " LEFT JOIN " . $GLOBALS['ecs']->table('order_info') . " as o ON o.order_id = r.order_id" .
            " LEFT JOIN " . $GLOBALS['ecs']->table('delivery_order') . " as d ON d.order_id = o.order_id " .
            " WHERE r.ret_id = '$ret_id'";
    } else {
        $sql = "SELECT *  FROM " . $GLOBALS['ecs']->table('order_return') .
            " WHERE order_sn = '$order_sn'";
    }
    $order = $GLOBALS['db']->getRow($sql);
    if ($order) {
        $order['attr_val'] = unserialize($order['attr_val']);
        $sql = " SELECT rg.out_num FROM " . $GLOBALS['ecs']->table('return_goods') . " as rg LEFT JOIN " .
            $GLOBALS['ecs']->table('order_return') . " as r ON rg.rec_id = r.rec_id  WHERE r.ret_id = '$ret_id' AND rg.return_type = 2";
        $back_num = $GLOBALS['db']->getAll($sql);
        if ($order['attr_val']) {
            foreach ($order['attr_val'] as $key => $value) {
                $order['attr_val'][$key]['out_num'] = $back_num [$key]['out_num'];
            }
        }
        $order['cause_id'] = $order['cause_id'];
        $order['back_num'] = $order['back_num'];
        $order['apply_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['apply_time']);
        $order['formated_update_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['update_time']);
        $order['formated_add_time'] = local_date($GLOBALS['_CFG']['time_format'], $order['add_time']);
        $order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1;
        $order['should_return'] = $order['should_return'] * $order['back_num'];
        $order['formated_should_return'] = price_format($order['should_return']);
        $order['return_status'] = $order['return_status'];
        $order['formated_return_status'] = $GLOBALS['_LANG']['rf'][$order['return_status']];
        $order['refund_status'] = $order['refund_status'];
        $order['formated_refund_status'] = $GLOBALS['_LANG']['ff'][$order['refund_status']];
        $order['address_detail'] = get_consignee_info($order['ret_id']);
        $order['return_cause'] = '';
        if ($order['cause_id']) {
            $sql = "SELECT cause_name " . 'FROM ' . $GLOBALS['ecs']->table('return_cause') . " WHERE cause_id=( SELECT parent_id FROM  " . $GLOBALS['ecs']->table('return_cause') . " WHERE cause_id = '" . $order['cause_id'] . "')";
            $parent = $GLOBALS['db']->getOne($sql);
            $sql = "SELECT c.cause_name " . 'FROM ' . $GLOBALS['ecs']->table('return_cause') . " AS c " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('return_cause') . " AS s ON s.parent_id=c.cause_id WHERE c.cause_id=" . $order['cause_id'];
            $child = $GLOBALS['db']->getOne($sql);
            $order['return_cause'] = $parent . " " . $child;
        }
        return $order;
    }
}

/**
 * 退换货退款 by ECToch Leah
 * @param $order 订单信息
 * @param $refund_type 退款类型 线上/线下
 * @param $rerefund_amount 退款金额
 * @param $refund_note 操作备注
 * @param int $refund_amount
 * @return bool
 */
function aftermarket_refund($order, $refund_type, $refund_amount, $refund_note)
{
    /* 检查参数 */
    $user_id = $order['user_id'];
    if ($user_id == 0 && $refund_type == 1) {
        die('anonymous, cannot return to account balance');
    }
    $refund_amount = $refund_amount > 0 ? $refund_amount : 0;
    if ($refund_amount <= 0) {
        return true;
    }
    if (!in_array($refund_type, array(1, 2, 3))) {
        die('invalid params');
    }
    /* 备注信息 */
    if ($refund_note) {
        $change_desc = $refund_note;
    } else {
        //include_once(ROOT_PATH . 'languages/' . $GLOBALS['_CFG']['lang'] . '/admin/aftermarket.php');
        $change_desc = '订单退款'. $order['order_sn'];
    }

    /* 处理退款 */
    if (1 == $refund_type) {
        log_account_change($user_id, $refund_amount, 0, 0, 0, $change_desc);
        return true;
    } elseif (2 == $refund_type) {
        //线下
        return true;
    } else {
        return true;
    }
}
/**
 * 退换货 用户积分退还
 * by Leah
 */
function return_surplus_integral_bonus($user_id, $return_goods_price)
{
    $sql = " SELECT pay_points  FROM " . $GLOBALS['ecs']->table('users') . " WHERE user_id=" . $user_id;
    $pay = $GLOBALS['db']->getOne($sql);
    $pay = $pay - $return_goods_price; //用户总积分-本次商品

    if ($pay > 0) {
        $sql = "UPDATE " . $GLOBALS['ecs']->table('users') . " SET pay_points =" . $pay . " WHERE user_id=" . $user_id;

        $GLOBALS['db']->query($sql);
    }
}

/**
 *  by　　Leah
 * @param type $shipping_config
 * @return type
 */
function free_price($shipping_config)
{
    $shipping_config = unserialize($shipping_config);

    $arr = array();

    if (is_array($shipping_config)) {
        foreach ($shipping_config as $key => $value) {
            foreach ($value as $k => $v) {
                $arr['configure'][$value['name']] = $value['value'];
            }
        }
    }
    return $arr;
}
/**
 * 获取凭证
 */
function get_cert_img($rec_id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('aftermarket_attachments') . " WHERE rec_id = $rec_id";
    $res = $GLOBALS['db']->query($sql);
    
    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $row['img_id'] = $row['img_id'];
        $row['img_url'] = $row['img_url'];
        $row['goods_id'] =$row['free_money'];
        $list[] = $row;
    }
    return $list;
}
//pc begin
/**
 * 查询订单商品是否已申请过服务
 * @param type $rec_id
 * @return type
 */
function check_aftermarket($rec_id)
{
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_return') . " WHERE rec_id = " . $rec_id;
    return ($GLOBALS['db']->getOne($sql));
}
/**
 * 获取订单所对应的服务类型数组
 * @param $order
 * @return array
 */
function get_service_opt($order)
{
    $service_return = get_service_info(ST_RETURN_GOODS); //退货退款
    $service_exchange = get_service_info(ST_EXCHANGE); //换货

    $time = gmtime();
    $type_list = array();
    if ($order['pay_status'] == PS_PAYED) {
        //订单已付款
        if ($order['order_status'] == OS_SPLITED) {
            //已分单
            if ($order['shipping_status'] == SS_SHIPPED) {
                //已发货 退款
                $sql = 'SELECT log_time FROM ' . $GLOBALS['ecs']->table('order_action') . " WHERE  shipping_status = " . SS_SHIPPED . " AND order_id = " . $order['order_id'];
                $action_time = $GLOBALS['db']->getOne($sql); //获取发货时间

                /* 退货退款 现在时间-发货时时间 得到天数 */
                $days = (($time - $action_time) / 3600 / 24);
                if ($days <= $service_return['unreceived_days']) {
                    $type_list[]= ST_RETURN_GOODS;
                } else {
                    show_message($GLOBALS['_LANG']['time_out']);
                }
                if ($days <= $service_exchange['unreceived_days']) {
                    $type_list[]= ST_EXCHANGE;
                } else {
                    show_message($GLOBALS['_LANG']['time_out']);
                }
            } elseif ($order['shipping_status'] == SS_RECEIVED) {
                //已收货 退货换货，退款, 换货, 维修
                $sql = 'SELECT log_time FROM ' . $GLOBALS['ecs']->table('order_action') . " WHERE  shipping_status = " . SS_RECEIVED . " AND order_id = " . $order['order_id'];
                $action_time = $GLOBALS['db']->getOne($sql);

                /* 退货退款 现在时间-发货时时间 得到天数 */
                $days = (($time - $action_time) / 3600 / 24);
                if ($days <= $service_return['unreceived_days']) {
                    $type_list[]= ST_RETURN_GOODS;
                } else {
                    show_message($GLOBALS['_LANG']['time_out']);
                }
                if ($days <= $service_exchange['unreceived_days']) {
                    $type_list[]= ST_EXCHANGE;
                } else {
                    show_message($GLOBALS['_LANG']['time_out']);
                }
            } else {
                //其他
            }
        }
    }
    return $type_list;
}
/*
 * 根据类型获得退换货类型详情
 */
function get_service_info($service_type)
{
    $sql = "SELECT * " .
        "FROM " . $GLOBALS['ecs']->table('service_type') . " WHERE service_type = $service_type";
    
    $result = $GLOBALS['db']->GetRow($sql);
    if ($result['service_type'] == ST_EXCHANGE) {
        /*换货*/
        $result['exchange'] = 1;
    } elseif ($result['service_type'] == ST_RETURN_GOODS) {
        /*退货*/
        $result['return_goods'] = 1;
    }
    return $result;
}
/**
 * 获取服务类型
 * by ECTouch Leah TODO delete
 * @return mix
 */
function get_service_type($service_id, $service_name = 0)
{
    $sql = "SELECT * " .
        "FROM " . $GLOBALS['ecs']->table('service_type') . " WHERE service_type='$service_id'";
    $result = $GLOBALS['db']->GetRow($sql);
    if ($service_name) {
        return $result['service_name'];
    } else {
        return $result;
    }
}
/**
 * 获取服务类型名称
 * @param type $service_id
 * @return type
 */
function get_service_type_name($type_id)
{
    $sql = "SELECT * " .
        "FROM " . $GLOBALS['ecs']->table('service_type') . " WHERE service_type='$type_id'";
    $result = $GLOBALS['db']->GetRow($sql);
    
    return $result['service_name'];
}
/** 获取所有服务类型列表
 * @param $service_type
 * @return array
 */
function get_service_type_list($service_id = array())
{
    $where =  '';
    if (!empty($service_id)) {
        $where = " AND service_type in(" . implode(',', $service_id) . ")";
    }
    $sql = 'SELECT service_id, service_name, service_desc,received_days, unreceived_days, service_type FROM ' . $GLOBALS['ecs']->table('service_type') . 'service_type' . ' WHERE  is_show = 1 ' . $where . ' ORDER BY sort_order, service_id';

    $res = $GLOBALS['db']->query($sql);
    $service_type = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $service_type[$row['service_id']]['service_name'] = $row['service_name'];
        $service_type[$row['service_id']]['service_id'] = $row['service_id'];
        $service_type[$row['service_id']]['received_days'] = $row['received_days'];
        $service_type[$row['service_id']]['unreceived_days'] = $row['unreceived_days'];
        $service_type[$row['service_id']]['service_desc'] = $row['service_desc'];
    }
    return $service_type;
}
/**
 * 取得订单商品 退换货
 * @param   int $rec_id 订单商品自增id
 * @return  array   订单商品数组
 */
function order_goods_info($rec_id)
{
    $sql = "SELECT og.rec_id, og.goods_id, og.goods_name, og.goods_sn, og.market_price, og.goods_number, " .
        "og.goods_price, og.goods_attr, og.is_real, og.parent_id, og.is_gift, g.goods_thumb ,g.suppliers_id," .
        "og.goods_price * og.goods_number AS subtotal, og.extension_code " .
        " FROM " . $GLOBALS['ecs']->table('order_goods') .
        " og LEFT JOIN ".$GLOBALS['ecs']->table('goods') ." g ON og.goods_id = g.goods_id ".
        " WHERE og.rec_id = '$rec_id'";
    $goods = $GLOBALS['db']->getRow($sql);
    if ($goods['extension_code'] == 'package_buy') {
        $goods['package_goods_list'] = get_package_goods($goods['goods_id']);
    }
    return $goods;
}

/**
 * 得到新退换货单号
 * @return  string
 */
function get_service_sn()
{
    /* 选择一个随机的方案 */
    mt_srand((double) microtime() * 1000000);

    return date('Ymd') . str_pad(mt_rand(1, 99999), 3, '0', STR_PAD_LEFT);
}
/**
 * 获取商家地址
 */
function get_business_address($suppliers_id)
{
    $address = '';
    
    if ($suppliers_id) {
        $address = '';
    } else {
        $sql = 'SELECT  region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id = '" . $GLOBALS['_CFG']['shop_country'] . "'";
        $address .= $GLOBALS['db']->getOne($sql) . ' ';
  
        $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id = '".$GLOBALS['_CFG']['shop_province']."'";
        $address .= $GLOBALS['db']->getOne($sql) . ' ';

        $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id = '".$GLOBALS['_CFG']['shop_city']."'";
        $address .= $GLOBALS['db']->getOne($sql) . ' ';

        $address.= $GLOBALS['_CFG']['shop_address'] . '收件人：' . $GLOBALS['_CFG']['shop_name'] . '联系电话：' . $GLOBALS['_CFG']['service_phone'];
    }
    return $address;
}
