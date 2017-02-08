<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%article_cat}}".
 *
 * @property integer $cat_id
 * @property string $cat_name
 * @property integer $cat_type
 * @property string $keywords
 * @property string $cat_desc
 * @property integer $sort_order
 * @property integer $show_in_nav
 * @property integer $parent_id
 */
class ArticleCat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%article_cat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_type', 'sort_order', 'show_in_nav', 'parent_id'], 'integer'],
            [['cat_name', 'keywords', 'cat_desc'], 'string', 'max' => 255],
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
            'cat_type' => Yii::t('app', 'Cat Type'),
            'keywords' => Yii::t('app', 'Keywords'),
            'cat_desc' => Yii::t('app', 'Cat Desc'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'show_in_nav' => Yii::t('app', 'Show In Nav'),
            'parent_id' => Yii::t('app', 'Parent ID'),
        ];
    }
}
