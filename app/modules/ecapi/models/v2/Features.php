<?php

namespace app\modules\ecapi\models\v2;

class Features extends Foundation
{

    //protected $table      = 'feature';
    //public    $timestamps = true;

    public static $features = [
        "invoice" => true,  // 是否支持发票
        "cashgift" => true,  // 是否支持红包
        "score" => true,  // 是否支持积分
        "coupon" => false,  // 是否支持优惠券
        "address.default" => true,   // 是否支持默认地址
        'signin.qq' => true,
        'signin.weibo' => true,
        'signin.weixin' => true,
        'signin.mobile' => true,
        'signin.default' => true,
        'share.qq' => true,
        'share.weibo' => true,
        'share.weixin' => true,
        'pay.alipay' => true,
        'pay.weixin' => true,
        'pay.unionpay' => true,
        'pay.wxweb' => true,
        'signup.mobile' => true,
        'signup.default' => true,
        'findpass.mobile' => true,
        'findpass.default' => true,
        'logistics' => true,
        'push' => true,
        'qiniu' => true,
        'statistics' => true,
    ];

    public static function getList()
    {
        return self::formatFeature(self::$features);
    }

    public static function check($code)
    {
        if (self::getStatus($code) === false) {
            return self::formatError(self::UNAUTHORIZED, trans('app', 'message.error.unauthorized'));
        }
    }

    public static function getStatus($code)
    {
        $arr = self::formatFeature(self::$features);
        return isset($arr[$code]) ? $arr[$code] : null;
    }

    private static function formatFeature($data)
    {
        return $data;
    }

}
