<?php

namespace app\modules\ecapi\models\v2;

class ShopConfig extends Foundation {

    protected $connection = 'shop';
    protected $table      = 'shop_config';
    public    $timestamps = true;

    public static function findByCode($code)
    {
        return self::find()->select(['value'])->where(['code' => $code])->one();
    }

    public static function getSiteInfo()
    {
        return[
            'site_info'=> [
                'name' => self::findByCode('shop_name'),
                'desc' => self::findByCode('shop_desc'),
                'logo' => formatPhoto(self::findByCode('shop_logo')),
                'opening' => (bool)!self::findByCode('shop_closed'),
                'telephone' => self::findByCode('service_phone'),
                'terms_url' => env('TERMS_URL'),
                'about_url' => env('ABOUT_URL'),
            ]
        ];
    }

    private static function getConfigure($configure)
    {
        $data = [];
        $configure = unserialize($configure);
        foreach ($configure as $key => $val) {
            $data[$val['name']] = $val['value'];
        }

        return $data;
    }
}
