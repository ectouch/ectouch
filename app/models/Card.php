<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%card}}".
 *
 * @property integer $card_id
 * @property string $card_name
 * @property string $card_img
 * @property string $card_fee
 * @property string $free_money
 * @property string $card_desc
 */
class Card extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%card}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['card_fee', 'free_money'], 'number'],
            [['card_name'], 'string', 'max' => 120],
            [['card_img', 'card_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'card_id' => Yii::t('app', 'Card ID'),
            'card_name' => Yii::t('app', 'Card Name'),
            'card_img' => Yii::t('app', 'Card Img'),
            'card_fee' => Yii::t('app', 'Card Fee'),
            'free_money' => Yii::t('app', 'Free Money'),
            'card_desc' => Yii::t('app', 'Card Desc'),
        ];
    }
}
