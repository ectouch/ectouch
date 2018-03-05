<?php
class Jssdk {
  private $appId;
  private $appSecret;

  public function __construct($appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->cache = new Cache();
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();

    // 注意 URL 一定要动态获取，不能 hardcode.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = strtolower("$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    $name = 'wechat_jsapi_ticket'.$this->appId;
    // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = $this->cache->get($name);
    if ($data === false) {
      $accessToken = $this->getAccessToken();
      // 如果是企业号用以下 URL 获取 ticket
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=$accessToken";
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $expire_time = $res->expires_in ? intval($res->expires_in)-100 : 3600;
        $this->cache->set($name, $ticket, $expire_time);
      }
    } else {
      $ticket = $data;
    }

    return $ticket;
  }

  private function getAccessToken() {
    $name = 'wechat_access_token'.$this->appId;
    // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
    $data = $this->cache->get($name);
    if ($data === false) {
      // 如果是企业号用以下URL获取access_token
      // $url = "https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$this->appId&corpsecret=$this->appSecret";
      $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
      $res = json_decode($this->httpGet($url));
      $access_token = $res->access_token;
      if ($access_token) {
        $expire_time = $res->expires_in ? intval($res->expires_in)-100 : 3600;
        $this->cache->set($name, $access_token, $expire_time);
      }
    } else {
      $access_token = $data;
    }
    return $access_token;
  }

  private function httpGet($url) {
    return Http::doGet($url);
  }
}

