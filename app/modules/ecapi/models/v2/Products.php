<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%products}}".
 *
 * @property string $product_id
 * @property string $goods_id
 * @property string $goods_attr
 * @property string $product_sn
 * @property integer $product_number
 */
class Products extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%products}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'product_number'], 'integer'],
            [['goods_attr'], 'string', 'max' => 50],
            [['product_sn'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_id' => 'Product ID',
            'goods_id' => 'Goods ID',
            'goods_attr' => 'Goods Attr',
            'product_sn' => 'Product Sn',
            'product_number' => 'Product Number',
        ];
    }

    public function getIdAttribute()
    {
        return $this->product_id;
    }
    public function getGoodsattrAttribute()
    {
        return $this->attributes['goods_attr'];
    }

    public function getStocknumberAttribute()
    {
        return $this->product_number;
    }

    public function getGoodsattrpriceAttribute()
    {
        return self::getTotalPrice($this->attributes['goods_attr']);
    }

    private function getTotalPrice($goods_attr)
    {
        $attr_ids = (explode('|',$goods_attr));
        $total = UserRank::getMemberRankPriceByGid($this->goods_id);
        if (!$attr_ids) {
            return $total;
        }
        foreach ($attr_ids as $key => $attr_id) {
            $price = GoodsAttr::where('goods_attr_id',$attr_id)->value('attr_price');

            if (!($price)) {
                $price = 0;
            }
            $total += floatval($price);

        }
        return $total;

    }

    /**
     * 取指定规格的货品信息
     *
     * @access      public
     * @param       string      $goods_id
     * @param       array       $spec_goods_attr_id
     * @return      array
     */
    public static function  get_products_info($goods_id, $spec_goods_attr_id)
    {

        $return_array = array();

        if (empty($spec_goods_attr_id) || !is_array($spec_goods_attr_id) || empty($goods_id))
        {
            return $return_array;
        }

        $goods_attr_array = Attribute::sort_goods_attr_id_array($spec_goods_attr_id);

        if(isset($goods_attr_array['sort']))
        {
            $goods_attr = implode('|', $goods_attr_array['sort']);

            $return_array = self::where('goods_id',$goods_id)->where('goods_attr',$goods_attr)->first();

            // if (!empty($return_array)) {
            //     $return_array = $return_array->toArray();
            // }
        }

        return $return_array;
    }
}
