<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%shop_config}}".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $code
 * @property string $type
 * @property string $store_range
 * @property string $store_dir
 * @property string $value
 * @property integer $sort_order
 */
class ShopConfig extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%shop_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'sort_order'], 'integer'],
            [['value'], 'required'],
            [['value'], 'string'],
            [['code'], 'string', 'max' => 30],
            [['type'], 'string', 'max' => 10],
            [['store_range', 'store_dir'], 'string', 'max' => 255],
            [['code'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'code' => Yii::t('app', 'Code'),
            'type' => Yii::t('app', 'Type'),
            'store_range' => Yii::t('app', 'Store Range'),
            'store_dir' => Yii::t('app', 'Store Dir'),
            'value' => Yii::t('app', 'Value'),
            'sort_order' => Yii::t('app', 'Sort Order'),
        ];
    }
}
