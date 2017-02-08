<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Brand;

class BrandController extends BaseController
{

    /**
     * POST ecapi.brand.list
     */
    public function actionIndex()
    {
        $rules = [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Brand::getList($this->validated);

        return $this->json($model);
    }

    /**
     * POST ecapi.recommend.brand.list
     */
    public function actionRecommend()
    {
        $rules = [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min' => 1],
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $model = Brand::getListByOrder($this->validated);

        return $this->json($model);
    }
}
