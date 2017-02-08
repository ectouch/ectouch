<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%stats}}".
 *
 * @property string $access_time
 * @property string $ip_address
 * @property integer $visit_times
 * @property string $browser
 * @property string $system
 * @property string $language
 * @property string $area
 * @property string $referer_domain
 * @property string $referer_path
 * @property string $access_url
 */
class Stats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%stats}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['access_time', 'visit_times'], 'integer'],
            [['ip_address'], 'string', 'max' => 15],
            [['browser'], 'string', 'max' => 60],
            [['system', 'language'], 'string', 'max' => 20],
            [['area'], 'string', 'max' => 30],
            [['referer_domain'], 'string', 'max' => 100],
            [['referer_path'], 'string', 'max' => 200],
            [['access_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'access_time' => Yii::t('app', 'Access Time'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'visit_times' => Yii::t('app', 'Visit Times'),
            'browser' => Yii::t('app', 'Browser'),
            'system' => Yii::t('app', 'System'),
            'language' => Yii::t('app', 'Language'),
            'area' => Yii::t('app', 'Area'),
            'referer_domain' => Yii::t('app', 'Referer Domain'),
            'referer_path' => Yii::t('app', 'Referer Path'),
            'access_url' => Yii::t('app', 'Access Url'),
        ];
    }
}
