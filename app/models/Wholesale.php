<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%wholesale}}".
 *
 * @property string $act_id
 * @property string $goods_id
 * @property string $goods_name
 * @property string $rank_ids
 * @property string $prices
 * @property integer $enabled
 */
class Wholesale extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%wholesale}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'goods_name', 'rank_ids', 'prices', 'enabled'], 'required'],
            [['goods_id', 'enabled'], 'integer'],
            [['prices'], 'string'],
            [['goods_name', 'rank_ids'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'act_id' => Yii::t('app', 'Act ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'goods_name' => Yii::t('app', 'Goods Name'),
            'rank_ids' => Yii::t('app', 'Rank Ids'),
            'prices' => Yii::t('app', 'Prices'),
            'enabled' => Yii::t('app', 'Enabled'),
        ];
    }
}
