<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%brand}}".
 *
 * @property integer $brand_id
 * @property string $brand_name
 * @property string $brand_logo
 * @property string $brand_desc
 * @property string $site_url
 * @property integer $sort_order
 * @property integer $is_show
 */
class Brand extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%brand}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['brand_desc'], 'required'],
            [['brand_desc'], 'string'],
            [['sort_order', 'is_show'], 'integer'],
            [['brand_name'], 'string', 'max' => 60],
            [['brand_logo'], 'string', 'max' => 80],
            [['site_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'brand_id' => Yii::t('app', 'Brand ID'),
            'brand_name' => Yii::t('app', 'Brand Name'),
            'brand_logo' => Yii::t('app', 'Brand Logo'),
            'brand_desc' => Yii::t('app', 'Brand Desc'),
            'site_url' => Yii::t('app', 'Site Url'),
            'sort_order' => Yii::t('app', 'Sort Order'),
            'is_show' => Yii::t('app', 'Is Show'),
        ];
    }
}
