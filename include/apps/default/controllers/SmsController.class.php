<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SmsController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 短信发送控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SmsController extends CommonController
{
    protected $mobile;
    //短信验证码
    protected $mobile_code;
    //安全码
    protected $sms_code;
    //验证码
    protected $captcha;

    public function __construct()
    {
        parent::__construct();

        $this->mobile = in($_POST['mobile']);
        $this->mobile_code = in($_POST['mobile_code']);
        $this->sms_code = in($_POST['sms_code']);
        $this->captcha = in($_POST['captcha']);
    }

    //发送
    public function send()
    {
        if (empty($this->sms_code) || $_SESSION['sms_code'] != $this->sms_code) {
            exit(json_encode(array('msg' => '请重新访问页面')));
        }
        // 检查验证码
        if (empty($this->captcha)) {
            exit(json_encode(array('msg' => '请输入图片验证码')));
        }
        if ($_SESSION['ectouch_verify'] !== strtoupper($this->captcha)) {
            exit(json_encode(array('msg' => '图片验证码不正确')));
        }
        if (empty($this->mobile)) {
            exit(json_encode(array('msg' => '手机号码不能为空')));
        }

        $preg = '/^13[0-9]{9}|15[012356789][0-9]{8}|18[0-9]{9}|14[579][0-9]{8}|17[0-9]{9}$/'; //简单的方法
        if (!preg_match($preg, $this->mobile)) {
            exit(json_encode(array('msg' => '手机号码格式不正确')));
        }

        if ($_SESSION['sms_mobile']) {
            if (strtotime($this->read_file($this->mobile)) > (time() - 60)) {
                exit(json_encode(array('msg' => '获取验证码过于频繁')));
            }
        }
        
        $sql = "SELECT user_id FROM ".$this->model->pre."users where user_name ='$this->mobile' OR mobile_phone = '$this->mobile'";
        $user_id = $this->model->query($sql);
        if ($_GET['flag'] == 'register') {
            //手机注册
            if (!empty($user_id)) {
                exit(json_encode(array('msg' => '手机号码已经注册')));
            }
        } elseif ($_GET['flag'] == 'forget') {
            //找回密码
            if (empty($user_id)) {
                exit(json_encode(array('msg' => "手机号码不存在\n无法通过该号码找回密码")));
            }
        }

        $this->mobile_code = $this->random(6, 1);
        $message = "您的验证码是：" . $this->mobile_code . "，请不要把验证码泄露给其他人，如非本人操作，可不用理会";

        //查询已安装排序第一位的短信插件        
        $sms_info = get_default_smsment();        
        if(!$sms_info){
            exit(json_encode(array('msg' => '未安装有效短信')));
        }
        $sms = new EcsSms();
        $send_result = $sms->send($this->mobile, $message, '', 1, '', '1.0', $sms_info);
        $this->write_file($this->mobile, date("Y-m-d H:i:s"));

        if ($send_result === true) {
            $_SESSION['sms_mobile'] = $this->mobile;
            $_SESSION['sms_mobile_code'] = $this->mobile_code;
            exit(json_encode(array('code' => 2)));
        } else {
            exit(json_encode(array('msg' => '验证码发送失败')));
        }
    }

    //验证
    public function check()
    {
        if ($this->mobile != $_SESSION['sms_mobile'] or $this->mobile_code != $_SESSION['sms_mobile_code']) {
            exit(json_encode(array('msg' => '手机验证码输入错误。')));
        } else {
            exit(json_encode(array('code' => '2')));
        }
    }

    private function random($length = 6, $numeric = 0)
    {
        PHP_VERSION < '4.2.0' && mt_srand((double) microtime() * 1000000);
        if ($numeric) {
            $hash = sprintf('%0' . $length . 'd', mt_rand(0, pow(10, $length) - 1));
        } else {
            $hash = '';
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789abcdefghjkmnpqrstuvwxyz';
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $hash .= $chars[mt_rand(0, $max)];
            }
        }
        return $hash;
    }

    private function write_file($file_name, $content)
    {
        $this->mkdirs(ROOT_PATH . 'data/smslog/' . date('Ymd'));
        $filename = ROOT_PATH . 'data/smslog/' . date('Ymd') . '/' . $file_name . '.log';
        $Ts = fopen($filename, "a+");
        fputs($Ts, "\r\n" . $content);
        fclose($Ts);
    }

    private function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!$this->mkdirs(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }

    private function read_file($file_name)
    {
        $content = '';
        $filename = ROOT_PATH . 'data/smslog/' . date('Ymd') . '/' . $file_name . '.log';
        if (function_exists('file_get_contents')) {
            @$content = file_get_contents($filename);
        } else {
            if (@$fp = fopen($filename, 'r')) {
                @$content = fread($fp, filesize($filename));
                @fclose($fp);
            }
        }
        $content = explode("\r\n", $content);
        return end($content);
    }
}
