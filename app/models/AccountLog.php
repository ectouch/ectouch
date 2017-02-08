<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%account_log}}".
 *
 * @property string $log_id
 * @property string $user_id
 * @property string $user_money
 * @property string $frozen_money
 * @property integer $rank_points
 * @property integer $pay_points
 * @property string $change_time
 * @property string $change_desc
 * @property integer $change_type
 */
class AccountLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%account_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'user_money', 'frozen_money', 'rank_points', 'pay_points', 'change_time', 'change_desc', 'change_type'], 'required'],
            [['user_id', 'rank_points', 'pay_points', 'change_time', 'change_type'], 'integer'],
            [['user_money', 'frozen_money'], 'number'],
            [['change_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => Yii::t('app', 'Log ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'user_money' => Yii::t('app', 'User Money'),
            'frozen_money' => Yii::t('app', 'Frozen Money'),
            'rank_points' => Yii::t('app', 'Rank Points'),
            'pay_points' => Yii::t('app', 'Pay Points'),
            'change_time' => Yii::t('app', 'Change Time'),
            'change_desc' => Yii::t('app', 'Change Desc'),
            'change_type' => Yii::t('app', 'Change Type'),
        ];
    }
}
