<?php

namespace app\http\controllers;

use Yii;
use yii\web\Controller;

class ExchangeController extends Controller
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
