<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%sessions}}".
 *
 * @property string $sesskey
 * @property string $expiry
 * @property string $userid
 * @property string $adminid
 * @property string $ip
 * @property string $user_name
 * @property integer $user_rank
 * @property string $discount
 * @property string $email
 * @property string $data
 */
class Sessions extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sessions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sesskey', 'user_name', 'user_rank', 'discount', 'email'], 'required'],
            [['expiry', 'userid', 'adminid', 'user_rank'], 'integer'],
            [['discount'], 'number'],
            [['sesskey'], 'string', 'max' => 32],
            [['ip'], 'string', 'max' => 15],
            [['user_name', 'email'], 'string', 'max' => 60],
            [['data'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sesskey' => Yii::t('app', 'Sesskey'),
            'expiry' => Yii::t('app', 'Expiry'),
            'userid' => Yii::t('app', 'Userid'),
            'adminid' => Yii::t('app', 'Adminid'),
            'ip' => Yii::t('app', 'Ip'),
            'user_name' => Yii::t('app', 'User Name'),
            'user_rank' => Yii::t('app', 'User Rank'),
            'discount' => Yii::t('app', 'Discount'),
            'email' => Yii::t('app', 'Email'),
            'data' => Yii::t('app', 'Data'),
        ];
    }
}
