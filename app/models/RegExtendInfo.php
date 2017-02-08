<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%reg_extend_info}}".
 *
 * @property string $Id
 * @property string $user_id
 * @property string $reg_field_id
 * @property string $content
 */
class RegExtendInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%reg_extend_info}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'reg_field_id', 'content'], 'required'],
            [['user_id', 'reg_field_id'], 'integer'],
            [['content'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'Id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'reg_field_id' => Yii::t('app', 'Reg Field ID'),
            'content' => Yii::t('app', 'Content'),
        ];
    }
}
