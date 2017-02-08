<?php

namespace app\models;

use Yii;

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
 */
class Cart extends \yii\db\ActiveRecord
{
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
            [['goods_attr'], 'required'],
            [['goods_attr'], 'string'],
            [['session_id'], 'string', 'max' => 32],
            [['goods_sn'], 'string', 'max' => 60],
            [['goods_name'], 'string', 'max' => 120],
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
            'user_id' => Yii::t('app', 'User ID'),
            'session_id' => Yii::t('app', 'Session ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'goods_sn' => Yii::t('app', 'Goods Sn'),
            'product_id' => Yii::t('app', 'Product ID'),
            'goods_name' => Yii::t('app', 'Goods Name'),
            'market_price' => Yii::t('app', 'Market Price'),
            'goods_price' => Yii::t('app', 'Goods Price'),
            'goods_number' => Yii::t('app', 'Goods Number'),
            'goods_attr' => Yii::t('app', 'Goods Attr'),
            'is_real' => Yii::t('app', 'Is Real'),
            'extension_code' => Yii::t('app', 'Extension Code'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'rec_type' => Yii::t('app', 'Rec Type'),
            'is_gift' => Yii::t('app', 'Is Gift'),
            'is_shipping' => Yii::t('app', 'Is Shipping'),
            'can_handsel' => Yii::t('app', 'Can Handsel'),
            'goods_attr_id' => Yii::t('app', 'Goods Attr ID'),
        ];
    }
}
