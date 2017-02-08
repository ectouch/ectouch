<?php

namespace app\modules\console\controllers;

use yii\web\Controller;

/**
 * Auth controller for the `console` module
 */
class AuthController extends Controller
{
    public $layout = 'admin';

    /**
     * Renders the login view for the module
     * @return string
     */
    public function actionLogin()
    {
        return $this->render('login');
    }

    /**
     * Renders the forgot view for the module
     * @return string
     */
    public function actionForgot()
    {
        return $this->render('forgot');
    }

    /**
     * Renders the reset view for the module
     * @return string
     */
    public function actionReset()
    {
        return $this->render('reset');
    }
}
