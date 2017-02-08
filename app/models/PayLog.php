<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%pay_log}}".
 *
 * @property string $log_id
 * @property string $order_id
 * @property string $order_amount
 * @property integer $order_type
 * @property integer $is_paid
 */
class PayLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%pay_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'order_type', 'is_paid'], 'integer'],
            [['order_amount'], 'required'],
            [['order_amount'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => Yii::t('app', 'Log ID'),
            'order_id' => Yii::t('app', 'Order ID'),
            'order_amount' => Yii::t('app', 'Order Amount'),
            'order_type' => Yii::t('app', 'Order Type'),
            'is_paid' => Yii::t('app', 'Is Paid'),
        ];
    }
}
