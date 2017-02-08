<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%payment}}".
 *
 * @property integer $pay_id
 * @property string $pay_code
 * @property string $pay_name
 * @property string $pay_fee
 * @property string $pay_desc
 * @property integer $pay_order
 * @property string $pay_config
 * @property integer $enabled
 * @property integer $is_cod
 * @property integer $is_online
 */
class Payment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%payment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pay_desc', 'pay_config'], 'required'],
            [['pay_desc', 'pay_config'], 'string'],
            [['pay_order', 'enabled', 'is_cod', 'is_online'], 'integer'],
            [['pay_code'], 'string', 'max' => 20],
            [['pay_name'], 'string', 'max' => 120],
            [['pay_fee'], 'string', 'max' => 10],
            [['pay_code'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'pay_id' => Yii::t('app', 'Pay ID'),
            'pay_code' => Yii::t('app', 'Pay Code'),
            'pay_name' => Yii::t('app', 'Pay Name'),
            'pay_fee' => Yii::t('app', 'Pay Fee'),
            'pay_desc' => Yii::t('app', 'Pay Desc'),
            'pay_order' => Yii::t('app', 'Pay Order'),
            'pay_config' => Yii::t('app', 'Pay Config'),
            'enabled' => Yii::t('app', 'Enabled'),
            'is_cod' => Yii::t('app', 'Is Cod'),
            'is_online' => Yii::t('app', 'Is Online'),
        ];
    }
}
