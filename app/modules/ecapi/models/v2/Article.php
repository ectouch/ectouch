<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%article}}".
 *
 * @property string $article_id
 * @property integer $cat_id
 * @property string $title
 * @property string $content
 * @property string $author
 * @property string $author_email
 * @property string $keywords
 * @property integer $article_type
 * @property integer $is_open
 * @property string $add_time
 * @property string $file_url
 * @property integer $open_type
 * @property string $link
 * @property string $description
 */
class Article extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%article}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'article_type', 'is_open', 'add_time', 'open_type'], 'integer'],
            [['content'], 'required'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 150],
            [['author'], 'string', 'max' => 30],
            [['author_email'], 'string', 'max' => 60],
            [['keywords', 'file_url', 'link', 'description'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'article_id' => 'Article ID',
            'cat_id' => 'Cat ID',
            'title' => 'Title',
            'content' => 'Content',
            'author' => 'Author',
            'author_email' => 'Author Email',
            'keywords' => 'Keywords',
            'article_type' => 'Article Type',
            'is_open' => 'Is Open',
            'add_time' => 'Add Time',
            'file_url' => 'File Url',
            'open_type' => 'Open Type',
            'link' => 'Link',
            'description' => 'Description',
        ];
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public static function getList(array $attributes)
    {
        extract($attributes);

        $model = static::find()->where(['cat_id' => $id]);

        $total = $model->count();

        $per_page = $per_page > 20 ? 20 : $per_page;
        $pages = new Pagination(['totalCount' => $total, 'pageSize' => $per_page]);

        $result = $model
            ->orderBy('add_time', 'DESC')
            ->orderBy('article_id', 'DESC')
            ->offset($pages->offset)
            ->limit($pages->limit)
            ->asArray()
            ->all();

        $data = [];
        foreach ($result as $key => $vo) {
            $data[$key]['id'] = $vo['article_id'];
            $data[$key]['title'] = $vo['title'];
            $data[$key]['type'] = $vo['article_type'];
            $data[$key]['keywords'] = $vo['keywords'];
            $data[$key]['description'] = $vo['description'];
            $data[$key]['cat_id'] = $vo['cat_id'];
            $data[$key]['author'] = $vo['author'];
            $data[$key]['create_at'] = date('Y-m-d', $vo['add_time']);
            $data[$key]['update_at'] = date('Y-m-d', $vo['add_time']);
        }

        return Foundation::formatBody(['articles' => $data, 'paged' => Foundation::formatPaged($page, $per_page, $total)]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getArticle($id)
    {
        $data = [];
        if ($model = static::find()->where(['article_id' => $id])->one()) {
            $data['id'] = $model->article_id;
            $data['title'] = $model->title;
            $data['type'] = $model->article_type;
            $data['keywords'] = $model->keywords;
            $data['description'] = $model->description;
            $data['cat_id'] = $model->cat_id;
            $data['author'] = $model->author;
            $data['content'] = $model->content;
            $data['create_at'] = date('Y-m-d', $model->add_time);
            $data['update_at'] = date('Y-m-d', $model->add_time);
        }

        return Foundation::formatBody(['article' => $data]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(ArticleCategory::className(), ['cat_id' => 'cat_id']);
    }
}
