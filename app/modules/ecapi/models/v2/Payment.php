<?php

namespace app\modules\ecapi\models\v2;

use Yii;
use app\modules\ecapi\helpers\Token;
use app\modules\ecapi\helpers\Header;
use app\services\payment\wxpay\WxPay;
use app\services\payment\wxpay\WxResponse;
use yii\helpers\Url;

/**
 * This is the model class for table "{{%config}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $type
 * @property string $description
 * @property string $code
 * @property string $config
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 */
class Payment extends Foundation
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'type', 'description', 'code', 'config'], 'required'],
            [['config'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'type', 'code'], 'string', 'max' => 50],
            [['description'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'type' => 'Type',
            'description' => 'Description',
            'code' => 'Code',
            'config' => 'Config',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public static function getList(array $attributes)
    {
        $userAgent = Header::getUserAgent();

        Yii::error('userAgent: ' . var_export($userAgent, true));
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
            $model = static::find()->where(['type' => 'payment', 'status' => 1])->andWhere(['!=', 'code', 'wxpay.web'])->asArray()->one();
        }


        return self::formatBody(['payment_types' => $model]);
    }

    public static function pay(array $attributes)
    {
        extract($attributes);
        $uid = Token::authorization();

        $order = Order::find()->where(['user_id' => $uid, 'order_id' => $order, 'pay_status' => Order::PS_UNPAYED])
            ->andWhere(['in', 'order_status', [Order::OS_UNCONFIRMED, Order::OS_CONFIRMED]])->with('goods')->one();
        if (!$order) {
            return self::formatError(self::NOT_FOUND);
        }

        $shop_name = ShopConfig::findByCode('shop_name');

        // ----------------------------

        $payment = static::find()->where(['type' => 'payment', 'status' => 1, 'code' => $code])->one();

        if (!$payment) {
            return self::formatError(self::NOT_FOUND);
        }

        if ($code == 'wxpay.web') {
            $config = self::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config) {
                return self::formatError(self::UNKNOWN_ERROR);
            }

            $wxpay = new WxPay();
            $wxpay->init($config['app_id'], $config['app_secret'], $config['mch_key']);
            $nonce_str = 'ibuaiVcKdpRxkhJA';
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
                'body' => $shop_name->value,

                'attach' => $shop_name->value,

                //商户订单号
                'out_trade_no' => $order->order_sn,

                //总金额
//                'total_fee' => $order->order_amount * 100,
                 'total_fee' => 1,

                //终端IP
                'spbill_create_ip' => Yii::$app->request->getUserIP(),

                //接受微信支付异步通知回调地址
                'notify_url' => Url::to(['v2/order/notify', 'code' => $code], true),

                //交易类型:JSAPI,NATIVE,APP
                'trade_type' => 'JSAPI'
            ];

            $inputParams['sign'] = $wxpay->createMd5Sign($inputParams);

            //获取prepayid
            $prepayid = $wxpay->sendPrepay($inputParams);

            $pack = 'prepay_id=' . $prepayid;

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
    }

    public static function notify($code)
    {
        $payment = static::find()->where(['type' => 'payment', 'status' => 1, 'code' => $code])->one();

        // Yii::error('获取小程序回调' . json_encode($code));

        if (!$payment) {
            return false;
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
            // Yii::error('获取小程序回调数据' . json_encode($postStr));

            if (empty($postStr)) {
                return false;
            }

            /* 创建支付应答对象 */
            $resHandler = new WxResponse();

            $inputParams = $resHandler->xmlToArray($postStr);

            foreach ($inputParams as $k => $v) {
                $resHandler->setParameter($k, $v);
            }

            $out_trade_no = $resHandler->getParameter("out_trade_no");

            $order = Order::findUnpayedBySN($out_trade_no);

            $config = static::checkConfig(['app_id', 'app_secret', 'mch_id', 'mch_key'], $payment);
            if (!$config || !$order) {
                return false;
            }

            $resHandler->setKey($config['mch_key']);

            //判断签名
            if ($resHandler->isTenpaySign() == true) {

                //支付结果
                $return_code = $resHandler->getParameter("return_code");

                //判断签名及结果
                if ("SUCCESS" == $return_code) {
                    //商户在收到后台通知后根据通知ID向财付通发起验证确认，采用后台系统调用交互模式
                    //商户交易单号
                    $out_trade_no = $resHandler->getParameter("out_trade_no");

                    //财付通订单号
                    $transaction_id = $resHandler->getParameter("transaction_id");

                    $order->order_status = Order::OS_CONFIRMED;
                    $order->pay_status = Order::PS_PAYED;
                    //插入付款时间
                    $order->pay_time = time();
                    $order->save(false);
                    OrderAction::toCreateOrUpdate($order->order_id, $order->order_status, $order->shipping_status, $order->pay_status, '微信手机支付');
                } else {
                    Yii::error('后台通知失败');
                }
                //回复服务器处理成功
                echo $resHandler->getSucessXml();
            } else {
                echo $resHandler->getFailXml();
                Yii::error("验证签名失败");
            }

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

    public function fields()
    {
        $fields = parent::fields();
        $fields['desc'] = 'description';
        return $fields;
    }

}
