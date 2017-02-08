<?php

namespace app\modules\ecapi\controllers\v2;

use yii\web\Controller;

/**
 * Default controller for the `ecapi` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return 'ecapi v2 is ready.';
    }
}
