<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\helpers\Token;
use Yii;
use yii\data\Pagination;

/**
 * This is the model class for table "{{%order_info}}".
 *
 * @property string $order_id
 * @property string $order_sn
 * @property string $user_id
 * @property integer $order_status
 * @property integer $shipping_status
 * @property integer $pay_status
 * @property string $consignee
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $email
 * @property string $best_time
 * @property string $sign_building
 * @property string $postscript
 * @property integer $shipping_id
 * @property string $shipping_name
 * @property integer $pay_id
 * @property string $pay_name
 * @property string $how_oos
 * @property string $how_surplus
 * @property string $pack_name
 * @property string $card_name
 * @property string $card_message
 * @property string $inv_payee
 * @property string $inv_content
 * @property string $goods_amount
 * @property string $shipping_fee
 * @property string $insure_fee
 * @property string $pay_fee
 * @property string $pack_fee
 * @property string $card_fee
 * @property string $money_paid
 * @property string $surplus
 * @property string $integral
 * @property string $integral_money
 * @property string $bonus
 * @property string $order_amount
 * @property integer $from_ad
 * @property string $referer
 * @property string $add_time
 * @property string $confirm_time
 * @property string $pay_time
 * @property string $shipping_time
 * @property integer $pack_id
 * @property integer $card_id
 * @property string $bonus_id
 * @property string $invoice_no
 * @property string $extension_code
 * @property string $extension_id
 * @property string $to_buyer
 * @property string $pay_note
 * @property integer $agency_id
 * @property string $inv_type
 * @property string $tax
 * @property integer $is_separate
 * @property string $parent_id
 * @property string $discount
 */
class Order extends Foundation
{

// ECM 订单状态
    const STATUS_CREATED     = 0; // 待付款
    const STATUS_PAID        = 1; // 已付款
    const STATUS_DELIVERING  = 2; // 发货中
    const STATUS_DELIVERIED  = 3; // 已收货，待评价
    const STATUS_FINISHED    = 4; // 已完成
    const STATUS_CANCELLED   = 5; // 已取消

    /* 订单状态 */
    const OS_UNCONFIRMED     = 0; // 未确认
    const OS_CONFIRMED       = 1; // 已确认
    const OS_CANCELED        = 2; // 已取消
    const OS_INVALID         = 3; // 无效
    const OS_RETURNED        = 4; // 退货
    const OS_SPLITED         = 5; // 已分单
    const OS_SPLITING_PART   = 6; // 部分分单

    /* 支付状态 */
    const PS_UNPAYED         = 0; // 未付款
    const PS_PAYING          = 1; // 付款中
    const PS_PAYED           = 2; // 已付款

