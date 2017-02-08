<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%vote_option}}".
 *
 * @property integer $option_id
 * @property integer $vote_id
 * @property string $option_name
 * @property string $option_count
 * @property integer $option_order
 */
class VoteOption extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%vote_option}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['vote_id', 'option_count', 'option_order'], 'integer'],
            [['option_name'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'option_id' => Yii::t('app', 'Option ID'),
            'vote_id' => Yii::t('app', 'Vote ID'),
            'option_name' => Yii::t('app', 'Option Name'),
            'option_count' => Yii::t('app', 'Option Count'),
            'option_order' => Yii::t('app', 'Option Order'),
        ];
    }
}
