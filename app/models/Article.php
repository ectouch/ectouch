<?php

namespace app\models;

use Yii;

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
class Article extends \yii\db\ActiveRecord
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
            'article_id' => Yii::t('app', 'Article ID'),
            'cat_id' => Yii::t('app', 'Cat ID'),
            'title' => Yii::t('app', 'Title'),
            'content' => Yii::t('app', 'Content'),
            'author' => Yii::t('app', 'Author'),
            'author_email' => Yii::t('app', 'Author Email'),
            'keywords' => Yii::t('app', 'Keywords'),
            'article_type' => Yii::t('app', 'Article Type'),
            'is_open' => Yii::t('app', 'Is Open'),
            'add_time' => Yii::t('app', 'Add Time'),
            'file_url' => Yii::t('app', 'File Url'),
            'open_type' => Yii::t('app', 'Open Type'),
            'link' => Yii::t('app', 'Link'),
            'description' => Yii::t('app', 'Description'),
        ];
    }
}
