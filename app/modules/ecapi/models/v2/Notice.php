<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Notice extends BaseModel
{
     protected $connection = 'shop';
     protected $table      = 'shop_config';
     public    $timestamps = true;

     protected $appends = ['title', 'url', 'created_at', 'updated_at'];

     protected $visible = ['id', 'title', 'url', 'created_at', 'updated_at'];

     public static function getList(array $attributes)
     {
         extract($attributes);
         $model = self::where('code', 'like', '%notice%');

         $total = $model->count();

         $data = $model
             ->paginate($per_page)
             ->toArray();

         return self::formatBody(['notices' => $data['data'],'paged' => self::formatPaged($page, $per_page, $total)]);
     }

     public static function getNotice($id)
     {
         if ($model = Notice::where('id', $id)->first()) {
             $data['content'] = $model->value;
             return view('notice.mobile', ['notice' => $data]);
         }
     }

     public function getTitleAttribute()
     {
         return mb_substr($this->attributes['value'], 0, 6, 'utf-8') . '...';
     }

     public function getUrlAttribute()
     {
         return url('/v2/notice.'.$this->attributes['id']);
     }

     public function getCreatedAtAttribute()
     {
         return time();
     }

     public function getUpdatedAtAttribute()
     {
         return time();
     }
}
