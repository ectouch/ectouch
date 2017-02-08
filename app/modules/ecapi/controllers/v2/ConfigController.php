<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Configs;

class ConfigController extends BaseController
{

    public function actionIndex()
    {
        $data = Configs::getList();
        return $this->json($data);
    }

}
