<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%package_goods}}".
 *
 * @property string $package_id
 * @property string $goods_id
 * @property string $product_id
 * @property integer $goods_number
 * @property integer $admin_id
 */
class PackageGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%package_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['package_id', 'goods_id', 'product_id', 'admin_id'], 'required'],
            [['package_id', 'goods_id', 'product_id', 'goods_number', 'admin_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'package_id' => Yii::t('app', 'Package ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'product_id' => Yii::t('app', 'Product ID'),
            'goods_number' => Yii::t('app', 'Goods Number'),
            'admin_id' => Yii::t('app', 'Admin ID'),
        ];
    }
}
