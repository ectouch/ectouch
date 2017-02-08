<?php

namespace app\modules\ecapi\models\v2;

use Yii;

/**
 * This is the model class for table "{{%region}}".
 *
 * @property integer $region_id
 * @property integer $parent_id
 * @property string $region_name
 * @property integer $region_type
 * @property integer $agency_id
 */
class Region extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%region}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_id', 'region_type', 'agency_id'], 'integer'],
            [['region_name'], 'string', 'max' => 120],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'region_id' => 'Region ID',
            'parent_id' => 'Parent ID',
            'region_name' => 'Region Name',
            'region_type' => 'Region Type',
            'agency_id' => 'Agency ID',
        ];
    }

    public static function getRegionName($region_id)
    {
        $data = self::getRegionGroup($region_id);
        if (!empty($data)) {
            foreach ($data as $vo){
                $array[] = $vo['region_name'];
            }
            if (!empty($array)) {
                return implode(" ", $array);
            }
        }

        return false;
    }

    //根据id 获取 国家 省 市 地区信息
    public static function getRegionGroup($region_id)
    {
        $body = [];
        while (true) {
            if ($model = Region::find()->where(['region_id' => $region_id])->one()) {
                $region_id = $model->parent_id;
                $body[] = $model;
            } else {
                break;
            }
        }
        return array_reverse($body);
    }

    //根据id 获取 parent type and parent id
    public static function getParentId($region_id)
    {
        $body = [];
        while (true) {
            if ($model = Region::find()->where(['region_id' => $region_id])->one()) {
                $region_id = $model->parent_id;

                switch ($model->region_type) {
                    case 0:
                        $body['country'] = $model->region_id;
                        break;

                    case 1:
                        $body['province'] = $model->region_id;
                        break;

                    case 2:
                        $body['city'] = $model->region_id;
                        break;

                    case 3:
                        $body['region'] = $model->region_id;
                        break;

                    default:
                        break;
                }

            } else {
                break;
            }
        }
        return $body;
    }

    public static function getList()
    {
        $key = 'regions';
        $cache = Yii::$app->cache;
        if (!$model = $cache->get($key)) {
            $model = Region::find()->with('regions')->where(['parent_id' => 0])->asArray()->all();
            $cache->set($key, $model, 0);
        }

        return self::formatBody(['regions' => $model]);
    }

    public function getRegions()
    {
        return $this->hasMany(Region::className(), ['parent_id' => 'region_id'])->with('regions');
    }

}
