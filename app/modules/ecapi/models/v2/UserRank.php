<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use app\modules\ecapi\helpers\Token;


/**
 * This is the model class for table "{{%user_rank}}".
 *
 * @property integer $rank_id
 * @property string $rank_name
 * @property string $min_points
 * @property string $max_points
 * @property integer $discount
 * @property integer $show_price
 * @property integer $special_rank
 */
class UserRank extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_rank}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['min_points', 'max_points', 'discount', 'show_price', 'special_rank'], 'integer'],
            [['rank_name'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rank_id' => 'Rank ID',
            'rank_name' => 'Rank Name',
            'min_points' => 'Min Points',
            'max_points' => 'Max Points',
            'discount' => 'Discount',
            'show_price' => 'Show Price',
            'special_rank' => 'Special Rank',
        ];
    }

    public static function getMemberRankPriceByGid($goods_id)
    {
        $user_rank = self::getUserRankByUid();

        $shop_price = Goods::find()->select(['shop_price'])->where(['goods_id'=>$goods_id])->column();
        $shop_price = $shop_price[0];

        if ($user_rank) {
            if ($price = MemberPrice::getMemberPriceByUid($user_rank['rank_id'], $goods_id)) {
                return $price;
            }
            if ($user_rank['discount']) {
                $member_price = $shop_price * $user_rank['discount'];
            }else{
                $member_price = $shop_price;
            }
            return $member_price;
        } else {
            return $shop_price;
        }
    }

    public static function getUserRankByUid()
    {
        $uid = Token::authorization();
        if (empty($uid)) {
            $data = null;
        } else {

            $user = Member::find()->where(['user_id'=>$uid])->one();
            if (!$user) {
                $data = null;
            } else {
                $user_rank = self::find()
                    ->where(['special_rank' => 0])
                    ->where(['<=', 'min_points', $user->rank_points])
                    ->where(['>', 'max_points', $user->rank_points])
                    ->one();
                $data['rank_id'] = $user_rank->rank_id;
                $data['discount'] = $user_rank->discount * 0.01;
            }
        }
        return $data;
    }
}
