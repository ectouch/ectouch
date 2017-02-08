<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use app\modules\ecapi\helpers\Token;
use yii\web\User;

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
class UserAddress extends Foundation
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
            'address_id' => 'Address ID',
            'address_name' => 'Address Name',
            'user_id' => 'User ID',
            'consignee' => 'Consignee',
            'email' => 'Email',
            'country' => 'Country',
            'province' => 'Province',
            'city' => 'City',
            'district' => 'District',
            'address' => 'Address',
            'zipcode' => 'Zipcode',
            'tel' => 'Tel',
            'mobile' => 'Mobile',
            'sign_building' => 'Sign Building',
            'best_time' => 'Best Time',
        ];
    }

    public static function getList()
    {
        $uid = Token::authorization();
        $member = Member::find()->where(['user_id' => $uid])->one();
        $data = static::find()
            ->where(['user_id' => $uid])
            ->asArray()
            ->all();
        foreach ($data as $key => $vo) {
            $data[$key]['full_mobile'] = $vo['mobile'];
            $data[$key]['full_address'] = $vo['address'];
            $data[$key]['mobile'] = mb_substr($vo['mobile'], 0, 3) . '****' . mb_substr($vo['mobile'], -4);
            $data[$key]['address'] = Region::getRegionName($vo['district']) . $vo['address'];
            $data[$key]['default'] = ($member['address_id'] == $vo['address_id']) ? 1 : 0;
        }
        return self::formatBody(['consignees' => $data]);
    }

    public static function get_consignee($consignee)
    {
        $uid = Token::authorization();
        $arr = array();
        if ($consignee) {
            return self::find()->where(['address_id' => $consignee])->one();
        }
        if ($uid > 0) {
            /* 取默认地址 */
            // $sql = "SELECT ua.*".
            //         " FROM " . $GLOBALS['ecs']->table('user_address') . "AS ua, ".$GLOBALS['ecs']->table('users').' AS u '.
            //         " WHERE u.user_id='$uid' AND ua.address_id = u.address_id";

            // $arr = $GLOBALS['db']->getRow($sql);
            $arr = self::find()->with('users')
                ->where(['user_id'=> $uid])
                ->one();
        }

        return $arr;
    }
    public function getUsers(){
        return $this->hasOne(User::className(), ['address_id' => 'address_id']);

    }

    public static function remove(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();
        // UserAddress::where('address_id', $consignee)->where('user_id', $uid)->delete();
        $address = UserAddress::find()->where(['address_id' => $consignee, 'user_id' => $uid])->one();
        if ($address->delete()) {
            if ($address = static::find()->where(['user_id' => $uid])->one()) {
                $model = Member::find()->where(['user_id' => $uid])->one();
                $model->address_id = $address->address_id;
                $model->save();
            }
        }
        return self::formatBody();
    }

    public static function add(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        $arr = Region::getParentId($region);

        $model = new UserAddress;
        $model->user_id = $uid;
        $model->consignee = $name;
        $model->email = '';
        $model->country = !empty($arr['country']) ? $arr['country'] : '';
        $model->province = !empty($arr['province']) ? $arr['province'] : '';
        $model->city = !empty($arr['city']) ? $arr['city'] : '';
        $model->district = !empty($arr['region']) ? $arr['region'] : '';
        $model->address = $address;
        $model->mobile = isset($mobile) ? $mobile : '';
        $model->tel = isset($tel) ? $tel : '';
        $model->zipcode = isset($zip_code) ? $zip_code : '';
        $model->address_name = '';
        $model->sign_building = '';
        $model->best_time = '';

        if ($model->save()) {
            $member = Member::find()->where(['user_id' => $uid])->one();

            if (!UserAddress::find()->where(['address_id' => $member->address_id])->one()) {
                $member->address_id = $model->address_id;
                $member->save();
            }

            return self::formatBody(['consignee' => self::formatBody($model->toArray())]);
        }

        return self::formatError(self::UNKNOWN_ERROR);

    }

    public static function modify(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        if ($model = UserAddress::find()->where(['address_id' => $consignee, 'user_id' => $uid])->one()) {
            $arr = Region::getParentId($region);

            $model->user_id = $uid;
            $model->consignee = $name;
            $model->country = !empty($arr['country']) ? $arr['country'] : '';
            $model->province = !empty($arr['province']) ? $arr['province'] : '';
            $model->city = !empty($arr['city']) ? $arr['city'] : '';
            $model->district = !empty($arr['region']) ? $arr['region'] : '';
            $model->address = $address;
            $model->mobile = isset($mobile) ? $mobile : ' ';
            $model->tel = isset($tel) ? $tel : ' ';
            $model->zipcode = isset($zip_code) ? $zip_code : ' ';

            if ($model->save()) {
                return self::formatBody(['consignee' => self::formatBody($model->toArray())]);
            }
        }

        return self::formatError(self::UNKNOWN_ERROR);

    }

    public static function setDefault(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        if (UserAddress::find()->where(['address_id' => $consignee, 'user_id' => $uid])->one()) {
            if ($model = Member::find()->where(['user_id' => $uid])->one()) {
                $model->address_id = $consignee;
                $model->save();
                return self::formatBody();
            }
        }

        return self::formatError(self::BAD_REQUEST, trans('app', 'message.address.error'));
    }

    public static function getRegionIdList($address_id)
    {
        $arr = [];
        if ($model = UserAddress::where('address_id', $address_id)->first()) {
            $arr['country'] = $model->country;
            $arr['province'] = $model->province;
            $arr['city'] = $model->city;
            $arr['district'] = $model->district;
        }

        return $arr;
    }
}
