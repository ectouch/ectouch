<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wxpay.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信支付插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 微信支付类
 */
class wxpay
{
    private $parameters; // cft 参数
    private $payment; // 配置信息

    /**
     * 生成支付代码
     * @param   array $order 订单信息
     * @param   array $payment 支付方式信息
     */
    public function get_code($order, $payment)
    {
        // 配置参数
        $this->payment = $payment;

        $options = array(
             'appid' => $this->payment['wxpay_appid'], //填写高级调用功能的app id
             'mch_id' => $this->payment['wxpay_mchid'], //微信支付商户号
             'key' => $this->payment['wxpay_key'] //微信支付API密钥
        );
        $weObj = new Wechat($options);

        $order_amount = $order['order_amount'] * 100;

        // 判断是否是微信浏览器 调用H5支付 MWEB, 需要商户另外申请
        if (!is_wechat_browser()) {
            $scene_info = json_encode(array('h5_info' => array('type' => 'Wap','wap_url' => __URL__,'wap_name' => C('shop_name'))));

            $this->setParameter("body", $order['order_sn']); // 商品描述
            $this->setParameter("out_trade_no", $order['order_sn'] . 'A' . $order_amount . 'B' . $order['log_id']); // 商户订单号
            $this->setParameter("total_fee", $order_amount); // 总金额
            $this->setParameter("spbill_create_ip", $this->get_client_ip()); // 客户端IP
            $this->setParameter("notify_url", notify_url(basename(__FILE__, '.php'), true)); // 异步通知地址
            $this->setParameter("trade_type", "MWEB"); // H5支付的交易类型为MWEB
            $this->setParameter("scene_info", $scene_info); // 场景信息

            if ($order['apply'] == 1) {
                $this->setParameter("attach", "drp");
            }
            if ($order['zc_apply'] == 'crowd') {
                $this->setParameter("attach", "crowd");
            }

            $respond = $weObj->PayUnifiedOrder($this->parameters);

            if (isset($respond['mweb_url'])) {
                if ($respond['result_code'] == 'SUCCESS') {
                    $redirect_url = __URL__ . "/respond.php?code=wxpay&style=wxh5&log_id=".$order['log_id'];
                }

                $button = '<div class="n-flow-alipay" style=" text-align:center"><button class="btn btn-info ect-btn-info ect-colorf ect-bg" style="background-color:#44b549;" type="button" onclick="window.open(\'' . $respond['mweb_url']. '&redirect_url='. urlencode($redirect_url) . '\')">微信支付</button></div>';
            } else {
                $button = '';
                return false;
            }
        } else {
            // 网页授权获取用户openid
            $openid = empty($_SESSION['openid']) ? $_SESSION['wechat_user']['openid'] : $_SESSION['openid'];
            $user = model('Users')->get_openid($_SESSION['user_id']);
            if (!isset($openid) || empty($openid)) {
                if (!empty($user)) {
                    $openid = $user;
                } else {
                    return false;
                }
            }
            $this->setParameter("openid", $openid); // 用户openid
            $this->setParameter("body", $order['order_sn']); // 商品描述
            $this->setParameter("out_trade_no", $order['order_sn'] . 'A' . $order_amount . 'B' . $order['log_id']); // 商户订单号
            $this->setParameter("total_fee", $order_amount); // 总金额
            $this->setParameter("notify_url", notify_url(basename(__FILE__, '.php'), true)); // 异步通知地址
            $this->setParameter("trade_type", "JSAPI"); // 交易类型
            if ($order['apply'] == 1) {
                $this->setParameter("attach", "drp");
            }
            if ($order['zc_apply'] == 'crowd') {
                $this->setParameter("attach", "crowd");
            }
            $prepay_id = $this->getPrepayId();
            $jsApiParameters = $this->getParameters($prepay_id);
            // wxjsbridge
            $js = '<script language="javascript">
            function jsApiCall(){WeixinJSBridge.invoke("getBrandWCPayRequest",' . $jsApiParameters . ',function(res){if(res.err_msg == "get_brand_wcpay_request:ok"){location.href="' . return_url(basename(__FILE__, '.php')) . '&status=1&log_id='.$order['log_id'].'"}else{location.href="' . return_url(basename(__FILE__, '.php')) . '&status=0&log_id='.$order['log_id'].'"}})};function callpay(){if (typeof WeixinJSBridge == "undefined"){if( document.addEventListener ){document.addEventListener("WeixinJSBridgeReady", jsApiCall, false);}else if (document.attachEvent){document.attachEvent("WeixinJSBridgeReady", jsApiCall);document.attachEvent("onWeixinJSBridgeReady", jsApiCall);}}else{jsApiCall();}}
            </script>';

            $button = '<div style="text-align:center"><button class="btn btn-info ect-btn-info ect-colorf ect-bg" style="background-color:#44b549;" type="button" onclick="callpay()">立即付款</button></div>' . $js;
        }

        return $button;
    }

