<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%keywords}}".
 *
 * @property string $date
 * @property string $searchengine
 * @property string $keyword
 * @property string $count
 */
class Keywords extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%keywords}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['date', 'searchengine', 'keyword'], 'required'],
            [['date'], 'safe'],
            [['count'], 'integer'],
            [['searchengine'], 'string', 'max' => 20],
            [['keyword'], 'string', 'max' => 90],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'date' => 'Date',
            'searchengine' => 'Searchengine',
            'keyword' => 'Keyword',
            'count' => 'Count',
        ];
    }


    public static function updateHistory($keyword)
    {
        $keyword = strip_tags($keyword);
        if(empty($keyword)){
            return false;
        }

        $model = self::find();
        $model->where(['=', 'keyword', $keyword])->andWhere(['=', 'date', date('Y-m-d', time())]);
        if ($model->count() > 0) {
            $key = $model->one();
            $key->count += 1;
            $key->save(false);
        } else {
            $keywords = new Keywords;
            $keywords->keyword = $keyword;
            $keywords->count = 1;
            $keywords->date = date('Y-m-d', time());
            $keywords->searchengine = 'wxtouch';
            $keywords->save();

        }
        return true;
    }
}
