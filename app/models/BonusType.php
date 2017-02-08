<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%bonus_type}}".
 *
 * @property integer $type_id
 * @property string $type_name
 * @property string $type_money
 * @property integer $send_type
 * @property string $min_amount
 * @property string $max_amount
 * @property integer $send_start_date
 * @property integer $send_end_date
 * @property integer $use_start_date
 * @property integer $use_end_date
 * @property string $min_goods_amount
 */
class BonusType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bonus_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type_money', 'min_amount', 'max_amount', 'min_goods_amount'], 'number'],
            [['send_type', 'send_start_date', 'send_end_date', 'use_start_date', 'use_end_date'], 'integer'],
            [['type_name'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'type_id' => Yii::t('app', 'Type ID'),
            'type_name' => Yii::t('app', 'Type Name'),
            'type_money' => Yii::t('app', 'Type Money'),
            'send_type' => Yii::t('app', 'Send Type'),
            'min_amount' => Yii::t('app', 'Min Amount'),
            'max_amount' => Yii::t('app', 'Max Amount'),
            'send_start_date' => Yii::t('app', 'Send Start Date'),
            'send_end_date' => Yii::t('app', 'Send End Date'),
            'use_start_date' => Yii::t('app', 'Use Start Date'),
            'use_end_date' => Yii::t('app', 'Use End Date'),
            'min_goods_amount' => Yii::t('app', 'Min Goods Amount'),
        ];
    }
}
