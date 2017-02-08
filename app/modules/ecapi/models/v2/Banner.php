<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

class Banner extends BaseModel {
   
    public static function getList()
    {
        $data = [];
        $file = @file_get_contents(config('app.shop_url').'/data/flash_data.xml');
        if (strlen($file) > 0) {
            $data = self::get_flash_xml($file); 
        }
        return self::formatBody(['banners' => $data]);
    }

    private static function get_flash_xml($file)
    {
        $flashdb = array();

        // 兼容v2.7.0及以前版本
        if (!preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"\ssort="([^"]*)"/', $file, $t, PREG_SET_ORDER))
        {
            preg_match_all('/item_url="([^"]+)"\slink="([^"]+)"\stext="([^"]*)"/', $file, $t, PREG_SET_ORDER);
        }

        if (!empty($t))
        {
            foreach ($t as $key => $val)
            {
                $val[4] = isset($val[4]) ? $val[4] : 0;
                $flashdb[] = array('id' => $key, 'photo'=>formatPhoto($val[1]), 'link'=>$val[2],'title'=>$val[3],'sort'=>$val[4]);
            }
        }

        return $flashdb;
    }
}
