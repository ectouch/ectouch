<?php

namespace app\modules\ecapi\models\v2;

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
class GoodsGallery extends Foundation
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
            'img_id' => 'Img ID',
            'goods_id' => 'Goods ID',
            'img_url' => 'Img Url',
            'img_desc' => 'Img Desc',
            'thumb_url' => 'Thumb Url',
            'img_original' => 'Img Original',
        ];
    }

    /**
     * 商品图片
     * @param  [type] $id [description]
     * @return [type]           [description]
     */
    public static function getPhotosById($id)
    {
        $goods_images = [];

        $model = self::find()->where(['goods_id'=> $id])->asArray()->all();

        if (!empty($model))
        {
            foreach ($model as $value) {

                $photo = self::formatPhoto($value['img_url'], $value['thumb_url']);
                if (!empty($photo) && is_file($photo)) {
                    $goods_images[] = $photo;
                }
            }
        }

        return $goods_images;
    }

    public static function getCategoryPhoto($cat_id)
    {
        //获取分类ids
        $cat_ids = GoodsCategory::where('parent_id', $cat_id)->orWhere('cat_id', $cat_id)->lists('cat_id')->toArray();
        if (!empty($cat_ids)) {
            $goods_id = Goods::whereIn('cat_id', $cat_ids)->where(['is_delete' => 0])->orderBy('is_hot', 'DESC')->first();
            if ($goods_id) {
                return formatPhoto($goods_id->goods_img);
            }
        }

        return null;
    }

    public static function formatPhoto($img, $thumb = null, $domain = null)
    {
        $path = Yii::$app->params['SHOP_URL'] . '/';


        if(!empty($img))
            return  $path . $img;
        else
            return  $path . 'images/no_picture.gif';

    }
}
