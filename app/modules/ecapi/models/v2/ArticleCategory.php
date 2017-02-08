<?php

namespace app\modules\ecapi\models\v2;

use yii\data\Pagination;

/**
 * This is the model class for table "{{%article_cat}}".
 *
 * @property integer $cat_id
 * @property string $cat_name
 * @property integer $cat_type
 * @property string $keywords
 * @property string $cat_desc
 * @property integer $sort_order
 * @property integer $show_in_nav
 * @property integer $parent_id
 */
class ArticleCategory extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%article_cat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_type', 'sort_order', 'show_in_nav', 'parent_id'], 'integer'],
            [['cat_name', 'keywords', 'cat_desc'], 'string', 'max' => 255],
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
            'cat_type' => 'Cat Type',
            'keywords' => 'Keywords',
            'cat_desc' => 'Cat Desc',
            'sort_order' => 'Sort Order',
            'show_in_nav' => 'Show In Nav',
            'parent_id' => 'Parent ID',
        ];
    }

    /**
     * @param array $attributes
     * @return array
     */
    public static function getList(array $attributes)
    {
        extract($attributes);

        if (self::find()->where(['parent_id' => $id])->count() > 0) {
            $isArticle = 0;
            $model = self::find()->where(['parent_id' => $id]);
        } else {
            $isArticle = 1;
            $model = Article::find()->where(['cat_id' => $id]);
        }

        $total = $model->count();

        $per_page = $per_page > 20 ? 20 : $per_page;
        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page]);

        $result = $model
            ->orderBy('cat_id', 'DESC')
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();

        $data = [];
        foreach ($result as $key => $vo){
            $data[$key]['id'] = $isArticle ? $vo['article_id'] : $vo['cat_id'];
            $data[$key]['title'] = $isArticle ? $vo['title'] : $vo['cat_name'];
            $data[$key]['type'] = $isArticle ? $vo['article_type'] : $vo['cat_type'];
            $data[$key]['keywords'] = $isArticle ? $vo['keywords'] : $vo['keywords'];
            $data[$key]['description'] = $isArticle ? $vo['description'] : $vo['cat_desc'];
            $data[$key]['cat_id'] = $isArticle ? $vo['cat_id'] : $vo['parent_id'];
            $data[$key]['author'] = $isArticle ? $vo['author'] : '';
            $data[$key]['create_at'] = $isArticle ? date('Y-m-d', $vo['add_time']) : date('Y-m-d');
            $data[$key]['update_at'] = date('Y-m-d');
        }

        return Foundation::formatBody(['articles' => $data, 'paged' => Foundation::formatPaged($page, $per_page, $total)]);
    }
}