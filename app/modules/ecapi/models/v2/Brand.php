<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%brand}}".
 *
 * @property integer $brand_id
 * @property string $brand_name
 * @property string $brand_logo
 * @property string $brand_desc
 * @property string $site_url
 * @property integer $sort_order
 * @property integer $is_show
 */
class Brand extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%brand}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['brand_desc'], 'required'],
            [['brand_desc'], 'string'],
            [['sort_order', 'is_show'], 'integer'],
            [['brand_name'], 'string', 'max' => 60],
            [['brand_logo'], 'string', 'max' => 80],
            [['site_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'brand_id' => 'Brand ID',
            'brand_name' => 'Brand Name',
            'brand_logo' => 'Brand Logo',
            'brand_desc' => 'Brand Desc',
            'site_url' => 'Site Url',
            'sort_order' => 'Sort Order',
            'is_show' => 'Is Show',
        ];
    }

    public static function getBrandByName($name)
    {
        $model = Brand::where('brand_name', $name)->first();
        if ($model) {
            return [
                'id'   => $model->brand_id,
                'name' => $model->brand_name,
                'logo' => formatPhoto($model->brand_logo, null)
            ];
        } else {
            return [
                'id'   => null,
                'name' => $name,
                'logo' => null
            ];
        }

    }

    public static function getBrandById($id)
    {
        $brand_name = Brand::find()->select(['brand_name'])->where(['brand_id' => $id])->column();
        if(!empty($brand_name))
            $brand_name = $brand_name[0];
        return $brand_name;
    }



    public static function getList(array $attributes)
    {
        extract($attributes);

        $total = Brand::count();

        $data = Brand::paginate($per_page)
            ->toArray();
        return self::formatBody(['brands' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public static function getListByOrder(array $attributes)
    {
        extract($attributes);

        $total = Brand::count();

        $data = Brand::orderBy('sort_order', 'ASC')
            ->paginate($per_page)
            ->toArray();

        return self::formatBody(['brands' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public function getIdAttribute()
    {
        return $this->attributes['brand_id'];    }

    public function getNameAttribute()
    {
        return $this->attributes['brand_name'];
    }

    public function getLogoAttribute()
    {
        return formatPhoto($this->attributes['brand_logo'], null);
    }
}
