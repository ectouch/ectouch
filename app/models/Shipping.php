<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%shipping}}".
 *
 * @property integer $shipping_id
 * @property string $shipping_code
 * @property string $shipping_name
 * @property string $shipping_desc
 * @property string $insure
 * @property integer $support_cod
 * @property integer $enabled
 * @property string $shipping_print
 * @property string $print_bg
 * @property string $config_lable
 * @property integer $print_model
 * @property integer $shipping_order
 */
class Shipping extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shipping}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['support_cod', 'enabled', 'print_model', 'shipping_order'], 'integer'],
            [['shipping_print'], 'required'],
            [['shipping_print', 'config_lable'], 'string'],
            [['shipping_code'], 'string', 'max' => 20],
            [['shipping_name'], 'string', 'max' => 120],
            [['shipping_desc', 'print_bg'], 'string', 'max' => 255],
            [['insure'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_id' => Yii::t('app', 'Shipping ID'),
            'shipping_code' => Yii::t('app', 'Shipping Code'),
            'shipping_name' => Yii::t('app', 'Shipping Name'),
            'shipping_desc' => Yii::t('app', 'Shipping Desc'),
            'insure' => Yii::t('app', 'Insure'),
            'support_cod' => Yii::t('app', 'Support Cod'),
            'enabled' => Yii::t('app', 'Enabled'),
            'shipping_print' => Yii::t('app', 'Shipping Print'),
            'print_bg' => Yii::t('app', 'Print Bg'),
            'config_lable' => Yii::t('app', 'Config Lable'),
            'print_model' => Yii::t('app', 'Print Model'),
            'shipping_order' => Yii::t('app', 'Shipping Order'),
        ];
    }
}
