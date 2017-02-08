<?php

namespace app\models;

use Yii;

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
class FavourableActivity extends \yii\db\ActiveRecord
{
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
            'act_id' => Yii::t('app', 'Act ID'),
            'act_name' => Yii::t('app', 'Act Name'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'user_rank' => Yii::t('app', 'User Rank'),
            'act_range' => Yii::t('app', 'Act Range'),
            'act_range_ext' => Yii::t('app', 'Act Range Ext'),
            'min_amount' => Yii::t('app', 'Min Amount'),
            'max_amount' => Yii::t('app', 'Max Amount'),
            'act_type' => Yii::t('app', 'Act Type'),
            'act_type_ext' => Yii::t('app', 'Act Type Ext'),
            'gift' => Yii::t('app', 'Gift'),
            'sort_order' => Yii::t('app', 'Sort Order'),
        ];
    }
}
