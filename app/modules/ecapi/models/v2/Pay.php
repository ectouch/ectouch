<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Pay extends BaseModel {

    protected $connection = 'shop';

    protected $table      = 'payment';
    
    public    $timestamps = false;

    public static function checkConfig($pay_code)
    {
    	// $sql = "SELECT * FROM " . $ecs->table('payment') . " WHERE pay_code = '$_REQUEST[code]' AND enabled = '1'";
    	if ($payment = self::where('pay_code', $pay_code)->where('enabled', '1')->first()) {
    		return true;
    	}
    	return false;
    }
}