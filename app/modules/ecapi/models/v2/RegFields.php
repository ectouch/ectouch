<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class RegFields extends BaseModel {
    protected $connection = 'shop';
    protected $table      = 'reg_fields';
    public    $timestamps = false;

    protected $guarded = [];
    protected $appends = ['name', 'value', 'is_hidden', 'is_required'];
    protected $visible = ['id', 'name', 'value', 'is_hidden', 'is_required'];

    public static function findAll()
    {
        $model = self::where('display', 1)->where('type', '<', 2)->where('id', '!=', 6)->orderBy('dis_order', 'ASC')->get();
        return self::formatBody(['signup_field' => $model->toArray()]);
    }

    public function getNameAttribute()
    {
        return $this->attributes['reg_field_name'];
    }

    public function getValueAttribute()
    {
        return '';
    }

    public function getIsHiddenAttribute()
    {
        return $this->attributes['display'] ? false : true;
    }

    public function getIsRequiredAttribute()
    {
        return (bool)$this->attributes['is_need'];
    }
}