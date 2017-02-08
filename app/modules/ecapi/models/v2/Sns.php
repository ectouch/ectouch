<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Sns extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'sns';
    protected $primaryKey = 'user_id';
    public    $timestamps = true;
}
