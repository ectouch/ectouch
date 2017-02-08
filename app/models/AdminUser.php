<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%admin_user}}".
 *
 * @property integer $user_id
 * @property string $user_name
 * @property string $email
 * @property string $password
 * @property string $ec_salt
 * @property integer $add_time
 * @property integer $last_login
 * @property string $last_ip
 * @property string $action_list
 * @property string $nav_list
 * @property string $lang_type
 * @property integer $agency_id
 * @property integer $suppliers_id
 * @property string $todolist
 * @property integer $role_id
 */
class AdminUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%admin_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['add_time', 'last_login', 'agency_id', 'suppliers_id', 'role_id'], 'integer'],
            [['action_list', 'nav_list', 'agency_id'], 'required'],
            [['action_list', 'nav_list', 'todolist'], 'string'],
            [['user_name', 'email'], 'string', 'max' => 60],
            [['password'], 'string', 'max' => 32],
            [['ec_salt'], 'string', 'max' => 10],
            [['last_ip'], 'string', 'max' => 15],
            [['lang_type'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => Yii::t('app', 'User ID'),
            'user_name' => Yii::t('app', 'User Name'),
            'email' => Yii::t('app', 'Email'),
            'password' => Yii::t('app', 'Password'),
            'ec_salt' => Yii::t('app', 'Ec Salt'),
            'add_time' => Yii::t('app', 'Add Time'),
            'last_login' => Yii::t('app', 'Last Login'),
            'last_ip' => Yii::t('app', 'Last Ip'),
            'action_list' => Yii::t('app', 'Action List'),
            'nav_list' => Yii::t('app', 'Nav List'),
            'lang_type' => Yii::t('app', 'Lang Type'),
            'agency_id' => Yii::t('app', 'Agency ID'),
            'suppliers_id' => Yii::t('app', 'Suppliers ID'),
            'todolist' => Yii::t('app', 'Todolist'),
            'role_id' => Yii::t('app', 'Role ID'),
        ];
    }
}
