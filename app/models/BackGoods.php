<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%back_goods}}".
 *
 * @property string $rec_id
 * @property string $back_id
 * @property string $goods_id
 * @property string $product_id
 * @property string $product_sn
 * @property string $goods_name
 * @property string $brand_name
 * @property string $goods_sn
 * @property integer $is_real
 * @property integer $send_number
 * @property string $goods_attr
 */
class BackGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%back_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['back_id', 'goods_id', 'product_id', 'is_real', 'send_number'], 'integer'],
            [['goods_attr'], 'string'],
            [['product_sn', 'brand_name', 'goods_sn'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => Yii::t('app', 'Rec ID'),
            'back_id' => Yii::t('app', 'Back ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'product_id' => Yii::t('app', 'Product ID'),
            'product_sn' => Yii::t('app', 'Product Sn'),
            'goods_name' => Yii::t('app', 'Goods Name'),
            'brand_name' => Yii::t('app', 'Brand Name'),
            'goods_sn' => Yii::t('app', 'Goods Sn'),
            'is_real' => Yii::t('app', 'Is Real'),
            'send_number' => Yii::t('app', 'Send Number'),
            'goods_attr' => Yii::t('app', 'Goods Attr'),
        ];
    }
}
