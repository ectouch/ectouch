<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%admin_log}}".
 *
 * @property string $log_id
 * @property string $log_time
 * @property integer $user_id
 * @property string $log_info
 * @property string $ip_address
 */
class AdminLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['log_time', 'user_id'], 'integer'],
            [['log_info'], 'string', 'max' => 255],
            [['ip_address'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => Yii::t('app', 'Log ID'),
            'log_time' => Yii::t('app', 'Log Time'),
            'user_id' => Yii::t('app', 'User ID'),
            'log_info' => Yii::t('app', 'Log Info'),
            'ip_address' => Yii::t('app', 'Ip Address'),
        ];
    }
}
