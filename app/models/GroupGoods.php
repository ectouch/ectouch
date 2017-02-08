<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%group_goods}}".
 *
 * @property string $parent_id
 * @property string $goods_id
 * @property string $goods_price
 * @property integer $admin_id
 */
class GroupGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%group_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'goods_id', 'admin_id'], 'required'],
            [['parent_id', 'goods_id', 'admin_id'], 'integer'],
            [['goods_price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'parent_id' => Yii::t('app', 'Parent ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'goods_price' => Yii::t('app', 'Goods Price'),
            'admin_id' => Yii::t('app', 'Admin ID'),
        ];
    }
}
