<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%topic}}".
 *
 * @property string $topic_id
 * @property string $title
 * @property string $intro
 * @property integer $start_time
 * @property integer $end_time
 * @property string $data
 * @property string $template
 * @property string $css
 * @property string $topic_img
 * @property string $title_pic
 * @property string $base_style
 * @property string $htmls
 * @property string $keywords
 * @property string $description
 */
class Topic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%topic}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['intro', 'data', 'css'], 'required'],
            [['intro', 'data', 'css', 'htmls'], 'string'],
            [['start_time', 'end_time'], 'integer'],
            [['title', 'template', 'topic_img', 'title_pic', 'keywords', 'description'], 'string', 'max' => 255],
            [['base_style'], 'string', 'max' => 6],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'topic_id' => Yii::t('app', 'Topic ID'),
            'title' => Yii::t('app', 'Title'),
            'intro' => Yii::t('app', 'Intro'),
            'start_time' => Yii::t('app', 'Start Time'),
            'end_time' => Yii::t('app', 'End Time'),
            'data' => Yii::t('app', 'Data'),
            'template' => Yii::t('app', 'Template'),
            'css' => Yii::t('app', 'Css'),
            'topic_img' => Yii::t('app', 'Topic Img'),
            'title_pic' => Yii::t('app', 'Title Pic'),
            'base_style' => Yii::t('app', 'Base Style'),
            'htmls' => Yii::t('app', 'Htmls'),
            'keywords' => Yii::t('app', 'Keywords'),
            'description' => Yii::t('app', 'Description'),
        ];
    }
}
