<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%ad}}".
 *
 * @property integer $ad_id
 * @property integer $position_id
 * @property integer $media_type
 * @property string $ad_name
 * @property string $ad_link
 * @property string $ad_code
 * @property integer $start_time
 * @property integer $end_time
 * @property string $link_man
 * @property string $link_email
 * @property string $link_phone
 * @property string $click_count
 * @property integer $enabled
 */
class Ad extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ad}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['position_id', 'media_type', 'start_time', 'end_time', 'click_count', 'enabled'], 'integer'],
            [['ad_code'], 'required'],
            [['ad_code'], 'string'],
            [['ad_name', 'link_man', 'link_email', 'link_phone'], 'string', 'max' => 60],
            [['ad_link'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ad_id' => Yii::t('app', 'Ad ID'),
            'position_id' => Yii::t('app', 'Position ID'),
            'media_type' => Yii::t('app', 'Media Type'),
            'ad_name' => Yii::t('app', 'Ad Name'),
            'ad_link' => Yii::t('app', 'Ad Link'),
            'ad_code' => Yii::t('app', 'Ad Code'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'link_man' => Yii::t('app', 'Link Man'),
            'link_email' => Yii::t('app', 'Link Email'),
            'link_phone' => Yii::t('app', 'Link Phone'),
            'click_count' => Yii::t('app', 'Click Count'),
            'enabled' => Yii::t('app', 'Enabled'),
        ];
    }
}
