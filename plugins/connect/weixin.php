<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wechat.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信登录插件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

$payment_lang = ROOT_PATH . 'plugins/connect/languages/' . C('lang') . '/' . basename(__FILE__);

if (file_exists($payment_lang)) {
    include_once($payment_lang);
    L($_LANG);
}
/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 类名 */
    $modules[$i]['name'] = '微信登录插件';
    // 文件名，不包含后缀
    $modules[$i]['type'] = 'weixin';

    $modules[$i]['className'] = 'weixin';
    // 作者信息
    $modules[$i]['author'] = 'ECTouch Team';

    // 作者QQ
    $modules[$i]['qq'] = '10000';

    // 作者邮箱
    $modules[$i]['email'] = 'support@ectouch.cn';

    // 申请网址
    $modules[$i]['website'] = 'http://mp.weixin.qq.com';

    // 版本号
    $modules[$i]['version'] = '1.0';

    // 更新日期
    $modules[$i]['date'] = '2014-8-19';
    /* 配置信息 */
    $modules[$i]['config'] = array(
        array('type' => 'text', 'name' => 'app_id', 'value' => ''),
        array('type' => 'text', 'name' => 'app_secret', 'value' => ''),
        array('type' => 'text', 'name' => 'token', 'value' => ''),
        array('type' => 'select', 'name' => 'auto_login', 'value' => ''),
    );
    return;
}

/**
 * WECHAT API client
 */
class weixin
{
    private $token = '';
    private $appid = '';
    private $appkey = '';
    private $weObj = '';

    /**
     * 构造函数
     *
     * @param unknown $app
     * @param string $access_token
     */
    public function __construct($conf)
    {
        $this->token = $conf['token'];
        $this->appid = $conf['app_id'];
        $this->appsecret = $conf['app_secret'];

        $config['token'] = $this->token;
        $config['appid'] = $this->appid;
        $config['appsecret'] = $this->appsecret;

        $this->weObj = new Wechat($config);
    }

    /**
     * 获取授权地址
     */
    public function act_login($callback_url, $state = 'wechat_oauth', $snsapi = 'snsapi_userinfo')
    {
        return $this->weObj->getOauthRedirect($callback_url, $state, $snsapi);
    }

    /**
     * 回调用户数据
     */
    public function call_back($callback_url, $code)
    {
        if (!empty($code)) {
            $token = $this->weObj->getOauthAccessToken();
            $userinfo = $this->weObj->getOauthUserinfo($token['access_token'], $token['openid']);

            if (!empty($userinfo) && !empty($userinfo['unionid'])) {
                $_SESSION['wechat_user'] = $userinfo;  // 兼容

                $_SESSION['openid'] = $userinfo['openid'];
                $_SESSION['nickname'] = $userinfo['nickname'];
                $_SESSION['headimgurl'] = $userinfo['headimgurl'];

                $data = array(
                    'unionid' => $userinfo['unionid'],
                    'openid' => $userinfo['openid'],
                    'nickname' => $userinfo['nickname'],
                    'sex' => $userinfo['sex'],
                    'headimgurl' => $userinfo['headimgurl'],
                    'city' => $userinfo['city'],
                    'province' => $userinfo['province'],
                    'country' => $userinfo['country'],
                );
                if (is_wechat_browser()) {
                    $this->update_wechat_unionid($userinfo);
                }
                return $data;
            } else {
                // echo '获取授权信息失败';
                return false;
            }
        } else {
            return false;
        }
    }

    public function update_wechat_unionid($info)
    {
        //公众号id
        $wechat = model('Base')->model->table('wechat')->field('id')->where(array('status' => 1, 'default_wx' => 1))->find();
        $wechat_id = $wechat['id'];
        // 组合数据
        $data = array(
            'wechat_id' => $wechat_id,
            'openid' => $info['openid'],
            'unionid' => $info['unionid']
        );
        // unionid 微信开放平台唯一标识
        if (!empty($info['unionid'])) {
            // 兼容查询用户 已经存在wechat_user 且 unionid 为空的情况 用openid 更新一下 unionid
            $where = array('openid' => $info['openid'], 'wechat_id' => $wechat_id);
            $res = model('Base')->model->table('wechat_user')->field('unionid, ect_uid')->where($where)->find();
            if (empty($res['unionid'])) {
                model('Base')->model->table('wechat_user')->data($data)->where($where)->update();
                if (!empty($res['ect_uid'])) {
                    // 更新社会化登录用户信息
                    $connect_userinfo = model('Users')->get_connect_user($info['unionid']);
                    if (empty($connect_userinfo)) {
                        model('Base')->model->table('connect_user')->data(array('open_id' => $info['unionid']))->where(array('open_id' => $info['openid']))->update();
                    }
                    $info['user_id'] = $res['ect_uid'];
                    model('Users')->update_connnect_user($info, 'wechat');
                }
            }
        }
    }
}
