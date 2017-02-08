<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\AreaCode;

class AreaCodeController extends BaseController
{
    /**
     * POST ecapi.areacode.list
     */
    public function actionIndex()
    {
        $model = AreaCode::getList();

        return $this->json($model);
    }

}
