<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Banner;

class BannerController extends BaseController
{

    /**
     * POST ecapi.banner.list
     */
    public function actionIndex()
    {
        $model = Banner::getList();

        return $this->json($model);
    }
}
