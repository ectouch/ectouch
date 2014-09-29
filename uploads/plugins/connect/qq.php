<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：qq.php
 * ----------------------------------------------------------------------------
 * 功能描述：腾讯qq登录插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = ROOT_PATH . 'plugins/connect/language/' . C('lang') . '/' . basename(__FILE__);

if (file_exists($payment_lang)) {
    include_once ($payment_lang);
    L($_LANG);
}
/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE) {
    $i = isset($modules) ? count($modules) : 0;
    /* 类名 */
    $modules[$i]['name'] = 'QQ';
    // 文件名，不包含后缀
    $modules[$i]['type'] = 'qq';

    $modules[$i]['className'] = 'qq';
    // 作者信息
    $modules[$i]['author'] = 'Zhulin';

    // 作者QQ
    $modules[$i]['qq'] = '2880175566';

    // 作者邮箱
    $modules[$i]['email'] = 'zhulin@ecmoban.com';

    // 申请网址
    $modules[$i]['website'] = 'http://open.qq.com';

    // 版本号
    $modules[$i]['version'] = '1.0';

    // 更新日期
    $modules[$i]['date'] = '2014-8-19';
    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('type' => 'text', 'name' => 'app_key', 'value' => ''),
        array('type' => 'text', 'name' => 'app_secret', 'value' => ''),
    );
    return;
}

/**
 * QQ API client
 */
class qq {

    public $api_url = 'https://graph.qq.com/';
    private $appid = '';
    private $appkey = '';
    private $access_token = '';
    private $scope = '';

    /**
     * 构造函数
     *
     * @param unknown $app            
     * @param string $access_token            
     */
    public function __construct($conf, $access_token = NULL) {
        $this->appid = $conf['app_key'];
        $this->appkey = $conf['app_secret'];
        $this->access_token = $access_token;
        $this->scope = 'get_user_info,add_share';
    }

    /**
     * 请求登录
     *
     * @param unknown $info            
     * @param unknown $url            
     * @return mixed
     */
    public function act_login($info, $url) {
        $login_url = $this->login_url($url, $this->scope);
        $login_url = str_replace('&amp;', '&', $login_url);
        return $login_url;
    }

    /**
     * 回调
     *
     * @param unknown $info            
     * @param unknown $url            
     * @param unknown $code            
     * @return boolean
     */
    public function call_back($info, $url, $code) {
        $result = $this->access_token($url, $code);
        if (isset($result['access_token']) && $result['access_token'] != '') {
            // 保存登录信息，此示例中使用session保存
            $_SESSION['access_token'] = $result['access_token']; // access token echo '授权完成，请记录<br/>access token：<input size="50" value="', $result['access_token'], '">' . $_SESSION['qq_t'];
            return true;
        } else {
            // echo "授权失败";
            return false;
        }
    }

    /**
     * 生成授权网址
     *
     * @param unknown $callback_url            
     * @param string $scope            
     * @return string
     */
    public function login_url($callback_url, $scope = '') {
        $params = array(
            'client_id' => $this->appid,
            'redirect_uri' => $callback_url,
            'response_type' => 'code',
            'scope' => $scope
        );
        return 'https://graph.qq.com/oauth2.0/authorize?' . http_build_query($params);
    }

    /**
     * 获取access token
     *
     * @param unknown $callback_url            
     * @param unknown $code            
     * @return multitype:
     */
    public function access_token($callback_url, $code) {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->appid,
            'client_secret' => $this->appkey,
            'code' => $code,
            'state' => '',
            'redirect_uri' => $callback_url
        );
        $url = 'https://graph.qq.com/oauth2.0/token?' . http_build_query($params);
        $url = str_replace('&amp;', '&', $url);
        $result_str = $this->http($url);
        $json_r = array();
        if ($result_str != '')
            parse_str($result_str, $json_r);
        return $json_r;
    }

    /**
     * 获取登录用户的openid
     *
     * @return Ambigous <>
     */
    public function get_openid() {
        $params = array(
            'access_token' => $this->access_token
        );
        $url = 'https://graph.qq.com/oauth2.0/me?' . http_build_query($params);
        $result_str = $this->http($url);
        $json_r = array();
        if ($result_str != '') {
            preg_match('/callback\(\s+(.*?)\s+\)/i', $result_str, $result_a);
            $json_r = json_decode($result_a[1], true);
        }
        return $json_r['openid'];
    }

    /**
     * 根据openid获取用户信息
     *
     * @param unknown $openid            
     * @return Ambigous <multitype:, mixed>
     */
    public function get_user_info($openid) {
        $params = array(
            'openid' => $openid
        );
        return $this->api('user/get_user_info', $params);
    }

    /**
     * 发布分享
     *
     * @param unknown $openid            
     * @param unknown $title            
     * @param unknown $url            
     * @param unknown $site            
     * @param unknown $fromurl            
     * @param string $images            
     * @param string $summary            
     * @return Ambigous <multitype:, mixed>
     */
    public function add_share($openid, $title, $url, $site, $fromurl, $images = '', $summary = '') {
        $params = array(
            'openid' => $openid,
            'title' => $title,
            'url' => $url,
            'site' => $site,
            'fromurl' => $fromurl,
            'images' => $images,
            'summary' => $summary
        );
        return $this->api('share/add_share', $params, 'POST');
    }

    /**
     * 调用接口
     *
     * @param unknown $url            
     * @param unknown $params            
     * @param string $method            
     * @return Ambigous <multitype:, mixed>
     *         //示例：根据openid获取用户信息
     *         $result=$qq->api('user/get_user_info', array('openid'=>$openid), 'GET');
     */
    public function api($url, $params = array(), $method = 'GET') {
        $url = $this->api_url . $url;
        $params['access_token'] = $this->access_token;
        $params['oauth_consumer_key'] = $this->appid;
        $params['format'] = 'json';
        if ($method == 'GET') {
            $query_url = $url . '?' . http_build_query($params);
            $query_url = str_replace('&amp;', '&', $query_url);
            $result_str = $this->http($query_url);
        } else {
            $query = http_build_query($params);
            $query = str_replace('&amp;', '&', $query);
            $result_str = $this->http($url, $query, 'POST');
        }
        $result = array();
        if ($result_str != '')
            $result = json_decode($result_str, true);
        return $result;
    }

    /**
     * 提交请求
     *
     * @param unknown $url            
     * @param string $postfields            
     * @param string $method            
     * @param unknown $headers            
     * @return mixed
     */
    private function http($url, $postfields = '', $method = 'GET', $headers = array()) {
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        if ($method == 'POST') {
            curl_setopt($ci, CURLOPT_POST, TRUE);
            if ($postfields != '')
                curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        $headers[] = 'User-Agent: QQ.PHP(piscdong.com)';
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }

}
