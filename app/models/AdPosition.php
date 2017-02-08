<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%ad_position}}".
 *
 * @property integer $position_id
 * @property string $position_name
 * @property integer $ad_width
 * @property integer $ad_height
 * @property string $position_desc
 * @property string $position_style
 */
class AdPosition extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ad_position}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ad_width', 'ad_height'], 'integer'],
            [['position_style'], 'required'],
            [['position_style'], 'string'],
            [['position_name'], 'string', 'max' => 60],
            [['position_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'position_id' => Yii::t('app', 'Position ID'),
            'position_name' => Yii::t('app', 'Position Name'),
            'ad_width' => Yii::t('app', 'Ad Width'),
            'ad_height' => Yii::t('app', 'Ad Height'),
            'position_desc' => Yii::t('app', 'Position Desc'),
            'position_style' => Yii::t('app', 'Position Style'),
        ];
    }
}
