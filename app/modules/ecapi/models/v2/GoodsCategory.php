<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use yii\data\Pagination;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property integer $cat_id
 * @property string $cat_name
 * @property string $keywords
 * @property string $cat_desc
 * @property integer $parent_id
 * @property integer $sort_order
 * @property string $template_file
 * @property string $measure_unit
 * @property integer $show_in_nav
 * @property string $style
 * @property integer $is_show
 * @property integer $grade
 * @property string $filter_attr
 */
class GoodsCategory extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'sort_order', 'show_in_nav', 'is_show', 'grade'], 'integer'],
            [['style'], 'required'],
            [['cat_name'], 'string', 'max' => 90],
            [['keywords', 'cat_desc', 'filter_attr'], 'string', 'max' => 255],
            [['template_file'], 'string', 'max' => 50],
            [['measure_unit'], 'string', 'max' => 15],
            [['style'], 'string', 'max' => 150],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cat_id' => 'Cat ID',
            'cat_name' => 'Cat Name',
            'keywords' => 'Keywords',
            'cat_desc' => 'Cat Desc',
            'parent_id' => 'Parent ID',
            'sort_order' => 'Sort Order',
            'template_file' => 'Template File',
            'measure_unit' => 'Measure Unit',
            'show_in_nav' => 'Show In Nav',
            'style' => 'Style',
            'is_show' => 'Is Show',
            'grade' => 'Grade',
            'filter_attr' => 'Filter Attr',
        ];
    }

    public static function getList(array $attributes)
    {
        extract($attributes);

        $model = GoodsCategory::find()->where(['cat_id' => $id]);

        if (isset($category) && $category) {
            //指定分类
            $model->where(['cat_id' => $category])->orWhere(['parent_id'=>$category]);
        } else {
            $model->where('parent_id', 0);
        }

        if (isset($keyword) && $keyword) {

            $model->where('cat_name', 'like', '%'.strip_tags($keyword).'%')->orWhere('cat_id', strip_tags($keyword));
        }

        $total = $model->count();

        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page]);

        $data = $model
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();

        return self::formatBody(['categories' => $data,'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    /**
     * 获取所有分类
     */
    public static function getAllList(array $attributes)
    {
        extract($attributes);

        $model = GoodsCategory::find();

        $data = $model
            ->where(['parent_id' => $id])
            ->andWhere(['is_show' => 1])
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->asArray()
            ->all();

        $path = Yii::$app->params['SHOP_URL'] . '/';

        foreach ($data as $k => $v) {
             $res = Goods::find()
                ->select(['goods_thumb'])
                ->where(['cat_id' => $v['cat_id']])
                 ->andWhere(['is_on_sale' => 1])
                 ->andWhere(['is_delete' => 0])
                ->orderBy('sort_order ')
                ->orderBy('goods_id ')
                ->limit(1)
                ->asArray()
                ->one();

            if(!empty($res['goods_thumb']))
                $data[$k]['goods_thumb'] =   $path . $res['goods_thumb'];
            else
                $data[$k]['goods_thumb'] =   $path . 'images/no_picture.gif';
        }
        foreach($data as $k => $v){

            if (isset($v['cat_id'])) {
                $child_tree = self::getAllList(array('id' => $v['cat_id']));

                if ($child_tree) {
                    $data[$k]['child'] = $child_tree;
                    $data[$k]['haschild'] = 1;
                }
            }
        }
        return $data;
    }


    ///没有用到的先不管

    public static function getCategoryIds($id)
    {
        if($model = GoodsCategory::find()->where(['cat_id' => $id])->andWhere(['is_show' => 1])->orderBy('cat_id ASC')->one())
        {
            $ids = GoodsCategory::find()->select('cat_id')->where(['parent_id' => $id])->andWhere(['is_show' => 1])->orderBy('cat_id ASC')->asArray()->column();


            @array_push($ids, $model->cat_id);

            return $ids;
        }
        return [0];

    }

    private static function getParentCategories($parent_id)
    {
        $model = self::where('parent_id', $parent_id)->where('is_show', 1)->orderBy('cat_id', 'ASC')->get();
        if (!$model->isEmpty()) {
            return $model->toArray();
        }
    }


    public function getIdAttribute()
    {
        return $this->cat_id;
    }
    public function getNameAttribute()
    {
        return $this->cat_name;
    }
    public function getDescAttribute()
    {
        return $this->cat_desc;
    }
    public function getPhotoAttribute()
    {
        if ($this->parent_id == 0) {
            return GoodsGallery::getCategoryPhoto($this->cat_id);
        }

        return null;
    }

    public function getCategoriesAttribute()
    {
        return self::where('parent_id', $this->cat_id)->where('is_show', 1)->orderBy('cat_id', 'ASC')->get();
    }

    public function getMoreAttribute()
    {
        return ($this->parent_id === 0) ? 1 : 0;
    }

    public function parentCategory()
    {
        return $this->belongsTo('app\models\v2\GoodsCategory', 'parent_id', 'id');
    }

    public function categories()
    {
        return $this->hasMany('app\models\v2\GoodsCategory', 'parent_id', 'id');
    }
}
