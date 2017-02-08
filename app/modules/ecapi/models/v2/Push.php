<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;

class Push extends BaseModel {
    
    protected $connection = 'shop';

    protected $table      = 'push';

    public  $timestamps   = true;

    protected $visible = ['id', 'title', 'photo', 'content', 'link', 'created_at'];

    public static function getSystemList(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $reg_time = Member::where('user_id', $uid)->value('reg_time');

        $model = Push::where('status', 2)->where('message_type', 1)
               ->where('created_at', '>', date('Y-m-d H:i:s', $reg_time))
               ->orderBy('created_at','DESC');

        $total = $model->count();

        $data = $model
            ->paginate($per_page)
            ->toArray();

        return self::formatBody(['messages' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public static function getOrderList(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();

        $model = Push::where('status', 2)->where('message_type', 2)->where('user_id', $uid)->orderBy('created_at','DESC');

        $total = $model->count();

        $data = $model
            ->paginate($per_page)
            ->toArray();

        return self::formatBody(['messages' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
    }

    public static function unread(array $attributes)
    {
        extract($attributes);

        $uid = Token::authorization();

        //如果有选择类型
        if(isset($type)){
            switch ($type) {
                //如果是给系统消息
                case 1:
                    $count = Push::where('status', 2)->where('message_type', 1)
                             ->where('created_at', '>', date('Y-m-d H:i:s', $after))->count();
                    break;
                //如果是给订单消息　　
                case 2:
                    if($uid = Token::authorization()){
                         $count = Push::where('status', 2)->where('message_type', 2)->where('user_id', $uid)
                                ->where('created_at', '>', date('Y-m-d H:i:s', $after))->count();
                    }else{
                         $count = 0;
                    }

                    break;

                default:
                    $count = 0;
                    break;
            }

        }else{
            $count = Push::where('status', 2)
                   ->where('created_at', '>', date('Y-m-d H:i:s', $after))->count();
        }


        return self::formatBody(['count' => $count]);
    }

    public function getCreatedAtAttribute()
    {
        return strtotime($this->attributes['created_at']);
    }

    public function getPhotoAttribute()
    {   
        if ($this->attributes['photo']) {
            return formatPhoto($this->attributes['photo']);
        }
        return null;
    }

}
