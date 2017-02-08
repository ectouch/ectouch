<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%auto_manage}}".
 *
 * @property integer $item_id
 * @property string $type
 * @property integer $starttime
 * @property integer $endtime
 */
class AutoManage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%auto_manage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['item_id', 'type', 'starttime', 'endtime'], 'required'],
            [['item_id', 'starttime', 'endtime'], 'integer'],
            [['type'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'item_id' => Yii::t('app', 'Item ID'),
            'type' => Yii::t('app', 'Type'),
            'starttime' => Yii::t('app', 'Starttime'),
            'endtime' => Yii::t('app', 'Endtime'),
        ];
    }
}
