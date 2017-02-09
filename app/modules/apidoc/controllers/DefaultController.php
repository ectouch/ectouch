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
    
    private $modulePath;

    public function init(){
        parent::init();

        $this->modulePath = dirname(__DIR__);
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $basePath = $this->modulePath . '/markdown/';
        $markfile = Yii::$app->request->get('detail');
        $markfile = empty($markfile) ? 'READMD.md' : 'api/' . $markfile;

        $data = $basePath . $markfile;
        if (file_exists($data)) {
            $content = file_get_contents($data);
        } else {
            $content = 'Please wait.';
        }

        $content = Parsedown::instance()->text($content);
        
        return $this->render('index', [
            'content' => $content,
        ]);
    }
}
