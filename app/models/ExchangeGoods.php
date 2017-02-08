<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%exchange_goods}}".
 *
 * @property string $goods_id
 * @property string $exchange_integral
 * @property integer $is_exchange
 * @property integer $is_hot
 */
class ExchangeGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%exchange_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id'], 'required'],
            [['goods_id', 'exchange_integral', 'is_exchange', 'is_hot'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_id' => Yii::t('app', 'Goods ID'),
            'exchange_integral' => Yii::t('app', 'Exchange Integral'),
            'is_exchange' => Yii::t('app', 'Is Exchange'),
            'is_hot' => Yii::t('app', 'Is Hot'),
        ];
    }
}
