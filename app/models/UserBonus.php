<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%user_bonus}}".
 *
 * @property string $bonus_id
 * @property integer $bonus_type_id
 * @property string $bonus_sn
 * @property string $user_id
 * @property string $used_time
 * @property string $order_id
 * @property integer $emailed
 */
class UserBonus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_bonus}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bonus_type_id', 'bonus_sn', 'user_id', 'used_time', 'order_id', 'emailed'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bonus_id' => Yii::t('app', 'Bonus ID'),
            'bonus_type_id' => Yii::t('app', 'Bonus Type ID'),
            'bonus_sn' => Yii::t('app', 'Bonus Sn'),
            'user_id' => Yii::t('app', 'User ID'),
            'used_time' => Yii::t('app', 'Used Time'),
            'order_id' => Yii::t('app', 'Order ID'),
            'emailed' => Yii::t('app', 'Emailed'),
        ];
    }
}
