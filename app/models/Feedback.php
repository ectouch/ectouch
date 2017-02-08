<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%feedback}}".
 *
 * @property string $msg_id
 * @property string $parent_id
 * @property string $user_id
 * @property string $user_name
 * @property string $user_email
 * @property string $msg_title
 * @property integer $msg_type
 * @property integer $msg_status
 * @property string $msg_content
 * @property string $msg_time
 * @property string $message_img
 * @property string $order_id
 * @property integer $msg_area
 */
class Feedback extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%feedback}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'user_id', 'msg_type', 'msg_status', 'msg_time', 'order_id', 'msg_area'], 'integer'],
            [['msg_content'], 'required'],
            [['msg_content'], 'string'],
            [['user_name', 'user_email'], 'string', 'max' => 60],
            [['msg_title'], 'string', 'max' => 200],
            [['message_img'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'msg_id' => Yii::t('app', 'Msg ID'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'user_name' => Yii::t('app', 'User Name'),
            'user_email' => Yii::t('app', 'User Email'),
            'msg_title' => Yii::t('app', 'Msg Title'),
            'msg_type' => Yii::t('app', 'Msg Type'),
            'msg_status' => Yii::t('app', 'Msg Status'),
            'msg_content' => Yii::t('app', 'Msg Content'),
            'msg_time' => Yii::t('app', 'Msg Time'),
            'message_img' => Yii::t('app', 'Message Img'),
            'order_id' => Yii::t('app', 'Order ID'),
            'msg_area' => Yii::t('app', 'Msg Area'),
        ];
    }
}
