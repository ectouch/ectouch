<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\helpers\Token;
use Yii;
use yii\data\Pagination;

/**
 * This is the model class for table "{{%goods}}".
 *
 * @property string $goods_id
 * @property integer $cat_id
 * @property string $goods_sn
 * @property string $goods_name
 * @property string $goods_name_style
 * @property string $click_count
 * @property integer $brand_id
 * @property string $provider_name
 * @property integer $goods_number
 * @property string $goods_weight
 * @property string $market_price
 * @property string $virtual_sales
 * @property string $shop_price
 * @property string $promote_price
 * @property string $promote_start_date
 * @property string $promote_end_date
 * @property integer $warn_number
 * @property string $keywords
 * @property string $goods_brief
 * @property string $goods_desc
 * @property string $goods_thumb
 * @property string $goods_img
 * @property string $original_img
 * @property integer $is_real
 * @property string $extension_code
 * @property integer $is_on_sale
 * @property integer $is_alone_sale
 * @property integer $is_shipping
 * @property string $integral
 * @property string $add_time
 * @property integer $sort_order
 * @property integer $is_delete
 * @property integer $is_best
 * @property integer $is_new
 * @property integer $is_hot
 * @property integer $is_promote
 * @property integer $bonus_type_id
 * @property string $last_update
 * @property integer $goods_type
 * @property string $seller_note
 * @property integer $give_integral
 * @property integer $rank_integral
 * @property integer $suppliers_id
 * @property integer $is_check
 */
class Goods extends Foundation
{

    const NOSORT     = 0;
    const SALE       = 1;
    const PRICE      = 2;

