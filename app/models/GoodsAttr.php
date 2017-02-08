<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%goods_attr}}".
 *
 * @property string $goods_attr_id
 * @property string $goods_id
 * @property integer $attr_id
 * @property string $attr_value
 * @property string $attr_price
 */
class GoodsAttr extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_attr}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'attr_id'], 'integer'],
            [['attr_value'], 'required'],
            [['attr_value'], 'string'],
            [['attr_price'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_attr_id' => Yii::t('app', 'Goods Attr ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'attr_id' => Yii::t('app', 'Attr ID'),
            'attr_value' => Yii::t('app', 'Attr Value'),
            'attr_price' => Yii::t('app', 'Attr Price'),
        ];
    }
}