    /* 配送状态 */
    const SS_UNSHIPPED       = 0; // 未发货
    const SS_SHIPPED         = 1; // 已发货
    const SS_RECEIVED        = 2; // 已收货
    const SS_PREPARING       = 3; // 备货中
    const SS_SHIPPED_PART    = 4; // 已发货(部分商品)
    const SS_SHIPPED_ING     = 5; // 发货中(处理分单)
    const OS_SHIPPED_PART    = 6; // 已发货(部分商品)

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_info}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'order_status', 'shipping_status', 'pay_status', 'country', 'province', 'city', 'district', 'shipping_id', 'pay_id', 'integral', 'from_ad', 'add_time', 'confirm_time', 'pay_time', 'shipping_time', 'pack_id', 'card_id', 'bonus_id', 'extension_id', 'agency_id', 'is_separate', 'parent_id'], 'integer'],
            [['goods_amount', 'shipping_fee', 'insure_fee', 'pay_fee', 'pack_fee', 'card_fee', 'money_paid', 'surplus', 'integral_money', 'bonus', 'order_amount', 'tax', 'discount'], 'number'],
            [['agency_id', 'inv_type', 'tax', 'discount'], 'required'],
            [['order_sn'], 'string', 'max' => 20],
            [['consignee', 'zipcode', 'tel', 'mobile', 'email', 'inv_type'], 'string', 'max' => 60],
            [['address', 'postscript', 'card_message', 'referer', 'invoice_no', 'to_buyer', 'pay_note'], 'string', 'max' => 255],
            [['best_time', 'sign_building', 'shipping_name', 'pay_name', 'how_oos', 'how_surplus', 'pack_name', 'card_name', 'inv_payee', 'inv_content'], 'string', 'max' => 120],
            [['extension_code'], 'string', 'max' => 30],
            [['order_sn'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_sn' => 'Order Sn',
            'user_id' => 'User ID',
            'order_status' => 'Order Status',
            'shipping_status' => 'Shipping Status',
            'pay_status' => 'Pay Status',
            'consignee' => 'Consignee',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'district' => 'District',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'tel' => 'Tel',
            'mobile' => 'Mobile',
            'email' => 'Email',
            'best_time' => 'Best Time',
            'sign_building' => 'Sign Building',
            'postscript' => 'Postscript',
            'shipping_id' => 'Shipping ID',
            'shipping_name' => 'Shipping Name',
            'pay_id' => 'Pay ID',
            'pay_name' => 'Pay Name',
            'how_oos' => 'How Oos',
            'how_surplus' => 'How Surplus',
            'pack_name' => 'Pack Name',
            'card_name' => 'Card Name',
            'card_message' => 'Card Message',
            'inv_payee' => 'Inv Payee',
            'inv_content' => 'Inv Content',
            'goods_amount' => 'Goods Amount',
            'shipping_fee' => 'Shipping Fee',
            'insure_fee' => 'Insure Fee',
            'pay_fee' => 'Pay Fee',
            'pack_fee' => 'Pack Fee',
            'card_fee' => 'Card Fee',
            'money_paid' => 'Money Paid',
            'surplus' => 'Surplus',
            'integral' => 'Integral',
            'integral_money' => 'Integral Money',
            'bonus' => 'Bonus',
            'order_amount' => 'Order Amount',
            'from_ad' => 'From Ad',
            'referer' => 'Referer',
            'add_time' => 'Add Time',
            'confirm_time' => 'Confirm Time',
            'pay_time' => 'Pay Time',
            'shipping_time' => 'Shipping Time',
            'pack_id' => 'Pack ID',
            'card_id' => 'Card ID',
            'bonus_id' => 'Bonus ID',
            'invoice_no' => 'Invoice No',
            'extension_code' => 'Extension Code',
            'extension_id' => 'Extension ID',
            'to_buyer' => 'To Buyer',
            'pay_note' => 'Pay Note',
            'agency_id' => 'Agency ID',
            'inv_type' => 'Inv Type',
            'tax' => 'Tax',
            'is_separate' => 'Is Separate',
            'parent_id' => 'Parent ID',
            'discount' => 'Discount',
        ];
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
    public static function order_fee($order, $goods, $consignee,$cart_good_id = 0,$shipping,$consignee_id)
    {
        /* 初始化订单的扩展code */
        if (!isset($order['extension_code']))
        {
            $order['extension_code'] = '';
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
        foreach ($goods AS $val)
        {
            /* 统计实体商品的个数 */
            if ($val['is_real'])
            {
                $total['real_goods_count']++;
            }

            $total['goods_price']  += $val['goods_price'] * $val['goods_number'];
            $total['market_price'] += $val['market_price'] * $val['goods_number'];
        }

        $total['saving']    = $total['market_price'] - $total['goods_price'];
        $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

        $total['goods_price_formated']  = Goods::price_format($total['goods_price'], false);
        $total['market_price_formated'] = Goods::price_format($total['market_price'], false);
        $total['saving_formated']       = Goods::price_format($total['saving'], false);
        /* 折扣 */
        $total['discount'] = Cart::compute_discount_check($goods);
        if ($total['discount'] > $total['goods_price'])
        {
            $total['discount'] = $total['goods_price'];
        }

        $total['discount_formated'] = Goods::price_format($total['discount'], false);

        /* 税额 */
        if (!empty($order['need_inv']) && $order['inv_type'] != '')
        {
            /* 查税率 */
            $rate = 0;
            foreach ($GLOBALS['_CFG']['invoice_type']['type'] as $key => $type)
            {
                if ($type == $order['inv_type'])
                {
                    $rate = floatval($GLOBALS['_CFG']['invoice_type']['rate'][$key]) / 100;
                    break;
                }
            }
            if ($rate > 0)
            {
                $total['tax'] = $rate * $total['goods_price'];
            }
        }
        $total['tax_formated'] = Goods::price_format($total['tax'], false);

        /* 包装费用 */

        /* 贺卡费用 */

        /* 红包 */

        if (!empty($order['bonus_id']))
        {
            $bonus          = BonusType::bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }
        $total['bonus_formated'] = Goods::price_format($total['bonus'], false);

        /* 线下红包 */
        if (!empty($order['bonus_kill']))
        {
            $bonus          = BonusType::bonus_info(0,$order['bonus_kill']);
            $total['bonus_kill'] = $order['bonus_kill'];
            $total['bonus_kill_formated'] = Goods::price_format($total['bonus_kill'], false);
        }


        /* 配送费用 */
        $shipping_cod_fee = NULL;

        if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0)
        {
            $region['country']  = $consignee['country'];
            $region['province'] = $consignee['province'];
            $region['city']     = $consignee['city'];
            $region['district'] = $consignee['district'];
            $total['shipping_fee'] = Shipping::total_shipping_fee($consignee_id, $goods, $shipping);
        }
        $total['shipping_fee_formated']    = Goods::price_format($total['shipping_fee'], false);

        // 购物车中的商品能享受红包支付的总额
        $bonus_amount = Cart::compute_discount_check($goods);
        // 红包和积分最多能支付的金额为商品总额
        $max_amount = $total['goods_price'] == 0 ? $total['goods_price'] : $total['goods_price'] - $bonus_amount;

        /* 计算订单总额 */
        if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0)
        {
            $total['amount'] = $total['goods_price'];
        }
        else
        {
            $total['amount'] = $total['goods_price'] - $total['discount'] + $total['tax'] + $total['pack_fee'] + $total['card_fee'] +$total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];

            // 减去红包金额
            $use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
            if(isset($total['bonus_kill']))
            {
                $use_bonus_kill   = min($total['bonus_kill'], $max_amount);
                $total['amount'] -=  $price = number_format($total['bonus_kill'], 2, '.', ''); // 还需要支付的订单金额
            }

            $total['bonus']   = $use_bonus;
            $total['bonus_formated'] = Goods::price_format($total['bonus'], false);

            $total['amount'] -= $use_bonus; // 还需要支付的订单金额
            $max_amount      -= $use_bonus; // 积分最多还能支付的金额

        }

        /* 积分 */
        $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
        if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0)
        {
            $integral_money = self::value_of_integral($order['integral']);

            // 使用积分支付
            $use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
            $total['amount']        -= $use_integral;
            $total['integral_money'] = $use_integral;
            $order['integral']       = self::integral_of_value($use_integral);
        }
        else
        {
            $total['integral_money'] = 0;
            $order['integral']       = 0;
        }
        $total['integral'] = $order['integral'];
        $total['integral_formated'] = Goods::price_format($total['integral_money'], false);

        /* 保存订单信息 */
        // $_SESSION['flow_order'] = $order;

        $se_flow_type = isset($_SESSION['flow_type']) ? $_SESSION['flow_type'] : '';

        /* 支付费用 */
        // if (!empty($order['pay_id']) && ($total['real_goods_count'] > 0 || $se_flow_type != CART_EXCHANGE_GOODS))
        // {
        //     $total['pay_fee']      = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
        // }

        // $total['pay_fee_formated'] = price_format($total['pay_fee'], false);

        // $total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
        // $total['amount_formated']  = price_format($total['amount'], false);

        /* 取得可以得到的积分和红包 */
        if ($order['extension_code'] == 'group_buy')
        {
            $total['will_get_integral'] = $group_buy['gift_integral'];
        }
        elseif ($order['extension_code'] == 'exchange_goods')
        {
            $total['will_get_integral'] = 0;
        }
        else
        {
            $total['will_get_integral'] = Cart::get_give_integral($goods);
        }
        $total['will_get_bonus']        = 0;

        $total['formated_goods_price']  = Goods::price_format($total['goods_price'], false);
        $total['formated_market_price'] = Goods::price_format($total['market_price'], false);
        $total['formated_saving']       = Goods::price_format($total['saving'], false);

        return $total;
    }

    /**
     * 订单列表
     */
    public static function getList(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $model = self::find()->where(['user_id' => $uid])->andWhere(['!=', 'order_status', self::OS_CANCELED]);

        if (isset($status)) {
            switch ($status) {
                case self::STATUS_PAID:
                    $model->andWhere(['in', 'pay_status', [self::PS_UNPAYED]]);
                    break;

                case self::STATUS_DELIVERING:
                    $model->andWhere(['in', 'shipping_status', [self::SS_SHIPPED, self::SS_SHIPPED_PART, self::OS_SHIPPED_PART]]);
                    break;
            }
        }

        $total = $model->count();
        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page, 'page' => $page-1]);

        $data = $model
            ->select(['order_id', 'order_sn', 'order_status', 'shipping_status', 'pay_status','goods_amount','order_amount','add_time','shipping_status','shipping_status'])
            ->with('goods')
            ->orderBy('add_time DESC')
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();
        foreach($data as $k => $v){
            $data[$k]['add_time'] = date('Y-m-d H:i', $v['add_time']); // 时间
            $data[$k]['order_status'] = self::orderStatus($v['order_status']); // 订单状态
            $data[$k]['pay_status'] = self::payStatus($v['pay_status']); // 支付状态
            $data[$k]['shipping_status'] = self::shipStatus($v['shipping_status']); // 配送状态
            $dataTotalNumber = 0;

            foreach($v['goods'] as $gk => $gv){
                $data[$k]['goods'][$gk]['goods_thumb'] = GoodsGallery::formatPhoto($gv['goods']['goods_thumb']);
                $dataTotalNumber += $gv['goods_number'];
                unset($data[$k]['goods'][$gk]['goods']);
            }
            $data[$k]['goods'] = array_slice($data[$k]['goods'], 0, 3);

            $data[$k]['total_number'] = $dataTotalNumber; // 配送状态


        }
        return self::formatBody(['orders' => $data,'paged' => self::formatPaged($page, $per_page, $total)]);
    }
    public static function orderStatus($num){
//        array('待付款', '已付款', '发货中', '已收货，待评价', '已完成', '已取消');
        $array = array('未确认', '已确认', '已取消', '无效', '退货', '已分单', '部分分单');  //订单状态

        return $array[$num];
    }
    public static function payStatus($num){
        $array = array('未付款', '付款中', '已付款'); //支付状态

        return $array[$num];
    }
    public static function shipStatus($num){
        $array = array('未发货', '已发货', '已收货', '备货中', '已发货(部分商品)', '发货中(处理分单)', '已发货(部分商品)'); //配送状态

        return $array[$num];
    }

    /**
     * 关联订单商品
     */
    public function getGoods()
    {
        return $this->hasMany(OrderGoods::className(), ['order_id' => 'order_id'])->with('goods');
    }

    /**
     * 得到新订单号
     * @return  string
     */
    public static function get_order_sn()
    {
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);

        return date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 添加订单
     */
    public static function insertGetId($data){
        $orderModel = new Order();
        foreach ($data as $k => $v) {
            $orderModel->$k = $v;
        }

        $orderModel->save(false);
        $id = Yii::$app->db->getLastInsertID();

        return $id;
    }

    /**
     * 改变订单中商品库存
     * @param   int     $order_id   订单号
     * @param   bool    $is_dec     是否减少库存
     * @param   bool    $storage     减库存的时机，1，下订单时；0，发货时；
     */
    public static function change_order_goods_storage($order_id, $is_dec = true, $storage = 0)
    {
        /* 查询订单商品信息 */
        switch ($storage)
        {
            case 0 :
                // $sql = "SELECT goods_id, SUM(send_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
                //         " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                $res = OrderGoods::find()->where(['order_id'=>$order_id])->andWhere(['is_real'=>1])
                    ->groupBy('goods_id')
                    ->groupBy('product_id')
                    ->select(['sum(send_number) as num','goods_id,max(extension_code) as extension_code','product_id'])
                    ->asArray()
                    ->all();
                break;

            case 1 :
                // $sql = "SELECT goods_id, SUM(goods_number) AS num, MAX(extension_code) AS extension_code, product_id FROM " . $GLOBALS['ecs']->table('order_goods') .
                //         " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id, product_id";
                $res = OrderGoods::find()->where(['order_id'=>$order_id])->andWhere(['is_real'=>1])
                    ->groupBy('goods_id')
                    ->groupBy('product_id')
                    ->select(['sum(goods_number) as num','goods_id,max(extension_code) as extension_code','product_id'])
                    ->asArray()
                    ->all();
                break;
        }
        foreach ($res as $key => $row) {

            if ($row['extension_code'] != "package_buy")
            {

                if ($is_dec)
                {
                    self::change_goods_storage($row['goods_id'], $row['product_id'], - $row['num']);
                }
                else
                {
                    self::change_goods_storage($row['goods_id'], $row['product_id'], $row['num']);
                }
                // $GLOBALS['db']->query($sql);
            }
            else
            {   //package_buy

                // $sql = "SELECT goods_id, goods_number" .
                //        " FROM " . $GLOBALS['ecs']->table('package_goods') .
                //        " WHERE package_id = '" . $row['goods_id'] . "'";
                // $res_goods = $GLOBALS['db']->query($sql);
                // while ($row_goods = $GLOBALS['db']->fetchRow($res_goods))
                // {
                //     $sql = "SELECT is_real" .
                //        " FROM " . $GLOBALS['ecs']->table('goods') .
                //        " WHERE goods_id = '" . $row_goods['goods_id'] . "'";
                //     $real_goods = $GLOBALS['db']->query($sql);
                //     $is_goods = $GLOBALS['db']->fetchRow($real_goods);

                //     if ($is_dec)
                //     {
                //         self::change_goods_storage($row_goods['goods_id'], $row['product_id'], - ($row['num'] * $row_goods['goods_number']));
                //     }
                //     elseif ($is_goods['is_real'])
                //     {
                //         self::change_goods_storage($row_goods['goods_id'], $row['product_id'], ($row['num'] * $row_goods['goods_number']));
                //     }
                // }
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
    public static function change_goods_storage($good_id, $product_id, $number = 0)
    {

        if ($number == 0)
        {
            return true; // 值为0即不做、增减操作，返回true
        }

        if (empty($good_id) || empty($number))
        {
            return false;
        }

        $number = ($number > 0) ? '+ ' . $number : $number;
        /* 处理货品库存 */
        $products_query = true;
        if (!empty($product_id))
        {
            // $sql = "UPDATE " . $GLOBALS['ecs']->table('products') ."
            //         SET product_number = product_number $number
            //         WHERE goods_id = '$good_id'
            //         AND product_id = '$product_id'
            //         LIMIT 1";
            // $products_query = $GLOBALS['db']->query($sql);
            $products_query = Products::find()->where(['goods_id'=>$good_id])
                ->andWhere(['product_id'=>$product_id])
                ->limit(1)
                ->one();
            $products_query->product_number = $number;
            $products_query->save(false);
//                ->increment('product_number' ,$number);
        }

        /* 处理商品库存 */
        // $sql = "UPDATE " . $GLOBALS['ecs']->table('goods') ."
        //         SET goods_number = goods_number $number
        //         WHERE goods_id = '$good_id'
        //         LIMIT 1";
        // $query = $GLOBALS['db']->query($sql);
        $query = Goods::find()->where(['goods_id'=>$good_id])
            ->limit(1)
            ->one();
        $query->goods_number += $number;
        $query->save(false);
        if ($query && $products_query)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * 订单详情页面
     */
    public static function getInfo(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $orderInfo = self::find()
            ->select(['order_id', 'order_sn', 'shipping_id', 'shipping_name', 'pay_id', 'pay_name', 'shipping_fee', 'goods_amount', 'order_amount', 'consignee',  'district', 'address', 'mobile', 'add_time', 'postscript as comment', 'order_status', 'pay_status', 'shipping_status'])
            ->where(['user_id' => $uid, 'order_id' => $order])
            ->asArray()
            ->one();

        if ($orderInfo) {
            $discount_price = 0;
            $order_goods = OrderGoods::find()->where(['order_id'=>$order])->asArray()->all();
            $totalNumber = 0;
            foreach ($order_goods as $key => $order_good) {
                $val = Goods::find()->where(['goods_id'=>$order_good['goods_id']])->one();
                $totalNumber += $order_good['goods_number'];
                $volume_price  = $val['shop_price']; //商品优惠价格 如果不存在优惠价格列表 价格为店铺价格
                //取得商品优惠价格列表
                $price_list   = Goods::get_volume_price_list($order_good['goods_id'], '1');
                if (!empty($price_list))
                {
                    foreach ($price_list as $value)
                    {
                        if ($order_good['goods_number'] >= $value['number'])
                        {
                            $volume_price = $value['price'];
                        }
                    }
                }
                $discount_price = ($val['shop_price'] - $volume_price) * $order_good['goods_number'];
                //
                $order_goods[$key]['goods_thumb'] = GoodsGallery::formatPhoto($val['goods_thumb']);

            }

            $orderInfo['order_status'] = self::orderStatus($orderInfo['order_status']); // 订单状态
            $orderInfo['pay_status'] = self::payStatus($orderInfo['pay_status']); // 支付状态
            $orderInfo['shipping_status'] = self::shipStatus($orderInfo['shipping_status']); // 配送状态
            $orderInfo['discount_price'] = $discount_price;
            $orderInfo['total_number'] = $totalNumber;
            $orderInfo['goods'] = $order_goods;
            $orderInfo['mobile'] = substr($orderInfo['mobile'], 0, 3).'****'.substr($orderInfo['mobile'], 7);
            $orderInfo['add_time'] = date('Y-m-d H:i', $orderInfo['add_time']);

            $orderInfo['address'] = Region::getRegionName( $orderInfo['district']) . ' ' . $orderInfo['address'];
            unset($orderInfo['district']);

            return self::formatBody(['order' => $orderInfo]);
        }

        return self::formatError(self::UNKNOWN_ERROR);
    }

    /**
     * 取消订单
     */
    public static function cancel(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        if ($model = self::find()->where(['user_id' => $uid])->andWhere(['order_id' => $order])->one()) {
            // 订单状态只能是“未确认”或“已确认”
            if ($model->order_status != self::OS_UNCONFIRMED && $model->order_status != self::OS_CONFIRMED)
            {
                return self::formatError(self::NOT_FOUND, trans('app', 'message.order.conformed'));
            }

            // 发货状态只能是“未发货”
            if ($model->shipping_status != self::SS_UNSHIPPED)
            {
                return self::formatError(self::NOT_FOUND, trans('app', 'message.order.unshipped'));
            }

            // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
            if ($model->pay_status != self::PS_UNPAYED)
            {
                return self::formatError(self::NOT_FOUND, trans('app', 'message.order.unpayed'));
            }

            //修改订单状态
            $model->order_status = self::OS_CANCELED;
//            self::return_user_integral_bonus($order);

            if ($model->save(false))
            {
//                OrderAction::toCreateOrUpdate($model->order_id, self::OS_CANCELED, $model->shipping_status, $model->pay_status, '');
//                Erp::order($model->order_sn);
                return self::formatBody(['order' => $model]);
            }
        }

        return self::formatError(self::NOT_FOUND);
    }

    /**
     * 退回积分、红包（取消、无效、退货时），把订单使用余额、积分、红包设为0
     * @param   array   $order  订单信息
     */
    public static function return_user_integral_bonus($order_id)
    {
        $uid = Token::authorization();
        if($order = self::find()->where(['user_id' => $uid])->andWhere(['order_id' => $order_id])->one()){

            /* 处理积分 */
            if($order->user_id >0 && $order->integral >0)
            {
                AccountLog::logAccountChange( 0, 0, 0, $order->integral, trans('app', 'message.score.cancel').$order_id.trans('app', 'message.score.order'));
            }

            /* 处理红包 */
            if($order->bonus_id >0)
            {
                UserBonus::unuseBonus($order->bonus_id);
            }

            $order->bonus_id = 0;
            $order->bonus    = 0;
            $order->integral = 0;
            $order->integral_money = 0;
            $order->surplus = 0;

            return $order->save();
        }

        return false;
    }

    /**
     * 根据订单号查找订单
     */
    public static function findUnpayedBySN($sn)
    {
        return self::find()->where(['order_sn' => $sn, 'pay_status' => Order::PS_UNPAYED])->one();
    }
}
