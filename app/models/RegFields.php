<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%reg_fields}}".
 *
 * @property integer $id
 * @property string $reg_field_name
 * @property integer $dis_order
 * @property integer $display
 * @property integer $type
 * @property integer $is_need
 */
class RegFields extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reg_fields}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['reg_field_name'], 'required'],
            [['dis_order', 'display', 'type', 'is_need'], 'integer'],
            [['reg_field_name'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'reg_field_name' => Yii::t('app', 'Reg Field Name'),
            'dis_order' => Yii::t('app', 'Dis Order'),
            'display' => Yii::t('app', 'Display'),
            'type' => Yii::t('app', 'Type'),
            'is_need' => Yii::t('app', 'Is Need'),
        ];
    }
}
