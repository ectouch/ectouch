<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

use DB;

class GoodsActivity extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'goods_activity';

    public    $timestamps = false;

    protected $visible = ['promo', 'name'];

    protected $appends = ['promo', 'name'];

    protected $guarded = [];

}