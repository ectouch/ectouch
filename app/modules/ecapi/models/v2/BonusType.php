<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;

class BonusType extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'bonus_type';
    public    $timestamps = false;

    protected $appends = ['id', 'name', 'status', 'value', 'effective', 'expires', 'condition'];

    protected $visible = ['id', 'name', 'status', 'value', 'effective', 'expires', 'condition'];




    public static function getListByUser(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();
        if (isset($status)) {

            $today  = self::today();
            $model = self::join('user_bonus', 'bonus_type.type_id', '=', 'user_bonus.bonus_type_id')
                   ->where('user_id', '<>', 0)
                   ->where('user_id', $uid);


            switch ($status) {
                case 0:
                $model =  $model->where('order_id', 0)
                                ->where('use_start_date', '<=', $today)
                                ->where('use_end_date', '>=', $today);
                    break;

                case 1:
                $model =  $model->where('order_id', 0)
                                ->where('use_start_date', '<=', $today)
                                ->where('use_end_date', '<', $today);
                    break;

                case 2:
                $model =  $model->where('order_id', '>', 0);
                    break;

                default:
                    return self::formatError(self::NOT_FOUND);
            }


            $total = $model->count();
            $data = $model->orderBy('type_id', 'DESC')
                ->paginate($per_page)
                ->toArray();

            return self::formatBody(['cashgifts' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
        }

        return self::formatError(self::NOT_FOUND);

    }

    public static function getAvailableListByUser(array $attributes)
    {
        extract($attributes);

        $today  = self::today();

        $uid = Token::authorization();

        $model =self::join('user_bonus','bonus_type.type_id','=','user_bonus.bonus_type_id')
                    ->where('user_id', '<>', 0)
                    ->where('user_id', $uid)
                    ->where('order_id', 0)
                    ->where('use_start_date', '<=', $today)
                    ->where('use_end_date', '>=', $today)
                    ->where('min_goods_amount', '<=', $total_price);


        $total = $model->count();
        $data = $model->paginate($per_page)->toArray();

        return self::formatBody(['cashgifts' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public function userbonus()
    {
        return $this->hasOne('app\models\v2\UserBonus', 'bonus_type_id', 'type_id');
    }

    public function getIdAttribute()
    {
        return $this->attributes['bonus_id'];
    }

    public function getNameAttribute()
    {
        return $this->attributes['type_name'];
    }

    public function getStatusAttribute()
    {
        $today  = self::today();

        if($this->order_id > 0){
            return 2;
        }elseif ($this->use_end_date >= $today) {
            return 0;
        }elseif($this->use_end_date < $today){
            return 1;
        }
    }

    public function getValueAttribute()
    {
        return $this->attributes['type_money'];
    }

    public function getEffectiveAttribute()
    {
        return $this->attributes['use_start_date'];
    }

    public function getExpiresAttribute()
    {
        return $this->attributes['use_end_date'];
    }

    public function getConditionAttribute()
    {
      return $this->attributes['min_goods_amount'];
    }


    /**
     * 取得红包信息
     * @param   int     $bonus_id   红包id
     * @param   string  $bonus_sn   红包序列号
     * @param   array   红包信息
     */
    public static function bonus_info($bonus_id, $bonus_sn = '')
    {
        return self::join('user_bonus', 'bonus_type.type_id', '=', 'user_bonus.bonus_type_id')
                ->where('bonus_id', $bonus_id)
                ->first();
    }

    /**
     * 取得今日23:59:59的时间戳
     * @param   int 　时间戳
     */
    public static function today()
    {
        $timezone = ShopConfig::findByCode('timezone');
        $day    = getdate();
        $today  = mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']) - $timezone * 3600;
        return $today;
    }
}
