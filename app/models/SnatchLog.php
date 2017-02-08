<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%snatch_log}}".
 *
 * @property string $log_id
 * @property integer $snatch_id
 * @property string $user_id
 * @property string $bid_price
 * @property string $bid_time
 */
class SnatchLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%snatch_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['snatch_id', 'user_id', 'bid_time'], 'integer'],
            [['bid_price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'log_id' => Yii::t('app', 'Log ID'),
            'snatch_id' => Yii::t('app', 'Snatch ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'bid_price' => Yii::t('app', 'Bid Price'),
            'bid_time' => Yii::t('app', 'Bid Time'),
        ];
    }
}
