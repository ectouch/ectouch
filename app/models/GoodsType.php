<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%goods_type}}".
 *
 * @property integer $cat_id
 * @property string $cat_name
 * @property integer $enabled
 * @property string $attr_group
 */
class GoodsType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_type}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['enabled'], 'integer'],
            [['attr_group'], 'required'],
            [['cat_name'], 'string', 'max' => 60],
            [['attr_group'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cat_id' => Yii::t('app', 'Cat ID'),
            'cat_name' => Yii::t('app', 'Cat Name'),
            'enabled' => Yii::t('app', 'Enabled'),
            'attr_group' => Yii::t('app', 'Attr Group'),
        ];
    }
}
