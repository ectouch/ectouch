<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%vote}}".
 *
 * @property integer $vote_id
 * @property string $vote_name
 * @property string $start_time
 * @property string $end_time
 * @property integer $can_multi
 * @property string $vote_count
 */
class Vote extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%vote}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['start_time', 'end_time', 'can_multi', 'vote_count'], 'integer'],
            [['vote_name'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'vote_id' => Yii::t('app', 'Vote ID'),
            'vote_name' => Yii::t('app', 'Vote Name'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'can_multi' => Yii::t('app', 'Can Multi'),
            'vote_count' => Yii::t('app', 'Vote Count'),
        ];
    }
}
