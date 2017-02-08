<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%area_region}}".
 *
 * @property integer $shipping_area_id
 * @property integer $region_id
 */
class AreaRegion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%area_region}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shipping_area_id', 'region_id'], 'required'],
            [['shipping_area_id', 'region_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'shipping_area_id' => Yii::t('app', 'Shipping Area ID'),
            'region_id' => Yii::t('app', 'Region ID'),
        ];
    }
}
