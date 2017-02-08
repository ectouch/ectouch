<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%group_goods}}".
 *
 * @property string $parent_id
 * @property string $goods_id
 * @property string $goods_price
 * @property integer $admin_id
 * @property integer $group_id
 */
class GoodsGroup extends \yii\db\ActiveRecord
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
            [['parent_id', 'goods_id', 'admin_id', 'group_id'], 'required'],
            [['parent_id', 'goods_id', 'admin_id', 'group_id'], 'integer'],
            [['goods_price'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'parent_id' => 'Parent ID',
            'goods_id' => 'Goods ID',
            'goods_price' => 'Goods Price',
            'admin_id' => 'Admin ID',
            'group_id' => 'Group ID',
        ];
    }

    public function getIdAttribute()
    {
        return $this->goods_id;
    }

    public function getPhotoAttribute()
    {
        return GoodsGallery::getPhotosById($this->goods_id);
    }
    public function getNameAttribute()
    {
        return Goods::where('goods_id',$this->goods_id)->value('goods_name');
    }

    public function getPriceAttribute()
    {
        return $this->goods_price;
    }

    public function getCreatedatAttribute()
    {
        return time();
    }
    public function getUpdatedatAttribute()
    {
        return time();
    }

    public function intro($id)
    {
        return Goods::getIntro($id);
    }
    public static function getAccessories($parent_id)
    {
        if($model = self::where('parent_id', $parent_id)->pluck('goods_id'))
        {
            return $model;
        }
        return [];
    }
}
