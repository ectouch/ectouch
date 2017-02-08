<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%order_goods}}".
 *
 * @property string $rec_id
 * @property string $order_id
 * @property string $goods_id
 * @property string $goods_name
 * @property string $goods_sn
 * @property string $product_id
 * @property integer $goods_number
 * @property string $market_price
 * @property string $goods_price
 * @property string $goods_attr
 * @property integer $send_number
 * @property integer $is_real
 * @property string $extension_code
 * @property string $parent_id
 * @property integer $is_gift
 * @property string $goods_attr_id
 */
class OrderGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'goods_id', 'product_id', 'goods_number', 'send_number', 'is_real', 'parent_id', 'is_gift'], 'integer'],
            [['market_price', 'goods_price'], 'number'],
            [['goods_attr'], 'required'],
            [['goods_attr'], 'string'],
            [['goods_name'], 'string', 'max' => 120],
            [['goods_sn'], 'string', 'max' => 60],
            [['extension_code'], 'string', 'max' => 30],
            [['goods_attr_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => Yii::t('app', 'Rec ID'),
            'order_id' => Yii::t('app', 'Order ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'goods_name' => Yii::t('app', 'Goods Name'),
            'goods_sn' => Yii::t('app', 'Goods Sn'),
            'product_id' => Yii::t('app', 'Product ID'),
            'goods_number' => Yii::t('app', 'Goods Number'),
            'market_price' => Yii::t('app', 'Market Price'),
            'goods_price' => Yii::t('app', 'Goods Price'),
            'goods_attr' => Yii::t('app', 'Goods Attr'),
            'send_number' => Yii::t('app', 'Send Number'),
            'is_real' => Yii::t('app', 'Is Real'),
            'extension_code' => Yii::t('app', 'Extension Code'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'is_gift' => Yii::t('app', 'Is Gift'),
            'goods_attr_id' => Yii::t('app', 'Goods Attr ID'),
        ];
    }
}
