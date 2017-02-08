<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;

use app\modules\ecapi\helpers\Token;
use app\modules\ecapi\helpers\XXTEA;
use App\Services\Cloud\Client;
use App\Services\QiNiu\QiNiu;
use App\Services\Other\JSSDK;

class Configs extends BaseModel
{
    protected $connection = 'shop';
    
    protected $table = 'config';

    protected $guarded = [];

    public  $timestamps   = true;


    public static function getList()
    {
        $data = self::where('status', 1)->get();
        $config = ['config' => self::formatConfig($data), 'feature' => Features::getList(), 'platform' => self::getApplicationPlatform()];
        return self::formatBody(['data' => base64_encode(XXTEA::encrypt($config, 'i1j56p443p739lbl1zr4zow76j6kz4g6'))]);   
    }

    private static function getApplicationPlatform()
    {
        return [
            'type'      => self::B2C,
            'vendor'    => self::ECSHOP,
            'version'   => '3.5.0'
        ];
    }
    
    public static function checkConfig($code)
    {
        if (!$license = Token::decode_license()) {
            return self::formatError(4444, trans('message.license.invalid'));
        }

        switch ($code) {
            case 'sms':
                if ($license['permissions']['sms'] !== true) {
                    return self::formatError(4445, trans('message.license.unauthorized'));
                }
                return self::initLeanCloud();
                break;
        }

        return true;
    }

    public static function verifyConfig(array $params, $config)
    {
        if (!isset($config->config)) {
            return false;
        }

        $data = json_decode($config->config, true);

        foreach ($params as $key => $value) {
            if (!isset($data[$value])) {
                return false;
            }
        }

        return $data;
    }

    private static function initLeanCloud()
    {
        if (!$cloud = Configs::where('code', 'leancloud')->first()) {
            return self::formatError(3001, trans('message.cloud.config'));
        }

        if (!$cloud->status) {
            return self::formatError(3002, trans('message.cloud.notopen'));
        }

        $cloud_config = json_decode($cloud->config);
        if ($cloud_config && isset($cloud_config->app_id) && isset($cloud_config->app_key)) {
            Client::initialize($cloud_config->app_id, $cloud_config->app_key);
            return true;
        } else {
            return self::formatError(3001, trans('message.cloud.config'));
        }
    }

    private static function formatConfig($data)
    {
       $body = null;
        foreach ($data as $value) {
            $arr = json_decode($value->config, true);

	    //qiniu格式化
            if( $value->code == 'qiniu'){
                $qiniu = new QiNiu($arr['app_key'], $arr['secret_key']);
                unset($arr['app_key']);
                unset($arr['secret_key']);
                $arr['token'] = $qiniu->uploadToken(array('scope' => $arr['bucket']));
            }
            //wxpay.web jssdk
            if( $value->code == 'wxpay.web' && $value->status){
                $jssdk = new JSSDK($arr['app_id'], $arr['app_secret']);
                $arr = $jssdk->GetSignPackage();
            }

            if(is_array($arr)){
                $body[$value->code] = $arr;
            }
        }        

        //安全处理
        unset($body['alipay.app']);
        unset($body['wxpay.app']);
        unset($body['unionpay.app']);
        unset($body['leancloud']['master_key']);

        return $body;
    }

}
