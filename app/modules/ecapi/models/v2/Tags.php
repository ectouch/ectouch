<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Tags extends BaseModel
{
    protected $connection = 'shop';

    protected $table      = 'tag';

    public    $timestamps = false;

    protected $visible = ['id', 'name','created_at','updated_at'];

    protected $appends = ['id', 'name','created_at','updated_at'];

    protected $guarded = [];


    public function getIdAttribute()
    {
        return $this->tag_id;
    }

    public function getNameAttribute()
    {
        return $this->tag_words;
    }

    public function getCreatedatAttribute()
    {
        return time();
    }

    public function getUpdatedatAttribute()
    {
        return time();
    }

}