<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class UserRegStatus extends BaseModel
{
    protected $connection = 'shop';
    protected $table      = 'user_reg_status';
    public    $timestamps = false;
    protected $primaryKey = 'user_id';
    protected $guarded = [];
    
    public static function toUpdate($uid, $is_completed)
    {
        return self::updateOrCreate(['user_id' => $uid], ['user_id' => $uid, 'is_completed' => $is_completed]);
    }

    public static function IsCompleted($uid)
    {
        if (self::checkRegFields() === false) {
            return true;
        }

        if ($model = self::where('user_id', $uid)->first()) {
            return (bool)$model->is_completed;
        }

        return false;
    }

    public static function checkRegFields()
    {
        if (RegFields::where(['display' => 1, 'type' => 1])->first()) {
            return true;
        } else {
            return false;
        }
    }
}