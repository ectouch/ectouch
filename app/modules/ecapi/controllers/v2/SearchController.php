<?php

namespace app\modules\ecapi\controllers;

use app\models\v2\Keywords;

class SearchController extends BaseController
{
    //POST  ecapi.search.keyword.list
    public function actionIndex()
    {
       return $this->json(Keywords::getHot());
    }
}