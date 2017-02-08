<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%user_account}}".
 *
 * @property string $id
 * @property string $user_id
 * @property string $admin_user
 * @property string $amount
 * @property integer $add_time
 * @property integer $paid_time
 * @property string $admin_note
 * @property string $user_note
 * @property integer $process_type
 * @property string $payment
 * @property integer $is_paid
 */
class UserAccount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_account}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'add_time', 'paid_time', 'process_type', 'is_paid'], 'integer'],
            [['admin_user', 'amount', 'admin_note', 'user_note', 'payment'], 'required'],
            [['amount'], 'number'],
            [['admin_user', 'admin_note', 'user_note'], 'string', 'max' => 255],
            [['payment'], 'string', 'max' => 90],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'admin_user' => Yii::t('app', 'Admin User'),
            'amount' => Yii::t('app', 'Amount'),
            'add_time' => Yii::t('app', 'Add Time'),
            'paid_time' => Yii::t('app', 'Paid Time'),
            'admin_note' => Yii::t('app', 'Admin Note'),
            'user_note' => Yii::t('app', 'User Note'),
            'process_type' => Yii::t('app', 'Process Type'),
            'payment' => Yii::t('app', 'Payment'),
            'is_paid' => Yii::t('app', 'Is Paid'),
        ];
    }
}
