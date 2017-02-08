<?php

namespace app\models\v2;

use app\models\BaseModel;

class ShippingArea extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'shipping_area';

    public    $timestamps = false;
    
}