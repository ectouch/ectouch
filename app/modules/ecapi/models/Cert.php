<?php

namespace app\models\v2;
use app\models\BaseModel;

class Cert extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'cert';
    public    $timestamps = true;
}
