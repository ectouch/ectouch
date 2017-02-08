<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%user_address}}".
 *
 * @property string $address_id
 * @property string $address_name
 * @property string $user_id
 * @property string $consignee
 * @property string $email
 * @property integer $country
 * @property integer $province
 * @property integer $city
 * @property integer $district
 * @property string $address
 * @property string $zipcode
 * @property string $tel
 * @property string $mobile
 * @property string $sign_building
 * @property string $best_time
 */
class UserAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_address}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'country', 'province', 'city', 'district'], 'integer'],
            [['address_name'], 'string', 'max' => 50],
            [['consignee', 'email', 'zipcode', 'tel', 'mobile'], 'string', 'max' => 60],
            [['address', 'sign_building', 'best_time'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'address_id' => Yii::t('app', 'Address ID'),
            'address_name' => Yii::t('app', 'Address Name'),
            'user_id' => Yii::t('app', 'User ID'),
            'consignee' => Yii::t('app', 'Consignee'),
            'email' => Yii::t('app', 'Email'),
            'country' => Yii::t('app', 'Country'),
            'province' => Yii::t('app', 'Province'),
            'city' => Yii::t('app', 'City'),
            'district' => Yii::t('app', 'District'),
            'address' => Yii::t('app', 'Address'),
            'zipcode' => Yii::t('app', 'Zipcode'),
            'tel' => Yii::t('app', 'Tel'),
            'mobile' => Yii::t('app', 'Mobile'),
            'sign_building' => Yii::t('app', 'Sign Building'),
            'best_time' => Yii::t('app', 'Best Time'),
        ];
    }
}
