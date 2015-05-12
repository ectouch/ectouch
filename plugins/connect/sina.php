<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：sina.php
 * ----------------------------------------------------------------------------
 * 功能描述：新浪微博登录插件
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
    $modules[$i]['name'] = 'Sina';
    // 文件名，不包含后缀
    $modules[$i]['type'] = 'sina';

    $modules[$i]['className'] = 'sina';
    // 作者信息
    $modules[$i]['author'] = 'Zhulin';

    // 作者QQ
    $modules[$i]['qq'] = '2880175566';

    // 作者邮箱
    $modules[$i]['email'] = 'zhulin@ecmoban.com';

    // 申请网址
    $modules[$i]['website'] = 'http://open.weibo.com';

    // 版本号
    $modules[$i]['version'] = '1.0';

    // 更新日期
    $modules[$i]['date'] = '2014-10-03';

    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('type' => 'text', 'name' => 'app_key', 'value' => ''),
        array('type' => 'text', 'name' => 'app_secret', 'value' => ''),
    );
    return;
}

/**
 * 新浪微博 API client
 */
class sina {

    public $api_url = 'https://api.weibo.com/2/';
    public $format = 'json';

    /**
     * 构造函数
     *
     * @param unknown $app            
     * @param string $access_token            
     */
    public function __construct($conf, $access_token = NULL) {
        $this->client_id = $conf['app_key'];
        $this->client_secret = $conf['app_secret'];
        $this->access_token = $access_token;
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
            $_SESSION['access_token'] = $result['access_token']; // access token
            // echo '授权完成，请记录<br/>access token：<input size="50" value="', $result['access_token'], '">' . $_SESSION['sina_t'];
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
     * @return string
     */
    public function login_url($callback_url) {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $callback_url
        );
        return 'https://api.weibo.com/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * 获取access token
     *
     * @param unknown $callback_url            
     * @param unknown $code            
     * @return Ambigous <multitype:, mixed>
     */
    public function access_token($callback_url, $code) {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $callback_url
        );
        $url = 'https://api.weibo.com/oauth2/access_token';
        $login_url = str_replace('&amp;', '&', $login_url);
        return $this->http($url, http_build_query($params, '', '&'), 'POST');
    }

    /**
     * 获取登录用户的uid
     *
     * @return Ambigous <>
     */
    public function get_openid() {
        $params = array();
        $result = $this->api('account/get_uid', $params);
        return $result['uid'];
    }

    /**
     * 根据uid获取用户信息
     *
     * @param unknown $uid            
     * @return Ambigous <multitype:, mixed>
     */
    public function get_user_info($uid) {
        $params = array(
            'uid' => $uid
        );
        return $this->api('users/show', $params);
    }
    /**
     * 获取用户名
     *
     * @param unknown $user_info            
     * @return Ambigous <multitype:, mixed>
     */
	public function get_user_name($userinfo){
		
		if($userinfo['screen_name'] != ''){
			return $userinfo['screen_name'];
			}
			else{
				return $userinfo['name'];
			}
		}
    /**
     * 发布微博
     *
     * @param unknown $img_c            
     * @param string $pic            
     * @return Ambigous <multitype:, mixed>
     */
    public function update($img_c, $pic = '') {
        $params = array(
            'status' => $img_c
        );
        if ($pic != '' && is_array($pic)) {
            $url = 'statuses/upload';
            $params['pic'] = $pic;
        } else {
            $url = 'statuses/update';
        }
        return $this->api($url, $params, 'POST');
    }

    /**
     * 根据uid获取用户微博列表
     *
     * @param unknown $uid            
     * @param number $count            
     * @param number $page            
     * @return Ambigous <multitype:, mixed>
     */
    public function user_timeline($uid, $count = 10, $page = 1) {
        $params = array(
            'uid' => $uid,
            'page' => $page,
            'count' => $count
        );
        return $this->api('statuses/user_timeline', $params);
    }

    /**
     * 调用接口
     *
     * @param unknown $url            
     * @param unknown $params            
     * @param string $method            
     * @return Ambigous <multitype:, mixed>
     *         //示例：根据uid获取用户信息
     *         $result=$sina->api('users/show', array('uid'=>$uid), 'GET');
     */
    public function api($url, $params = array(), $method = 'GET') {
        $url = $this->api_url . $url . '.' . $this->format;
        $params['access_token'] = $this->access_token;
        if ($method == 'GET') {
            $query = http_build_query($params);
            $query = str_replace('&amp;', '&', $query);
            $result = $this->http($url . '?' . $query);
        } else {
            if (isset($params['pic'])) {
                uksort($params, 'strcmp');
                $str_b = uniqid('------------------');
                $str_m = '--' . $str_b;
                $str_e = $str_m . '--';
                $body = '';
                foreach ($params as $k => $v) {
                    if ($k == 'pic') {
                        if (is_array($v)) {
                            $img_c = $v[2];
                            $img_n = $v[1];
                        } elseif ($v{0} == '@') {
                            $url = ltrim($v, '@');
                            $img_c = file_get_contents($url);
                            $url_a = explode('?', basename($url));
                            $img_n = $url_a[0];
                        }
                        $body .= $str_m . "\r\n";
                        $body .= 'Content-Disposition: form-data; name="' . $k . '"; filename="' . $img_n . '"' . "\r\n";
                        $body .= "Content-Type: image/unknown\r\n\r\n";
                        $body .= $img_c . "\r\n";
                    } else {
                        $body .= $str_m . "\r\n";
                        $body .= 'Content-Disposition: form-data; name="' . $k . '"' . "\r\n\r\n";
                        $body .= $v . "\r\n";
                    }
                }
                $body .= $str_e;
                $headers[] = 'Content-Type: multipart/form-data; boundary=' . $str_b;
                $result = $this->http($url, $body, 'POST', $headers);
            } else {
                $query = http_build_query($params);
                $query = str_replace('&amp;', '&', $query);
                $result = $this->http($url, $query, 'POST');
            }
        }
        return $result;
    }

    /**
     * 提交请求
     *
     * @param unknown $url            
     * @param string $postfields            
     * @param string $method            
     * @param unknown $headers            
     * @return Ambigous <multitype:, mixed>
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
        $headers[] = 'User-Agent: weibo.PHP(piscdong.com)';
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        curl_close($ci);
        $json_r = array();
        if ($response != '')
            $json_r = json_decode($response, true);
        return $json_r;
    }

}
