<?php

namespace app\http\controllers;

use Yii;
use yii\web\Controller;

class CategoryController extends Controller
{

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
