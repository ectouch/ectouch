<?php

namespace app\models;

use Yii;

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
class UserRank extends \yii\db\ActiveRecord
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
            'rank_id' => Yii::t('app', 'Rank ID'),
            'rank_name' => Yii::t('app', 'Rank Name'),
            'min_points' => Yii::t('app', 'Min Points'),
            'max_points' => Yii::t('app', 'Max Points'),
            'discount' => Yii::t('app', 'Discount'),
            'show_price' => Yii::t('app', 'Show Price'),
            'special_rank' => Yii::t('app', 'Special Rank'),
        ];
    }
}
