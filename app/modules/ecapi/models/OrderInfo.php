<?php

namespace app\models\v2;
use app\models\BaseModel;

use app\helpers\Token;


class OrderInfo extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'order_info';
    public    $timestamps = false;

}
