<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%attribute}}".
 *
 * @property integer $attr_id
 * @property integer $cat_id
 * @property string $attr_name
 * @property integer $attr_input_type
 * @property integer $attr_type
 * @property string $attr_values
 * @property integer $attr_index
 * @property integer $sort_order
 * @property integer $is_linked
 * @property integer $attr_group
 */
class Attribute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%attribute}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'attr_input_type', 'attr_type', 'attr_index', 'sort_order', 'is_linked', 'attr_group'], 'integer'],
            [['attr_values'], 'required'],
            [['attr_values'], 'string'],
            [['attr_name'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'attr_id' => Yii::t('app', 'Attr ID'),
            'cat_id' => Yii::t('app', 'Cat ID'),
            'attr_name' => Yii::t('app', 'Attr Name'),
            'attr_input_type' => Yii::t('app', 'Attr Input Type'),
            'attr_type' => Yii::t('app', 'Attr Type'),
            'attr_values' => Yii::t('app', 'Attr Values'),
            'attr_index' => Yii::t('app', 'Attr Index'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'is_linked' => Yii::t('app', 'Is Linked'),
            'attr_group' => Yii::t('app', 'Attr Group'),
        ];
    }
}
