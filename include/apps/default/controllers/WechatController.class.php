<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台API
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class WechatController extends CommonController
{
    private $weObj = '';
    private $orgid = '';
    private $wechat_id = 0;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 获取公众号配置
        $this->orgid = I('get.orgid', '', 'trim');
        if ($this->orgid) {
            $wxinfo = $this->get_config($this->orgid);

            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            $this->weObj = new Wechat($config);
            $this->weObj->valid();
            $this->wechat_id = $wxinfo['id'];
        }
    }

    /**
     * 执行方法
     */
    public function index()
    {
        // 事件类型
        $type = $this->weObj->getRev()->getRevType();
        $wedata = $this->weObj->getRev()->getRevData();
        $keywords = '';

        // 兼容更新用户关注状态（未配置微信通之前关注的粉丝）
        $userinfo = $this->weObj->getUserInfo($wedata['FromUserName']);
        if (!empty($userinfo) && !empty($userinfo['unionid'])) {
            $user_data = array(
                'subscribe' => $userinfo['subscribe'],
                'subscribe_time' => $userinfo['subscribe_time'],
            );
            $res = $this->model->table('wechat_user')->field('openid, unionid')->where(array('unionid' => $userinfo['unionid'], 'wechat_id' => $this->wechat_id))->find();
            if (!empty($res)) {
                $this->model->table('wechat_user')->data($user_data)->where(array('unionid' => $userinfo['unionid'], 'wechat_id' => $this->wechat_id))->update();
            }
        }

        if ($type == Wechat::MSGTYPE_TEXT) {
            $keywords = $wedata['Content'];
        } elseif ($type == Wechat::MSGTYPE_EVENT) {
            if ('subscribe' == $wedata['Event']) {
                $scene_id = 0;
                // 用户扫描带参数二维码(未关注)
                if (isset($wedata['Ticket']) && !empty($wedata['Ticket'])) {
                    $scene_id = $this->weObj->getRevSceneId();
                    $flag = true;
                    // 关注
                    $this->subscribe($wedata['FromUserName'], $scene_id);
                    // 关注时回复信息
                    $this->msg_reply('subscribe');
                } else {
                    // 关注
                    $this->subscribe($wedata['FromUserName']);
                    // 关注时回复信息
                    $this->msg_reply('subscribe');
                }
            } elseif ('unsubscribe' == $wedata['Event']) {
                // 取消关注
                $this->unsubscribe($wedata['FromUserName']);
                exit();
            } elseif ('CLICK' == $wedata['Event']) {
                // 点击菜单
                $keywords = $wedata['EventKey'];
            } elseif ('VIEW' == $wedata['Event']) {
                $this->redirect($wedata['EventKey']);
            } elseif ('SCAN' == $wedata['Event']) {
                $scene_id = $this->weObj->getRevSceneId();
            } elseif ('LOCATION' == $wedata['Event']) {
                // 关注开启地理位置响应
                exit('success');
            } elseif ('MASSSENDJOBFINISH' == $wedata['Event']) {
                // 群发结果
                $data['status'] = $wedata['Status'];
                $data['totalcount'] = $wedata['TotalCount'];
                $data['filtercount'] = $wedata['FilterCount'];
                $data['sentcount'] = $wedata['SentCount'];
                $data['errorcount'] = $wedata['ErrorCount'];
                // 更新群发结果
                $this->model->table('wechat_mass_history')
                    ->data($data)
                    ->where('msg_id = "' . $wedata['MsgID'] . '"')
                    ->update();
                exit();
            } elseif ($wedata['Event'] == 'TEMPLATESENDJOBFINISH') {
                // 模板消息发送结束事件
                if ($wedata['Status'] == 'success') {
                    // 推送成功
                    $data = array('status' => 1);
                } elseif ($wedata['Status'] == 'failed:user block') {
                    // 用户拒收
                    $data = array('status' => 2);
                } else {
                    // 发送失败
                    $data = array('status' => 0); // status 0 发送失败，1 发送与接收成功，2 用户拒收
                }
                // 更新模板消息发送状态
                $this->model->table('wechat_template_log')->data($data)->where(array('msgid' => $wedata['MsgID'], 'openid' => $wedata['FromUserName']))->update();
                exit();
            }
        } else {
            $this->msg_reply('msg');
            exit();
        }
        //扫描二维码
        if (!empty($scene_id)) {
            $qrcode_fun = $this->model->table('wechat_qrcode')->field('function')->where('scene_id = "'.$scene_id.'"')->getOne();
            //扫码引荐
            if (!empty($qrcode_fun) && isset($flag)) {
                //增加扫描量
                $this->model->table('wechat_qrcode')->data('scan_num = scan_num + 1')->where('scene_id = "'.$scene_id.'"')->update();
            }
            $keywords = $qrcode_fun;
        }
        // 回复
        if (!empty($keywords)) {
            $keywords = html_in($keywords);
            //记录用户操作信息
            $this->record_msg($wedata['FromUserName'], $keywords);
            // 多客服
            $rs = $this->customer_service($wedata['FromUserName'], $keywords);
            if (empty($rs) && $keywords != 'kefu') {
                // 功能插件
                $rs1 = $this->get_function($wedata['FromUserName'], $keywords);
                if (empty($rs1)) {
                    // 关键词回复
                    $rs2 = $this->keywords_reply($keywords);
                    if (empty($rs2)) {
                        // 消息自动回复
                        $this->msg_reply('msg');
                        //推荐商品
                      // $rs_rec = $this->recommend_goods($wedata['FromUserName'], $keywords);
                    }
                }
            }
        }
    }

    /**
     * 关注处理
     *
     * @param array $info
     */
    private function subscribe($openid = '', $scene_id = 0)
    {
        if (empty($openid)) {
            exit('null');
        }

        // 获取微信用户信息
        $info = $this->weObj->getUserInfo($openid);
        if (empty($info)) {
            $this->weObj->resetAuth();
            exit('null');
        } else {
            $data = array(
                'wechat_id' => $this->wechat_id,
                'subscribe' => $info['subscribe'],
                'openid' => $info['openid'],
                'nickname' => $info['nickname'],
                'sex' => $info['sex'],
                'language' => $info['language'],
                'city' => $info['city'],
                'country' => $info['country'],
                'province' => $info['province'],
                'headimgurl' => $info['headimgurl'],
                'subscribe_time' => $info['subscribe_time'],
                'remark' => $info['remark'],
                'group_id' => isset($info['groupid']) ? $info['groupid'] : $this->weObj->getUserGroup($openid),
                'unionid' => isset($info['unionid']) ? $info['unionid'] : '',
            );
        }
        // 公众号启用微信开发者平台，检查unionid
        if (empty($data['unionid'])) {
            // exit('关注失败，请联系管理员开通微信开放平台');
            exit('null');
        }
        // 已关注用户基本信息
        update_wechat_unionid($info, $this->wechat_id); //兼容更新平台粉丝unionid
        
        $sql = "SELECT uid, ect_uid, openid, unionid FROM " .$this->model->pre."wechat_user where unionid = '".$data['unionid']."' and wechat_id = ".$this->wechat_id;
        $result = $this->model->getRow($sql);

        // 未关注
        if (empty($result)) {
            //开启自动登录
            $res = get_auto_login();
            if($res != 1){
                // 兼容原touch_user_info表
                $aite_id = 'wechat_' . $data['unionid'];
                $old_userinfo = model('Users')->get_one_user($aite_id);
                if (!empty($old_userinfo)) {
                    // 同步社会化登录表
                    $res = array(
                        'user_id' => $old_userinfo['user_id'],
                        'unionid' => $data['unionid'],
                        'nickname' => $data['nickname'],
                        );
                    model('Users')->update_connnect_user($res, 'wechat');
                    // 删除旧表信息
                    $where['user_id'] = $old_userinfo['user_id'];
                    $this->model->table('touch_user_info')->where($where)->delete();
                }

                // 其他平台(PC,APP)是否注册
                $userinfo = model('Users')->get_connect_user($data['unionid']);

                // 是否绑定注册
                if (empty($userinfo)) {
                    // 设置的用户注册信息
                    $username = model('Users')->get_wechat_username($data['unionid'], 'weixin');
                    $password = mt_rand(100000, 999999);
                    // 通知模版
                    $template = '默认用户名：' . $username . "\r\n" . '默认密码：' . $password;
                    $email = $username . '@qq.com';
                    // 查询推荐人ID
                    if (!empty($scene_id)) {
                        $scene_user_id = $this->model->table("users")->field('user_id')->where(array('user_id'=>$scene_id))->getOne();
                    }
                    $scene_user_id = empty($scene_user_id) ? 0 : $scene_user_id;
                    // 用户注册
                    $extend = array(
                        'parent_id' => $scene_user_id,
                        'sex' => $data['sex'],
                    );
                    if (model('Users')->register($username, $password, $email, $extend) !== false) {
                        // 同步社会化登录用户信息表
                        $res = array(
                            'user_id' => $_SESSION['user_id'],
                            'unionid' => $data['unionid'],
                            'nickname' => $data['nickname'],
                            );
                        model('Users')->update_connnect_user($res, 'wechat');
                        model('Users')->update_user_info();
                    } else {
                        exit('null');
                    }
                    // 注册微信资料
                    $data['ect_uid'] = $_SESSION['user_id'];
                }

                // 新增微信粉丝
                $this->model->table('wechat_user')->data($data)->insert();
                // 新用户送红包
                $bonus_msg = $this->send_message($openid, 'bonus', $this->weObj, 1);
                if (!empty($bonus_msg)) {
                    $template = !$template ?  $template . "\r\n" . $bonus_msg['content'] : $bonus_msg['content'];
                }
                // 微信端发送消息
                if (!empty($template)) {
                    $msg = array(
                        'touser' => $openid,
                        'msgtype' => 'text',
                        'text' => array(
                            'content' => $template
                        )
                    );
                    $this->weObj->sendCustomMessage($msg);
                    //记录用户操作信息
                    $this->record_msg($openid, $template, 1);
                }
            }else{
                model('Users')->add_wechat_user($info, $this->wechat_id);
            }
            
        } else {
            $template = $data['nickname'] .  '，欢迎您再次回来';
            // 更新微信用户资料
            $this->model->table('wechat_user')->data($data)->where($condition)->update();

            // 先授权登录后再关注送红包
            if($result['ect_uid'] > 0){
                $bonus_num = $this->model->table('user_bonus')->where('user_id = "'.$result['ect_uid'].'"')->count();
                if ($bonus_num <= 0) {
                    $bonus_msg = $this->send_message($openid, 'bonus', $this->weObj, 1);
                    if (! empty($bonus_msg)) {
                        $template = $template . "\r\n" . $bonus_msg['content'];
                    }
                }
            }
            
            // 微信端发送消息
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $template
                )
            );
            $this->weObj->sendCustomMessage($msg);
        }
    }

    /**
     * 取消关注
     *
     * @param string $openid
     */
    public function unsubscribe($openid = '')
    {
        // 未关注
        $where['openid'] = $openid;
        $where['wechat_id'] = $this->wechat_id;
        $rs = $this->model->table('wechat_user')->where($where)->count();
        // 修改关注状态
        if ($rs > 0) {
            $data['subscribe'] = 0;
            $this->model->table('wechat_user')->data($data)->where($where)->update();
        }
    }

    /**
     * 被动关注，消息回复
     *
     * @param string $type
     * @param string $return
     */
    private function msg_reply($type, $return = 0)
    {
        $replyInfo = $this->model->table('wechat_reply')
            ->field('content, media_id')
            ->where('type = "' . $type . '" and wechat_id = ' . $this->wechat_id)
            ->find();
        if (!empty($replyInfo)) {
            if (!empty($replyInfo['media_id'])) {
                $replyInfo['media'] = $this->model->table('wechat_media')
                    ->field('title, content, file, type, file_name')
                    ->where('id = ' . $replyInfo['media_id'])
                    ->find();
                if ($replyInfo['media']['type'] == 'news') {
                    $replyInfo['media']['type'] = 'image';
                }

                // 上传多媒体文件
                $filename = ROOT_PATH . $replyInfo['media']['file'];
                $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $replyInfo['media']['type']);
                if (empty($rs)) {
                    logResult($this->weObj->errMsg);
                }

                // 回复数据重组
                if ($rs['type'] == 'image' || $rs['type'] == 'voice') {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                } elseif ('video' == $rs['type']) {
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                }
                $this->weObj->reply($replyData);
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
            } else {
                // 文本回复
                $replyInfo['content'] = html_out($replyInfo['content']);
                if ($replyInfo['content']) {
                    $this->weObj->text($replyInfo['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($this->weObj->getRev()->getRevTo(), $replyInfo['content'], 1);
                }
            }
        }
    }

    /**
     * 关键词回复
     *
     * @param string $keywords
     * @return boolean
     */
    private function keywords_reply($keywords)
    {
        $endrs = false;
        $sql = 'SELECT r.content, r.media_id, r.reply_type FROM ' . $this->model->pre . 'wechat_reply r LEFT JOIN ' . $this->model->pre . 'wechat_rule_keywords k ON r.id = k.rid WHERE k.rule_keywords = "' . $keywords . '" and r.wechat_id = ' . $this->wechat_id . ' order by r.add_time desc LIMIT 1';
        $result = $this->model->query($sql);
        if (!empty($result)) {
            // 素材回复
            if (!empty($result[0]['media_id'])) {
                $mediaInfo = $this->model->table('wechat_media')
                    ->field('id, title, content, digest, file, type, file_name, article_id, link')
                    ->where('id = ' . $result[0]['media_id'])
                    ->find();

                // 回复数据重组
                if ($result[0]['reply_type'] == 'image' || $result[0]['reply_type'] == 'voice') {
                    // 上传多媒体文件
                    $filename = ROOT_PATH . $mediaInfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $result[0]['reply_type']);
                    if (empty($rs)) {
                        logResult($this->weObj->errMsg);
                    }
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id']
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('video' == $result[0]['reply_type']) {
                    // 上传多媒体文件
                    $filename = ROOT_PATH . $mediaInfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), $result[0]['reply_type']);
                    if (empty($rs)) {
                        logResult($this->weObj->errMsg);
                    }
                    $replyData = array(
                        'ToUserName' => $this->weObj->getRev()->getRevFrom(),
                        'FromUserName' => $this->weObj->getRev()->getRevTo(),
                        'CreateTime' => time(),
                        'MsgType' => $rs['type'],
                        ucfirst($rs['type']) => array(
                            'MediaId' => $rs['media_id'],
                            'Title' => $replyInfo['media']['title'],
                            'Description' => strip_tags($replyInfo['media']['content'])
                        )
                    );
                    // 回复
                    $this->weObj->reply($replyData);
                    $endrs = true;
                } elseif ('news' == $result[0]['reply_type']) {
                    // 图文素材
                    $articles = array();
                    if (!empty($mediaInfo['article_id'])) {
                        $artids = explode(',', $mediaInfo['article_id']);
                        foreach ($artids as $key => $val) {
                            $artinfo = $this->model->table('wechat_media')
                                ->field('id, title, digest, file, content, link')
                                ->where('id = ' . $val)
                                ->find();
                            $artinfo['content'] = sub_str(strip_tags(html_out($artinfo['content'])), 100);
                            $articles[$key]['Title'] = $artinfo['title'];
                            $articles[$key]['Description'] = empty($artinfo['digest']) ? $artinfo['content'] : $artinfo['digest'];
                            $articles[$key]['PicUrl'] = __URL__ . '/' . $artinfo['file'];
                            $articles[$key]['Url'] = empty($artinfo['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$artinfo['id'])) : strip_tags(html_out($artinfo['link']));
                        }
                    } else {
                        $articles[0]['Title'] = $mediaInfo['title'];
                        $articles[0]['Description'] = empty($mediaInfo['digest']) ? sub_str(strip_tags(html_out($mediaInfo['content'])), 100) : $mediaInfo['digest'];
                        $articles[0]['PicUrl'] = __URL__ . '/' . $mediaInfo['file'];
                        $articles[0]['Url'] = empty($mediaInfo['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$mediaInfo['id'])) : strip_tags(html_out($mediaInfo['link']));
                    }
                    // 回复
                    $this->weObj->news($articles)->reply();
                    //记录用户操作信息
                    $this->record_msg($this->weObj->getRev()->getRevTo(), '图文信息', 1);
                    $endrs = true;
                }
            } else {
                // 文本回复
                $result[0]['content'] = html_out($result[0]['content']);
                $this->weObj->text($result[0]['content'])->reply();
                //记录用户操作信息
                $this->record_msg($this->weObj->getRev()->getRevTo(), $result[0]['content'], 1);
                $endrs = true;
            }
        }
        return $endrs;
    }

    /**
     * 功能变量查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function get_function($fromusername, $keywords)
    {
        $return = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, command, config')
            ->where('(keywords like "%' . $keywords . '%" or command like "%' . $keywords . '%") and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();
        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once($file);
            $wechat = new $rs['command']();
            $data = $wechat->show($fromusername, $rs);
            if (! empty($data)) {
                // 数据回复类型
                if ($data['type'] == 'text') {
                    $this->weObj->text($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, $data['content'], 1);
                } elseif ($data['type'] == 'news') {
                    $this->weObj->news($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图文消息', 1);
                } elseif ($data['type'] == 'image') {
                    // 上传多媒体文件
                    $filename = dirname(ROOT_PATH) . '/' . $data['path'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), 'image');
                    if (empty($rs)) {
                        logResult($this->weObj->errMsg);
                    }
                    $this->weObj->image($rs['media_id'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图片', 1);
                }
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 商品推荐查询
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @return boolean
     */
    public function recommend_goods($fromusername, $keywords)
    {
        $return = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, keywords, command, config')
            ->where('command = "recommend" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();

        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once($file);
            $wechat = new $rs['command']();
            $rs['user_keywords'] = $keywords;
            $data = $wechat->show($fromusername, $rs);
            if (!empty($data)) {
                // 数据回复类型
                if ($data['type'] == 'text') {
                    $this->weObj->text($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, $data['content'], 1);
                } elseif ($data['type'] == 'news') {
                    $this->weObj->news($data['content'])->reply();
                    //记录用户操作信息
                    $this->record_msg($fromusername, '图文消息', 1);
                }
                $return = true;
            }
        }
        return $return;
    }

    /**
     * 主动发送信息
     *
     * @param unknown $tousername
     * @param unknown $fromusername
     * @param unknown $keywords
     * @param unknown $weObj
     * @param unknown $return
     * @return boolean
     */
    public function send_message($fromusername, $keywords, $weObj, $return = 0)
    {
        $result = false;
        $rs = $this->model->table('wechat_extend')
            ->field('name, command, config')
            ->where('keywords like "%' . $keywords . '%" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->find();
        $file = ROOT_PATH . 'plugins/wechat/' . $rs['command'] . '/' . $rs['command'] . '.class.php';
        if (file_exists($file)) {
            require_once($file);
            $wechat = new $rs['command']();
            $data = $wechat->show($fromusername, $rs);
            if (!empty($data)) {
                if ($return) {
                    $result = $data;
                } else {
                    $weObj->sendCustomMessage($data['content']);
                    $result = true;
                }
            }
        }
        return $result;
    }

    /**
     * 多客服
     *
     * @param unknown $fromusername
     * @param unknown $keywords
     */
    public function customer_service($fromusername, $keywords)
    {
        /*$kfevent = $this->weObj->getRevKFClose();
        logResult(var_export($kfevent, true));*/
        $result = false;
        //是否超时
        $timeout = false;
        //查找用户
        $uid = $this->model->table('wechat_user')->field('uid')->where(array('openid'=>$fromusername))->getOne();
        if ($uid) {
            $time_list = $this->model->table('wechat_custom_message')->field('send_time')->where(array('uid'=>$uid))->order('send_time desc')->limit(2)->select();
            if ($time_list[0]['send_time'] - $time_list[1]['send_time'] > 3600 * 2) {
                $timeout = true;
            }
        }

        // 是否处在多客服流程
        $kefu = $this->model->table('wechat_user')
            ->field('openid')
            ->where('openid = "' . $fromusername . '"')
            ->getOne();
        if ($kefu && $keywords == 'kefu') {
            $rs = $this->model->table('wechat_extend')
                ->field('config')
                ->where('command = "kefu" and enable = 1 and wechat_id = ' . $this->wechat_id)
                ->getOne();
            if (!empty($rs)) {
                $config = unserialize($rs);
                $msg = array(
                    'touser' => $fromusername,
                    'msgtype' => 'text',
                    'text' => array(
                        'content' => '欢迎进入多客服系统'
                    )
                );
                $this->weObj->sendCustomMessage($msg);
                //记录用户操作信息
                $this->record_msg($fromusername, $msg['text']['content'], 1);
                // 在线客服列表
                $online_list = $this->weObj->getCustomServiceOnlineKFlist();
                if ($online_list['kf_online_list']) {
                    foreach ($online_list['kf_online_list'] as $key => $val) {
                        if ($config['customer'] == $val['kf_account'] && $val['status'] > 0 && $val['accepted_case'] < $val['auto_accept']) {
                            $customer = $config['customer'];
                        } else {
                            $customer = '';
                        }
                    }
                }
                // 转发客服消息
                $this->weObj->transfer_customer_service($customer)->reply();
                $result = true;
            }
        }

        return $result;
    }

    /**
     * 获取用户昵称，头像
     *
     * @param unknown $user_id
     * @return multitype:
     */
    public static function get_avatar($user_id)
    {
        $u_row = model('Base')->model->table('wechat_user')
            ->field('nickname, headimgurl')
            ->where('ect_uid = ' . $user_id)
            ->find();
        if (empty($u_row)) {
            $u_row = array();
        }
        return $u_row;
    }

    public static function snsapi_base()
    {
        $wxinfo = model('Base')->model->table('wechat')->field('token, appid, appsecret, status, oauth_redirecturi')->find();
        if (!empty($wxinfo['appid']) && is_wechat_browser() && ($_SESSION['user_id'] === 0 || empty($_SESSION['unionid']))) {
            if ($wxinfo['status']) {
                self::snsapi_userinfo();
            } else {
                $config = model('Base')->model->table('wechat')->field('token, appid, appsecret, status')->find();
                if ($config['status']) {
                    $obj = new Wechat($config);
                    // 用code换token
                    if (isset($_GET['code']) && $_GET['state'] == 'repeat') {
                        $token = $obj->getOauthAccessToken();
                        $_SESSION['openid'] = $token['openid'];
                    }
                    // 生成请求链接
                    if (!empty($wxinfo['oauth_redirecturi'])) {
                        $callback = rtrim($wxinfo['oauth_redirecturi'], '/')  .'/'. $_SERVER['REQUEST_URI'];
                    }
                    if (!isset($callback)) {
                        $callback = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
                        ;
                    }
                    $obj->getOauthRedirect($callback, 'repeat', 'snsapi_base');
                }
            }
        }
    }

    /**
     * 跳转到第三方登录
     */
    public static function snsapi_userinfo()
    {
        if (is_wechat_browser() && ($_SESSION['user_id'] === 0 || empty($_SESSION['unionid'])) && strtolower(CONTROLLER_NAME) != 'oauth') {
            $wxinfo   = model('Base')->model->table('wechat')->field('token, appid, appsecret, status, oauth_redirecturi')->find();
            if (!$wxinfo['status']) {
                return false;
            }
            if (!empty($wxinfo['oauth_redirecturi'])) {
                $callback = rtrim($wxinfo['oauth_redirecturi'], '/')  .'/'. $_SERVER['REQUEST_URI'];
            }
            if (! isset($_SESSION['redirect_url'])) {
                $callback = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
                ;
            }
            $url = url('oauth/index', array('type' => 'weixin', 'back_url' => urlencode($callback)), 'org_mode');
            header("Location: ".$url);
            exit;
        }
    }

    /**
     * 记录用户操作信息
     */
    public function record_msg($fromusername, $keywords, $iswechat = 0)
    {
        $uid = $this->model->table('wechat_user')->field('uid')->where(array('openid'=>$fromusername))->getOne();
        if ($uid) {
            $data['uid'] = $uid;
            $data['msg'] = $keywords;
            $data['send_time'] = gmtime();
            //是公众号回复
            if ($iswechat) {
                $data['iswechat'] = 1;
            }
            $this->model->table('wechat_custom_message')
                ->data($data)
                ->insert();
        }
    }

    /**
     * 检查是否是微信浏览器访问
     */
    public static function is_wechat_browser()
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($user_agent, 'MicroMessenger') === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 插件页面显示方法
     *
     * @param string $plugin
     */
    public function plugin_show()
    {
        if (is_wechat_browser() && (!isset($_SESSION['unionid']) || empty($_SESSION['unionid']) || empty($_SESSION['openid']))) {
            $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect('oauth/index', array('type' => 'weixin', 'back_url' => urlencode($back_url)));
        }
        $plugin = I('get.name');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once($file);
            $wechat = new $plugin();
            $wechat->html_show();
        }
    }

    /**
     * 插件处理方法
     *
     * @param string $plugin
     */
    public function plugin_action()
    {
        $plugin = I('get.name', '', 'trim');
        $file = ADDONS_PATH . 'wechat/' . $plugin . '/' . $plugin . '.class.php';
        if (file_exists($file)) {
            include_once($file);
            $wechat = new $plugin();
            $wechat->action();
        }
    }

    /**
     * 获取公众号配置
     *
     * @param string $orgid
     * @return array
     */
    private function get_config($orgid = '')
    {
        $config = $this->model->table('wechat')
            ->field('id, token, appid, appsecret')
            ->where('orgid = "' . $orgid . '" and status = 1')
            ->find();
        if (empty($config)) {
            $config = array();
        }
        return $config;
    }

    /**
     * 获取access_token的接口
     * @return [type] [description]
     */
    public function check_auth()
    {
        $appid = I('get.appid');
        $appsecret = I('get.appsecret');
        if (empty($appid) || empty($appsecret)) {
            echo json_encode(array('errmsg' => '信息不完整，请提供完整信息', 'errcode' => 1));
            exit;
        }
        $config = $this->model->table('wechat')
            ->field('token, appid, appsecret')
            ->where('appid = "' . $appid . '" and appsecret = "'.$appsecret.'" and status = 1')
            ->find();
        if (empty($config)) {
            echo json_encode(array('errmsg' => '信息错误，请检查提供的信息', 'errcode' => 1));
            exit;
        }
        $obj = new Wechat($config);
        $access_token = $obj->checkAuth();
        if ($access_token) {
            echo json_encode(array('access_token' => $access_token, 'errcode' => 0));
            exit;
        } else {
            echo json_encode(array('errmsg' => $obj->errmsg, 'errcode' => $obj->errcode));
            exit;
        }
    }

    /**
    * 推荐分成二维码
    * @param  string  $user_name [description]
    * @param  integer $user_id   [description]
    * @param  integer $time      [description]
    * @param  string  $fun       [description]
    * @return [type]             [description]
    */
    public static function rec_qrcode($user_name = '', $user_id = 0, $expire_seconds = 0, $fun = '', $force = false)
    {
        if (empty($user_id)) {
            return false;
        }
        // 默认公众号信息
        $wxinfo = model('Base')->model->table('wechat')->field('id, token, appid, appsecret, oauth_redirecturi, type, oauth_status')->where('default_wx = 1 and status = 1')->find();

        if (! empty($wxinfo) && $wxinfo['type'] == 2) {
            $config['token'] = $wxinfo['token'];
            $config['appid'] = $wxinfo['appid'];
            $config['appsecret'] = $wxinfo['appsecret'];
            // 微信通验证
            $weObj = new Wechat($config);
            if ($force) {
                $weObj->clearCache();
                model('Base')->model->table('wechat_qrcode')->where(array('scene_id'=>$user_id, 'wechat_id'=>$wxinfo['id']))->delete();
            }

            $qrcode = model('Base')->model->table('wechat_qrcode')->field('id, scene_id, type, expire_seconds, qrcode_url')->where(array('scene_id'=>$user_id, 'wechat_id'=>$wxinfo['id']))->find();
            if ($qrcode['id'] && !empty($qrcode['qrcode_url'])) {
                return $qrcode['qrcode_url'];
            } elseif ($qrcode['id'] && empty($qrcode['qrcode_url'])) {
                $ticket = $weObj->getQRCode((int)$qrcode['scene_id'], $qrcode['type'], $qrcode['expire_seconds']);
                if (empty($ticket)) {
                    $weObj->resetAuth();
                    //$weObj->errCode, $weObj->errMsg
                    return false;
                }
                $data['ticket'] = $ticket['ticket'];
                $data['expire_seconds'] = $ticket['expire_seconds'];
                $data['endtime'] = time() + $ticket['expire_seconds'];
                // 二维码地址
                $data['qrcode_url'] = $weObj->getQRUrl($ticket['ticket']);
                M()->table('wechat_qrcode')->data($data)->where(array('id'=>$qrcode['id']))->update();
                return $data['qrcode_url'];
            } else {
                $data['function'] = $fun;
                $data['scene_id'] = $user_id;
                $data['username'] = $user_name;
                $data['type'] = empty($expire_seconds) ? 1 : 0;
                $data['wechat_id'] = $wxinfo['id'];
                $data['status'] = 1;
                //生成二维码
                $ticket = $weObj->getQRCode((int)$data['scene_id'], $data['type'], $expire_seconds);
                if (empty($ticket)) {
                    $weObj->resetAuth();
                    //$weObj->errCode, $weObj->errMsg
                    return false;
                }
                $data['ticket'] = $ticket['ticket'];
                $data['expire_seconds'] = $ticket['expire_seconds'];
                $data['endtime'] = time() + $ticket['expire_seconds'];
                // 二维码地址
                $data['qrcode_url'] = $weObj->getQRUrl($ticket['ticket']);

                M()->table('wechat_qrcode')->data($data)->insert();
                return $data['qrcode_url'];
            }
        }
        return false;
    }
}
