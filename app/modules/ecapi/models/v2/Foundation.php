<?php

namespace app\modules\ecapi\models\v2;

class Foundation extends \yii\db\ActiveRecord
{
    const SUCCESS = 0;
    const UNKNOWN_ERROR = 10000;
    const INVALID_SESSION = 10001;
    const EXPIRED_SESSION = 10002;

    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;

    // 平台类型
    const B2C = 0;    //单店

    // 平台厂商
    const ECSHOP = 1;

    public static function formatPaged($page, $size, $total)
    {
        return [
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'more' => ($total > $page * $size) ? 1 : 0
        ];
    }

    public static function formatBody(array $data = [])
    {
        $data['error_code'] = 0;
        return $data;
    }

    public static function formatError($code, $message = null)
    {
        switch ($code) {
            case self::UNKNOWN_ERROR:
                $message = trans('app', 'message.error.unknown');
                break;

            case self::NOT_FOUND:
                $message = trans('app', 'message.error.404');
                break;
        }

        $body['error'] = true;
        $body['error_code'] = $code;
        $body['error_desc'] = $message;

        return $body;
    }
}