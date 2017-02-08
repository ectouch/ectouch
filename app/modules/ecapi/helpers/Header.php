<?php

namespace app\helpers;

use Yii;

class Header
{
    public static function getUserAgent($key = false)
    {
        $headers = Yii::$app->request->headers;
        $arr = [];

        if ($ua = $headers->get('X-' . Yii::$app->params['name'] . '-UserAgent')) {
            $items = @explode(', ', $ua);
            if (is_array($items)) {
                foreach ($items as $property) {
                    if (strpos($property, '/') !== false) {
                        $property = explode('/', $property);
                        $arr[$property[0]] = $property[1];
                    }
                }
            }
        }

        if ($key) {
            return (isset($arr[$key]) && $arr[$key]) ? strtolower($arr[$key]) : '';
        }

        return $arr;
    }

    public static function getVer()
    {
        $headers = Yii::$app->request->headers;
        if ($ver = $headers->get('X-' . Yii::$app->params['name'] . '-Ver')) {
            $rule = '/^[(\d)+.(\d)+.(\d)+]+$/';
            if (preg_match($rule, $ver)) {
                return $ver;
            }
        }
        return null;
    }

    public static function getUDID()
    {
        $headers = Yii::$app->request->headers;
        if ($UDID = $headers->get('X-' . Yii::$app->params['name'] . '-UDID')) {
            return $UDID;
        }
        return null;
    }

}
