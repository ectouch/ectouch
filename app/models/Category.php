<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property integer $cat_id
 * @property string $cat_name
 * @property string $keywords
 * @property string $cat_desc
 * @property integer $parent_id
 * @property integer $sort_order
 * @property string $template_file
 * @property string $measure_unit
 * @property integer $show_in_nav
 * @property string $style
 * @property integer $is_show
 * @property integer $grade
 * @property string $filter_attr
 */
class Category extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'sort_order', 'show_in_nav', 'is_show', 'grade'], 'integer'],
            [['style'], 'required'],
            [['cat_name'], 'string', 'max' => 90],
            [['keywords', 'cat_desc', 'filter_attr'], 'string', 'max' => 255],
            [['template_file'], 'string', 'max' => 50],
            [['measure_unit'], 'string', 'max' => 15],
            [['style'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cat_id' => Yii::t('app', 'Cat ID'),
            'cat_name' => Yii::t('app', 'Cat Name'),
            'keywords' => Yii::t('app', 'Keywords'),
            'cat_desc' => Yii::t('app', 'Cat Desc'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'template_file' => Yii::t('app', 'Template File'),
            'measure_unit' => Yii::t('app', 'Measure Unit'),
            'show_in_nav' => Yii::t('app', 'Show In Nav'),
            'style' => Yii::t('app', 'Style'),
            'is_show' => Yii::t('app', 'Is Show'),
            'grade' => Yii::t('app', 'Grade'),
            'filter_attr' => Yii::t('app', 'Filter Attr'),
        ];
    }
}
