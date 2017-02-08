<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%comment}}".
 *
 * @property string $comment_id
 * @property integer $comment_type
 * @property string $id_value
 * @property string $email
 * @property string $user_name
 * @property string $content
 * @property integer $comment_rank
 * @property string $add_time
 * @property string $ip_address
 * @property integer $status
 * @property string $parent_id
 * @property string $user_id
 */
class Comment extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%comment}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['comment_type', 'id_value', 'comment_rank', 'add_time', 'status', 'parent_id', 'user_id'], 'integer'],
            [['content'], 'required'],
            [['content'], 'string'],
            [['email', 'user_name'], 'string', 'max' => 60],
            [['ip_address'], 'string', 'max' => 15],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'comment_id' => Yii::t('app', 'Comment ID'),
            'comment_type' => Yii::t('app', 'Comment Type'),
            'id_value' => Yii::t('app', 'Id Value'),
            'email' => Yii::t('app', 'Email'),
            'user_name' => Yii::t('app', 'User Name'),
            'content' => Yii::t('app', 'Content'),
            'comment_rank' => Yii::t('app', 'Comment Rank'),
            'add_time' => Yii::t('app', 'Add Time'),
            'ip_address' => Yii::t('app', 'Ip Address'),
            'status' => Yii::t('app', 'Status'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'user_id' => Yii::t('app', 'User ID'),
        ];
    }
}
