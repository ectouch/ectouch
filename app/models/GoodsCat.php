<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%goods_cat}}".
 *
 * @property string $goods_id
 * @property integer $cat_id
 */
class GoodsCat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_cat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id', 'cat_id'], 'required'],
            [['goods_id', 'cat_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'goods_id' => Yii::t('app', 'Goods ID'),
            'cat_id' => Yii::t('app', 'Cat ID'),
        ];
    }
}
