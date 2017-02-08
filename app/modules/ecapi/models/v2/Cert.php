<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Cert extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'cert';
    public    $timestamps = true;
}
