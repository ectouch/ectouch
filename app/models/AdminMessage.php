<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%admin_message}}".
 *
 * @property integer $message_id
 * @property integer $sender_id
 * @property integer $receiver_id
 * @property string $sent_time
 * @property string $read_time
 * @property integer $readed
 * @property integer $deleted
 * @property string $title
 * @property string $message
 */
class AdminMessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_message}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sender_id', 'receiver_id', 'sent_time', 'read_time', 'readed', 'deleted'], 'integer'],
            [['message'], 'required'],
            [['message'], 'string'],
            [['title'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'message_id' => Yii::t('app', 'Message ID'),
            'sender_id' => Yii::t('app', 'Sender ID'),
            'receiver_id' => Yii::t('app', 'Receiver ID'),
            'sent_time' => Yii::t('app', 'Sent Time'),
            'read_time' => Yii::t('app', 'Read Time'),
            'readed' => Yii::t('app', 'Readed'),
            'deleted' => Yii::t('app', 'Deleted'),
            'title' => Yii::t('app', 'Title'),
            'message' => Yii::t('app', 'Message'),
        ];
    }
}
