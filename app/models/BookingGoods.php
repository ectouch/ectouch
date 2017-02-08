<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%booking_goods}}".
 *
 * @property string $rec_id
 * @property string $user_id
 * @property string $email
 * @property string $link_man
 * @property string $tel
 * @property string $goods_id
 * @property string $goods_desc
 * @property integer $goods_number
 * @property string $booking_time
 * @property integer $is_dispose
 * @property string $dispose_user
 * @property string $dispose_time
 * @property string $dispose_note
 */
class BookingGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%booking_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'goods_id', 'goods_number', 'booking_time', 'is_dispose', 'dispose_time'], 'integer'],
            [['email', 'link_man', 'tel'], 'string', 'max' => 60],
            [['goods_desc', 'dispose_note'], 'string', 'max' => 255],
            [['dispose_user'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => Yii::t('app', 'Rec ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'email' => Yii::t('app', 'Email'),
            'link_man' => Yii::t('app', 'Link Man'),
            'tel' => Yii::t('app', 'Tel'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'goods_desc' => Yii::t('app', 'Goods Desc'),
            'goods_number' => Yii::t('app', 'Goods Number'),
            'booking_time' => Yii::t('app', 'Booking Time'),
            'is_dispose' => Yii::t('app', 'Is Dispose'),
            'dispose_user' => Yii::t('app', 'Dispose User'),
            'dispose_time' => Yii::t('app', 'Dispose Time'),
            'dispose_note' => Yii::t('app', 'Dispose Note'),
        ];
    }
}
