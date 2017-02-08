<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%suppliers}}".
 *
 * @property integer $suppliers_id
 * @property string $suppliers_name
 * @property string $suppliers_desc
 * @property integer $is_check
 */
class Suppliers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%suppliers}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['suppliers_desc'], 'string'],
            [['is_check'], 'integer'],
            [['suppliers_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'suppliers_id' => Yii::t('app', 'Suppliers ID'),
            'suppliers_name' => Yii::t('app', 'Suppliers Name'),
            'suppliers_desc' => Yii::t('app', 'Suppliers Desc'),
            'is_check' => Yii::t('app', 'Is Check'),
        ];
    }
}
