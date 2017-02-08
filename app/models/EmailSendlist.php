<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%email_sendlist}}".
 *
 * @property integer $id
 * @property string $email
 * @property integer $template_id
 * @property string $email_content
 * @property integer $error
 * @property integer $pri
 * @property integer $last_send
 */
class EmailSendlist extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%email_sendlist}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email', 'template_id', 'email_content', 'pri', 'last_send'], 'required'],
            [['template_id', 'error', 'pri', 'last_send'], 'integer'],
            [['email_content'], 'string'],
            [['email'], 'string', 'max' => 100],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'email' => Yii::t('app', 'Email'),
            'template_id' => Yii::t('app', 'Template ID'),
            'email_content' => Yii::t('app', 'Email Content'),
            'error' => Yii::t('app', 'Error'),
            'pri' => Yii::t('app', 'Pri'),
            'last_send' => Yii::t('app', 'Last Send'),
        ];
    }
}