    /**
     * 同步通知
     * @param $data
     * @return mixed
     */
    public function callback($data)
    {
        if (isset($_GET) && $_GET['status'] == 1) {
            $order = array();
            $order['log_id']= intval($_GET['log_id']);
            $payment = model('Payment')->get_payment($data['code']);
            return $this->queryOrder($order, $payment);
        } else {
            return false;
        }
    }

    /**
     * 异步通知
     * @param $data
     * @return mixed
     */
    public function notify($data)
    {
        $inputdata = file_get_contents("php://input");
        if (!empty($inputdata)) {
            $payment = model('Payment')->get_payment($data['code']);
            $postdata = json_decode(json_encode(simplexml_load_string($inputdata, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            /* 检查插件文件是否存在，如果存在则验证支付是否成功，否则则返回失败信息 */
            // 微信端签名
            $wxsign = $postdata['sign'];
            unset($postdata['sign']);

            // 微信附加参数
            $attach = $postdata['attach'];

            foreach ($postdata as $k => $v) {
                $Parameters[$k] = $v;
            }
            // 签名步骤一：按字典序排序参数
            ksort($Parameters);

            $buff = "";
            foreach ($Parameters as $k => $v) {
                $buff .= $k . "=" . $v . "&";
            }
            $String = '';
            if (strlen($buff) > 0) {
                $String = substr($buff, 0, strlen($buff) - 1);
            }
            // 签名步骤二：在string后加入KEY
            $String = $String . "&key=" . $payment['wxpay_key'];
            // 签名步骤三：MD5加密
            $String = md5($String);
            // 签名步骤四：所有字符转为大写
            $sign = strtoupper($String);
            // 验证成功
            if ($wxsign == $sign) {
                // 交易成功
                if ($postdata['result_code'] == 'SUCCESS') {
                    // 获取log_id
                    $out_trade_no = explode('B', $postdata['out_trade_no']);
                    $log_id = $out_trade_no[1]; // 订单号log_id
                    $order_trade_no = explode('A', $out_trade_no[0]);
                    // 改变订单状态
                    if ($attach == 'drp') {
                        model('Payment')->drp_order_paid($log_id, 2);
                    } elseif ($attach == 'crowd') {
                        model('Payment')->crowd_order_paid($log_id, 2);
                    } else {
                        model('Payment')->order_paid($log_id, 2);
                    }
                    // 修改订单信息(openid，tranid)
                    model('Base')->model->table('pay_log')
                        ->data('openid = "' . $postdata['openid'] . '", transid = "' . $postdata['transaction_id'] . '"')
                        ->where('log_id = ' . $log_id)
                        ->update();
                }
                $returndata['return_code'] = 'SUCCESS';
            } else {
                $returndata['return_code'] = 'FAIL';
                $returndata['return_msg'] = '签名失败';
            }
        } else {
            $returndata['return_code'] = 'FAIL';
            $returndata['return_msg'] = '无数据返回';
        }
        // 数组转化为xml
        $xml = "<xml>";
        foreach ($returndata as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        exit($xml);
    }

    public function trimString($value)
    {
        $ret = null;
        if (null != $value) {
            $ret = $value;
            if (strlen($ret) == 0) {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 作用：产生随机字符串，不长于32位
     */
    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 作用：设置请求参数
     */
    public function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);

        $buff = "";
        foreach ($Parameters as $k => $v) {
            $buff .= $k . "=" . $v . "&";
        }
        $String = '';
        if (strlen($buff) > 0) {
            $String = substr($buff, 0, strlen($buff) - 1);
        }
        // echo '【string1】'.$String.'</br>';
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->payment['wxpay_key'];
        // echo "【string2】".$String."</br>";
        // 签名步骤三：MD5加密
        $String = md5($String);
        // echo "【string3】 ".$String."</br>";
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        // echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 获取当前服务器的IP
     */
    private function get_client_ip()
    {
        if ($_SERVER['REMOTE_ADDR']) {
            $cip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv("REMOTE_ADDR")) {
            $cip = getenv("REMOTE_ADDR");
        } elseif (getenv("HTTP_CLIENT_IP")) {
            $cip = getenv("HTTP_CLIENT_IP");
        } else {
            $cip = "unknown";
        }
        return $cip;
    }

    /**
     * 作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml, $url, $second = 30)
    {
        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, false);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        // 运行curl
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 查询订单
     * 当商户后台、网络、服务器等出现异常，商户系统最终未接收到支付通知
     *
     * @param $order['log_id']
     * @param $payment
     */
    public function queryOrder($order, $payment)
    {
        // 查询未支付的订单
        $res = model('Base')->model->table('pay_log')->field('transid, is_paid, log_id')->where(array('log_id' => $order['log_id']))->find();
        if ($res['is_paid'] == 0) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            // 微信订单号  商户订单号  二选一 ， 微信的订单号，建议优先使用
            $transaction_id = $res['transid'];
            $this->setParameter("transaction_id", $transaction_id); // 微信订单号

            $respond = $weObj->PayQueryOrder($this->parameters);
            if ($respond['result_code'] == 'SUCCESS' && $respond['trade_state'] == 'SUCCESS') {
                // 改变订单状态
                order_paid($order['log_id'], 2);
                return true;
            } else {
                return false;
            }
        } elseif ($res['is_paid'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 退款申请
     * array(
     *     'order_id' => '1',
     *     'order_sn' => '2017061609464501623',
     *     'pay_id' => '',
     *     'pay_status' => 2
     * )
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    public function payRefund($order, $payment)
    {
        // 查询已支付的订单
        $res = model('Base')->model->table('pay_log')->field('transid, is_paid, log_id, order_amount')->where(array('order_id' => $order['order_id']))->find();

        if ($res['is_paid'] == 1 && $order['pay_status'] == 2) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            $order_amount = $res['order_amount'] * 100;
            $out_trade_no = $order['order_sn'] . 'A' . $order_amount . 'B' . $res['log_id']; // 商户订单号

            $order_return_info = model('Base')->model->table('order_return')->field('return_sn, order_sn, return_status, refound_status')->where(array('order_id' => $order['order_id']))->find();

            $out_refund_no = $order_return_info['return_sn']; // 商户退款单号
            $total_fee = $order_amount;
            $refund_fee = isset($order['should_return']) ? $order['should_return'] : $order_amount;   // 退款金额 默认退全款

            $this->setParameter("out_trade_no", $out_trade_no); // 商户订单号
            $this->setParameter("out_refund_no", $out_refund_no);// 商户退款单号
            $this->setParameter("total_fee", $total_fee);//总金额
            $this->setParameter("refund_fee", $refund_fee);//退款金额
            $this->setParameter("op_user_id", $payment['wxpay_mchid']);//操作员

            $respond = $weObj->PayRefund($this->parameters);
            // 退款申请接收成功
            if ($respond['result_code'] == 'SUCCESS') {
                $out_refund_no = $respond['out_refund_no']; // 商户退款单号
                return $out_refund_no;
            } else {
                logResult($respond);
                return false;
            }
        }
    }

    /**
     * 查询退款
     *
     * @param $order
     * @param $payment
     * @return bool
     */
    public function payRefundQuery($order, $payment)
    {
        // 查询已退款的订单
        $order_return_info = model('Base')->model->table('order_return')->field('return_sn, order_sn, return_status, refound_status')->where(array('order_id' => $order['order_id']))->find();
        if ($order_return_info && $order_return_info['refound_status'] == 1) {
            $options = array(
                     'appid' => $payment['wxpay_appid'], //填写高级调用功能的app id
                     'mch_id' => $payment['wxpay_mchid'], //微信支付商户号
                     'key' => $payment['wxpay_key'], //微信支付API密钥
                 );
            $weObj = new Wechat($options);

            // 微信订单号 transaction_id， 商户订单号 out_trade_no， 商户退款单号 out_refund_no，微信退款单号 refund_id 四选一
            // $this->setParameter("out_trade_no", $out_trade_no);
            $this->setParameter("out_refund_no", $order_return_info['return_sn']);// 商户退款单号
            // $this->setParameter("transaction_id", $transaction_id);
            // $this->setParameter("refund_id", $refund_id);

            $respond = $weObj->PayRefundQuery($this->parameters);
            // 退款查询
            if ($respond['result_code'] == 'SUCCESS' && $respond['refund_status'] == 'SUCCESS') {
                /*
                refund_status_$n $n为下标，从0开始编号。
                退款状态：
                SUCCESS—退款成功
                REFUNDCLOSE—退款关闭。
                PROCESSING—退款处理中
                CHANGE—退款异常，退款到银行发现用户的卡作废或者冻结了
                 */
                $out_refund_no = $respond['out_refund_no']; // 商户退款单号
                $refund_count = $respond['refund_count']; // 退款笔数
                $refund_fee = $respond['refund_fee']; // 退款金额

                return $out_refund_no;
            } else {
                logResult($respond);
                return false;
            }
        }
    }

    /**
     * 获取prepay_id
     */
    private function getPrepayId()
    {
        // 设置接口链接
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        try {
            // 检测必填参数
            if ($this->parameters["out_trade_no"] == null) {
                throw new Exception("缺少统一支付接口必填参数out_trade_no！" . "<br>");
            } elseif ($this->parameters["body"] == null) {
                throw new Exception("缺少统一支付接口必填参数body！" . "<br>");
            } elseif ($this->parameters["total_fee"] == null) {
                throw new Exception("缺少统一支付接口必填参数total_fee！" . "<br>");
            } elseif ($this->parameters["notify_url"] == null) {
                throw new Exception("缺少统一支付接口必填参数notify_url！" . "<br>");
            } elseif ($this->parameters["trade_type"] == null) {
                throw new Exception("缺少统一支付接口必填参数trade_type！" . "<br>");
            } elseif ($this->parameters["trade_type"] == "JSAPI" && $this->parameters["openid"] == null) {
                throw new Exception("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！" . "<br>");
            }
            $this->parameters["appid"] = $this->payment['wxpay_appid']; // 公众账号ID
            $this->parameters["mch_id"] = $this->payment['wxpay_mchid']; // 商户号
            $this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR']; // 终端ip
            $this->parameters["nonce_str"] = $this->createNoncestr(); // 随机字符串
            $this->parameters["sign"] = $this->getSign($this->parameters); // 签名
            $xml = "<xml>";
            foreach ($this->parameters as $key => $val) {
                if (is_numeric($val)) {
                    $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
                } else {
                    $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
                }
            }
            $xml .= "</xml>";
        } catch (Exception $e) {
            die($e->getMessage());
        }

        // $response = $this->postXmlCurl($xml, $url, 30);
        $response = Http::curlPost($url, $xml, 30);
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $prepay_id = $result["prepay_id"];
        return $prepay_id;
    }

    /**
     * 作用：设置jsapi的参数
     */
    private function getParameters($prepay_id)
    {
        $jsApiObj["appId"] = $this->payment['wxpay_appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepay_id";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $this->parameters = json_encode($jsApiObj);

        return $this->parameters;
    }

    /**
     * 输出xml字符
     **/
    private function toXml($data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new Exception("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}
