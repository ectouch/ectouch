<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Goods;
use app\models\v2\ShopConfig;

class SiteController extends BaseController
{
    //POST  ecapi.site.get
    public function actionIndex()
    {

        $rules = [
            [['page', 'per_page'], 'required'],
            [['page', 'per_page'], 'integer', 'min'=>1]
        ];

        if ($error = $this->validateInput($rules)) {
            return $error;
        }

        $goodsList = Goods::getBestGoodsList($this->validated);

        return $this->json($goodsList);
//        return $this->json(ShopConfig::getSiteInfo());
    }
}