<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%collect_goods}}".
 *
 * @property string $rec_id
 * @property string $user_id
 * @property string $goods_id
 * @property string $add_time
 * @property integer $is_attention
 */
class CollectGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%collect_goods}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'goods_id', 'add_time', 'is_attention'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rec_id' => Yii::t('app', 'Rec ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'add_time' => Yii::t('app', 'Add Time'),
            'is_attention' => Yii::t('app', 'Is Attention'),
        ];
    }
}
