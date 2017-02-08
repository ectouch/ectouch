<?php

namespace app\http\controllers\v2;

use app\models\v2\Version;

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
