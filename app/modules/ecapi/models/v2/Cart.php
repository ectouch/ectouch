<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\helpers\Token;
use Yii;
use yii\log\Logger;

/**
 * This is the model class for table "{{%cart}}".
 *
 * @property string $rec_id
 * @property string $user_id
 * @property string $session_id
 * @property string $goods_id
 * @property string $goods_sn
 * @property string $product_id
 * @property string $goods_name
 * @property string $market_price
 * @property string $goods_price
 * @property integer $goods_number
 * @property string $goods_attr
 * @property integer $is_real
 * @property string $extension_code
 * @property string $parent_id
 * @property integer $rec_type
 * @property integer $is_gift
 * @property integer $is_shipping
 * @property integer $can_handsel
 * @property string $goods_attr_id
 * @property string $group_id
 */
class Cart extends Foundation
{
    /* 购物车商品类型 */
    const CART_GENERAL_GOODS        = 0; // 普通商品
    const CART_GROUP_BUY_GOODS      = 1; // 团购商品
    const CART_AUCTION_GOODS        = 2; // 拍卖商品
    const CART_SNATCH_GOODS         = 3; // 夺宝奇兵
    const CART_EXCHANGE_GOODS       = 4; // 积分商城

    /* 减库存时机 */
    const SDT_SHIP                  = 0; // 发货时
    const SDT_PLACE                 = 1; // 下订单时
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cart}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'goods_id', 'product_id', 'goods_number', 'is_real', 'parent_id', 'rec_type', 'is_gift', 'is_shipping', 'can_handsel'], 'integer'],
            [['market_price', 'goods_price'], 'number'],
            [['goods_attr', 'group_id'], 'required'],
            [['goods_attr'], 'string'],
            [['session_id'], 'string', 'max' => 32],
            [['goods_sn'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
            [['extension_code'], 'string', 'max' => 30],
            [['goods_attr_id', 'group_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => 'Rec ID',
            'user_id' => 'User ID',
            'session_id' => 'Session ID',
            'goods_id' => 'Goods ID',
            'goods_sn' => 'Goods Sn',
            'product_id' => 'Product ID',
            'goods_name' => 'Goods Name',
            'market_price' => 'Market Price',
            'goods_price' => 'Goods Price',
            'goods_number' => 'Goods Number',
            'goods_attr' => 'Goods Attr',
            'is_real' => 'Is Real',
            'extension_code' => 'Extension Code',
            'parent_id' => 'Parent ID',
            'rec_type' => 'Rec Type',
            'is_gift' => 'Is Gift',
            'is_shipping' => 'Is Shipping',
            'can_handsel' => 'Can Handsel',
            'goods_attr_id' => 'Goods Attr ID',
            'group_id' => 'Group ID',
        ];
    }

    /**
     * 添加商品到购物车
     *
     * @access  public
     * @param   integer $goods_id   商品编号
     * @param   integer $num        商品数量
     * @param   json   $property       规格值对应的id json数组
     * @param   integer $parent     基本件
     * @return  boolean
     */
    public static function add(array $attributes)
    {
        extract($attributes);
        //获取商品信息
        $good = Goods::find()
            ->where(['goods_id' => $product, 'is_delete' => 0])
            ->one();

        if (!$good) {
            // 商品不存在
            return self::formatError(self::NOT_FOUND);
        }
        /* 如果是作为配件添加到购物车的，需要先检查购物车里面是否已经有基本件*/

        /* 是否正在销售 */
        if ($good['is_on_sale'] == 0) {
            return self::formatError(self::BAD_REQUEST,trans('app', 'message.good.off_sale'));
        }

        /* 不是配件时检查是否允许单独销售 */
        if ($good['is_alone_sale'] == 0) {
            //不能单独销售
            return self::formatError(self::BAD_REQUEST,trans('app', 'message.good.not_alone'));
        }
        if (isset($property) && json_decode($property,true)) {
            $property = json_decode($property,true);
        }else{
            $property = [];
        }

        /* 如果商品有规格则取规格商品信息 配件除外 property */
        $prod = Products::find()
            ->where(['goods_id'=>$product])
            ->one();

        if (Attribute::is_property($property) && !empty($prod))
        {
            $product_info = Products::get_products_info($product, $property);
        }
        if (empty($product_info))
        {
            $product_info = array('product_number' => '', 'product_id' => 0);
        }
        /* 检查：库存 */
        //检查：商品购买数量是否大于总库存
        if ($amount > $good['goods_number'])
        {
            return self::formatError(self::BAD_REQUEST,trans('message.good.out_storage'));
        }
        //商品存在规格 是货品 检查该货品库存
        if (Attribute::is_property($property) && !empty($prod))
        {
            if (!empty($property))
            {
                /* 取规格的货品库存 */
                if ($amount > $product_info['product_number'])
                {
                    return self::formatError(self::BAD_REQUEST,trans('app', 'message.good.out_storage'));
                }
            }
        }
        /* 计算商品的促销价格 */
        $property_price         = GoodsAttr::property_price($property);
        $goods_price            = Goods::get_final_price($product, $amount, true, $property);
        $good['market_price']  += $property_price;
        $goods_attr             = Attribute::get_goods_attr_info($property);
        $goods_attr_id          = join(',', (array)$property);
        /* 初始化要插入购物车的基本件数据 */

        $parent = array(
            'user_id'       => Token::authorization(), //uid
            // 'session_id'    => SESS_ID,
            'goods_id'      => $product,
            'goods_sn'      => addslashes($good['goods_sn']),
            'product_id'    => $product_info['product_id'],
            'goods_name'    => addslashes($good['goods_name']),
            'market_price'  => $good['market_price'],
            'goods_attr'    => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real'       => $good['is_real'],
            'extension_code'=> $good['extension_code'],
            'is_gift'       => 0,
            'is_shipping'   => $good['is_shipping'],
            'rec_type'      => Cart::CART_GENERAL_GOODS,
        );
        /* 如果该配件在添加为基本件的配件时，所设置的“配件价格”比原价低，即此配件在价格上提供了优惠， */
        /* 则按照该配件的优惠价格卖，但是每一个基本件只能购买一个优惠价格的“该配件”，多买的“该配件”不享 */
        /* 受此优惠 */
        $basic_list = array();

        $res = GoodsGroup::find()
            ->select(['parent_id', 'goods_price'])
            ->where(['goods_id'=>$product])
            ->where(['<', 'goods_price', $goods_price])
            ->where(['parent_id'=>$parent])
            ->orderBy('goods_price')
            ->all();
        foreach ($res as $key => $row) {
            $basic_list[$row['parent_id']] = $row['goods_price'];
        }

        /* 取得购物车中该商品每个基本件的数量 */

        $basic_count_list = array();
        if ($basic_list)
        {
            $res = Cart::find()
                ->select('sum(goods_number) as count, goods_id')
                ->where(['parent_id'=>0])
                ->where(['!=','extension_code','package_buy'])
                ->where(['in', 'goods_id', array_keys($basic_list)])
                ->groupBy('goods_id')
                ->asArray()
                ->all();

            foreach ($res as $key => $row) {
                $basic_count_list[$row['goods_id']] = $row['count'];
            }
        }

        /* 取得购物车中该商品每个基本件已有该商品配件数量，计算出每个基本件还能有几个该商品配件 */
        /* 一个基本件对应一个该商品配件 */

        if ($basic_count_list)
        {
            $res = Cart::find()
                ->select(['sum(goods_number) as count, goods_id'])
                ->where(['parent_id' => 0])
                ->andWhere(['!=', 'extension_code', 'package_buy'])
                ->andWhere(['goods_id'=>$product])
                ->andWhere(['in', 'parent_id',array_keys($basic_count_list)])
                ->groupBy('parent_id')
                ->asArray()
                ->all();

            foreach ($res as $key => $row) {
                $basic_count_list[$row['goods_id']] = $row['count'];
            }
        }

        /* 循环插入配件 如果是配件则用其添加数量依次为购物车中所有属于其的基本件添加足够数量的该配件 */
        foreach ($basic_list as $parent_id => $fitting_price)
        {
            /* 如果已全部插入，退出 */
            if ($amount <= 0)
            {
                break;
            }

            /* 如果该基本件不再购物车中，执行下一个 */
            if (!isset($basic_count_list[$parent_id]))
            {
                continue;
            }

            /* 如果该基本件的配件数量已满，执行下一个基本件 */
            if ($basic_count_list[$parent_id] <= 0)
            {
                continue;
            }

            /* 作为该基本件的配件插入 */
            $parent['goods_price']  = max($fitting_price, 0) + $property_price; //允许该配件优惠价格为0
            $parent['goods_number'] = min($amount, $basic_count_list[$parent_id]);
            $parent['parent_id']    = $parent_id;

            /* 添加 */
            // $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
            $cartModel = new Cart();
            foreach ($parent as $k => $v) {
                $cartModel->$k = $v;
            }
            $cartModel->insert(false);

//            Cart::insert($parent);
            /* 改变数量 */
            $amount -= $parent['goods_number'];
        }
        /* 如果数量不为0，作为基本件插入 */

        if ($amount > 0)
        {
            /* 检查该商品是否已经存在在购物车中 */
            $user_id = Token::authorization();

            $row = Cart::find()
                ->where(['goods_id'=>$product])
                ->andWhere(['user_id'=>$user_id])
                ->andWhere(['parent_id'=>0])
                ->andWhere(['goods_attr'=>Attribute::get_goods_attr_info($property)])
                ->andWhere(['!=','extension_code','package_buy'])
                ->andWhere(['rec_type'=>self::CART_GENERAL_GOODS])
                ->one();

            if($row) //如果购物车已经有此物品，则更新
            {
                $amount += $row['goods_number'];
                if(Attribute::is_property($property) && !empty($prod) )
                {
                    $goods_storage = $product_info['product_number'];
                }
                else
                {
                    $goods_storage = $good['goods_number'];
                }
                // if ($GLOBALS['_CFG']['use_storage'] == 0 || $amount <= $goods_storage)
                if ($amount <= $goods_storage)
                {
                    $goods_final_price = Goods::get_final_price($product, $amount, true, $property);

                    $cart = Cart::find()
                        ->where(['goods_id' => $product])
                        ->andWhere(['user_id'=>$user_id])
                        ->andWhere(['parent_id' => 0])
                        ->andWhere(['goods_attr' =>Attribute::get_goods_attr_info($property)])
                        ->andWhere(['!=','extension_code','package_buy'])
                        ->andWhere(['rec_type'=>self::CART_GENERAL_GOODS])
                        ->one();
                    $cart->goods_number = $amount;
                    $cart->goods_price = $goods_final_price;
                    $cart->save(false);
                }
                else
                {
                    return self::formatError(self::BAD_REQUEST,trans('app', 'message.good.out_storage'));
                }
            }
            else //购物车没有此物品，则插入
            {
                $goods_price = Goods::get_final_price($product, $amount, true, $property);
                $cartModel = new Cart();
                $cartModel->goods_price = max($goods_price, 0);
                $cartModel->goods_number = $amount;
                $cartModel->parent_id = 0;

                $cartModel->user_id = Token::authorization();
                $cartModel->goods_id = $product;
                $cartModel->goods_sn = addslashes($good['goods_sn']);
                $cartModel->product_id = $product_info['product_id'];
                $cartModel->goods_name = addslashes($good['goods_name']);
                $cartModel->market_price = $good['market_price'];
                $cartModel->goods_attr =  addslashes($goods_attr);
                $cartModel->goods_attr_id = $goods_attr_id;
                $cartModel->is_real = $good['is_real'];
                $cartModel->extension_code = $good['extension_code'];
                $cartModel->is_gift = 0;
                $cartModel->is_shipping = $good['is_shipping'];
                $cartModel->rec_type = Cart::CART_GENERAL_GOODS;
                $cartModel->save(false);

            }
        }
        /* 把赠品删除 */

        $cart_is_gift = Cart::find()
            ->where(['!=','is_gift',0])
            ->one();
        if(!empty($cart_is_gift)){
            $cart_is_gift->delete();
        }

        return self::formatBody(['goods' => $parent]);
    }

    /**
     * 删除购物车商品
     * @return [type] [description]
     */
    public static function remove(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $cartGood = Cart::find()
            ->where(['rec_type'=>self::CART_GENERAL_GOODS])
            ->andWhere(['rec_id'=>$good])
            ->andWhere(['user_id'=>$uid])
            ->one();
        if(!empty($cartGood)){
            $cartGood->delete();

            return self::formatBody([self::SUCCESS=>'删除成功']);
        }

        return self::formatBody([self::BAD_REQUEST=>'删除失败']);
    }

    /**
     * 购物车列表
     * @return [type] [description]
     */
    public static function getList()
    {
        $uid = Token::authorization();

        $data = [];
        $goods = self::findAllByUid($uid);

        if (count($goods) > 0) {
            $data['shop']['name'] = ShopConfig::findByCode('shop_name')->value;
            $data['shop']['id'] = 1;
            $data['goods'] = $goods;
            $data['total_price'] = self::TotalPrice();
            $data['total_amount'] = self::TotalAmount();
        }

        return self::formatBody(['goods_groups' => $data]);
    }

    /**
     * 根据用户ID找到购物车列表
     */
    public static function findAllByUid($uid)
    {
        $prefix = Yii::$app->db->tablePrefix;
        $path = Yii::$app->params['SHOP_URL'] . '/';

        $ids =  self::find()
            ->select(['rec_id'])
            ->leftJoin($prefix.'goods', $prefix.'goods.goods_id =' . $prefix.'cart.goods_id')
            ->where([$prefix.'cart.user_id'=>$uid])
            ->andWhere([$prefix.'goods.is_on_sale'=>1])
            ->column();

        $data = self::find()
//            ->with('product')
            ->select([$prefix.'goods.goods_thumb', $prefix.'cart.*'])
            ->where(['in', 'rec_id', $ids])
            ->leftJoin($prefix.'goods', $prefix.'goods.goods_id = '.$prefix.'cart.goods_id')
            ->orderBy('rec_id DESC')
            ->asArray()
            ->all();
        foreach ($data as $k => $v){
            $data[$k]['goods_thumb'] = $path . $v['goods_thumb'];

        }

        return $data;
    }

    /**
     * 总价
     */
    public static function TotalPrice()
    {
        $uid = Token::authorization();

        $goods =  self::find()
            ->where(['user_id' => $uid])
            ->orderBy('rec_id DESC')
            ->asArray()
            ->all();
        $total = 0;
        foreach ($goods as $key => $good) {
            $total += ($good['goods_number'] * $good['goods_price']);
        }
        return (float)$total;
    }

    /**
     * 总数量
     */
    public static function TotalAmount()
    {
        $uid = Token::authorization();
        $total_number = self::find()->where(['user_id'=>$uid])->orderBy('rec_id DESC')->sum('goods_number');
        return empty($total_number) ? 0 : $total_number;
    }

    /**
     * 修改购物车商品数量
     */
    public static function updateAmount(array $attributes)
    {
        extract($attributes);
        $cartModel = Cart::find()
            ->where(['rec_type' => self::CART_GENERAL_GOODS])
            ->where(['rec_id' => $good])
            ->one();
        if(empty($cartModel)){
            return self::formatBody([self::BAD_REQUEST => '没有找到该商品']);
        }

        $cartModel->goods_number = $amount;
        $cartModel->save(false);

        return self::formatBody();
    }


    /**
     * 清空购物车
     * @param   int     $type   类型：默认普通商品
     */
    public static function clear_cart($type = self::CART_GENERAL_GOODS)
    {
        $uid = Token::authorization();
        self::deleteAll(['and', 'rec_type = :rec_type', 'user_id = :user_id'], ['rec_type'=>$type, 'user_id'=>$uid]);
        return self::formatBody();
    }

    /**
     * 订单确认页面
     */
    public static function flow($attributes){
        extract($attributes);

        $uid = Token::authorization();
        if(!$uid){
            return self::formatBody([self::BAD_REQUEST=>'没有这个用户']);
        }

        $data = self::cart_goods(self::CART_GENERAL_GOODS, $uid);
        $total = 0;
        $totalNumber = 0;
        foreach ($data as $k => $v) {
            $total += $v['goods_price'] * $v['goods_number'];
            $totalNumber += $v['goods_number'];
        }

        //收货人地址
        $consignee = UserAddress::getList();
        $userInfo = Member::find()
            ->select(['address_id'])
            ->where(['user_id'=>$uid])
            ->asArray()
            ->column();

        $cons = !empty($consignee['consignees'][0]) ? $consignee['consignees'][0] : '';
        if(!empty($userInfo)){
            $addressId = $userInfo[0];

            foreach ($consignee['consignees'] as $k => $v) {
                if($v['address_id'] == $addressId){

                    $cons = $consignee['consignees'][$k];
                }
            }
        }
        //配送地址
        $shippingList = Shipping::getAllShipping();

        return self::formatBody(['cart_goods'=>$data, 'total' => $total, 'total_number' => $totalNumber, 'consignees' => $cons, 'shipping'=>$shippingList]);
    }
    /**
     * 取得购物车商品
     * @param   int     $type   类型：默认普通商品
     * @return  array   购物车商品数组
     */
    public static function cart_goods($type = self::CART_GENERAL_GOODS,$uid)
    {

        $arr = self::find()->where(['rec_type'=>$type])->andWhere(['user_id'=> $uid])->asArray()->all();

        /* 格式化价格及礼包商品 */
        foreach ($arr as $key => $value)
        {
            $goods_thumb = Goods::find()
                ->select(['goods_thumb'])
                ->where(['goods_id' => $value['goods_id']])
                ->column();
            $arr[$key]['goods_thumb'] = !empty($goods_thumb) ? GoodsGallery::formatPhoto($goods_thumb[0]) : '';
            $arr[$key]['formated_market_price'] = Goods::price_format($value['market_price'], false);
            $arr[$key]['formated_goods_price']  = Goods::price_format($value['goods_price'], false);
            $arr[$key]['formated_subtotal']     = Goods::price_format($value['goods_price'] * $value['goods_number'], false);
        }

        return $arr;
    }

    /**
     * 购物车结算
     * @param     int     $shop            // 店铺ID(无)
     * @param     int     $consignee       // 收货人ID
     * @param     int     $shipping        // 快递ID
     * @param     string     $invoice_type    // 发票类型，如：公司、个人
     * @param     string     $invoice_content // 发票内容，如：办公用品、礼品
     * @param     string     $invoice_title   // 发票抬头，如：xx科技有限公司
     * @param     int     $coupon          // 优惠券ID (无)
     * @param     int     $cashgift        // 红包ID
     * @param     int     $comment         // 留言
     * @param     int     $score           // 积分
     * @param     int     $cart_good_id    // 购物车商品id数组
     */

    public static function checkout(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        //-- 完成所有订单操作，提交到数据库
        /* 取得购物类型 */
        $flow_type = self::CART_GENERAL_GOODS;

        /* 检查购物车中是否有商品 */

        /* 检查商品库存 */
        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage') == '1')
        {
            $cart_goods_stock = self::get_cart_goods();
            $_cart_goods_stock = array();
            foreach ($cart_goods_stock['goods_list'] as $value)
            {
                $_cart_goods_stock[$value['rec_id']] = $value['goods_number'];
            }

            if (!self::flow_cart_stock($_cart_goods_stock)) {
                return self::formatError(self::BAD_REQUEST,trans('message.good.out_storage'));
            }

            unset($cart_goods_stock, $_cart_goods_stock);
        }


        $consignee_info = UserAddress::get_consignee($consignee);

        if (!$consignee_info) {
            return self::formatError(self::BAD_REQUEST,trans('message.consignee.not_found'));
        }

        $inv_type = ShopConfig::findByCode('invoice_type');
        $inv_type = isset($invoice_type) ? $invoice_type : !empty($inv_type) ? $inv_type->value : '' ;
        $inv_payee = ShopConfig::findByCode('invoice_title');
        $inv_payee = isset($invoice_title) ? $invoice_title : !empty($inv_payee) ? $inv_payee->value : '' ;//发票抬头
        $inv_content = ShopConfig::findByCode('invoice_title');
        $inv_content = isset($invoice_content) ? $invoice_content : !empty($inv_content) ? $inv_content->value : '';
        $postscript = isset($comment) ? $comment : '';
        $user_id = Token::authorization();

        $order = array(
            'shipping_id'     => intval($shipping),
            'pay_id'          => intval(0),
            'pack_id'         => isset($_POST['pack']) ? intval($_POST['pack']) : 0,//包装id
            'card_id'         => isset($_POST['card']) ? intval($_POST['card']) : 0,//贺卡id
            'card_message'    => '',//贺卡内容
            'surplus'         => isset($_POST['surplus']) ? floatval($_POST['surplus']) : 0.00,
            'integral'        => isset($score) ? intval($score) : 0,//使用的积分的数量,取用户使用积分,商品可用积分,用户拥有积分中最小者
            'bonus_id'        => isset($cashgift) ? intval($cashgift) : 0,//红包ID
            // 'need_inv'        => empty($_POST['need_inv']) ? 0 : 1,
            'inv_type'        => '',
            'inv_payee'       => '',
            'inv_content'     => '',
            'postscript'      => trim($postscript),
            'how_oos'         => '',//缺货处理
            // 'how_oos'         => isset($_LANG['oos'][$_POST['how_oos']]) ? addslashes($_LANG['oos'][$_POST['how_oos']]) : '',
            // 'need_insure'     => isset($_POST['need_insure']) ? intval($_POST['need_insure']) : 0,
            'user_id'         => $user_id,
            'add_time'        => time(),
            'order_status'    => Order::OS_UNCONFIRMED,
            'shipping_status' => Order::SS_UNSHIPPED,
            'pay_status'      => Order::PS_UNPAYED,
            'agency_id'       => 0 ,//办事处的id
        );

        /* 扩展信息 */
        $order['extension_code'] = '';
        $order['extension_id'] = 0;

        /* 订单中的商品 */
        $cart_goods = self::cart_goods($flow_type, $uid);
        $cart_good_ids = [];
        foreach ($cart_goods as $k => $v) {
            array_push($cart_good_ids, $v['rec_id']);
        }
//        $cart_good_ids = implode(',', $cart_good_ids);

        if (empty($cart_goods))
        {
            return self::formatError(self::BAD_REQUEST, trans('app', 'message.cart.no_goods'));
        }

        /* 检查积分余额是否合法 */
        if ($user_id > 0)
        {
            $user_info = Member::user_info($user_id);

            $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
            if ($order['surplus'] < 0)
            {
                $order['surplus'] = 0;
            }

            // 查询用户有多少积分
            $total_integral = 0;
            foreach ($cart_goods as $goods) {
                $integral = Goods::find()->select(['integral'])->where(['goods_id' => $goods['goods_id']])->column();
                $integral = $integral[0];
                $total_integral = $total_integral + $integral * $goods['goods_number'];
            }

            $scale = ShopConfig::findByCode('integral_scale');
            $scale = $scale->value;
            if($scale > 0){
                $flow_points = $total_integral / ($scale / 100);
            }else{
                $flow_points = 0;
            }

            $user_points = $user_info['pay_points']; // 用户的积分总数

            $order['integral'] = min($order['integral'], $user_points, $flow_points);
            if ($order['integral'] < 0)
            {
                $order['integral'] = 0;
            }
        }
        else
        {
            $order['surplus']  = 0;
            $order['integral'] = 0;
        }


        /* 检查红包是否存在 */
        if ($order['bonus_id'] > 0)
        {
            $bonus = BonusType::bonus_info($order['bonus_id']);

            if (empty($bonus) || $bonus['user_id'] != $user_id || $bonus['order_id'] > 0 || $bonus['min_goods_amount'] > self::cart_amount(true, $flow_type))
            {
                $order['bonus_id'] = 0;
            }
        }

        /* 订单中的商品 */

        if (empty($cart_goods))
        {
            return self::formatError(self::BAD_REQUEST,trans('message.cart.no_goods'));
        }

        /* 检查商品总额是否达到最低限购金额 */
        // app和web有区别，购物车到结算不同
        // app 可以选择要结算的商品
        if ($flow_type == self::CART_GENERAL_GOODS && self::getCartAmount($cart_good_ids) < ShopConfig::findByCode('min_goods_amount')->value)
        {
            return self::formatError(self::BAD_REQUEST,trans('message.good.min_goods_amount'));
        }
        /* 收货人信息 */
        $order['consignee'] = $consignee_info->consignee;
        $order['country'] = $consignee_info->country;
        $order['province'] = $consignee_info->province;
        $order['city'] = $consignee_info->city;
        $order['mobile'] = $consignee_info->mobile;
        $order['tel'] = $consignee_info->tel;
        $order['zipcode'] = $consignee_info->zipcode;
        $order['district'] = $consignee_info->district;
        $order['address'] = $consignee_info->address;
        /* 判断是不是实体商品 */
        foreach ($cart_goods AS $val)
        {
            /* 统计实体商品的个数 */
            if ($val['is_real'])
            {
                $is_real_good=1;
            }
        }
        if(isset($is_real_good))
        {
            // $sql="SELECT shipping_id FROM " . $ecs->table('shipping') . " WHERE shipping_id=".$order['shipping_id'] ." AND enabled =1";
            $shipping_is_real = Shipping::find()->where(['shipping_id' => $order['shipping_id']])->where(['enabled' => 1])->one();
            if(!$shipping_is_real)
            {
                return self::formatError(self::BAD_REQUEST,trans('message.good.min_goods_amount'));
                // show_message($_LANG['flow_no_shipping']);
            }
        }
        /* 订单中的总额 */
        $total = Order::order_fee($order, $cart_goods, $consignee_info,$cart_good_ids,$shipping,$consignee);
        /* 红包 */
        if (!empty($order['bonus_id']))
        {
            $bonus          = BonusType::bonus_info($order['bonus_id']);
            $total['bonus'] = $bonus['type_money'];
        }
        // $total['bonus_formated'] = Goods::price_format($total['bonus'], false);

        $order['bonus']        = isset($bonus)? $bonus['type_money'] : '';

        $order['goods_amount'] = $total['goods_price'];
        $order['discount']     = $total['discount'];
        $order['surplus']      = $total['surplus'];
        $order['tax']          = $total['tax'];

        // 购物车中的商品能享受红包支付的总额
        $discount_amout = self::compute_discount_amount($cart_good_ids);
        // 红包和积分最多能支付的金额为商品总额
        $temp_amout = $order['goods_amount'] - $discount_amout;

        if ($temp_amout <= 0)
        {
            $order['bonus_id'] = 0;
        }

        /* 配送方式 */
        if ($order['shipping_id'] > 0)
        {
            $shipping = Shipping::find()->where(['shipping_id' => $order['shipping_id']])
                ->andWhere(['enabled' => 1])
                ->one();
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
        }
        $order['shipping_fee'] = $total['shipping_fee'];
        $order['insure_fee']   = 0;
        /* 支付方式 */
        if ($order['pay_id'] > 0)
        {
            $payment = payment_info($order['pay_id']);
            $order['pay_name'] = addslashes($payment['pay_name']);
        }
        $order['pay_fee'] = $total['pay_fee'];
        $order['cod_fee'] = $total['cod_fee'];

        /* 商品包装 */

        /* 祝福贺卡 */

        /* 如果全部使用余额支付，检查余额是否足够 没有余额支付*/
        $order['order_amount']  = number_format($total['amount'], 2, '.', '');

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0)
        {
            $order['order_status'] = Order::OS_CONFIRMED;
            $order['confirm_time'] = time();
            $order['pay_status']   = Order::PS_PAYED;
            $order['pay_time']     = time();
            $order['order_amount'] = 0;
        }

        $order['integral_money']   = $total['integral_money'];
        $order['integral']         = $total['integral'];

        $order['parent_id'] = 0;
        $order['order_sn'] = Order::get_order_sn(); //获取新订单号

        /* 插入订单表 */

        unset($order['timestamps']);
        unset($order['perPage']);
        unset($order['incrementing']);
        unset($order['dateFormat']);
        unset($order['morphClass']);
        unset($order['exists']);
        unset($order['wasRecentlyCreated']);
        unset($order['cod_fee']);
        $order['bonus'] = !empty($order['bonus']) ? $order['bonus'] : (!empty($order['bonus_id']) ? $order['bonus_id'] : 0);
        // unset($order['surplus']);

        $new_order_id = Order::insertGetId($order);
        $order['order_id'] = $new_order_id;
        /* 插入订单商品 */
        foreach ($cart_goods as $key => $cart_good) {
            $order_good                 = new OrderGoods;
            $order_good->order_id       = $new_order_id;
            $order_good->goods_id       = $cart_good['goods_id'];
            $order_good->goods_name     = $cart_good['goods_name'];
            $order_good->goods_sn       = $cart_good['goods_sn'];
            $order_good->product_id     = $cart_good['product_id'];
            $order_good->goods_number   = $cart_good['goods_number'];
            $order_good->market_price   = $cart_good['market_price'];
            $order_good->goods_price    = $cart_good['goods_price'];
            $order_good->goods_attr     = $cart_good['goods_attr'];
            $order_good->is_real        = $cart_good['is_real'];
            $order_good->extension_code = $cart_good['extension_code'];
            $order_good->parent_id      = $cart_good['parent_id'];
            $order_good->is_gift        = $cart_good['is_gift'];
            $order_good->goods_attr_id  = $cart_good['goods_attr_id'];
            $order_good->save(false);
        }

        /* 修改拍卖活动状态 */

        /* 处理余额、积分、红包 */

        if ($order['user_id'] > 0 && $order['integral'] > 0)
        {
            AccountLog::logAccountChange(0, 0, 0, $order['integral'] * (-1), trans('message.score.pay'), $order['order_sn']);
        }


        if ($order['bonus_id'] > 0 && $temp_amout > 0)
        {
            UserBonus::useBonus($order['bonus_id'], $new_order_id);
        }

        /* 如果使用库存，且下订单时减库存，则减少库存 */
        if (ShopConfig::findByCode('use_storage')->value == '1' && ShopConfig::findByCode('stock_dec_time')->value == self::SDT_PLACE)
        {
            Order::change_order_goods_storage($order['order_id'], true, self::SDT_PLACE);
        }

        /* 给商家发邮件 */
        /* 增加是否给客服发送邮件选项 */
        /* 如果需要，发短信 */
        /* 如果订单金额为0 处理虚拟卡 */
        if ($order['order_amount'] <= 0)
        {
            // $sql = "SELECT goods_id, goods_name, goods_number AS num FROM ".
            //        $GLOBALS['ecs']->table('cart') .
            //         " WHERE is_real = 0 AND extension_code = 'virtual_card'".
            //         " AND session_id = '".SESS_ID."' AND rec_type = '$flow_type'";

            // $res = $GLOBALS['db']->getAll($sql);
            $res = self::find()
                ->select(['goods_id', 'goods_name', 'goods_number as num'])
                ->where(['is_real' => 0])
                ->andWhere(['extension_code' => 'virtual_card'])
                ->andWhere(['rec_type' => 'flow_type'])
                ->all();

            $virtual_goods = array();
            foreach ($res AS $row)
            {
                $virtual_goods['virtual_card'][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
            }

            if ($virtual_goods AND $flow_type != self::CART_GROUP_BUY_GOODS)
            {
                /* 虚拟卡发货 */
                if (virtual_goods_ship($virtual_goods,$msg, $order['order_sn'], true))
                {
                    /* 如果没有实体商品，修改发货状态，送积分和红包 */
                    // $sql = "SELECT COUNT(*)" .
                    //         " FROM " . $ecs->table('order_goods') .
                    //         " WHERE order_id = '$order[order_id]' " .
                    //         " AND is_real = 1";
                    $get_count = OrderGoods::find()->where(['order_id'=>$order['order_id']])
                        ->andWhere(['is_real'=>1])
                        ->count();

                    if ($get_count <= 0)
                    {
                        /* 修改订单状态 */
                        update_order($order['order_id'], array('shipping_status' => SS_SHIPPED, 'shipping_time' => time()));

                        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
                        if ($order['user_id'] > 0)
                        {
                            /* 取得用户信息 */
                            $user = Member::user_info($order['user_id']);

                            /* 计算并发放积分 */
                            $integral = integral_to_give($order);
                            AccountLog::logAccountChange( 0, 0, intval($integral['rank_points']), intval($integral['custom_points']), trans('message.score.register'), $order['order_sn']);

                            /* 发放红包 */
                            send_order_bonus($order['order_id']);
                        }
                    }
                }
            }

        }
        /* 清空购物车 */
        self::clear_cart_ids($cart_good_ids,$flow_type);
        /* 清除缓存，否则买了商品，但是前台页面读取缓存，商品数量不减少 */
        // clear_all_files();


        /* 插入支付日志 */
        // $order['log_id'] = insert_pay_log($new_order_id, $order['order_amount'], PAY_ORDER);


        if(!empty($order['shipping_name']))
        {
            $order['shipping_name']=trim(stripcslashes($order['shipping_name']));
        }
        $orderObj = Order::findOne(['order_id' => $new_order_id]);

//        Erp::order($orderObj->order_sn, 'order_create');

        return self::formatBody(['order' => $orderObj]);
    }

    /**
     * 获得购物车中的商品
     *
     * @access  public
     * @return  array
     */
    public static function get_cart_goods($cart_good_ids = [])
    {
        /* 初始化 */
        $uid = Token::authorization();
        $goods_list = array();
        $total = array(
            'goods_price'  => 0, // 本店售价合计（有格式）
            'market_price' => 0, // 市场售价合计（有格式）
            'saving'       => 0, // 节省金额（有格式）
            'save_rate'    => 0, // 节省百分比
            'goods_amount' => 0, // 本店售价合计（无格式）
        );

        /* 循环、统计 */

        $res = self::find()
            ->where(['rec_type',self::CART_GENERAL_GOODS])
            ->andWhere(['user_id', $uid])
            ->orderBy('parent_id')
            ->all();
        /* 用于统计购物车中实体商品和虚拟商品的个数 */
        $virtual_goods_count = 0;
        $real_goods_count    = 0;
        foreach ($res as $key => $row) {

            $total['goods_price']  += $row['goods_price'] * $row['goods_number'];
            $total['market_price'] += $row['market_price'] * $row['goods_number'];

            $row['subtotal']     = Goods::price_format($row['goods_price'] * $row['goods_number'], false);
            $row['goods_price']  = Goods::price_format($row['goods_price'], false);
            $row['market_price'] = Goods::price_format($row['market_price'], false);

            /* 统计实体商品和虚拟商品的个数 */
            if ($row['is_real'])
            {
                $real_goods_count++;
            }
            else
            {
                $virtual_goods_count++;
            }

            /* 查询规格 */

            /* 增加是否在购物车里显示商品图 */

            $goods_list[] = $row;
        }
        $total['goods_amount'] = $total['goods_price'];
        $total['saving']       = Goods::price_format($total['market_price'] - $total['goods_price'], false);
        if ($total['market_price'] > 0)
        {
            $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
                    100 / $total['market_price']).'%' : 0;
        }
        $total['goods_price']  = Goods::price_format($total['goods_price'], false);
        $total['market_price'] = Goods::price_format($total['market_price'], false);
        $total['real_goods_count']    = $real_goods_count;
        $total['virtual_goods_count'] = $virtual_goods_count;

        return array('goods_list' => $goods_list, 'total' => $total);
    }

    /**
     * 检查订单中商品库存
     *
     * @access  public
     * @param   array   $arr
     *
     * @return  void
     */
    public static function flow_cart_stock($arr)
    {
        foreach ($arr AS $key => $val)
        {
            $val = intval(make_semiangle($val));
            if ($val <= 0 || !is_numeric($key))
            {
                continue;
            }

            // $sql = "SELECT `goods_id`, `goods_attr_id`, `extension_code` FROM" .$GLOBALS['ecs']->table('cart').
            //        " WHERE rec_id='$key' AND session_id='" . SESS_ID . "'";
            // $goods = $GLOBALS['db']->getRow($sql);

            $goods = self::where('rec_id',$key)->first(['goods_id','goods_attr_id','extension_code']);
            // $sql = "SELECT g.goods_name, g.goods_number, c.product_id ".
            //         "FROM " .$GLOBALS['ecs']->table('goods'). " AS g, ".
            //             $GLOBALS['ecs']->table('cart'). " AS c ".
            //         "WHERE g.goods_id = c.goods_id AND c.rec_id = '$key'";
            // $row = $GLOBALS['db']->getRow($sql);

            $row = Goods::join('cart','goods.goods_id', '=', 'cart.goods_id')
                ->where('cart.rec_id', $key)
                ->select(
                    'goods.goods_name',
                    'goods.goods_number',
                    'cart.product_id'
                )->first();
            //系统启用了库存，检查输入的商品数量是否有效
            if (intval(ShopConfig::findByCode('use_storage')) > 0 && $goods['extension_code'] != 'package_buy')
            {
                if ($row['goods_number'] < $val)
                {
                    // show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                    // $row['goods_number'], $row['goods_number']));
                    // exit;

                    return false;

                }

                /* 是货品 */
                $row['product_id'] = trim($row['product_id']);
                if (!empty($row['product_id']))
                {
                    // $sql = "SELECT product_number FROM " .$GLOBALS['ecs']->table('products'). " WHERE goods_id = '" . $goods['goods_id'] . "' AND product_id = '" . $row['product_id'] . "'";
                    // $product_number = $GLOBALS['db']->getOne($sql);
                    $product_number = Products::where('goods_id',$goods['goods_id'])->where('product_id',$row['product_id'])->value('product_number');
                    if ($product_number < $val)
                    {
                        // show_message(sprintf($GLOBALS['_LANG']['stock_insufficiency'], $row['goods_name'],
                        // $row['goods_number'], $row['goods_number']));
                        // exit;
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 取得购物车总金额
     * @params  boolean $include_gift   是否包括赠品
     * @param   int     $type           类型：默认普通商品
     * @return  float   购物车总金额
     */
    public static function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS)
    {
        // $sql = "SELECT SUM(goods_price * goods_number) " .
        //         " FROM " . $GLOBALS['ecs']->table('cart') .
        //         " WHERE session_id = '" . SESS_ID . "' " .
        //         "AND rec_type = '$type' ";
        $user_id = Token::authorization();
        $res = self::where('rec_type',$type)->where('user_id', $user_id);
        if (!$include_gift)
        {
            // $sql .= ' AND is_gift = 0 AND goods_id > 0';
            $res->where('is_gift',0)->where('goods_id','>',0);
        }
        $total = $res->selectRaw('sum(goods_price * goods_number) as total')
            ->value('total');

        return (float)($total);
    }

    /**
     * 获取已选择商品的价格
     */
    public static function getCartAmount($arr, $include_gift = true, $type = self::CART_GENERAL_GOODS)
    {
        $uid = Token::authorization();
        $carts = self::find()->where(['in', 'rec_id',$arr])->andWhere(['rec_type' => $type])->andWhere(['user_id'=>$uid]);
        if (!$include_gift)
        {
            $carts->where(['is_gift'=>0])->andWhere(['>','goods_id',0]);
        }
        $total = $carts->select('sum(goods_price * goods_number) as total')
            ->column();

        return (float)($total[0]);
    }

    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @return  float   享受红包支付的总额
     */
    public static function compute_discount_amount($cart_good_ids)
    {
        /* 查询优惠活动 */
        $now = time();
        $user_rank = UserRank::getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        // $sql = "SELECT *" .
        //         "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
        //         " WHERE start_time <= '$now'" .
        //         " AND end_time >= '$now'" .
        //         " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'" .
        //         " AND act_type " . db_create_in(array(FavourableActivity::FAT_DISCOUNT, FavourableActivity::FAT_PRICE));
        // $favourable_list = $GLOBALS['db']->getAll($sql);

        $favourable_list = FavourableActivity::find()
            ->where(['<=','start_time',$now])
            ->andWhere(['>=','end_time',$now])
            ->andWhere(['LIKE', "CONCAT(',', user_rank, ',')", "%".$user_rank."%"])
            ->andWhere(['in','act_type',array(FavourableActivity::FAT_DISCOUNT, FavourableActivity::FAT_PRICE)])
            ->asArray()
            ->all();

        if (!$favourable_list)
        {
            return 0;
        }

        $goods_list = Cart::join('goods','cart.goods_id','=','goods.goods_id')
            ->where('cart.parent_id',0)
            ->where(['in', 'cart.rec_id',$cart_good_ids])
            ->where('cart.is_gift',0)
            ->where('cart.rec_type',Cart::CART_GENERAL_GOODS)
            ->get()->toArray();
        if (!$goods_list)
        {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = array();

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable)
        {

            $total_amount = 0;
            if ($favourable['act_range'] == FavourableActivity::FAR_ALL)
            {
                foreach ($goods_list as $goods)
                {
                    $total_amount += $goods['price'] * $goods['amount'];
                }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_CATEGORY)
            {
                // /* 找出分类id的子分类id */
                // $id_list = array();
                // $raw_id_list = explode(',', $favourable['act_range_ext']);
                // foreach ($raw_id_list as $id)
                // {
                //     $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                // }
                // $ids = join(',', array_unique($id_list));

                // foreach ($goods_list as $goods)
                // {
                //     if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false)
                //     {
                //         $total_amount += $goods['price'] * $goods['amount'];
                //     }
                // }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_BRAND)
            {
                foreach ($goods_list as $goods)
                {

                    $brand_id = Goods::where('goods_id',$goods['id'])->value('brand_id');
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $brand_id . ',') !== false)
                    {
                        $total_amount += $goods['price'] * $goods['amount'];
                    }
                }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_GOODS)
            {
                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['id'] . ',') !== false)
                    {
                        $total_amount += $goods['price'] * $goods['amount'];
                    }
                }
            }
            else
            {
                continue;
            }
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0))
            {
                if ($favourable['act_type'] == FavourableActivity::FAT_DISCOUNT)
                {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                }
                elseif ($favourable['act_type'] == FavourableActivity::FAT_PRICE)
                {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }

        return $discount;
    }
    /**
     * 计算购物车中的商品能享受红包支付的总额
     * @return  float   享受红包支付的总额
     */
    public static function compute_discount_check($order_products)
    {
        /* 查询优惠活动 */
        $now = time();
        $user_rank = UserRank::getUserRankByUid();
        $user_rank = ',' . $user_rank['rank_id'] . ',';
        $favourable_list = FavourableActivity::find()->where(['<=','start_time',$now])
            ->andWhere(['>=','end_time',$now])
            ->andWhere(['LIKE', "CONCAT(',', user_rank, ',')", "%".$user_rank."%"])
            ->andWhere(['in', 'act_type', array(FavourableActivity::FAT_DISCOUNT, FavourableActivity::FAT_PRICE)])
            ->asArray()
            ->all();
        if (!$favourable_list)
        {
            return 0;
        }
        $goods_list = $order_products;
        foreach ($goods_list as $key => $good) {
            $good_property = [];
            if ($good['goods_attr_id']) {
                $good_property = explode(',', $good['goods_attr_id']);

            }
            $goods_list[$key]['price'] = Goods::get_final_price($good['goods_id'],$good['goods_number'],true,$good_property) ;
            $goods_list[$key]['amount'] = $good['goods_number'];

        }
        if (!$goods_list)
        {
            return 0;
        }

        /* 初始化折扣 */
        $discount = 0;
        $favourable_name = array();

        /* 循环计算每个优惠活动的折扣 */
        foreach ($favourable_list as $favourable)
        {
            $total_amount = 0;
            if ($favourable['act_range'] == FavourableActivity::FAR_ALL)
            {
                foreach ($goods_list as $goods)
                {
                    $total_amount += $goods['goods_price'] * $goods['goods_number'];

                }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_CATEGORY)
            {
                // /* 找出分类id的子分类id */
                // $id_list = array();
                // $raw_id_list = explode(',', $favourable['act_range_ext']);
                // foreach ($raw_id_list as $id)
                // {
                //     $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                // }
                // $ids = join(',', array_unique($id_list));

                // foreach ($goods_list as $goods)
                // {
                //     if (strpos(',' . $ids . ',', ',' . $goods['cat_id'] . ',') !== false)
                //     {
                //         $total_amount += $goods['price'] * $goods['amount'];
                //     }
                // }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_BRAND)
            {
                foreach ($goods_list as $goods)
                {
                    $brand_id = Goods::where('goods_id',$goods['goods_id'])->value('brand_id');
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $brand_id . ',') !== false)
                    {
                        $total_amount += $goods['goods_price'] * $goods['goods_number'];
                    }
                }
            }
            elseif ($favourable['act_range'] == FavourableActivity::FAR_GOODS)
            {
                foreach ($goods_list as $goods)
                {
                    if (strpos(',' . $favourable['act_range_ext'] . ',', ',' . $goods['goods_id'] . ',') !== false)
                    {
                        $total_amount += $goods['goods_price'] * $goods['goods_number'];
                    }
                }
            }
            else
            {
                continue;
            }
            if ($total_amount > 0 && $total_amount >= $favourable['min_amount'] && ($total_amount <= $favourable['max_amount'] || $favourable['max_amount'] == 0))
            {
                if ($favourable['act_type'] == FavourableActivity::FAT_DISCOUNT)
                {
                    $discount += $total_amount * (1 - $favourable['act_type_ext'] / 100);
                }
                elseif ($favourable['act_type'] == FavourableActivity::FAT_PRICE)
                {
                    $discount += $favourable['act_type_ext'];
                }
            }
        }

        return $discount;
    }

    /**
     * 取得购物车该赠送的积分数
     * @return  int     积分数
     */
    public static function get_give_integral()
    {
        $prefix = Yii::$app->db->tablePrefix;
        $uid = Token::authorization();

        // $sql = "SELECT SUM(c.goods_number * IF(g.give_integral > -1, g.give_integral, c.goods_price))" .
        //         "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, " .
        //                   $GLOBALS['ecs']->table('goods') . " AS g " .
        //         "WHERE c.goods_id = g.goods_id " .
        //         "AND c.session_id = '" . SESS_ID . "' " .
        //         "AND c.goods_id > 0 " .
        //         "AND c.parent_id = 0 " .
        //         "AND c.rec_type = 0 " .
        //         "AND c.is_gift = 0";
        $allIntegral = Cart::find()
            ->select([$prefix.'cart.*', $prefix.'goods.give_integral as give_integral'])
            ->leftJoin($prefix.'goods',$prefix.'goods.goods_id ='.$prefix.'cart.goods_id')
            ->where(['>',$prefix.'cart.goods_id',0])
            ->andWhere([$prefix.'cart.parent_id'=>0])
            ->andWhere([$prefix.'cart.rec_type'=>0])
            ->andWhere([$prefix.'cart.is_gift'=>0])
            ->andWhere(['user_id'=>$uid])
            ->asArray()
            ->all();
        $sum = 0;
        foreach ($allIntegral as $key => $value) {
            if ($value['give_integral'] > -1 ) {
                $sum += $value['give_integral'] * $value['goods_number'];
            }else{
                $sum += $value['goods_price'] * $value['goods_number'];
            }
        }

        return $sum;
    }
    /**
     * 清空指定购物车
     * @param   arr     $arr   购物车id
     * @param   int     $type   类型：默认普通商品
     */
    public static function clear_cart_ids($arr,$type = self::CART_GENERAL_GOODS)
    {
        $uid = Token::authorization();
        if(is_array($arr)){
            $arr = '(' . implode(',', $arr) . ')';
        }

        self::deleteAll(['and', 'rec_id in ' . $arr, 'rec_type = :rec_type', 'user_id = :user_id'], ['rec_type'=>$type, 'user_id'=>$uid]);

    }
}
