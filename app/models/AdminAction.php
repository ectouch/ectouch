<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%admin_action}}".
 *
 * @property integer $action_id
 * @property integer $parent_id
 * @property string $action_code
 * @property string $relevance
 */
class AdminAction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_action}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id'], 'integer'],
            [['action_code', 'relevance'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'action_id' => Yii::t('app', 'Action ID'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'action_code' => Yii::t('app', 'Action Code'),
            'relevance' => Yii::t('app', 'Relevance'),
        ];
    }
}
