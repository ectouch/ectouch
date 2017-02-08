<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class RegExtendInfo extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'reg_extend_info';
    public    $timestamps = false;

    protected $guarded = [];

    public static function toUpdate($id, $user_id, $value)
    {
        if (!$field = RegFields::where('id', $id)->first()) {
            return false;
        }

        // users 表预留字段
        if ($field->type == 1) {
           
            if ($member = Member::where('user_id', $user_id)->first()) {
            
                switch ($id) {
                    case '1':
                        $member->msn = $value;
                        break;
                    
                    case '2':
                        $member->qq = $value;
                        break;

                    case '3':
                        $member->office_phone = $value;
                        break;

                    case '4':
                        $member->home_phone = $value;
                        break;

                    case '5':
                        $member->mobile_phone = $value;
                        break;
                }

                UserRegStatus::toUpdate($user_id, 1);

                // save
                return $member->save();
            }

            return false;
        }

        //reg_extend_info 表扩展字段
        if ($field->type == 0) {
            
            UserRegStatus::toUpdate($user_id, 1);
            return self::updateOrCreate(['reg_field_id' => $id], ['user_id' => $user_id, 'reg_field_id' => $id, 'content' => $value]);
        }

        return false;
    }
}