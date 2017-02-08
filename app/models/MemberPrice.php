<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%member_price}}".
 *
 * @property string $price_id
 * @property string $goods_id
 * @property integer $user_rank
 * @property string $user_price
 */
class MemberPrice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%member_price}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'user_rank'], 'integer'],
            [['user_price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'price_id' => Yii::t('app', 'Price ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'user_rank' => Yii::t('app', 'User Rank'),
            'user_price' => Yii::t('app', 'User Price'),
        ];
    }
}
