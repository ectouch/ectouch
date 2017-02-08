<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%plugins}}".
 *
 * @property string $code
 * @property string $version
 * @property string $library
 * @property integer $assign
 * @property string $install_date
 */
class Plugins extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%plugins}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
            [['assign', 'install_date'], 'integer'],
            [['code'], 'string', 'max' => 30],
            [['version'], 'string', 'max' => 10],
            [['library'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'code' => Yii::t('app', 'Code'),
            'version' => Yii::t('app', 'Version'),
            'library' => Yii::t('app', 'Library'),
            'assign' => Yii::t('app', 'Assign'),
            'install_date' => Yii::t('app', 'Install Date'),
        ];
    }
}
