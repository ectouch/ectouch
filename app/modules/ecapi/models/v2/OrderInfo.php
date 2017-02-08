<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;

class OrderInfo extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'order_info';
    public    $timestamps = false;

}
