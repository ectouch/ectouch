<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%user_feed}}".
 *
 * @property string $feed_id
 * @property string $user_id
 * @property string $value_id
 * @property string $goods_id
 * @property integer $feed_type
 * @property integer $is_feed
 */
class UserFeed extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_feed}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'value_id', 'goods_id', 'feed_type', 'is_feed'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'feed_id' => Yii::t('app', 'Feed ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'value_id' => Yii::t('app', 'Value ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'feed_type' => Yii::t('app', 'Feed Type'),
            'is_feed' => Yii::t('app', 'Is Feed'),
        ];
    }
}
