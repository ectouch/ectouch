<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%searchengine}}".
 *
 * @property string $date
 * @property string $searchengine
 * @property string $count
 */
class Searchengine extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%searchengine}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'searchengine'], 'required'],
            [['date'], 'safe'],
            [['count'], 'integer'],
            [['searchengine'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'date' => Yii::t('app', 'Date'),
            'searchengine' => Yii::t('app', 'Searchengine'),
            'count' => Yii::t('app', 'Count'),
        ];
    }
}
