<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%goods_gallery}}".
 *
 * @property string $img_id
 * @property string $goods_id
 * @property string $img_url
 * @property string $img_desc
 * @property string $thumb_url
 * @property string $img_original
 */
class GoodsGallery extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_gallery}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['goods_id'], 'integer'],
            [['img_url', 'img_desc', 'thumb_url', 'img_original'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'img_id' => Yii::t('app', 'Img ID'),
            'goods_id' => Yii::t('app', 'Goods ID'),
            'img_url' => Yii::t('app', 'Img Url'),
            'img_desc' => Yii::t('app', 'Img Desc'),
            'thumb_url' => Yii::t('app', 'Thumb Url'),
            'img_original' => Yii::t('app', 'Img Original'),
        ];
    }
}
