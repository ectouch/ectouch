<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%shipping_area}}".
 *
 * @property integer $shipping_area_id
 * @property string $shipping_area_name
 * @property integer $shipping_id
 * @property string $configure
 */
class ShippingArea extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shipping_area}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shipping_id'], 'integer'],
            [['configure'], 'required'],
            [['configure'], 'string'],
            [['shipping_area_name'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_area_id' => Yii::t('app', 'Shipping Area ID'),
            'shipping_area_name' => Yii::t('app', 'Shipping Area Name'),
            'shipping_id' => Yii::t('app', 'Shipping ID'),
            'configure' => Yii::t('app', 'Configure'),
        ];
    }
}
