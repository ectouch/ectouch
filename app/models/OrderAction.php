<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%order_action}}".
 *
 * @property string $action_id
 * @property string $order_id
 * @property string $action_user
 * @property integer $order_status
 * @property integer $shipping_status
 * @property integer $pay_status
 * @property integer $action_place
 * @property string $action_note
 * @property string $log_time
 */
class OrderAction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_action}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'order_status', 'shipping_status', 'pay_status', 'action_place', 'log_time'], 'integer'],
            [['action_user'], 'string', 'max' => 30],
            [['action_note'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'action_id' => Yii::t('app', 'Action ID'),
            'order_id' => Yii::t('app', 'Order ID'),
            'action_user' => Yii::t('app', 'Action User'),
            'order_status' => Yii::t('app', 'Order Status'),
            'shipping_status' => Yii::t('app', 'Shipping Status'),
            'pay_status' => Yii::t('app', 'Pay Status'),
            'action_place' => Yii::t('app', 'Action Place'),
            'action_note' => Yii::t('app', 'Action Note'),
            'log_time' => Yii::t('app', 'Log Time'),
        ];
    }
}