    const ASC        = 1;
    const DESC       = 2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'click_count', 'brand_id', 'goods_number', 'promote_start_date', 'promote_end_date', 'warn_number', 'is_real', 'is_on_sale', 'is_alone_sale', 'is_shipping', 'integral', 'add_time', 'sort_order', 'is_delete', 'is_best', 'is_new', 'is_hot', 'is_promote', 'bonus_type_id', 'last_update', 'goods_type', 'give_integral', 'rank_integral', 'suppliers_id', 'is_check'], 'integer'],
            [['goods_weight', 'market_price', 'shop_price', 'promote_price'], 'number'],
            [['virtual_sales', 'goods_desc'], 'required'],
            [['goods_desc'], 'string'],
            [['goods_sn', 'goods_name_style'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
            [['provider_name'], 'string', 'max' => 100],
            [['virtual_sales'], 'string', 'max' => 10],
            [['keywords', 'goods_brief', 'goods_thumb', 'goods_img', 'original_img', 'seller_note'], 'string', 'max' => 255],
            [['extension_code'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_id' => 'Goods ID',
            'cat_id' => 'Cat ID',
            'goods_sn' => 'Goods Sn',
            'goods_name' => 'Goods Name',
            'goods_name_style' => 'Goods Name Style',
            'click_count' => 'Click Count',
            'brand_id' => 'Brand ID',
            'provider_name' => 'Provider Name',
            'goods_number' => 'Goods Number',
            'goods_weight' => 'Goods Weight',
            'market_price' => 'Market Price',
            'virtual_sales' => 'Virtual Sales',
            'shop_price' => 'Shop Price',
            'promote_price' => 'Promote Price',
            'promote_start_date' => 'Promote Start Date',
            'promote_end_date' => 'Promote End Date',
            'warn_number' => 'Warn Number',
            'keywords' => 'Keywords',
            'goods_brief' => 'Goods Brief',
            'goods_desc' => 'Goods Desc',
            'goods_thumb' => 'Goods Thumb',
            'goods_img' => 'Goods Img',
            'original_img' => 'Original Img',
            'is_real' => 'Is Real',
            'extension_code' => 'Extension Code',
            'is_on_sale' => 'Is On Sale',
            'is_alone_sale' => 'Is Alone Sale',
            'is_shipping' => 'Is Shipping',
            'integral' => 'Integral',
            'add_time' => 'Add Time',
            'sort_order' => 'Sort Order',
            'is_delete' => 'Is Delete',
            'is_best' => 'Is Best',
            'is_new' => 'Is New',
            'is_hot' => 'Is Hot',
            'is_promote' => 'Is Promote',
            'bonus_type_id' => 'Bonus Type ID',
            'last_update' => 'Last Update',
            'goods_type' => 'Goods Type',
            'seller_note' => 'Seller Note',
            'give_integral' => 'Give Integral',
            'rank_integral' => 'Rank Integral',
            'suppliers_id' => 'Suppliers ID',
            'is_check' => 'Is Check',
        ];
    }

    /**
     * 商品列表
     * @param  array  $attributes [description]
     * @return [type]             [description]
     */
    public static function getList(array $attributes)
    {
        extract($attributes);

        $prefix = Yii::$app->db->tablePrefix;

        //全站商品
        $model = self::find()->where(['is_delete' => 0, 'is_on_sale' => 1]);

        if (isset($keyword) && $keyword) {
            $keyword = trim($keyword);
            $keyword = strip_tags($keyword);
            $model->andWhere(['like', $prefix.'goods.goods_name', strip_tags($keyword)])->orWhere(['keywords' => strip_tags($keyword)])->orWhere([$prefix.'goods.goods_id' => strip_tags($keyword)]);

            // 搜索历史
            Keywords::updateHistory($keyword);
        }

//        if (isset($brand) && $brand) {
//            $model->andWhere(['brand_id' => Brand::getBrandById($brand)]);
//        }

        if (isset($category) && $category) {
            $ids = GoodsCategory::getCategoryIds($category);
            sort($ids);
            $ids = implode(',', $ids);
            $model->andWhere('cat_id in (' . $ids . ')');
        }

        if (isset($sort_key)) {

            switch ($sort_value) {
                case '1':
                    $sort = 'ASC';
                    break;

                case '2':
                    $sort = 'DESC';
                    break;

                default:
                    $sort = 'DESC';
                    break;
            }

            switch ($sort_key) {

                case self::NOSORT:
                    $model->orderBy('sort_order '.$sort);
                    break;

                case self::SALE:
                    if (isset($keyword) || isset($category)) {
                        $model->select([$prefix.'goods.*','sum('.$prefix.'order_goods.goods_number) AS total_sales'])
                            ->leftJoin($prefix.'order_goods', $prefix.'goods.goods_id = ' . $prefix.'order_goods.goods_id')
                            ->groupBy($prefix.'goods.goods_id')
                            ->orderBy('total_sales '.$sort);

                    } else {
                        $model->andWhere('is_hot', 1)->orderBy('sort_order ' . $sort)->orderBy('virtual_sales '.$sort);
                    }
                    break;

                case self::PRICE:
                    $model->orderBy('shop_price '.$sort);
                    break;

                default:
                    $model->orderBy('sort_order DESC');
                    break;
            }
        } else {
            $model->orderBy('sort_order DESC');
        }

        // TODO : 同步
        if ($sort_key == self::SALE ) {
            $data = $model->asArray()->all();
            $total = count($data);
        } else {
            $total = $model->count();
        }

        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page, 'page' => $page-1]);
        $path = Yii::$app->params['SHOP_URL'] . '/';

        $data = $model
            ->addSelect([$prefix.'goods.goods_id', $prefix.'goods.goods_name as title', $prefix.'goods.shop_price as money', 'goods_thumb'])
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();
        foreach ($data as $k => $v) {
            if(!empty($v['goods_thumb']))
                $data[$k]['pic'] = $path . $v['goods_thumb'];
            else
                $data[$k]['pic'] = $path . 'images/no_picture.gif';
        }

        return self::formatBody(['list' => $data,'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    /**
     * 获取推荐商品
     */
    public static function getBestGoodsList(array $attributes){
        extract($attributes);

        $total = self::find()
            ->where(['is_best' => 1])
            ->count();
        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page, 'page' => $page-1]);

        $goods = self::find()
            ->select(['goods_id', 'cat_id', 'goods_name', 'click_count', 'goods_thumb'])
            ->where(['is_best' => 1])
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();
        foreach ($goods as $k => $v) {
            $goods[$k]['shop_price'] = self::get_final_price($v['goods_id'], 1, true);
            $goods[$k]['goods_thumb'] = GoodsGallery::formatPhoto($v['goods_thumb']);
        }

        return $goods;
    }

    /**
     * 商品详情
     */
    public static function getInfo(array $attributes)
    {
        extract($attributes);

        $model = Goods::find()->where(['is_delete' => 0, 'goods_id' => $product]);

        $data = $model
            ->select(['cat_id', 'brand_id', 'goods_name as title', 'shop_price as price', 'market_price as delDetailMoney', 'goods_number as stock', '(SELECT count(*) FROM ecs_order_goods WHERE ecs_order_goods.goods_id = ecs_goods.goods_id )AS sales', 'goods_desc', 'goods_thumb'])
            ->asArray()
            ->one();
        $data['goodsImg'] = GoodsGallery::getPhotosById($product);
        if(empty($data['goodsImg'])){
            $data['goodsImg'][] = GoodsGallery::formatPhoto($data['goods_thumb']);
            unset($data['goods_thumb']);
        }

        $properties = GoodsAttr::getProperById($product);
        $data['properties'] = $properties['pro'];
        $data['specification'] = $properties['spe'];
//        $data = $model->with(['properties','tags','stock','attachments'])->one();
        $data['comment'] = Comment::getCommentById($product);

        if (!$data) {
            return self::formatError(self::NOT_FOUND);
        }

        if (isset($data['is_on_sale']) && !$data['is_on_sale']) {
            return self::formatError(self::BAD_REQUEST, trans('message.good.off_sale'));
        }
        $current_price = UserRank::getMemberRankPriceByGid($product);

        $data['promos'] = FavourableActivity::getPromoByGoods($product,$data['cat_id'], $data['brand_id']);

        return self::formatBody( array_merge($data, ['current_price' => $current_price]));
    }

    /**
     * 取得商品最终使用价格
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $property          规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    public static function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $property = array())
    {
        $final_price   = '0'; //商品最终购买价格
        $volume_price  = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price    = '0'; //商品会员价格
        $prefix = Yii::$app->db->tablePrefix;


        //取得商品优惠价格列表
        $price_list   = self::get_volume_price_list($goods_id, '1');
        if (!empty($price_list))
        {
            foreach ($price_list as $value)
            {
                if ($goods_num >= $value['number'])
                {
                    $volume_price = $value['price'];
                }
            }
        }

        $goods = Goods::find()
            ->select([$prefix.'goods.promote_price',$prefix.'goods.promote_start_date',$prefix.'goods.promote_end_date',$prefix.'member_price.user_price'])
            ->where([$prefix.'goods.goods_id'=>$goods_id])
            ->where([$prefix.'goods.is_delete'=>0])
            ->leftJoin($prefix.'member_price', [$prefix.'member_price.goods_id'=>$prefix.'goods.goods_id'])
            ->asArray()
            ->one();

        $member_price = UserRank::getMemberRankPriceByGid($goods_id);
        $uid = Token::authorization();
        $user_rank = Member::find()
            ->select(['user_rank'])
            ->where(['user_id'=>$uid])
            ->one();
        if(empty($user_rank)){
            return self::formatBody([self::BAD_REQUEST => '没有找到该会员']);
        }
        $user_rank = $user_rank->user_rank;


        $user_price = MemberPrice::getMemberPriceByUid($user_rank,$goods_id);

        $goods['user_price'] = $user_price;
        $goods['shop_price'] = (isset($user_price) && !empty($user_price)) ? $user_price : $member_price;
        /* 计算商品的促销价格 */
        if (is_array($goods) && array_key_exists('promote_price',$goods) &&$goods['promote_price'] > 0) {
            $promote_price = self::bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        }else{
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price    = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price))
        {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        }
        elseif (!empty($volume_price) && empty($promote_price))
        {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        }
        elseif (empty($volume_price) && !empty($promote_price))
        {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        }
        elseif (!empty($volume_price) && !empty($promote_price))
        {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        }
        else
        {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price)
        {
            if (!empty($property))
            {
                $property_price   = GoodsAttr::property_price($property);

                $final_price += $property_price;

            }
        }
        //返回商品最终购买价格
        return $final_price;
    }

    /**
     * 取得商品优惠价格列表
     *
     * @param   string  $goods_id    商品编号
     * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    public static function get_volume_price_list($goods_id, $price_type = '1')
    {
        $volume_price = array();
        $temp_index   = '0';

        $res = VolumePrice::find()
            ->where(['goods_id' => $goods_id])
            ->andWhere(['price_type' =>$price_type])
            ->orderBy('volume_number')
            ->asArray()
            ->all();

        foreach ($res as $k => $v)
        {
            $volume_price[$temp_index]                 = array();
            $volume_price[$temp_index]['number']       = $v['volume_number'];
            $volume_price[$temp_index]['price']        = $v['volume_price'];
            $volume_price[$temp_index]['format_price'] = self::price_format($v['volume_price']);
            $temp_index ++;
        }
        return $volume_price;
    }

    /**
     * 判断某个商品是否正在特价促销期
     *
     * @access  public
     * @param   float   $price      促销价格
     * @param   string  $start      促销开始日期
     * @param   string  $end        促销结束日期
     * @return  float   如果还在促销期则返回促销价，否则返回0
     */
    public static function bargain_price($price, $start, $end)
    {
        if ($price == 0)
        {
            return 0;
        }
        else
        {
            $time = time();
            // $time = gmtime();
            if ($time >= $start && $time <= $end)
            {
                return $price;
            }
            else
            {
                return 0;
            }
        }
    }

    /**
     * 格式化商品价格
     *
     * @access  public
     * @param   float   $price  商品价格
     * @return  string
     */
    public static function price_format($price, $change_price = true)
    {
        $price_format = 1;
        if($price === '')
        {
            $price = 0;
        }
        if ($change_price )
        {
            switch ($price_format)
            {
                case 0:
                    $price = number_format($price, 2, '.', '');
                    break;
                case 1: // 保留不为 0 的尾数
                    $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                    if (substr($price, -1) == '.')
                    {
                        $price = substr($price, 0, -1);
                    }
                    break;
                case 2: // 不四舍五入，保留1位
                    $price = substr(number_format($price, 2, '.', ''), 0, -1);
                    break;
                case 3: // 直接取整
                    $price = intval($price);
                    break;
                case 4: // 四舍五入，保留 1 位
                    $price = number_format($price, 1, '.', '');
                    break;
                case 5: // 先四舍五入，不保留小数
                    $price = round($price);
                    break;
            }
        }
        else
        {
            $price = number_format($price, 2, '.', '');
        }

        // return sprintf('￥%s元', $price);
        return $price;
    }
}
