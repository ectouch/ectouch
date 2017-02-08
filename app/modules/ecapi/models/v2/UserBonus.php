<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;

class UserBonus extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'user_bonus';
    public    $timestamps = false;
    protected $primaryKey = 'bonus_id';

    /* 红包发放的方式 */
    const SEND_BY_USER               = 0; // 按用户发放
    const SEND_BY_GOODS              = 1; // 按商品发放
    const SEND_BY_ORDER              = 2; // 按订单发放
    const SEND_BY_PRINT              = 3; // 线下发放


    /**
     * 设置红包为已使用
     * @param   int     $bonus_id   红包id
     * @param   int     $order_id   订单id
     * @return  bool
     */
    public static function useBonus($bonus_id, $order_id)
    {
        if($model = self::where('bonus_id', $bonus_id)->first()){
            $model->order_id  = $order_id;
            $model->used_time = time();
            if($model->save()){
                return true;
            }
        }
        return false;
    }

    /**
     * 设置红包为未使用
     * @param   int     $bonus_id   红包id
     * @return  bool
     */
    public static function unuseBonus($bonus_id)
    {
        if($model = self::where('bonus_id', $bonus_id)->first()){
            $model->order_id  = 0;
            $model->used_time = 0;
            if($model->save()){
                return true;
            }
        }
        return false;
    }

}
