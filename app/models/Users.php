<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%users}}".
 *
 * @property string $user_id
 * @property string $email
 * @property string $user_name
 * @property string $password
 * @property string $question
 * @property string $answer
 * @property integer $sex
 * @property string $birthday
 * @property string $user_money
 * @property string $frozen_money
 * @property string $pay_points
 * @property string $rank_points
 * @property string $address_id
 * @property string $reg_time
 * @property string $last_login
 * @property string $last_time
 * @property string $last_ip
 * @property integer $visit_count
 * @property integer $user_rank
 * @property integer $is_special
 * @property string $ec_salt
 * @property string $salt
 * @property integer $parent_id
 * @property integer $flag
 * @property string $alias
 * @property string $msn
 * @property string $qq
 * @property string $office_phone
 * @property string $home_phone
 * @property string $mobile_phone
 * @property integer $is_validated
 * @property string $credit_line
 * @property string $passwd_question
 * @property string $passwd_answer
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sex', 'pay_points', 'rank_points', 'address_id', 'reg_time', 'last_login', 'visit_count', 'user_rank', 'is_special', 'parent_id', 'flag', 'is_validated'], 'integer'],
            [['birthday', 'last_time'], 'safe'],
            [['user_money', 'frozen_money', 'credit_line'], 'number'],
            [['alias', 'msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone', 'credit_line'], 'required'],
            [['email', 'user_name', 'alias', 'msn'], 'string', 'max' => 60],
            [['password'], 'string', 'max' => 32],
            [['question', 'answer', 'passwd_answer'], 'string', 'max' => 255],
            [['last_ip'], 'string', 'max' => 15],
            [['ec_salt', 'salt'], 'string', 'max' => 10],
            [['qq', 'office_phone', 'home_phone', 'mobile_phone'], 'string', 'max' => 20],
            [['passwd_question'], 'string', 'max' => 50],
            [['user_name'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'email' => Yii::t('app', 'Email'),
            'user_name' => Yii::t('app', 'User Name'),
            'password' => Yii::t('app', 'Password'),
            'question' => Yii::t('app', 'Question'),
            'answer' => Yii::t('app', 'Answer'),
            'sex' => Yii::t('app', 'Sex'),
            'birthday' => Yii::t('app', 'Birthday'),
            'user_money' => Yii::t('app', 'User Money'),
            'frozen_money' => Yii::t('app', 'Frozen Money'),
            'pay_points' => Yii::t('app', 'Pay Points'),
            'rank_points' => Yii::t('app', 'Rank Points'),
            'address_id' => Yii::t('app', 'Address ID'),
            'reg_time' => Yii::t('app', 'Reg Time'),
            'last_login' => Yii::t('app', 'Last Login'),
            'last_time' => Yii::t('app', 'Last Time'),
            'last_ip' => Yii::t('app', 'Last Ip'),
            'visit_count' => Yii::t('app', 'Visit Count'),
            'user_rank' => Yii::t('app', 'User Rank'),
            'is_special' => Yii::t('app', 'Is Special'),
            'ec_salt' => Yii::t('app', 'Ec Salt'),
            'salt' => Yii::t('app', 'Salt'),
            'parent_id' => Yii::t('app', 'Parent ID'),
            'flag' => Yii::t('app', 'Flag'),
            'alias' => Yii::t('app', 'Alias'),
            'msn' => Yii::t('app', 'Msn'),
            'qq' => Yii::t('app', 'Qq'),
            'office_phone' => Yii::t('app', 'Office Phone'),
            'home_phone' => Yii::t('app', 'Home Phone'),
            'mobile_phone' => Yii::t('app', 'Mobile Phone'),
            'is_validated' => Yii::t('app', 'Is Validated'),
            'credit_line' => Yii::t('app', 'Credit Line'),
            'passwd_question' => Yii::t('app', 'Passwd Question'),
            'passwd_answer' => Yii::t('app', 'Passwd Answer'),
        ];
    }
}
