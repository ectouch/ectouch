<?php

namespace app\modules\ecapi\models\v2;

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
            'price_type' => 'Price Type',
            'goods_id' => 'Goods ID',
            'volume_number' => 'Volume Number',
            'volume_price' => 'Volume Price',
        ];
    }
}
