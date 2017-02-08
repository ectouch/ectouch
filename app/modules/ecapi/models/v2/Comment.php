<?php

namespace app\modules\ecapi\models\v2;

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
 * @property integer $order_id
 */
class Comment extends \yii\db\ActiveRecord
{
    const GOODS = 0;
    const ARTICLE = 1;

    const BAD     = 1;            // 差评
    const MEDIUM  = 2;            // 中评
    const GOOD    = 3;            // 好评
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
            [['comment_type', 'id_value', 'comment_rank', 'add_time', 'status', 'parent_id', 'user_id', 'order_id'], 'integer'],
            [['content', 'order_id'], 'required'],
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
            'comment_id' => 'Comment ID',
            'comment_type' => 'Comment Type',
            'id_value' => 'Id Value',
            'email' => 'Email',
            'user_name' => 'User Name',
            'content' => 'Content',
            'comment_rank' => 'Comment Rank',
            'add_time' => 'Add Time',
            'ip_address' => 'Ip Address',
            'status' => 'Status',
            'parent_id' => 'Parent ID',
            'user_id' => 'User ID',
            'order_id' => 'Order ID',
        ];
    }
    /**
     * 根据商品id获取评论
     */
    public static function getCommentById($id){
        $comment = Comment::find()
            ->select(['comment_id', 'user_name as name', 'comment_type', 'id_value', 'content as cont', 'comment_rank', 'add_time as time'])
            ->where(['id_value' => $id, 'comment_type' => 0, 'status' => 1, 'parent_id' => 0, ])
            ->orderBy('comment_id DESC')
            ->asArray()
            ->all();
        foreach ($comment as $k => $v) {
            $comment[$k]['time'] = date('Y-m-d H:i', $comment[$k]['time']);
            if(strlen($comment[$k]['name']) > 4)
            $comment[$k]['name'] = substr($comment[$k]['name'], 0, 1). '****' .substr($comment[$k]['name'] , -1);
        }
        return $comment;
    }
}
