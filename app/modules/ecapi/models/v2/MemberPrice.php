<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%member_price}}".
 *
 * @property string $price_id
 * @property string $goods_id
 * @property integer $user_rank
 * @property string $user_price
 */
class MemberPrice extends Foundation
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
            'price_id' => 'Price ID',
            'goods_id' => 'Goods ID',
            'user_rank' => 'User Rank',
            'user_price' => 'User Price',
        ];
    }

    public static function getMemberPriceByUid($rank,$goods_id)
    {
        $price = self::find()->select(['user_price'])->where(['user_rank'=>$rank])->where(['goods_id'=>$goods_id])->column();
        if($price)
            $price = $price[0];
        return $price;
    }
}
