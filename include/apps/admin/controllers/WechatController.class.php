<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：WechatController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台管理
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
class WechatController extends AdminController
{
    protected $weObj = '';

    protected $wechat_id = 0;

    protected $page_num = 0;

    public function __construct()
    {
        parent::__construct();
        $this->assign('ur_here', L('wechat'));
        $this->assign('action', ACTION_NAME);
        // 默认微信公众号
        $this->wechat_id = 1;
        // 查找公众号
        $mpInfo = $this->model->table('wechat')->where('id = '. $this->wechat_id)->find();
        if (empty($mpInfo)) {
            $data = array(
                'id' => $this->wechat_id,
                'time' => gmtime(),
                'type' => 2,
                'status' => 1,
                'default_wx' => 1
            );
            $this->model->table('wechat')->data($data)->insert();
            $this->redirect(url('modify'));
        }
        // 获取配置信息
        $this->get_config();
        // 初始化 每页分页数量
        $this->page_num = 10;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 我的公众号
     */
    public function index()
    {
        $this->redirect(url('modify'));
        /*
        $list = $this->model->table('wechat')
            ->order('sort asc, id asc')
            ->select();
        if($list){
            foreach($list as $key=>$val){
                if($val['type'] == 2){
                    $list[$key]['manage_url'] = url('mass_message', array('wechat_id'=>$val['id']));
                }
                else{
                    $list[$key]['manage_url'] = url('reply_subscribe', array('wechat_id'=>$val['id']));
                }
            }
        }
        $l = sprintf(L('wechat_register'), '<a href=' . url('append') . '>');

        $this->assign('wechat_register', $l);
        $this->assign('list', $list);
        $this->display();
        */
    }

    /**
     * 设置公众号为默认
     */
    /*
    public function set_default()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message('请选择公众号', NULL, 'error');
        }
        // 取消默认
        $data['default_wx'] = 0;
        $this->model->table('wechat')
            ->data($data)
            ->where('1')
            ->update();
        // 设置默认
        $data1['default_wx'] = 1;
        $this->model->table('wechat')
            ->data($data1)
            ->where('id = ' . $id)
            ->update();

        $this->redirect(url('index'));
    }
    */

    /**
     * 新增公众号
     */
    public function append()
    {
        $this->redirect(url('index'));
        /*
        if (IS_POST) {
            $data = I('post.data', '', 'trim,htmlspecialchars');
            $data['time'] = gmtime();
            // 验证数据
            $result = Check::rule(array(
                Check::must($data['name']),
                L('must_name')
            ), array(
                Check::must($data['orgid']),
                L('must_id')
            ), array(
                Check::must($data['token']),
                L('must_token')
            ));
            if ($result !== true) {
                $this->message($result, NULL, 'error');
            }
            // 更新数据
            $this->model->table('wechat')
                ->data($data)
                ->insert();
            $this->redirect(url('wechat/index'));
        }
        $this->display();
        */
    }

    /**
     * 修改公众号
     */
    public function modify()
    {
        $condition['id'] = $this->wechat_id;
        if (IS_POST) {
            $data = I('post.data', '', 'trim,htmlspecialchars');
            // 验证数据
            $result = Check::rule(array(
                Check::must($data['name']),
                L('must_name')
            ), array(
                Check::must($data['orgid']),
                L('must_id')
            ), array(
                Check::must($data['token']),
                L('must_token')
            ));
            if ($result !== true) {
                $this->message($result, null, 'error');
            }
            // 更新数据
            $this->model->table('wechat')
                ->data($data)
                ->where($condition)
                ->update();
            $this->message(L('wechat_editor') . L('success'), U('modify'));
        }

        $data = $this->model->table('wechat')->where($condition)->find();
        $data['oauth_redirecturi'] = !empty($data['oauth_redirecturi']) ? $data['oauth_redirecturi'] :  __URL__;

        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 删除公众号
     */
    /*
    public function delete()
    {
        $condition['id'] = intval($_GET['id']);
        $this->model->table('wechat')
            ->where($condition)
            ->delete();
        $this->redirect(url('wechat/index'));
    }
    */

    /**
     * 公众号菜单
     */
    public function menu_list()
    {
        $where1['wechat_id'] = $this->wechat_id;
        $list = $this->model->table('wechat_menu')
            ->where($where1)
            ->order('sort asc')
            ->select();
        $result = array();
        if (is_array($list)) {
            foreach ($list as $vo) {
                if ($vo['pid'] == 0) {
                    $vo['val'] = ($vo['type'] == 'click') ? $vo['key'] : $vo['url'];
                    $sub_button = array();
                    foreach ($list as $val) {
                        $val['val'] = ($val['type'] == 'click') ? $val['key'] : $val['url'];
                        if ($val['pid'] == $vo['id']) {
                            $sub_button[] = $val;
                        }
                    }
                    $vo['sub_button'] = $sub_button;
                    $result[] = $vo;
                }
            }
        }
        $this->assign('list', $result);
        $this->display();
    }

    /**
     * 编辑菜单
     */
    public function menu_edit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            if ('click' == $data['type']) {
                if (empty($data['key'])) {
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => L('menu_keyword') . L('empty')
                    )));
                }
                $data['url'] = '';
            } else {
                if (empty($data['url'])) {
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => L('menu_url') . L('empty')
                    )));
                }
                $data['key'] = '';
            }
            // 编辑
            if (! empty($id)) {
                $this->model->table('wechat_menu')
                    ->data($data)
                    ->where('id = ' . $id)
                    ->update();
            } else {
                // 添加
                $this->model->table('wechat_menu')
                    ->data($data)
                    ->insert();
            }

            exit(json_encode(array(
                'status' => 1,
                'msg' => L('attradd_succed')
            )));
        }
        $id = I('get.id');
        $info = array();
        // 顶级菜单
        $top_menu = $this->model->table('wechat_menu')
            ->where('pid = 0 and wechat_id = ' . $this->wechat_id)
            ->select();
        if (! empty($id)) {
            $info = $this->model->table('wechat_menu')
                ->where('id = ' . $id)
                ->find();
            // 顶级菜单
            $top_menu = $this->model->table('wechat_menu')
                ->where('id <> '.$id.' and pid = 0 and wechat_id = ' . $this->wechat_id)
                ->select();
        }

        $this->assign('top_menu', $top_menu);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 删除菜单
     */
    public function menu_del()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message(L('menu_select_del'), null, 'error');
        }
        $minfo = $this->model->table('wechat_menu')
            ->field('id, pid')
            ->where('id = ' . $id)
            ->find();
        // 顶级栏目
        if ($minfo['pid'] == 0) {
            $this->model->table('wechat_menu')
                ->where('pid = ' . $minfo['id'])
                ->delete();
        }
        $this->model->table('wechat_menu')
            ->where('id = ' . $minfo['id'])
            ->delete();
        $this->message(L('drop') . L('success'), url('menu_list'));
    }

    /**
     * 生成自定义菜单
     */
    public function sys_menu()
    {
        $list = $this->model->table('wechat_menu')
            ->where('status = 1 and wechat_id = ' . $this->wechat_id)
            ->order('sort asc')
            ->select();
        if (empty($list)) {
            $this->message('请至少添加一个自定义菜单', null, 'error');
        }
        $data = array();
        if (is_array($list)) {
            foreach ($list as $val) {
                if ($val['pid'] == 0) {
                    $sub_button = array();
                    foreach ($list as $v) {
                        if ($v['pid'] == $val['id']) {
                            $sub_button[] = $v;
                        }
                    }
                    $val['sub_button'] = $sub_button;
                    $data[] = $val;
                }
            }
        }
        $menu_list = array();
        foreach ($data as $key => $val) {
            if (empty($val['sub_button'])) {
                $menu_list['button'][$key]['type'] = $val['type'];
                $menu_list['button'][$key]['name'] = $val['name'];
                if ('click' == $val['type']) {
                    $menu_list['button'][$key]['key'] = $val['key'];
                } else {
                    $menu_list['button'][$key]['url'] = html_out($val['url']);
                }
            } else {
                $menu_list['button'][$key]['name'] = $val['name'];
                foreach ($val['sub_button'] as $k => $v) {
                    $menu_list['button'][$key]['sub_button'][$k]['type'] = $v['type'];
                    $menu_list['button'][$key]['sub_button'][$k]['name'] = $v['name'];
                    if ('click' == $v['type']) {
                        $menu_list['button'][$key]['sub_button'][$k]['key'] = $v['key'];
                    } else {
                        $menu_list['button'][$key]['sub_button'][$k]['url'] = html_out($v['url']);
                    }
                }
            }
        }
        /*
         * $data = array( 'button'=>array( array('type'=>'click', 'name'=>"今日歌曲", 'key'=>'MENU_KEY_MUSIC'), array('type'=>'view', 'name'=>"歌手简介", 'url'=>'http://www.qq.com/'), array('name'=>"菜单", 'sub_button'=>array(array('type'=>'click', 'name'=>'hello world', 'key'=>'MENU_KEY_MENU'))) ) );
         */

        $rs = $this->weObj->createMenu($menu_list);
        if (empty($rs)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
        }
        $this->message(L('menu_create') . L('success'), url('menu_list'));
    }

    /**
     * 关注用户列表
     */
    public function subscribe_list()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('subscribe_list', $filter), 10);
        $total = $this->model->table('wechat_user')
            ->where('wechat_id = "' . $this->wechat_id . '" and subscribe = 1')
            ->order('subscribe_time desc')
            ->count();
        $this->assign('page', $this->pageShow($total));
        $sql = 'SELECT u.*, g.name, us.user_name FROM ' . $this->model->pre . 'wechat_user u LEFT JOIN ' . $this->model->pre . 'wechat_user_group g ON u.group_id = g.group_id LEFT JOIN '.$this->model->pre.'users us ON us.user_id = u.ect_uid where u.subscribe = 1 and u.wechat_id = ' . $this->wechat_id . ' group by u.uid order by u.subscribe_time desc limit ' . $offset;
        $list = $this->model->query($sql);
        if (empty($list)) {
            $list = array();
        }
        // 分组
        $where1['wechat_id'] = $this->wechat_id;
        $group_list = $this->model->table('wechat_user_group')
            ->field('id, group_id, name, count')
            ->where($where1)
            ->order('id, sort desc')
            ->select();

        $this->assign('list', $list);
        $this->assign('group_list', $group_list);
        $this->display();
    }

    /**
     * 关注用户列表搜索
     */
    public function subscribe_search()
    {
        $keywords = I('post.keywords') ? I('post.keywords') : I('get.k');
        $group_id = I('get.group_id');
        $where = '';
        $where1 = '';
        if (! empty($keywords)) {
            $where = ' and (u.nickname like "%' . $keywords . '%" or u.province like "%' . $keywords . '%" or u.city like "%' . $keywords . '%")';
            $where1 = 'wechat_id = "' . $this->wechat_id . '" and subscribe = 1 and (nickname like "%' . $keywords . '%" or province like "%' . $keywords . '%" or city like "%' . $keywords . '%")';
        }
        if (isset($_GET['group_id']) && $group_id >= 0) {
            $where = ' and u.group_id = ' . $group_id;
            $where1 = 'wechat_id = "' . $this->wechat_id . '" and subscribe = 1 and group_id = ' . $group_id;
        }

        // 分页
        $filter['page'] = '{page}';
        $filter['group_id'] = !empty($group_id) ? $group_id : 0;
        $filter['k'] = $keywords;
        $offset = $this->pageLimit(url('subscribe_search', $filter), 10);
        $total = $this->model->table('wechat_user')
            ->where($where1)
            ->order('uid, subscribe_time desc')
            ->count();
        $this->assign('page', $this->pageShow($total));
        $sql1 = 'SELECT u.*, g.name, us.user_name FROM ' . $this->model->pre . 'wechat_user u LEFT JOIN ' . $this->model->pre . 'wechat_user_group g ON u.group_id = g.group_id LEFT JOIN '.$this->model->pre.'users us ON us.user_id = u.ect_uid where u.subscribe = 1 and u.wechat_id = ' . $this->wechat_id . $where . ' group by u.uid order by u.subscribe_time desc limit ' . $offset;
        $list = $this->model->query($sql1);

        // 分组
        $where2['wechat_id'] = $this->wechat_id;
        $group_list = $this->model->table('wechat_user_group')
            ->field('id, group_id, name, count')
            ->where($where2)
            ->order('id, sort desc')
            ->select();

        $this->assign('list', $list);
        $this->assign('group_list', $group_list);
        $this->display('wechat_subscribe_list');
    }

    /**
     * 移动关注用户分组
     */
    public function subscribe_move()
    {
        if (IS_POST) {
            if (empty($this->wechat_id)) {
                $this->message(L('wechat_empty'), null, 'error');
            }
            $group_id = I('post.group_id');
            $openid = I('post.id');
            if (is_array($openid)) {
                foreach ($openid as $v) {
                    // 微信端移动用户
                    $this->weObj->updateGroupMembers($group_id, $v);
                    // 数据处理
                    $this->model->table('wechat_user')
                        ->data('group_id = "' . $group_id . '"')
                        ->where('openid = "' . $v . '"')
                        ->update();
                }
                $this->message(L('sub_move_sucess'), url('subscribe_list'));
            } else {
                $this->message(L('select_please'), null, 'error');
            }
        }
    }

    /**
     * 更新用户信息
     */
    public function subscribe_update()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), null, 'error');
        }
        // 本地数据
        $where['wechat_id'] = $this->wechat_id;
        $local_user = $this->model->table('wechat_user')
            ->field('openid')
            ->where($where)
            ->select();
        if (empty($local_user)) {
            $local_user = array();
        }
        $user_list = array();
        foreach ($local_user as $v) {
            $user_list[] = $v['openid'];
        }
        // 微信端数据
        $wechat_user = $this->weObj->getUserList();

        if ($wechat_user['total'] <= 10000) {
            $wechat_user_list = $wechat_user['data']['openid'];
        } else {
            $num = ceil($wechat_user['total'] / 10000);
            $wechat_user_list = $wechat_user['data']['openid'];
            for ($i = 0; $i <= $num; $i ++) {
                $wechat_user1 = $this->weObj->getUserList($wechat_user['next_openid']);
                $wechat_user_list = array_merge($wechat_user_list, $wechat_user1['data']['openid']);
            }
        }
        // 数据对比
        foreach ($local_user as $val) {
            // 数据在微信端存在
            if (in_array($val['openid'], $wechat_user_list)) {
                $info = $this->weObj->getUserInfo($val['openid']);
                $info['group_id'] = $info['groupid'];
                $where1['openid'] = $val['openid'];
                unset($info['groupid']);
                unset($info['tagid_list']);
                $this->model->table('wechat_user')
                    ->data($info)
                    ->where($where1)
                    ->update();
            } else {
                $where2['openid'] = $val['openid'];
                $data['subscribe'] = 0;
                $this->model->table('wechat_user')
                    ->data($data)
                    ->where($where2)
                    ->update();
            }
        }
        // 数据不存在
        // foreach ($wechat_user_list as $vs) {
        //     if (! in_array($vs, $user_list)) {
        //         $info = $this->weObj->getUserInfo($vs);
        //         $info['group_id'] = $info['groupid'];
        //         $info['wechat_id'] = $this->wechat_id;
        //         unset($info['groupid']);
        //         unset($info['tagid_list']);
        //         $this->model->table('wechat_user')
        //             ->data($info)
        //             ->insert();
        //     }
        // }

        // $this->redirect(url('subscribe_list'));
    }

    /**
     * 发送客服消息
     */
    public function send_custom_message()
    {
        if (IS_POST) {
            $data = I('post.data');
            $openid = I('post.openid');
            $rs = Check::rule(array(
                Check::must($openid),
                L('select_openid')
            ), array(
                Check::must($data['msg']),
                L('message_content') . L('empty')
            ));
            if ($rs !== true) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => $rs
                )));
            }
            $data['send_time'] = gmtime();
            $data['iswechat'] = 1;
            $data['msg'] = strip_tags(html_out($data['msg']));
            // 微信端发送消息
            $msg = array(
                'touser' => $openid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $data['msg']
                )
            );
            $rs = $this->weObj->sendCustomMessage($msg);
            if (empty($rs)) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg
                )));
            }
            // 添加数据
            $this->model->table('wechat_custom_message')
                ->data($data)
                ->insert();

            exit(json_encode(array(
                'status' => 1
            )));
        }
        $uid = I('get.uid');
        $openid = I('get.openid');
        $openid = $openid ? $where['openid'] = $openid : $where['uid'] = $uid;
        $info = $this->model->table('wechat_user')
            ->field('uid, nickname, openid')
            ->where($where)
            ->find();

        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 客服消息列表
     */
    public function custom_message_list()
    {
        $uid = I('get.uid');
        if (empty($uid)) {
            $this->message(L('select_openid'), null, 'error');
        }
        $nickname = $this->model->table('wechat_user')
            ->field('nickname')
            ->where('uid = ' . $uid)
            ->getOne();
        // 分页
        $filter['page'] = '{page}';
        $filter['uid'] = $uid;
        $offset = $this->pageLimit(url('custom_message_list', $filter), 20);
        $total = $this->model->table('wechat_custom_message')
            ->where('uid = ' . $uid)
            ->order('send_time desc')
            ->count();
        $list = $this->model->table('wechat_custom_message')
            ->field('msg, send_time, iswechat')
            ->where('uid = ' . $uid)
            ->order('send_time desc, id desc')->limit($offset)
            ->select();
        foreach ($list as $key => $value) {
            $list[$key]['send_time'] = local_date('Y-m-d H:i:s', $value['send_time']);
        }
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('nickname', $nickname);
        $this->display();
    }

    /**
     * 分组管理
     */
    public function groups_list()
    {
        $where['wechat_id'] = $this->wechat_id;
        $local_list = $this->model->table('wechat_user_group')
            ->where($where)
            ->order('id, sort desc')
            ->select();

        $this->assign('list', $local_list);
        $this->display();
    }

    /**
     * 同步分组
     */
    public function sys_groups()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), null, 'error');
        }
        // 微信端分组列表
        $list = $this->weObj->getGroup();
        if (empty($list)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
        }
        // 本地分组
        $where['wechat_id'] = $this->wechat_id;
        $this->model->table('wechat_user_group')
            ->where($where)
            ->delete();
        foreach ($list['groups'] as $key => $val) {
            $data['wechat_id'] = $this->wechat_id;
            $data['group_id'] = $val['id'];
            $data['name'] = $val['name'];
            $data['count'] = $val['count'];
            $this->model->table('wechat_user_group')
                ->data($data)
                ->insert();
        }
        // 更新所有关注用户信息
        $this->subscribe_update();

        $this->redirect(url('subscribe_list'));
    }

    /**
     * 添加、编辑分组
     */
    public function groups_edit()
    {
        if (empty($this->wechat_id)) {
            $this->message(L('wechat_empty'), null, 'error');
        }
        if (IS_POST) {
            $name = I('post.name');
            $id = I('post.id', 0, 'intval');
            $group_id = I('post.group_id');
            if (empty($name)) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('group_name') . L('empty')
                )));
            }
            $data['name'] = $name;
            if (! empty($id)) {
                // 微信端更新
                $rs = $this->weObj->updateGroup($group_id, $name);
                if (empty($rs)) {
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg
                    )));
                }
                // 数据更新
                $where['id'] = $id;
                $this->model->table('wechat_user_group')
                    ->data($data)
                    ->where($where)
                    ->update();
            } else {
                // 微信端新增
                $rs = $this->weObj->createGroup($name);
                if (empty($rs)) {
                    exit(json_encode(array(
                        'status' => 0,
                        'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg
                    )));
                }
                // 数据新增
                $data['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_user_group')
                    ->data($data)
                    ->insert();
            }
            exit(json_encode(array(
                'status' => 1
            )));
        }
        $id = I('get.id', 0, 'intval');
        $group = array();
        if (! empty($id)) {
            $where['id'] = $id;
            $group = $this->model->table('wechat_user_group')
                ->field('id, group_id, name')
                ->where($where)
                ->find();
        }

        $this->assign('group', $group);
        $this->display();
    }

    /**
     * 渠道二维码
     */
    public function qrcode_list()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('qrcode_list', $filter), 10);
        $total = $this->model->table('wechat_qrcode')
            ->where('username is null and wechat_id = ' . $this->wechat_id)
            ->order('sort asc')
            ->count();
        $list = $this->model->table('wechat_qrcode')
            ->where('username is null and wechat_id = ' . $this->wechat_id)
            ->order('sort asc')
            ->limit($offset)
            ->select();

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }


    /**
     * 编辑二维码
     */
    public function qrcode_edit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            // 验证数据
            $result = Check::rule(array(
                Check::must($data['function']),
                L('qrcode_function') . L('empty')
            ), array(
                Check::must($data['scene_id']),
                L('qrcode_scene_value') . L('empty')
            ));
            if ($result !== true) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => $result
                )));
            }
            $rs = $this->model->table('wechat_qrcode')->where('scene_id = '.$data['scene_id'])->count();
            if ($rs > 0) {
                exit(json_encode(array('status'=>0, 'msg'=>L('qrcode_scene_limit'))));
            }

            $this->model->table('wechat_qrcode')
                ->data($data)
                ->insert();
            exit(json_encode(array(
                'status' => 1
            )));
        }
        $id = I('get.id', 0, 'intval');
        if (! empty($id)) {
            $status = I('get.status', 0, 'intval');
            $this->model->table('wechat_qrcode')
                ->data('status = ' . $status)
                ->where('id = ' . $id)
                ->update();
            $this->redirect(url('qrcode_list'));
        }
        $this->display();
    }

    /**
     * 扫码引荐
     */
    public function share_list()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('share_list', $filter), 10);
        $total = $this->model->table('wechat_qrcode')
        ->where('username is not null and wechat_id = ' . $this->wechat_id)
        ->order('sort asc')
        ->count();
        //数据
        $list = $this->model->table('wechat_qrcode')
        ->where('username is not null and wechat_id = ' . $this->wechat_id)
        ->order('sort asc')
        ->limit($offset)
        ->select();
        //成交量
        if ($list) {
            foreach ($list as $key=>$val) {
                $list[$key]['share_account'] = $this->model->table('affiliate_log')->field('sum(money)')->where('separate_type = 0 and user_id = '.$val['scene_id'])->getOne();
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 编辑二维码
     */
    public function share_edit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $data['wechat_id'] = $this->wechat_id;
            // 验证数据
            $result = Check::rule(array(
                Check::must($data['username']),
                L('share_name') . L('empty')
            ), array(
                Check::must($data['scene_id']),
                L('share_userid') . L('empty')
            ), array(
                Check::must($data['function']),
                L('qrcode_function') . L('empty')
            ));
            if ($result !== true) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => $result
                )));
            }
            $rs = $this->model->table('wechat_qrcode')->where('scene_id = '.$data['scene_id'])->count();
            if ($rs > 0) {
                exit(json_encode(array('status'=>0, 'msg'=>L('qrcode_scene_limit'))));
            }

            if (empty($data['expire_seconds'])) {
                $data['type'] = 1;
            } else {
                $data['type'] = 0;
            }
            $this->model->table('wechat_qrcode')
            ->data($data)
            ->insert();
            exit(json_encode(array(
                'status' => 1
            )));
        }
        $this->display();
    }


    /**
     * 删除二维码
     */
    public function qrcode_del()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            $this->message(L('select_please') . L('qrcode'), null, 'error');
        }
        $this->model->table('wechat_qrcode')
            ->where('id = ' . $id)
            ->delete();
        $url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('qrcode_list');
        $this->message(L('qrcode') . L('drop') . L('success'), $url);
    }

    /**
     * 更新并获取二维码
     */
    public function qrcode_get()
    {
        $id = I('get.id', 0, 'intval');
        if (empty($id)) {
            exit(json_encode(array(
                'status' => 0,
                'msg' => L('select_please') . L('qrcode')
            )));
        }
        $rs = $this->model->table('wechat_qrcode')
            ->field('type, scene_id, expire_seconds, qrcode_url, status')
            ->where('id = ' . $id)
            ->find();
        if (empty($rs['status'])) {
            exit(json_encode(array(
                'status' => 0,
                'msg' => '二维码已禁用，请重新启用！'
            )));
        }
        if (empty($rs['qrcode_url'])) {
            // 获取二维码ticket
            $ticket = $this->weObj->getQRCode((int)$rs['scene_id'], $rs['type'], $rs['expire_seconds']);
            if (empty($ticket)) {
                exit(json_encode(array(
                    'status' => 0,
                    'msg' => L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg
                )));
            }
            $data['ticket'] = $ticket['ticket'];
            $data['expire_seconds'] = $ticket['expire_seconds'];
            $data['endtime'] = gmtime() + $ticket['expire_seconds'];
            // 二维码地址
            $qrcode_url = $this->weObj->getQRUrl($ticket['ticket']);
            $data['qrcode_url'] = $qrcode_url;

            $this->model->table('wechat_qrcode')
                ->data($data)
                ->where('id = ' . $id)
                ->update();
        } else {
            $qrcode_url = $rs['qrcode_url'];
        }

        $this->assign('qrcode_url', $qrcode_url);
        $this->display();
    }

    /**
     * 图文回复(news)
     */
    public function article()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('article', $filter), 12);
        $total = $this->model->table('wechat_media')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "news"')
            ->count();

        $list = $this->model->table('wechat_media')
            ->field('id, title, file, content, add_time, sort, article_id')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "news"')
            ->order('sort asc, add_time desc')
            ->limit($offset)
            ->select();
        foreach ((array) $list as $key => $val) {
            // 多图文
            if (! empty($val['article_id'])) {
                $id = explode(',', $val['article_id']);
                foreach ($id as $v) {
                    $list[$key]['articles'][] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where('id = ' . $v)
                        ->find();
                }
            }
            $list[$key]['content'] = msubstr(strip_tags(html_out($val['content'])), 100);
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 图文回复编辑
     */
    public function article_edit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            $data['content'] = I('post.content');
            $pic_path = I('post.file_path');
            // 封面处理
            if ($_FILES['pic']['name']) {
                $result = $this->ectouchUpload('pic', 'wechat');
                if ($result['error'] > 0) {
                    $this->message($result['message'], null, 'error');
                }
                $data['file'] = substr($result['message']['pic']['savepath'], 2) . $result['message']['pic']['savename'];
                $data['file_name'] = $result['message']['pic']['name'];
                $data['size'] = $result['message']['pic']['size'];
            } else {
                $data['file'] = $pic_path;
            }
            $rs = Check::rule(array(
                Check::must($data['title']),
                L('title') . L('empty')
            ), array(
                Check::must($data['file']),
                L('please_upload')
            ), array(
                Check::must($data['content']),
                L('content') . L('empty')
            ), array(
                Check::url($data['link']),
                L('link_err')
            ));
            if ($rs !== true) {
                $this->message($rs, null, 'error');
            }

            $data['wechat_id'] = $this->wechat_id;
            $data['type'] = 'news';

            if (! empty($id)) {
                // 删除图片
                if ($pic_path != $data['file']) {
                    @unlink(ROOT_PATH . $pic_path);
                }
                $data['edit_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->where('id = ' . $id)
                    ->update();
            } else {
                $data['add_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->insert();
            }
            $this->message(L('edit') . L('success'), url('article'));
        }
        $id = I('get.id');
        if (! empty($id)) {
            $article = $this->model->table('wechat_media')
                ->where('id = ' . $id)
                ->find();
            $this->assign('article', $article);
        }
        $this->display();
    }

    /**
     * 多图文回复编辑
     */
    public function article_edit_news()
    {
        if (IS_POST) {
            $id = I('post.id');
            $article_id = I('post.article');
            $data['sort'] = I('post.sort');
            if (is_array($article_id)) {
                $data['article_id'] = implode(',', $article_id);
                $data['wechat_id'] = $this->wechat_id;
                $data['type'] = 'news';

                if (! empty($id)) {
                    $data['edit_time'] = gmtime();
                    $this->model->table('wechat_media')
                        ->data($data)
                        ->where('id = ' . $id)
                        ->update();
                } else {
                    $data['add_time'] = gmtime();
                    $this->model->table('wechat_media')
                        ->data($data)
                        ->insert();
                }

                $this->redirect(url('article'));
            } else {
                $this->message('请重新添加', null, 'error');
            }
        }
        $id = I('get.id');
        if (! empty($id)) {
            $rs = $this->model->table('wechat_media')
                ->field('article_id, sort')
                ->where('id = ' . $id)
                ->find();
            if (! empty($rs['article_id'])) {
                $articles = array();
                $art = explode(',', $rs['article_id']);
                foreach ($art as $key => $val) {
                    $articles[] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where('id = ' . $val)
                        ->find();
                }
                $this->assign('articles', $articles);
            }
            $this->assign('sort', $rs['sort']);
        }

        $this->assign('id', $id);
        $this->display();
    }

    /**
     * 单图文列表供多图文选择
     */
    public function articles_list()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('articles_list', $filter), 4);
        $total = $this->model->table('wechat_media')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "news" and article_id is NULL')
            ->count();
        // 图文信息
        $article = $this->model->table('wechat_media')
            ->field('id, title, file, content, add_time')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "news" and article_id is NULL')
            ->limit($offset)
            ->order('sort asc, add_time desc')
            ->select();
        if (! empty($article)) {
            foreach ($article as $k => $v) {
                $article[$k]['content'] = strip_tags(html_out($v['content']));
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('article', $article);
        $this->display();
    }

    /**
     * 多图文回复清空
     */
    public function article_news_del()
    {
        $id = I('get.id');
        if (! empty($id)) {
            $this->model->table('wechat_media')
                ->data('article_id = 0')
                ->where('id  = ' . $id)
                ->update();
        }
        $this->redirect(url('article_edit_news'));
    }

    /**
     * 图文回复删除
     */
    public function article_del()
    {
        $id = I('get.id');
        $pic = $this->model->table('wechat_media')
            ->field('file')
            ->where('id = ' . $id)
            ->getOne();
        if (empty($id)) {
            $this->message(L('select_please') . L('article'), null, 'error');
        }
        $this->model->table('wechat_media')
            ->where('id = ' . $id)
            ->delete();
        if (! empty($pic)) {
            @unlink(ROOT_PATH . $pic);
        }

        $this->redirect(url('article'));
    }

    /**
     * 图片管理(image)
     */
    public function picture()
    {
        if (IS_POST) {
            if ($_FILES['pic']['name']) {
                $result = $this->ectouchUpload('pic', 'wechat', true);
                if ($result['error'] > 0) {
                    $this->message($result['message'], null, 'error');
                }
                $data['file'] = substr($result['message']['pic']['savepath'], 2) . $result['message']['pic']['savename'];
                $data['thumb'] = substr($result['message']['pic']['savepath'], 2) . 'thumb_' . $result['message']['pic']['savename'];
                $data['file_name'] = $result['message']['pic']['name'];
                $data['size'] = $result['message']['pic']['size'];
                $data['type'] = 'image';
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;

                $this->model->table('wechat_media')
                    ->data($data)
                    ->insert();

                $this->redirect(url('picture'));
            }
        }
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('picture', $filter), 12);
        $total = $this->model->table('wechat_media')
            ->where('wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and (type = "image" or type = "news")')
            ->count();

        $list = $this->model->table('wechat_media')
            ->field('id, file, file_name, thumb, size')
            ->where('wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and (type = "image" or type = "news")')
            ->order('add_time desc, sort asc')
            ->limit($offset)
            ->select();
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
        }
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 语音
     */
    public function voice()
    {
        if (IS_POST) {
            if ($_FILES['voice']['name']) {
                $result = $this->ectouchUpload('voice', 'voice');
                if ($result['error'] > 0) {
                    $this->message($result['message'], null, 'error');
                }
                $data['file'] = substr($result['message']['voice']['savepath'], 2) . $result['message']['voice']['savename'];
                $data['file_name'] = $result['message']['voice']['name'];
                $data['size'] = $result['message']['voice']['size'];
                ;
                $data['type'] = 'voice';
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;
                $this->model->table('wechat_media')
                    ->data($data)
                    ->insert();

                $url = $_SERVER['HTTP_REFERER'];
                $this->redirect($url);
            }
        }
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('voice', $filter), 12);
        $total = $this->model->table('wechat_media')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "voice"')
            ->count();

        $list = $this->model->table('wechat_media')
            ->field('id, file, file_name, size')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "voice"')
            ->order('add_time desc, sort asc')
            ->limit($offset)
            ->select();
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 视频
     */
    public function video()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('video', $filter), 12);
        $total = $this->model->table('wechat_media')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "video"')
            ->count();

        $list = $this->model->table('wechat_media')
            ->field('id, file, file_name, size')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "video"')
            ->order('add_time desc, sort asc')
            ->limit($offset)
            ->select();
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            if ($val['size'] > (1024 * 1024)) {
                $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
            } else {
                $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
            }
        }

        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 视频编辑
     */
    public function video_edit()
    {
        if (IS_POST) {
            $data = I('post.data');
            $id = I('post.id');

            if (empty($data['file']) || empty($data['file_name']) || empty($data['size'])) {
                $this->message('请上传视频', null, 'error');
            }
            if (empty($data['title'])) {
                $this->message('请填写标题', null, 'error');
            }
            $data['type'] = 'video';
            $data['wechat_id'] = $this->wechat_id;
            if (! empty($id)) {
                $data['edit_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->where('id = ' . $id)
                    ->update();
            } else {
                $data['add_time'] = gmtime();
                $this->model->table('wechat_media')
                    ->data($data)
                    ->insert();
            }

            $this->redirect(url('video'));
        }
        $id = I('get.id');
        if (! empty($id)) {
            $video = $this->model->table('wechat_media')
                ->field('id, file, file_name, size, title, content')
                ->where('id = ' . $id)
                ->find();

            $this->assign('video', $video);
        }
        $this->display();
    }

    /**
     * 视频上传webuploader
     */
    public function video_upload()
    {
        if (IS_POST && ! empty($_FILES['file']['name'])) {
            $vid = I('post.vid');
            if (! empty($vid)) {
                $file = $this->model->table('wechat_media')
                    ->field('file')
                    ->where('id = ' . $vid)
                    ->getOne();
                if (file_exists(ROOT_PATH . $file)) {
                    @unlink(ROOT_PATH . $file);
                }
            }
            $result = $this->ectouchUpload('file', 'video');
            if ($result['error'] > 0) {
                $data['errcode'] = 1;
                $data['errmsg'] = $result['message'];
                echo json_encode($data);
                exit();
            }
            $data['errcode'] = 0;
            $data['file'] = substr($result['message']['file']['savepath'], 2) . $result['message']['file']['savename'];
            $data['file_name'] = $result['message']['file']['name'];
            $data['size'] = $result['message']['file']['size'];

            echo json_encode($data);
        }
    }

    /**
     * 素材编辑
     */
    public function media_edit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $pic_name = I('post.file_name');
            $rs = Check::rule(array(
                Check::must($id),
                '请选择'
            ), array(
                Check::must($pic_name),
                '请输入名称'
            ));
            if ($rs !== true) {
                exit(json_encode(array(
                    'status' => 0,
                    'error' => $rs
                )));
            }
            $data['file_name'] = $pic_name;
            $data['edit_time'] = gmtime();
            $num = $this->model->table('wechat_media')
                ->data($data)
                ->where('id = ' . $id)
                ->update();

            exit(json_encode(array(
                'status' => $num
            )));
        }
        $id = I('get.id');
        $pic = $this->model->table('wechat_media')
            ->field('id, file_name')
            ->where('id = ' . $id)
            ->find();
        if (empty($pic)) {
            $url = $_SERVER['HTTP_REFERER'];
            $this->redirect($url);
        }
        $this->assign('pic', $pic);
        $this->display();
    }

    /**
     * 素材删除
     */
    public function media_del()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message('请选择', null, 'error');
        }
        $pic = $this->model->table('wechat_media')
            ->field('file, thumb')
            ->where('id = ' . $id)
            ->find();
        if (! empty($pic)) {
            $this->model->table('wechat_media')
                ->where('id = ' . $id)
                ->delete();
        }
        if (file_exists(ROOT_PATH . $pic['file'])) {
            @unlink(ROOT_PATH . $pic['file']);
        }
        if (file_exists(ROOT_PATH . $pic['thumb'])) {
            @unlink(ROOT_PATH . $pic['thumb']);
        }
        $url = $_SERVER['HTTP_REFERER'];
        $this->redirect($url);
    }

    /**
     * 下载
     */
    public function download()
    {
        $id = I('get.id');
        $pic = $this->model->table('wechat_media')
            ->field('file, file_name')
            ->where('id = ' . $id)
            ->find();
        $filename = ROOT_PATH . $pic['file'];
        if (file_exists($filename)) {
            Http::download($filename, $pic['file_name']);
        } else {
            $this->message('文件不存在', null, 'error');
        }
    }

    /**
     * 群发消息列表
     */
    public function mass_list()
    {
        // 分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('mass_list', $filter), 10);
        $total = $this->model->table('wechat_mass_history')
            ->where('wechat_id = ' . $this->wechat_id)
            ->count();
        $this->assign('page', $this->pageShow($total));

        $list = $this->model->table('wechat_mass_history')
            ->field('id, media_id, type, status, send_time, totalcount, sentcount, errorcount')
            ->where('wechat_id = ' . $this->wechat_id)
            ->order('send_time desc')
            ->limit($offset)
            ->select();
        foreach ((array) $list as $key => $val) {
            $media = $this->model->table('wechat_media')
                ->field('title, content, file, article_id')
                ->where('id = ' . $val['media_id'])
                ->find();
            if (! empty($media['article_id'])) {
                // 多图文
                $artids = explode(',', $media['article_id']);
                $artinfo = $this->model->table('wechat_media')
                    ->field('title, content, file')
                    ->where('id = ' . $artids[0])
                    ->find();
            } else {
                $artinfo = $media;
            }
            if ('news' == $val['type']) {
                $artinfo['type'] = '图文消息';
            }
            $artinfo['content'] = strip_tags(html_out($artinfo['content']));
            $list[$key]['artinfo'] = $artinfo;
        }

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 群发消息
     */
    public function mass_message()
    {
        if (IS_POST) {
            $group_id = I('post.group_id', '', 'intval');
            $media_id = I('post.media_id');
            if ((empty($group_id) && $group_id !== 0) || empty($media_id)) {
                $this->message('请选择用户分组或者选择要发送的信息', null, 2);
            }
            $article = array();
            $article_info = $this->model->table('wechat_media')
                ->field('id, title, author, file, is_show, digest, content, link, type, article_id')
                ->where('id = ' . $media_id)
                ->find();
            // 多图文
            if (! empty($article_info['article_id'])) {
                $articles = explode(',', $article_info['article_id']);
                foreach ($articles as $key => $val) {
                    $artinfo = $this->model->table('wechat_media')
                        ->field('title, author, file, is_show, digest, content, link')
                        ->where('id = ' . $val)
                        ->find();
                    $artinfo['content'] = html_out($artinfo['content']);
                    // 上传多媒体文件
                    $filename = ROOT_PATH . $artinfo['file'];
                    $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), 'image');
                    if (empty($rs)) {
                        $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
                    }
                    // 重组数据
                    $article[$key]['thumb_media_id'] = $rs['media_id'];
                    $article[$key]['author'] = $artinfo['author'];
                    $article[$key]['title'] = $artinfo['title'];
                    $article[$key]['content_source_url'] = empty($artinfo['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$artinfo['id'])) : strip_tags(html_out($artinfo['link']));
                    $article[$key]['content'] =$this->uploadMassMessageContentImg($artinfo['content']);
                    $article[$key]['digest'] = $artinfo['digest'];
                    $article[$key]['show_cover_pic'] = $artinfo['is_show'];
                }
            } else {
                // 单图文
                // 上传多媒体文件
                $filename = ROOT_PATH . $article_info['file'];
                $rs = $this->weObj->uploadMedia(array('media' => realpath_wechat($filename)), 'image');
                if (empty($rs)) {
                    $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
                }
                $article_info['content'] = html_out($article_info['content']);
                // 重组数据
                $article[0]['thumb_media_id'] = $rs['media_id'];
                $article[0]['author'] = $article_info['author'];
                $article[0]['title'] = $article_info['title'];
                $article[0]['content_source_url'] = empty($article_info['link']) ? __HOST__ . url('article/wechat_news_info', array('id'=>$article_info['id'])) : strip_tags(html_out($article_info['link']));
                $article[0]['content'] = $this->uploadMassMessageContentImg($article_info['content']);
                $article[0]['digest'] = $article_info['digest'];
                $article[0]['show_cover_pic'] = $article_info['is_show'];
            }
            $article_list = array(
                'articles' => $article
            );
            // 图文消息上传
            $rs1 = $this->weObj->uploadArticles($article_list);
            if (empty($rs1)) {
                $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
            }
            /**
             * 根据标签组进行群发sendGroupMassMessage
             * 群发接口新增原创校验流程
             * 当 send_ignore_reprint 参数设置为1时，文章被判定为转载时，将继续进行群发操作。
             * 当 send_ignore_reprint 参数设置为0时，文章被判定为转载时，将停止群发操作。
             * send_ignore_reprint 默认为0。
             * clientmsgid  群发接口新增 clientmsgid 参数，开发者调用群发接口时可以主动设置，避免重复推送。
             *
             */
            $mass_id = $this->model->table('wechat_mass_history')->field('id')->where(array('wechat_id' => $this->wechat_id))->order('id DESC')->getOne();
            $clientmsgid = !empty($mass_id) ? $mass_id + 1 : 0;
            $massmsg = array(
                'filter' => array(
                    'is_to_all' => false,
                    'group_id' => $group_id
                ),
                'mpnews' => array(
                    'media_id' => $rs1['media_id']
                ),
                'msgtype' => 'mpnews',
                'send_ignore_reprint' => 0,
                'clientmsgid' => $clientmsgid
            );
            $rs2 = $this->weObj->sendGroupMassMessage($massmsg);
            if (empty($rs2)) {
                $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
            }

            // 数据处理
            $msg_data['wechat_id'] = $this->wechat_id;
            $msg_data['media_id'] = $article_info['id'];
            $msg_data['type'] = $article_info['type'];
            $msg_data['send_time'] = gmtime();
            $msg_data['msg_id'] = $rs2['msg_id'];
            $id = $this->model->table('wechat_mass_history')
                ->data($msg_data)
                ->insert();

            $this->message('群发任务已启动，不过一般需要较长的时间才能全部发送完毕，请耐心等待', url('mass_message'));
        }
        // 分组信息
        $groups = $this->model->table('wechat_user_group')
            ->field('group_id, name')
            ->where('wechat_id = ' . $this->wechat_id)
            ->order('group_id')
            ->select();
        // 图文信息
        $article = $this->model->table('wechat_media')
            ->field('id, title, file, content, article_id, add_time')
            ->where('wechat_id = ' . $this->wechat_id . ' and type = "news"')
            ->order('sort asc, add_time desc')
            ->select();
        foreach ((array) $article as $key => $val) {
            if (! empty($val['article_id'])) {
                $id = explode(',', $val['article_id']);
                foreach ($id as $v) {
                    $article[$key]['articles'][] = $this->model->table('wechat_media')
                        ->field('id, title, file, add_time')
                        ->where('id = ' . $v)
                        ->find();
                }
            }
            $article[$key]['content'] = strip_tags(html_out($val['content']));
        }

        $this->assign('groups', $groups);
        $this->assign('article', $article);
        $this->display();
    }

    /**
     * 群发消息 内容上传图片（不是封面上传）
     * @param  string $content
     * @return
     */
    public function uploadMassMessageContentImg($content = '')
    {
        $content = html_out($content);
        $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.bmp|\.jpeg]))[\'|\"].*?[\/]?>/";
        preg_match_all($pattern, $content, $match);

        if (count($match[1]) > 0) {
            foreach ($match[1] as $img) {
                if (strtolower(substr($img, 0, 4)) != 'http') {
                    $filename = dirname(ROOT_PATH)  . $img;
                    $rs = $this->weObj->uploadImg(array('media' => realpath_wechat($filename)), 'image');
                    if (!empty($rs)) {
                        $replace = $rs['url'];// http://mmbiz.qpic.cn/mmbiz/gLO17UPS6FS2xsypf378iaNhWacZ1G1UplZYWEYfwvuU6Ont96b1roYs CNFwaRrSaKTPCUdBK9DgEHicsKwWCBRQ/0
                        $content = str_replace($img, $replace, $content);
                    }
                }
            }
        }
                            
        return $content;
    }

    /**
     * 群发消息删除
     */
    public function mass_del()
    {
        $id = I('get.id');
        $msg_id = $this->model->table('wechat_mass_history')
            ->field('msg_id')
            ->where('id = ' . $id)
            ->getOne();
        if (empty($msg_id)) {
            $this->message('消息不存在', null, 'error');
        }
        // 删除群发接口新增 article_idx 参数, 默认 0 删除全部文章
        $delmass = array(
            'msg_id' => $msg_id,
            'article_idx' => 0
        );
        $rs = $this->weObj->deleteMassMessage($delmass);
        if (empty($rs)) {
            $this->message(L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg, null, 'error');
        }

        $data['status'] = 'send success(已删除)';
        $this->model->table('wechat_mass_history')
            ->data($data)
            ->where('id = ' . $id)
            ->update();
        $this->redirect(url('mass_list'));
    }

    /**
     * ajax获取图文信息
     */
    public function get_article()
    {
        if (IS_AJAX) {
            $data = I('post.article');
            $article = array();
            if (is_array($data)) {
                $id = implode(',', $data);
                $article = $this->model->table('wechat_media')
                    ->field('id, title, file, link, content, add_time')
                    ->where('id in (' . $id . ')')
                    ->order('sort asc, add_time desc')
                    ->select();
                foreach ($article as $key => $val) {
                    $article[$key]['add_time'] = date('Y年m月d日', $val['add_time']);
                    $article[$key]['content'] = html_out($val['content']);
                }
            }
            echo json_encode($article);
        }
    }

    /**
     * 自动回复
     */
    public function auto_reply()
    {
        // 素材数据
        $type = I('get.type');
        if (! empty($type)) {
            // 分页
            $filter['page'] = '{page}';
            $filter['type'] = $type;
            $offset = $this->pageLimit(url('auto_reply', $filter), 5);
            if ('image' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and (type = "image" or type="news")';
                $list = $this->model->table('wechat_media')
                    ->field('id, file, file_name, size, add_time, type')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
            } elseif ('voice' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "voice"';
                $list = $this->model->table('wechat_media')
                    ->field('id, file, file_name, size, add_time, type')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
            } elseif ('video' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "video"';
                $list = $this->model->table('wechat_media')
                    ->field('id, file, file_name, size, add_time, type')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
            } elseif ('voice' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "voice"';
                $list = $this->model->table('wechat_media')
                    ->field('id, file, file_name, size, add_time, type')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
            } elseif ('video' == $type) {
                $where = 'wechat_id = ' . $this->wechat_id . ' and file is NOT NULL and type = "video"';
                $list = $this->model->table('wechat_media')
                    ->field('id, file, file_name, size, add_time, type')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
            } elseif ('news' == $type) {
                $offset = $this->pageLimit(url('auto_reply', $filter), 6);
                // 只显示单图文
                $no_list = I('get.no_list', 0, 'intval');
                $this->assign('no_list', $no_list);
                if (! empty($no_list)) {
                    $where = 'wechat_id = ' . $this->wechat_id . ' and type="news" and article_id is NULL';
                } else {
                    $where = 'wechat_id = ' . $this->wechat_id . ' and type="news"';
                }
                $list = $this->model->table('wechat_media')
                    ->field('id, title, file, file_name, size, content, add_time, type, article_id')
                    ->where($where)
                    ->order('add_time desc')
                    ->limit($offset)
                    ->select();
                foreach ((array) $list as $key => $val) {
                    if (! empty($val['article_id'])) {
                        $id = explode(',', $val['article_id']);
                        foreach ($id as $v) {
                            $list[$key]['articles'][] = $this->model->table('wechat_media')
                                ->field('id, title, file, add_time')
                                ->where('id = ' . $v)
                                ->find();
                        }
                    }
                    $list[$key]['content'] = strip_tags(html_out($val['content']));
                }
            }
            foreach ((array) $list as $key => $val) {
                if ($val['size'] > (1024 * 1024)) {
                    $list[$key]['size'] = round(($val['size'] / (1024 * 1024)), 1) . 'MB';
                } else {
                    $list[$key]['size'] = round(($val['size'] / 1024), 1) . 'KB';
                }
            }

            $total = $this->model->table('wechat_media')
                ->where($where)
                ->count();
            $this->assign('page', $this->pageShow($total));
            $this->assign('list', $list);
            $this->assign('type', $type);
            $this->display();
        }
    }

    /**
     * 关注回复(subscribe)
     */
    public function reply_subscribe()
    {
        if (IS_POST) {
            $content_type = I('post.content_type');
            if ($content_type == 'text') {
                $data['content'] = I('post.content');
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = I('post.media_id');
                $data['content'] = '';
            }
            $data['type'] = 'subscribe';
            if (is_array($data) && (! empty($data['media_id']) || ! empty($data['content']))) {
                $id = $this->model->table('wechat_reply')
                    ->field('id')
                    ->where('type = "' . $data['type'] . '" and wechat_id =' . $this->wechat_id)
                    ->getOne();
                if (! empty($id)) {
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->where('type = "' . $data['type'] . '" and wechat_id =' . $this->wechat_id)
                        ->update();
                } else {
                    $data['wechat_id'] = $this->wechat_id;
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->insert();
                }
                $this->redirect(url('reply_subscribe'));
            } else {
                $this->message('请填写内容', null, 'error');
            }
        }
        // 自动回复数据
        $subscribe = $this->model->table('wechat_reply')
            ->where('type = "subscribe" and wechat_id =' . $this->wechat_id)
            ->find();
        if (! empty($subscribe['media_id'])) {
            $subscribe['media'] = $this->model->table('wechat_media')
                ->field('file, type, file_name')
                ->where('id = ' . $subscribe['media_id'])
                ->find();
        }
        $this->assign('subscribe', $subscribe);
        $this->display();
    }

    /**
     * 消息回复(msg)
     */
    public function reply_msg()
    {
        if (IS_POST) {
            $content_type = I('post.content_type');
            if ($content_type == 'text') {
                $data['content'] = I('post.content');
                $data['media_id'] = 0;
            } else {
                $data['media_id'] = I('post.media_id');
                $data['content'] = '';
            }
            $data['type'] = 'msg';
            if (is_array($data) && (! empty($data['media_id']) || ! empty($data['content']))) {
                $id = $this->model->table('wechat_reply')
                    ->field('id')
                    ->where('type = "' . $data['type'] . '" and wechat_id =' . $this->wechat_id)
                    ->getOne();
                if (! empty($id)) {
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->where('type = "' . $data['type'] . '" and wechat_id =' . $this->wechat_id)
                        ->update();
                } else {
                    $data['wechat_id'] = $this->wechat_id;
                    $this->model->table('wechat_reply')
                        ->data($data)
                        ->insert();
                }
                $this->redirect(url('reply_msg'));
            } else {
                $this->message('请填写内容', null, 'error');
            }
        }
        // 自动回复数据
        $msg = $this->model->table('wechat_reply')
            ->where('type = "msg" and wechat_id =' . $this->wechat_id)
            ->find();
        if (! empty($msg['media_id'])) {
            $msg['media'] = $this->model->table('wechat_media')
                ->field('file, type, file_name')
                ->where('id = ' . $msg['media_id'])
                ->find();
        }
        $this->assign('msg', $msg);
        $this->display();
    }

    /**
     * 关键词自动回复
     */
    public function reply_keywords()
    {
        $list = $this->model->table('wechat_reply')
            ->field('id, rule_name, content, media_id, reply_type')
            ->where('type = "keywords" and wechat_id =' . $this->wechat_id)
            ->order('add_time desc')
            ->select();
        foreach ((array) $list as $key => $val) {
            // 内容不是文本
            if (! empty($val['media_id'])) {
                $media = $this->model->table('wechat_media')
                    ->field('title, file, file_name, type, content, add_time, article_id')
                    ->where('id = ' . $val['media_id'])
                    ->find();
                $media['content'] = strip_tags(html_out($media['content']));
                if (! empty($media['article_id'])) {
                    $artids = explode(',', $media['article_id']);
                    foreach ($artids as $v) {
                        $list[$key]['medias'][] = $this->model->table('wechat_media')
                            ->field('title, file, add_time')
                            ->where('id = ' . $v)
                            ->find();
                    }
                } else {
                    $list[$key]['media'] = $media;
                }
            }
            $keywords = $this->model->table('wechat_rule_keywords')
                ->field('rule_keywords')
                ->where('rid = ' . $val['id'])
                ->order('id desc')
                ->select();
            $list[$key]['rule_keywords'] = $keywords;
            // 编辑关键词时显示
            if (! empty($keywords)) {
                $rule_keywords = array();
                foreach ($keywords as $k => $v) {
                    $rule_keywords[] = $v['rule_keywords'];
                }
                $rule_keywords = implode(',', $rule_keywords);
                $list[$key]['rule_keywords_string'] = $rule_keywords;
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 关键词回复添加规则
     */
    public function rule_edit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $content_type = I('post.content_type');
            $rule_keywords = I('post.rule_keywords');
            // 主表数据
            $data['rule_name'] = I('post.rule_name');
            $data['media_id'] = I('post.media_id');
            $data['content'] = I('post.content');
            $data['reply_type'] = $content_type;
            if ($content_type == 'text') {
                $data['media_id'] = 0;
            } else {
                $data['content'] = '';
            }
            $rs = Check::rule(array(
                Check::must($data['rule_name']),
                '请填写规则名称'
            ), array(
                Check::must($rule_keywords),
                '请至少填写1个关键词'
            ));
            if ($rs !== true) {
                $this->message($rs, null, 'error');
            }
            if (empty($data['content']) && empty($data['media_id'])) {
                $this->message('请填写或选择回复内容', null, 'error');
            }
            $data['type'] = 'keywords';
            if (! empty($id)) {
                $this->model->table('wechat_reply')
                    ->data($data)
                    ->where('id = ' . $id)
                    ->update();
                $this->model->table('wechat_rule_keywords')
                    ->where('rid = ' . $id)
                    ->delete();
            } else {
                $data['add_time'] = gmtime();
                $data['wechat_id'] = $this->wechat_id;
                $id = $this->model->table('wechat_reply')
                    ->data($data)
                    ->insert();
            }
            // 编辑关键词
            $rule_keywords = explode(',', $rule_keywords);
            foreach ($rule_keywords as $val) {
                $kdata['rid'] = $id;
                $kdata['rule_keywords'] = $val;
                $this->model->table('wechat_rule_keywords')
                    ->data($kdata)
                    ->insert();
            }
            $this->redirect(url('reply_keywords'));
        }
    }

    /**
     * 关键词回复规则删除
     */
    public function reply_del()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message('请选择', null, 'error');
        }
        $this->model->table('wechat_reply')
            ->where('id = ' . $id)
            ->delete();
        $this->redirect(url('reply_keywords'));
    }

    /**
     * 素材管理
     */
    public function media_list()
    {
        $this->display();
    }

    /**
     * 提醒设置
     */
    public function remind()
    {
        if (IS_POST) {
            $command = I('post.command');
            $data = I('post.data');
            $config = I('post.config');
            $info = Check::rule(array(
                Check::must($command),
                '关键词不正确'
            ));
            if ($info !== true) {
                $this->message($info, null, 'error');
            }
            if (! empty($config)) {
                $data['config'] = serialize($config);
            }
            $data['wechat_id'] = $this->wechat_id;
            $num = $this->model->table('wechat_extend')
                ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                ->count();
            if ($num > 0) {
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                    ->update();
            } else {
                $data['command'] = $command;
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->insert();
            }

            $this->redirect($_SERVER['HTTP_REFERER']);
        }

        $order_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "order_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($order_remind['config']) {
            $order_remind['config'] = unserialize($order_remind['config']);
        }
        $pay_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "pay_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($pay_remind['config']) {
            $pay_remind['config'] = unserialize($pay_remind['config']);
        }
        $send_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "send_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($send_remind['config']) {
            $send_remind['config'] = unserialize($send_remind['config']);
        }
        $register_remind = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "register_remind" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($register_remind['config']) {
            $register_remind['config'] = unserialize($register_remind['config']);
        }
        $this->assign('order_remind', $order_remind);
        $this->assign('pay_remind', $pay_remind);
        $this->assign('send_remind', $send_remind);
        $this->assign('register_remind', $register_remind);
        $this->display();
    }

    /**
     * 多客服设置
     */
    public function customer_service()
    {
        if (IS_POST) {
            $command = I('post.command');
            $data = I('post.data');
            $config = I('post.config');
            $info = Check::rule(array(
                Check::must($command),
                '关键词不正确'
            ));
            if ($info !== true) {
                $this->message($info, null, 'error');
            }

            if (! empty($config)) {
                $data['config'] = serialize($config);
            }
            $num = $this->model->table('wechat_extend')
                ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                ->count();
            if ($num > 0) {
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->where('command = "' . $command . '" and wechat_id = ' . $this->wechat_id)
                    ->update();
            } else {
                $data['wechat_id'] = $this->wechat_id;
                $data['command'] = $command;
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->insert();
            }

            $this->redirect($_SERVER['HTTP_REFERER']);
        }

        $customer_service = $this->model->table('wechat_extend')
            ->field('name, enable, config')
            ->where('command = "kefu" and wechat_id = ' . $this->wechat_id)
            ->find();
        if ($customer_service['config']) {
            $customer_service['config'] = unserialize($customer_service['config']);
        }
        $this->assign('customer_service', $customer_service);
        $this->display();
    }

    /**
     * 模板消息
     */
    public function template_massage_list()
    {
        $list = $this->model->table('wechat_template')
            ->order('id asc')
            ->select();
        if ($list) {
            foreach ($list as $key=>$val) {
                $list[$key]['add_time'] = local_date('Y-m-d H:i', $val['add_time']);
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 开关按钮
     */
    public function is_switch()
    {
        if (IS_GET) {
            $id = I('get.id', 0, 'intval');
            $status = I('get.status', 0, 'intval');
            if (empty($id)) {
                $this->message('请选择模板消息', null, 'error');
            }
            $condition['id'] = $id;
            $condition['wechat_id'] = $this->wechat_id;

            $data = array();
            $data['add_time'] = gmtime();

            // 启用模板消息
            if ($status == 1) {
                // 模板ID为空
                $template = $this->model->table('wechat_template')->field('template_id, code')->where($condition)->find();
                if (empty($template['template_id'])) {
                    $template_id = $this->weObj->addTemplateMessage($template['code']);
                    // 已经存在模板ID
                    if ($template_id) {
                        $data['template_id'] = $template_id;
                        $this->model->table('wechat_template')->data($data)->where($condition)->update();
                    } else {
                        // $this->message($this->weObj->errMsg, null, 2);
                        exit(json_encode(array('status'=> 0, 'message'=> $this->weObj->errMsg)));
                    }
                }
                // 重新启用 更新状态status
                $data['status'] = 1;
                $this->model->table('wechat_template')->data($data)->where($condition)->update();
                exit(json_encode(array('status'=> 200, 'message'=> '已启用')));
            } else {
                // 禁用 更新状态status
                $data['status'] = 0;
                $this->model->table('wechat_template')->data($data)->where($condition)->update();
                exit(json_encode(array('status'=> 200, 'message'=> '已禁用')));
            }
        }
    }

    /**
     * 编辑模板消息
     */
    public function edit_template()
    {
        if (IS_AJAX) {
            $id = I('post.id');
            $data = I('post.data', '', 'trim');
            if ($id) {
                $this->model->table('wechat_template')->data($data)->where(array('id'=>$id))->update();
                exit(json_encode(array('status'=>1)));
            } else {
                exit(json_encode(array('status'=>0, 'msg'=>'编辑失败')));
            }
        }
        $id = I('get.id');
        if ($id) {
            $template = $this->model->table('wechat_template')->where(array('id'=>$id))->find();
            $this->assign('template', $template);
        }
        $this->display();
    }

    /**
     * 重置模板消息
     * @return
     */
    public function reset_template()
    {
        if (IS_AJAX) {
            $json_result = array('error' => 0, 'msg' => '', 'url' => '');

            $id = I('get.id', 0, 'intval');
            if (!empty($id)) {
                $condition['id'] = $id;
                $condition['wechat_id'] = $this->wechat_id;
                $template = $this->model->table('wechat_template')->field('template_id')->where($condition)->find();
                if (!empty($template['template_id'])) {
                    $rs = $this->weObj->delTemplate($template['template_id']);
                    if (empty($rs)) {
                        $json_result['msg'] = L('errcode') . $this->weObj->errCode . L('errmsg') . $this->weObj->errMsg;
                        exit(json_encode($json_result));
                    }
                    $this->model->table('wechat_template')->data(array('template_id' => '', 'status' => 0))->where(array('id' => $id))->update();
                    $json_result['msg'] = '重置成功！';
                    exit(json_encode($json_result));
                }
            }
            $json_result['error'] = 1;
            $json_result['msg'] = '重置失败！';
            exit(json_encode($json_result));
        }
    }

    /**
     * 获取配置信息
     */
    private function get_config()
    {
        $without = array(
            'index',
            'append',
            'modify',
            'delete',
            'set_default'
        );
        if (! in_array(ACTION_NAME, $without)) {
            $id = $this->wechat_id; //I('get.wechat_id', 0, 'intval');
            if (! empty($id)) {
                session('wechat_id', $id);
            } else {
                $id = session('wechat_id') ? session('wechat_id') : 0;
            }

            $status = $this->model->table('wechat')
                ->field('status')
                ->where('id = ' . $id)
                ->getOne();
            if (empty($status)) {
                $this->message(L('open_wechat'), null, 'error');
            }
            $this->wechat_id = session('wechat_id');
            if (! empty($this->wechat_id)) {
                // 公众号配置信息
                $where['id'] = $this->wechat_id;
                $wechat = $this->model->table('wechat')
                    ->field('token, appid, appsecret, type')
                    ->where($where)
                    ->find();
                if (empty($wechat)) {
                    $wechat = array();
                }
                $config = array();
                $config['token'] = $wechat['token'];
                $config['appid'] = $wechat['appid'];
                $config['appsecret'] = $wechat['appsecret'];

                $this->weObj = new Wechat($config);
                $this->assign('type', $wechat['type']);
            }
        }
    }
}
