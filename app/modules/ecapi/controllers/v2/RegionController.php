<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Region;

class RegionController extends BaseController
{

    private $regions;

    public function behaviors()
    {
        return [
            [
                'class' => 'yii\filters\HttpCache',
                'only' => ['index'],
                'etagSeed' => function ($action, $params) {
                    $this->regions = Region::getList();
                    return md5(serialize($this->regions));
                },
            ],
        ];
    }

    public function actionIndex()
    {
        $this->regions = Region::getList();
        return $this->json($this->regions);
    }
}
