<?php

namespace app\modules\console;

/**
 * console module definition class
 */
class Module extends \yii\base\Module
{
    public $layout = 'dashboard';

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\console\controllers';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }
}
