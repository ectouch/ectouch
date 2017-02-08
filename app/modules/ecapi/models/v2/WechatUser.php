<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%wechat_user}}".
 *
 * @property string $uid
 * @property string $wechat_id
 * @property integer $subscribe
 * @property string $openid
 * @property string $nickname
 * @property integer $sex
 * @property string $city
 * @property string $country
 * @property string $province
 * @property string $language
 * @property string $headimgurl
 * @property string $subscribe_time
 * @property string $remark
 * @property string $privilege
 * @property string $unionid
 * @property string $group_id
 * @property string $ect_uid
 * @property integer $bein_kefu
 * @property integer $isbind
 */
class WechatUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wechat_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wechat_id', 'subscribe', 'sex', 'subscribe_time', 'group_id', 'ect_uid', 'bein_kefu', 'isbind'], 'integer'],
            [['openid', 'nickname', 'city', 'country', 'province', 'headimgurl', 'remark', 'privilege', 'unionid'], 'string', 'max' => 255],
            [['language'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'wechat_id' => 'Wechat ID',
            'subscribe' => 'Subscribe',
            'openid' => 'Openid',
            'nickname' => 'Nickname',
            'sex' => 'Sex',
            'city' => 'City',
            'country' => 'Country',
            'province' => 'Province',
            'language' => 'Language',
            'headimgurl' => 'Headimgurl',
            'subscribe_time' => 'Subscribe Time',
            'remark' => 'Remark',
            'privilege' => 'Privilege',
            'unionid' => 'Unionid',
            'group_id' => 'Group ID',
            'ect_uid' => 'Ect Uid',
            'bein_kefu' => 'Bein Kefu',
            'isbind' => 'Isbind',
        ];
    }
}
