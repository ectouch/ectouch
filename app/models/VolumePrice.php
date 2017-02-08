<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%volume_price}}".
 *
 * @property integer $price_type
 * @property string $goods_id
 * @property integer $volume_number
 * @property string $volume_price
 */
class VolumePrice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%volume_price}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['price_type', 'goods_id', 'volume_number'], 'required'],
            [['price_type', 'goods_id', 'volume_number'], 'integer'],
            [['volume_price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'price_type' => Yii::t('app', 'Price Type'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'volume_number' => Yii::t('app', 'Volume Number'),
            'volume_price' => Yii::t('app', 'Volume Price'),
        ];
    }
}
