<?php

namespace app\modules\ecapi\models\v2;

use app\modules\ecapi\models\BaseModel;
use app\modules\ecapi\helpers\Token;
use app\modules\ecapi\helpers\Header;
use App\Services\Payment\Alipay\AlipayRSA;
use App\Services\Payment\Alipay\AlipayNotify;
use App\Services\Payment\wxpay\WxPay;
use App\Services\Payment\wxpay\WxResponse;
use App\Services\Payment\Unionpay\Union;
use Log;
use App\Services\Shopex\Erp;
use App\Services\Shopex\Authorize;
use App\Services\Payment\Teegon\TeegonService;

class Payment extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'config';
    public    $timestamps = false;

    protected $appends = ['desc'];
    protected $visible = ['code', 'name', 'desc'];

    public static function getList(array $attributes)
    {
        $userAgent = Header::getUserAgent();

	    Log::error('userAgent: '.var_export($userAgent,true));
        // if (isset($userAgent['Platform']) && strtolower($userAgent['Platform']) == 'wechat') {
        //     $model = self::where(['type' => 'payment', 'status' => 1, 'code' => 'wxpay.web'])->get();
        // }else{
        //     $model = self::where(['type' => 'payment', 'status' => 1])->where('code', '!=', 'wxpay.web')->get();
        // }
        
        $model = null;
        if (isset($userAgent['Platform']) && strtolower($userAgent['Platform']) == 'wechat') {
            $response = Authorize::info();
            if ($response['result'] == 'success') {
                // 旗舰版授权...
                if ($response['info']['authorize_code'] == 'NDE') {
                    $model = self::where(['type' => 'payment', 'status' => 1, 'code' => 'wxpay.web'])->get()->toArray();
                } 

                //天工收银
                if ($arr = Pay::where('pay_code', 'yunqi')->first()) {
                    $arr = $arr->toArray();
                    $arr = [
                        'name' => '天工收银',
                        'code' => 'teegon.wap',
                        'desc' => '天工收银'
                    ];

                    array_push($model, $arr);
                }

            }
        } else {
            $model = self::where(['type' => 'payment', 'status' => 1])->where('code', '!=', 'wxpay.web')->get()->toArray();
        }


        return self::formatBody(['payment_types' => $model]);
    }

    public static function pay(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $order = Order::where(['user_id' => $uid, 'order_id' => $order, 'pay_status' => Order::PS_UNPAYED])->with('goods')->first();
        if (!$order) {
            return self::formatError(self::NOT_FOUND);
        }

        $shop_name = ShopConfig::findByCode('shop_name');
        
        //-------------  天工收银  -----------
        if ($code == 'teegon.wap') {

            if (!isset($channel) || empty($channel)) {
                return self::formatError(self::BAD_REQUEST, trans('message.teegon.channel')); // 选择支付方式
            }

            $result = Pay::checkConfig('yunqi');

            $config = ShopConfig::where('code', 'yunqi_account')->first();

            if (empty($config)) {
                return self::formatError(self::NOT_FOUND);
            }

            $config = unserialize($config['value']);

            if (!$result || empty($config['appkey']) || empty($config['appsecret'])) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $data['order_no'] = $order->order_sn; //订单号
            $data['channel'] = $channel;            
            $data['return_url'] = urldecode($referer);    
            $data['amount'] = number_format($order->order_amount, 2, '.', '');
            $data['subject'] = $shop_name; // $shop_name
            $data['metadata'] = ""; // 可选
            $data['notify_url'] = url("/v2/order.notify.teegon.wap");//支付成功后天工支付网关通知
            $data['client_ip'] = '127.0.0.1';

            $srv = new TeegonService('https://api.teegon.com/', $config['appkey'], $config['appsecret']);

            $sign = $srv->sign($data);
            $data['sign'] = $sign;
            Log::info('data:' . json_encode($data));
            $res = $srv->pay($data, true);

            if (isset($res['error'])) {
                return self::formatError(self::BAD_REQUEST, $res['error']);
            }

            Log::error('userAgent: '.var_export($res,true));

            $url = $html = null;

            if ($channel == 'wxpay_jsapi') { // 微信支付
                $url = str_replace(['window.location=', '"'], '', $res['result']['action']['params']);
            }
            if ($channel == 'chinapay') { // 银联支付
                $html = $srv->buildRequestForm($data, 'post', '确认'); 
            }
            return self::formatBody([
                'order' => $order, 'teegon' => ['url' => $url, 'html' => $html],
            ]);
        }

        // ----------------------------
        
        $payment = self::where(['type' => 'payment', 'status' => 1, 'code' => $code])->first();

        if (!$payment) {
            return self::formatError(self::NOT_FOUND);
        }

        if ($code == 'cod.app') {
            $order->order_status = 1;
            $order->pay_status = 2;
            $order->pay_time = time();
            $order->save();
            return self::formatBody(['order' => $order]);
        }
        if ($code == 'alipay.app') {
            $config = self::checkConfig(['partner_id', 'seller_id', 'private_key'], $payment);
            if (!$config) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $data = [
                "notify_url"     => url('/v2/order.notify.alipay.app'),
                "partner"        => $config['partner_id'],
                "seller_id"      => $config['seller_id'],
                "out_trade_no"   => $order->order_sn,
                "subject"        => $shop_name,
                "body"           => $shop_name,
                "total_fee"      => number_format($order->order_amount, 2, '.', ''),
                "service"        => "mobile.securitypay.pay",
                "payment_type"   => "1",
                "_input_charset" => "utf-8",
                "it_b_pay"       => "30m",
                "show_url"       => "m.alipay.com"
            ];

            $sign = AlipayRSA::rsaSign(AlipayRSA::getSignContent($data), keyToPem($config['private_key'], true));
            $data['sign'] = $sign;
            $data['sign_type'] = 'RSA';

            return self::formatBody(['order' => $order, 'alipay' => ['order_string' => http_build_query($data)]]);
        }

        if ($code == 'wxpay.app') {
            $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $wxpay = new WxPay();
            $wxpay->init($config['app_id'], $config['app_secret'], $config['mch_key']);
            $nonce_str = str_random(32);
            $time_stamp = time();
            $pack = 'Sign=WXPay';

            $inputParams = [

                //公众账号ID
                'appid' => $config['app_id'],

                //商户号
                'mch_id' => $config['mch_id'],

                'device_info' => '1000',

                //随机字符串
                'nonce_str' => $nonce_str,

                //商品描述
                'body' => $shop_name,

                'attach' => $shop_name,

                //商户订单号
                'out_trade_no' => $order->order_sn,

                //总金额
                'total_fee' => $order->order_amount * 100,
                // 'total_fee' => 1,

                //终端IP
                'spbill_create_ip' => app('request')->ip(),

                //接受微信支付异步通知回调地址
                'notify_url' => url('/v2/order.notify.wxpay.app'),

                //交易类型:JSAPI,NATIVE,APP
                'trade_type' => 'APP'
            ];

            $inputParams['sign'] = $wxpay->createMd5Sign($inputParams);

            //获取prepayid
            $prepayid = $wxpay->sendPrepay($inputParams);

            $prePayParams = [
                'appid' => $config['app_id'],
                'partnerid' => $config['mch_id'],
                'prepayid' => $prepayid,
                'package' => $pack,
                'noncestr' => $nonce_str,
                'timestamp' => $time_stamp,
            ];

            //生成签名
            $sign = $wxpay->createMd5Sign($prePayParams);

            $body = [
                'appid' => $config['app_id'],
                'mch_id' => $config['mch_id'],
                'prepay_id' => $prepayid,
                'nonce_str' => $nonce_str,
                'timestamp' => $time_stamp,
                'packages' => $pack,
                'sign' => $sign,
            ];
            return self::formatBody(['order' => $order, 'wxpay' => $body]);
        }

        if ($code == 'wxpay.web') {
            $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $wxpay = new WxPay();
            $wxpay->init($config['app_id'], $config['app_secret'], $config['mch_key']);
            $nonce_str = str_random(32);
            $time_stamp = (string)time();

            $inputParams = [

                //公众账号ID
                'appid' => $config['app_id'],

                //商户号
                'mch_id' => $config['mch_id'],

                //商户号
                'openid' => $openid,

                'device_info' => '1000',

                //随机字符串
                'nonce_str' => $nonce_str,

                //商品描述
                'body' => $shop_name,

                'attach' => $shop_name,

                //商户订单号
                'out_trade_no' => $order->order_sn,

                //总金额
                'total_fee' => $order->order_amount * 100,
                // 'total_fee' => 1,

                //终端IP
                'spbill_create_ip' => app('request')->ip(),

                //接受微信支付异步通知回调地址
                'notify_url' => url('/v2/order.notify.wxpay.web'),

                //交易类型:JSAPI,NATIVE,APP
                'trade_type' => 'JSAPI'
            ];

            $inputParams['sign'] = $wxpay->createMd5Sign($inputParams);

            //获取prepayid
            $prepayid = $wxpay->sendPrepay($inputParams);

            $pack = 'prepay_id='.$prepayid;

            $prePayParams = [
                'appId' => $config['app_id'],
                'timeStamp' => $time_stamp,
                'package' => $pack,
                'nonceStr' => $nonce_str,
                'signType' => 'MD5'
            ];

            //生成签名
            $sign = $wxpay->createMd5Sign($prePayParams);

            $body = [
                'appid' => $config['app_id'],
                'mch_id' => $config['mch_id'],
                'prepay_id' => $prepayid,
                'nonce_str' => $nonce_str,
                'timestamp' => $time_stamp,
                'packages' => $pack,
                'sign' => $sign,
            ];

            return self::formatBody(['order' => $order, 'wxpay' => $body]);
        }

        if ($code == 'unionpay.app') {
            $config = self::checkConfig(['mer_id', 'cert_pwd'], $payment);
            $signCert = Cert::where('config_id',$payment->id)->value('file');

            if (!$config || !$signCert) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $unionpay = new Union;
            $unionpay->config = [
                'appUrl' => 'https://gateway.95516.com/gateway/api/appTransReq.do', //App请求交易地址
                'frontUrl' => 'https://gateway.95516.com/gateway/api/frontTransReq.do', //前台交易请求地址
                'singleQueryUrl' => 'https://gateway.95516.com/gateway/api/queryTrans.do', //单笔查询请求地址
                'signCertPath' => $signCert, //签名证书路径
                'verifyCertPath' => app()->basePath() . '/app/Services/Payment/Unionpay/UpopRsaCert.cer', //生产 验签证书路径
                'merId' => $config['mer_id'],
                'signCertPwd' => $config['cert_pwd'], //签名证书密码
            ]; //上面给出的配置参数
            $unionpay->params = [
                'version' => '5.0.0', //版本号
                'encoding' => 'UTF-8', //编码方式
                'certId' => $unionpay->getSignCertId(), //证书ID
                'signature' => '', //签名
                'signMethod' => '01', //签名方式
                'txnType' => '01', //交易类型
                'txnSubType' => '01', //交易子类
                'bizType' => '000201', //产品类型
                'channelType' => '08',//渠道类型
                'backUrl' => url('/v2/order.notify.unionpay.app'), //后台通知地址
                'frontUrl' => 'https://gateway.95516.com/gateway/api/frontTransReq.do', //前台通知地址
                'accessType' => '0', //接入类型
                'merId' => $config['mer_id'], //商户代码
                'orderId' => $order->order_sn, //商户订单号
                'txnTime' => date('YmdHis'), //订单发送时间
                'txnAmt' => $order->order_amount * 100, //交易金额，单位分
                'currencyCode' => '156', //交易币种
            ];

            $tn = $unionpay->getTn(); //手机控件支付的所需的tn参数。
            return self::formatBody(['order' => $order, 'unionpay' => ['tn' => $tn]]);
        }
    }

    public static function notify($code)
    {
        //--------- 天工收银 notify ----------
        if ($code == 'teegon.wap') {

            Log::info('notify:'. json_encode($_POST));

            if (isset($_POST['charge_id'])) {
                
                if($_POST['is_success'] == true){

                    /* 修改订单状态 */
                    $order = Order::findUnpayedBySN($_POST['order_no']);

                    $order->pay_time = time();
                    $order->pay_status = Order::PS_PAYED;
                    $order->save();

                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '天工收银支付宝手机支付');
                    Erp::order($order->order_sn);

                    Log::info('notify_order:'. json_encode($order));

                    Log::info('notify_is_success:'. $_POST['is_success']);
                    return true;
                }
                else{
                    Log::info('notify:'. json_encode($_POST));
                    return false;
                }
            } else {
                Log::info('notify_not_post:'. json_encode($_POST));
                return false;
            }
        }
        //----------------------

        $payment = self::where(['type' => 'payment', 'status' => 1, 'code' => $code])->first();

        if (!$payment) {
            return false;
        }

        if ($code == 'alipay.app') {

            $out_trade_no = isset($_POST['out_trade_no']) ? $_POST['out_trade_no'] : 0;

            $order = Order::findUnpayedBySN($out_trade_no);

            $config = self::checkConfig(['partner_id', 'seller_id', 'private_key'], $payment);
            if (!$config || !$order) {
                return false;
            }
            $alipay_config = array(
                "partner"           => $config['partner_id'],
                "alipay_public_key" => keyToPem($config['public_key']),
                "sign_type"         => strtoupper('RSA'),
                "input_charset"     => strtolower('utf-8'),
                "cacert"            => app()->basePath() . '/app/Services/Payment/Alipay/cacert.pem',
                "transport"         => "http",
                "notify_url"        => url("/v2.order.notify.alipay.app"),
            );

            $alipayNotify = new AlipayNotify($alipay_config);
            $verify_result = $alipayNotify->verifyNotify();

            if($verify_result) {//验证成功

                if (empty($_POST['out_trade_no']) && !empty($_POST['notify_data'])) {
                    $_POST = json_decode(json_encode(simplexml_load_string($_POST['notify_data'])), true);
                }

                $trade_status = $_POST['trade_status'];
                $trade_no = $_POST['trade_no'];

                if($_POST['trade_status'] == 'TRADE_FINISHED') {
                    //修改订单状态
                    $order->pay_status = Order::PS_PAYED;
		    //插入付款时间
	            $order->pay_time = time();
                    $order->save();
                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '支付宝手机支付');
                }
                else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                    //修改订单状态
                    $order->pay_status = Order::PS_PAYED;
		    //插入付款时间
                    $order->pay_time = time();
                    $order->save();
                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '支付宝手机支付');
                    Erp::order($order->order_sn);

                }else
                {
                    Log::error('订单支付回调处理异常: '.$out_trade_no);
                    Log::error('TRADE_STATUS:'.$_POST['trade_status']);
                }

                echo "success";

            } else {
                Log::info('订单支付回调故障: '.$out_trade_no);
                echo "fail";
            }

            return true;
        }

        if ($code == 'wxpay.app') {

            if (version_compare(PHP_VERSION, '5.6.0', '<')) {
                if (!empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
                    $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
                } else {
                    $postStr = file_get_contents('php://input');
                }
            } else {
                $postStr = file_get_contents('php://input');
            }

            if (empty($postStr)) {
                return false;
            }

            /* 创建支付应答对象 */
            $resHandler = new WxResponse();

            $inputParams = $resHandler->xmlToArray($postStr);

            foreach($inputParams as $k => $v) {
                $resHandler->setParameter($k, $v);
            }

            $out_trade_no = $resHandler->getParameter("out_trade_no");

            $order = Order::findUnpayedBySN($out_trade_no);

            $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config || !$order) {
                return false;
            }

            $resHandler->setKey($config['mch_key']);

            //判断签名
            if($resHandler->isTenpaySign() == true) {

                //支付结果
                $return_code = $resHandler->getParameter("return_code");

                //判断签名及结果
                if ("SUCCESS"==$return_code){

                    //商户在收到后台通知后根据通知ID向财付通发起验证确认，采用后台系统调用交互模式
                    //商户交易单号
                    $out_trade_no = $resHandler->getParameter("out_trade_no");

                    //财付通订单号
                    $transaction_id = $resHandler->getParameter("transaction_id");
                    $order->pay_time = time();
                    $order->pay_status = Order::PS_PAYED;
                    $order->save();
                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '微信手机支付');
                    Erp::order($order->order_sn);
                } else {
                    Log::error('后台通知失败');
                }
                //回复服务器处理成功
                echo $resHandler->getSucessXml();
            } else {
                echo $resHandler->getFailXml();
                Log::error("验证签名失败");
            }

            return true;
        }

        if ($code == 'wxpay.web') {

            if (version_compare(PHP_VERSION, '5.6.0', '<')) {
                if (!empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
                    $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
                } else {
                    $postStr = file_get_contents('php://input');
                }
            } else {
                $postStr = file_get_contents('php://input');
            }

            if (empty($postStr)) {
                return false;
            }

            /* 创建支付应答对象 */
            $resHandler = new WxResponse();

            $inputParams = $resHandler->xmlToArray($postStr);

            foreach($inputParams as $k => $v) {
                $resHandler->setParameter($k, $v);
            }

            $out_trade_no = $resHandler->getParameter("out_trade_no");

            $order = Order::findUnpayedBySN($out_trade_no);

            $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config || !$order) {
                return false;
            }

            $resHandler->setKey($config['mch_key']);

            //判断签名
            if($resHandler->isTenpaySign() == true) {

                //支付结果
                $return_code = $resHandler->getParameter("return_code");

                //判断签名及结果
                if ("SUCCESS"==$return_code){

                    //商户在收到后台通知后根据通知ID向财付通发起验证确认，采用后台系统调用交互模式
                    //商户交易单号
                    $out_trade_no = $resHandler->getParameter("out_trade_no");

                    //财付通订单号
                    $transaction_id = $resHandler->getParameter("transaction_id");

                    $order->pay_status = Order::PS_PAYED;
		            //插入付款时间
                    $order->pay_time = time();
                    $order->save();
                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '微信手机支付');
                    Erp::order($order->order_sn);
                } else {
                    Log::error('后台通知失败');
                }
                //回复服务器处理成功
                echo $resHandler->getSucessXml();
            } else {
                echo $resHandler->getFailXml();
                Log::error("验证签名失败");
            }

            return true;
        }

        if ($code == 'unionpay.app') {

            $out_trade_no = isset($_POST['orderId']) ? $_POST['orderId'] : 0;

            $order = Order::findUnpayedBySN($out_trade_no);

            $config = self::checkConfig(['mer_id', 'cert_pwd'], $payment);
            if (!$config || !$order) {
                return false;
            }

            $unionpay = new Union;

            $unionpay->config = [
                'appUrl' => 'https://101.231.204.80:5000/gateway/api/appTransReq.do', //App请求交易地址
                'frontUrl' => 'https://101.231.204.80:5000/gateway/api/frontTransReq.do', //前台交易请求地址
                'singleQueryUrl' => 'https://101.231.204.80:5000/gateway/api/queryTrans.do', //单笔查询请求地址
                'signCertPath' => app()->basePath() . '/app/Services/Payment/Unionpay/cert.pfx', //签名证书路径

                'verifyCertPath' => app()->basePath() . '/app/Services/Payment/Unionpay/UpopRsaCert.cer', //生产 验签证书路径
                'merId' => $config['mer_id'],
                'signCertPwd' => $config['cert_pwd'], //签名证书密码
            ]; //上面给出的配置参数

            $postStr = $_POST;
            $unionpay->params = $_POST;
            if(!$unionpay->verifySign()) {
                echo 'fail';
                return false;
            }
            if($unionpay->params['respCode'] == '00') {
                $out_trade_no = $unionpay->params['queryId'];
                $order_sn = $unionpay->params['orderId'];

                //业务代码
                $order->pay_status = Order::PS_PAYED;
		        //插入付款时间
                $order->pay_time = time();
                $order->save();
                OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '银联手机支付');
                Erp::order($order->order_sn);
            }
            echo 'success';
            return true;
        }

    }

    private static function checkConfig(array $params, $payment)
    {
        $config = json_decode($payment->config, true);

        foreach ($params as $key => $value) {
            if (!isset($config[$value])) {
                return false;
            }
        }

        return $config;
    }

    public function getDescAttribute()
    {
        return $this->attributes['description'];
    }
}
