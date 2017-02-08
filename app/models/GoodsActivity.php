<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%goods_activity}}".
 *
 * @property string $act_id
 * @property string $act_name
 * @property string $act_desc
 * @property integer $act_type
 * @property string $goods_id
 * @property string $product_id
 * @property string $goods_name
 * @property string $start_time
 * @property string $end_time
 * @property integer $is_finished
 * @property string $ext_info
 */
class GoodsActivity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_activity}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['act_name', 'act_desc', 'act_type', 'goods_id', 'goods_name', 'start_time', 'end_time', 'is_finished', 'ext_info'], 'required'],
            [['act_desc', 'ext_info'], 'string'],
            [['act_type', 'goods_id', 'product_id', 'start_time', 'end_time', 'is_finished'], 'integer'],
            [['act_name', 'goods_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'act_id' => Yii::t('app', 'Act ID'),
            'act_name' => Yii::t('app', 'Act Name'),
            'act_desc' => Yii::t('app', 'Act Desc'),
            'act_type' => Yii::t('app', 'Act Type'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'product_id' => Yii::t('app', 'Product ID'),
            'goods_name' => Yii::t('app', 'Goods Name'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'is_finished' => Yii::t('app', 'Is Finished'),
            'ext_info' => Yii::t('app', 'Ext Info'),
        ];
    }
}
