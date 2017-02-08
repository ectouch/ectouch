<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%crons}}".
 *
 * @property integer $cron_id
 * @property string $cron_code
 * @property string $cron_name
 * @property string $cron_desc
 * @property integer $cron_order
 * @property string $cron_config
 * @property integer $thistime
 * @property integer $nextime
 * @property integer $day
 * @property string $week
 * @property string $hour
 * @property string $minute
 * @property integer $enable
 * @property integer $run_once
 * @property string $allow_ip
 * @property string $alow_files
 */
class Crons extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%crons}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cron_code', 'cron_name', 'cron_config', 'nextime', 'day', 'week', 'hour', 'minute', 'alow_files'], 'required'],
            [['cron_desc', 'cron_config'], 'string'],
            [['cron_order', 'thistime', 'nextime', 'day', 'enable', 'run_once'], 'integer'],
            [['cron_code'], 'string', 'max' => 20],
            [['cron_name'], 'string', 'max' => 120],
            [['week'], 'string', 'max' => 1],
            [['hour'], 'string', 'max' => 2],
            [['minute', 'alow_files'], 'string', 'max' => 255],
            [['allow_ip'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cron_id' => Yii::t('app', 'Cron ID'),
            'cron_code' => Yii::t('app', 'Cron Code'),
            'cron_name' => Yii::t('app', 'Cron Name'),
            'cron_desc' => Yii::t('app', 'Cron Desc'),
            'cron_order' => Yii::t('app', 'Cron Order'),
            'cron_config' => Yii::t('app', 'Cron Config'),
            'thistime' => Yii::t('app', 'Thistime'),
            'nextime' => Yii::t('app', 'Nextime'),
            'day' => Yii::t('app', 'Day'),
            'week' => Yii::t('app', 'Week'),
            'hour' => Yii::t('app', 'Hour'),
            'minute' => Yii::t('app', 'Minute'),
            'enable' => Yii::t('app', 'Enable'),
            'run_once' => Yii::t('app', 'Run Once'),
            'allow_ip' => Yii::t('app', 'Allow Ip'),
            'alow_files' => Yii::t('app', 'Alow Files'),
        ];
    }
}
