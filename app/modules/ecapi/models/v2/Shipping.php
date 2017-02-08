<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%shipping}}".
 *
 * @property integer $shipping_id
 * @property string $shipping_code
 * @property string $shipping_name
 * @property string $shipping_desc
 * @property string $insure
 * @property integer $support_cod
 * @property integer $enabled
 * @property string $shipping_print
 * @property string $print_bg
 * @property string $config_lable
 * @property integer $print_model
 * @property integer $shipping_order
 */
class Shipping extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shipping}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['support_cod', 'enabled', 'print_model', 'shipping_order'], 'integer'],
            [['shipping_print'], 'required'],
            [['shipping_print', 'config_lable'], 'string'],
            [['shipping_code'], 'string', 'max' => 20],
            [['shipping_name'], 'string', 'max' => 120],
            [['shipping_desc', 'print_bg'], 'string', 'max' => 255],
            [['insure'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_id' => 'Shipping ID',
            'shipping_code' => 'Shipping Code',
            'shipping_name' => 'Shipping Name',
            'shipping_desc' => 'Shipping Desc',
            'insure' => 'Insure',
            'support_cod' => 'Support Cod',
            'enabled' => 'Enabled',
            'shipping_print' => 'Shipping Print',
            'print_bg' => 'Print Bg',
            'config_lable' => 'Config Lable',
            'print_model' => 'Print Model',
            'shipping_order' => 'Shipping Order',
        ];
    }

    /**
     * 原findone函数
     */
    public static function total_shipping_fee($address, $goods, $shipping_id)
    {
        //格式化，拿到需要的goods_id 和数量
        $products = [];
        $IsShippingFree = true;

        foreach ($goods as $key => $value) {
            $products[$key]['goods_id'] = $value['goods_id'];
            $products[$key]['num'] = $value['goods_number'];
            $is_shipping = Goods::find()->select('is_shipping')->where(['goods_id' => $value['goods_id']])->column();
            $products[$key]['is_shipping'] = $is_shipping;

            if(!intval($is_shipping)){
                $IsShippingFree = false;
            }
        }

//        Log::error('findOne: '.var_export($products,true));

        // 查看购物车中是否全为免运费商品，若是则把运费赋为零
        if($IsShippingFree){
            return 0;
        }

//        $products = json_encode($products);
//        self::$attrs = ['products' => $products];
        $region_id_list = UserAddress::getRegionIdList($address);


        $model = Shipping::join('shipping_area', 'shipping_area.shipping_id', '=', 'shipping.shipping_id')
            ->join('area_region', 'area_region.shipping_area_id', '=', 'shipping_area.shipping_area_id')
            ->whereIn('area_region.region_id', $region_id_list)
            ->where('shipping.enabled', 1)
            ->where('shipping.shipping_id', $shipping_id)
            ->first();

        if(count($model) > 0){
            return $model->fee;
        }
        return false;
    }

    /**
     * 查询所有配送方式
     */
    public static function getAllShipping()
    {
        $shipping = self::find()
            ->where(['enabled' => 1])
            ->asArray()
            ->all();

        if(count($shipping) > 0){
            return $shipping;
        }
        return self::formatBody(self::BAD_REQUEST, trans('message.shipping.error'));
    }
}
