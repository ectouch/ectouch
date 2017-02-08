<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use yii\db\Query;

/**
 * This is the model class for table "{{%favourable_activity}}".
 *
 * @property integer $act_id
 * @property string $act_name
 * @property string $start_time
 * @property string $end_time
 * @property string $user_rank
 * @property integer $act_range
 * @property string $act_range_ext
 * @property string $min_amount
 * @property string $max_amount
 * @property integer $act_type
 * @property string $act_type_ext
 * @property string $gift
 * @property integer $sort_order
 */
class FavourableActivity extends Foundation
{

    const 	FAT_GOODS                 = 0; // 送赠品或优惠购买
    const 	FAT_PRICE                 = 1; // 现金减免
    const 	FAT_DISCOUNT              = 2; // 价格打折优惠

    /* 优惠活动的优惠范围 */
    const   FAR_ALL                   = 0; // 全部商品
    const   FAR_CATEGORY              = 1; // 按分类选择
    const   FAR_BRAND                 = 2; // 按品牌选择
    const   FAR_GOODS                 = 3; // 按商品选择

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%favourable_activity}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['act_name', 'start_time', 'end_time', 'user_rank', 'act_range', 'act_range_ext', 'min_amount', 'max_amount', 'act_type', 'act_type_ext', 'gift'], 'required'],
            [['start_time', 'end_time', 'act_range', 'act_type', 'sort_order'], 'integer'],
            [['min_amount', 'max_amount', 'act_type_ext'], 'number'],
            [['gift'], 'string'],
            [['act_name', 'user_rank', 'act_range_ext'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'act_id' => 'Act ID',
            'act_name' => 'Act Name',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'user_rank' => 'User Rank',
            'act_range' => 'Act Range',
            'act_range_ext' => 'Act Range Ext',
            'min_amount' => 'Min Amount',
            'max_amount' => 'Max Amount',
            'act_type' => 'Act Type',
            'act_type_ext' => 'Act Type Ext',
            'gift' => 'Gift',
            'sort_order' => 'Sort Order',
        ];
    }


    public static function getPromoByGoods($goods_id, $cat_id, $brand_id)
    {
        $data = [];
        $now = time();
        $sql = '';

        $user_rank = UserRank::getUserRankByUid();
        if (!empty($user_rank)) {
            $sql = ' AND FIND_IN_SET(' . $user_rank['rank_id'] . ', `user_rank`)';
        }

        $model = self::find()->where('(`start_time` < '.$now.' AND `end_time` > '.$now.') AND (`act_range` = 0 OR (`act_range` = 1 AND FIND_IN_SET('.$cat_id.',`act_range_ext`)) OR (`act_range` = 2 AND FIND_IN_SET('.$brand_id.',`act_range_ext`)) OR (`act_range` = 3 AND FIND_IN_SET('.$goods_id.',`act_range_ext`)))' . $sql)->all();

        if (!empty($model)) {

            foreach ($model as $key => $value) {
                switch ($value->act_type) {
                    case 0:
                        // 满赠
                        $data[$key]['promo'] = '满'.$value->min_amount.'送赠品';
                        break;

                    case 1:
                        // 满减
                        $data[$key]['promo'] = '满'.$value->min_amount.'减'.$value->act_type_ext;
                        break;

                    case 2:
                        // 满折
                        $data[$key]['promo'] = '满'.$value->min_amount.'打'.($value->act_type_ext/10).'折';
                        break;

                    default:
                        $data[$key]['promo'] =  $value->act_name;
                        break;
                }

                $data[$key]['name'] = $value->act_name;
            }


        }
        return $data;
    }
}
