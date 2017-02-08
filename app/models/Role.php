<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%role}}".
 *
 * @property integer $role_id
 * @property string $role_name
 * @property string $action_list
 * @property string $role_describe
 */
class Role extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%role}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['action_list'], 'required'],
            [['action_list', 'role_describe'], 'string'],
            [['role_name'], 'string', 'max' => 60],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'role_id' => Yii::t('app', 'Role ID'),
            'role_name' => Yii::t('app', 'Role Name'),
            'action_list' => Yii::t('app', 'Action List'),
            'role_describe' => Yii::t('app', 'Role Describe'),
        ];
    }
}
