<?php

namespace app\modules\apidoc\controllers;

use Yii;
use yii\web\Controller;
use Parsedown;

/**
 * Default controller for the `apidoc` module
 */
class DefaultController extends Controller
{

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $basePath = dirname(__DIR__) . '/markdown/';
        $detail = Yii::$app->request->get('detail');
        $basePath .= empty($detail) ? 'READMD.md' : 'api/' . $detail;

        if (file_exists($basePath)) {
            $content = file_get_contents($basePath);
        } else {
            $content = 'Please wait.';
        }

        $content = Parsedown::instance()->text($content);
        
        return $this->render('index', [
            'content' => $content,
        ]);
    }
}
