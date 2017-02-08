<?php

namespace app\modules\ecapi\controllers;

use app\modules\ecapi\models\Version;

class VersionController extends BaseController
{
    /**
     * POST ecapi.version.check
     */
    public function actionCheck()
    {
        $data = Version::checkVersion();
        return $this->json($data);
    }

}
