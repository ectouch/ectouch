<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ActivityControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：优惠活动控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class WallController extends CommonController
{

    /**
     * 微信交流墙
     */
    public function wall_msg()
    {
        $wall_id = I('get.wall_id');
        if (empty($wall_id)) {
            $this->redirect(url('index/index'));
        }

        //活动内容
        $wall = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support')->where(array('id'=>$wall_id, 'status'=>1))->find();

        $cache_key = md5('cache_0');
        $Eccache = new Cache();
        $list = $Eccache->get($cache_key);
        if (!$list) {
            //留言
            $sql = "SELECT u.nickname, u.headimg, m.content, m.addtime FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 and u.wall_id = '$wall_id' ORDER BY m.addtime DESC LIMIT 0, 10";
            $data = $this->model->query($sql);
            if ($data) {
                usort($data, function ($a, $b) {
                    if ($a['addtime'] == $b['addtime']) {
                        return 0;
                    }
                    return $a['addtime'] > $b['addtime'] ? 1 : -1;
                });
                foreach ($data as $k=>$v) {
                    $data[$k]['addtime'] = date('Y-m-d H:i', $v['addtime']);
                }
            }
            $Eccache->set($cache_key, $data, 10);
            $list = $Eccache->get($cache_key);
        }

        $sql = "SELECT count(*) as num FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC";
        $num = $this->model->query($sql);

        $this->assign('list', $list);
        $this->assign('msg_count', $num[0]['num']);
        $this->assign('wall', $wall);
        $this->assign('list', $list);
        $this->display('wall/wall_msg.dwt');
    }

    /**
     * 微信头像墙
     */
    public function wall_user()
    {
        $wall_id = I('get.wall_id');
        if (empty($wall_id)) {
            $this->redirect(url('index/index'));
        }
        //活动内容
        $wall = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support')->where(array('id'=>$wall_id, 'status'=>1))->find();

        //用户
        $list = $this->model->table('wechat_wall_user')->field('nickname, headimg')->where(array('wall_id'=>$wall_id, 'status'=>1))->order('addtime desc')->select();
        /*$sql = "SELECT u.nickname, u.headimg FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE u.wall_id = '$wall_id' AND m.status = 1 GROUP BY m.user_id ORDER BY u.addtime DESC";
        $list = $this->model->query($sql);*/

        $this->assign('wall', $wall);
        $this->assign('list', $list);
        $this->display('wall/wall_user.dwt');
    }

    /**
     * 抽奖页面
     */
    public function wall_prize()
    {
        $wall_id = I('get.wall_id');
        if (empty($wall_id)) {
            $this->redirect(url('index/index'));
        }
        //活动内容
        $wall = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support')->where(array('id'=>$wall_id, 'status'=>1))->find();
        if ($wall) {
            $wall['prize'] = unserialize($wall['prize']);
        }

        //中奖的用户
        //$sql = "SELECT u.nickname, u.headimg, u.id FROM ".$this->model->pre."wechat_wall_user u LEFT JOIN ".$this->model->pre."wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid in (SELECT openid FROM ".$this->model->pre."wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') GROUP BY u.id ORDER BY p.dateline ASC";
        $sql = "SELECT u.nickname, u.headimg, u.id, u.wechatname, u.headimgurl FROM ".$this->model->pre."wechat_prize p LEFT JOIN ".$this->model->pre."wechat_wall_user u ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid in (SELECT openid FROM ".$this->model->pre."wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall' AND prize_type = 1) GROUP BY u.id ORDER BY p.dateline ASC";
        $rs = $this->model->query($sql);
        $list = array();
        if ($rs) {
            foreach ($rs as $k=>$v) {
                $list[$k+1] = $v;
            }
        }
        //参与人数
        $total = $this->model->table('wechat_wall_user')->where(array('status'=>1))->count();

        $this->assign('total', $total);
        $this->assign('prize_num', count($list));
        $this->assign('list', $list);
        $this->assign('wall', $wall);
        $this->display('wall/wall_prize.dwt');
    }

    /**
     * 获取未中奖用户
     */
    public function no_prize()
    {
        if (IS_AJAX) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            //没中奖的用户
            $sql = "SELECT nickname, headimg, id, wechatname, headimgurl FROM ".$this->model->pre."wechat_wall_user WHERE wall_id = '$wall_id' AND status = 1 AND openid not in (SELECT openid FROM ".$this->model->pre."wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') ORDER BY addtime DESC";
            //$sql = "SELECT u.nickname, u.headimg, u.id FROM ".$this->model->pre."wechat_wall_user u LEFT JOIN ".$this->model->pre."wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid not in (SELECT openid FROM ".$this->model->pre."wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') ORDER BY u.addtime ASC";
            $no_prize = $this->model->query($sql);
            if (empty($no_prize)) {
                $result['errCode'] = 2;
                $result['errMsg'] = '暂无参与抽奖用户';
                exit(json_encode($result));
            }
            
            $result['data'] = $no_prize;
            exit(json_encode($result));
        }
    }

    /**
     * 抽奖的动作
     */
    public function start_draw()
    {
        if (IS_AJAX) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            $wall = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support')->where(array('id'=>$wall_id, 'status'=>1))->find();
            if (empty($wall)) {
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            if ($wall['starttime'] > time() || $wall['endtime'] < time()) {
                $result['errCode'] = 2;
                $result['errMsg'] = '活动尚未开始或者已结束';
                exit(json_encode($result));
            }

            $sql = "SELECT u.nickname, u.headimg, u.openid, u.id, u.wechatname, u.headimgurl FROM ".$this->model->pre."wechat_wall_user u LEFT JOIN ".$this->model->pre."wechat_prize p ON u.openid = p.openid WHERE u.wall_id = '$wall_id' AND u.status = 1 AND u.openid not in (SELECT openid FROM ".$this->model->pre."wechat_prize WHERE wall_id = '$wall_id' AND activity_type = 'wall') ORDER BY u.addtime DESC";
            $list = $this->model->query($sql);
            //$list = array('1');
            if ($list) {
                //随机一个中奖人
                $key = mt_rand(0, count($list) - 1);
                $rs = isset($list[$key]) ? $list[$key] : $list[0];
                //存储中奖用户
                $data['wechat_id'] = $this->model->table('wechat')->field('id')->where(array('status'=>1))->getOne();
                $data['openid'] = $rs['openid'];
                $data['issue_status'] = 0;
                $data['dateline'] = time();
                $data['prize_type'] = 1;
                $data['activity_type'] = 'wall';
                $data['wall_id'] = $wall_id;
                $this->model->table('wechat_prize')->data($data)->insert();

                //中奖人数
                $rs['prize_num'] = $this->model->table('wechat_prize')->where(array('wall_id'=>$wall_id, 'activity_type'=>'wall'))->count();

                /*$rs = array();
                $rs['nickname'] = 'null';
                $rs['headimg'] = __TPL__.'/wall/img/wall_4.png';*/
                $result['data'] = $rs;
                exit(json_encode($result));
            }
        }
        $result['errCode'] = 2;
        $result['errMsg'] = '暂无数据';
        exit(json_encode($result));
    }

    /**
     * 重置抽奖
     */
    public function reset_draw()
    {
        if (IS_AJAX) {
            $result['errCode'] = 0;
            $result['errMsg'] = '';

            $wall_id = I('get.wall_id');
            if (empty($wall_id)) {
                $result['errCode'] = 1;
                $result['errMsg'] = url('index/index');
                exit(json_encode($result));
            }
            //删除中奖的用户
            //$this->model->table('wechat_prize')->where(array('wall_id'=>$wall_id, 'activity_type'=>'wall'))->delete();
            //不显示在中奖池
            $this->model->table('wechat_prize')->data(array('prize_type'=>0))->where(array('wall_id'=>$wall_id, 'activity_type'=>'wall'))->update();
            exit(json_encode($result));
        }
        $result['errCode'] = 2;
        $result['errMsg'] = '无效的请求';
        exit(json_encode($result));
    }

    /**
     * 微信端抽奖用户申请
     */
    public function wall_user_wechat()
    {
        if (!empty($_SESSION['wechat_user'])) {
            if (IS_POST) {
                $wall_id = I('post.wall_id');
                if (empty($wall_id)) {
                    show_message("请选择对应的活动");
                }
                $data['nickname'] = I('post.nickname');
                $data['headimg'] = I('post.headimg');
                $data['sex'] = I('post.sex');
                $data['wall_id'] = $wall_id;
                $data['addtime'] = time();
                $data['openid'] = $_SESSION['wechat_user']['openid'];
                $data['wechatname'] = $_SESSION['wechat_user']['nickname'];
                $data['headimgurl'] = $_SESSION['wechat_user']['headimgurl'];

                $this->model->table('wechat_wall_user')->data($data)->insert();
                $this->redirect(url('wall_msg_wechat', array('wall_id'=>$wall_id)));
                exit;
            }
            $wall_id = I('get.wall_id');
            if (empty($wall_id)) {
                $this->redirect(url('index/index'));
            }
            /*if(isset($_GET['debug'])){
                $_SESSION['wechat_user']['openid'] = 'o1UgVuKGG67Y1Yoy_zC1JqoYSH54';
            }*/
            //更改过头像跳到聊天页面
            $wechat_user = $this->model->table('wechat_wall_user')->where(array('openid'=>$_SESSION['wechat_user']['openid']))->count();
            if ($wechat_user > 0) {
                $this->redirect(url('wall_msg_wechat', array('wall_id'=>$wall_id)));
            }

            $this->assign('user', $_SESSION['wechat_user']);
            $this->assign('wall_id', $wall_id);
            $this->display('wall/wall_user_wechat.dwt');
        }
    }

    /**
     * 微信端留言页面
     */
    public function wall_msg_wechat()
    {
        if (!empty($_SESSION['wechat_user'])) {
            if (IS_POST && IS_AJAX) {
                $wall_id = I('post.wall_id');
                if (empty($wall_id)) {
                    exit(json_encode(array('code'=>1, 'errMsg'=>'请选择对应的活动')));
                }
                $data['user_id'] = I('post.user_id');
                $data['content'] = I('post.content', '', 'trim,htmlspecialchars');
                if (empty($data['user_id']) || empty($data['content'])) {
                    exit(json_encode(array('code'=>1, 'errMsg'=>'请先登录或者发表的内容不能为空')));
                }
                $data['addtime'] = time();

                $this->model->table('wechat_wall_msg')->data($data)->insert();
                //留言成功，跳转
                exit(json_encode(array('code'=>0, 'errMsg'=>'您的留言正在进行审查，请关注微信墙')));
            }
            $wall_id = I('get.wall_id');
            if (empty($wall_id)) {
                $this->redirect(url('index/index'));
            }
            /*if(isset($_GET['debug'])){
                $_SESSION['wechat_user']['openid'] = 'o1UgVuKGG67Y1Yoy_zC1JqoYSH54';
            }*/
            $openid = $_SESSION['wechat_user']['openid'];
            $wechat_user = $this->model->table('wechat_wall_user')->field('id')->where(array('openid'=>$openid))->find();
            //聊天室人数
            $user_num = $this->model->table('wechat_wall_msg')->field("COUNT(DISTINCT user_id)")->getOne();

            //初始缓存
            $cache_key = md5('cache_wechat_0');
            $Eccache = new Cache();
            $list = $Eccache->get($cache_key);
            if (!$list) {
                $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC LIMIT 0, 10";
                $data = $this->model->query($sql);
                
                if ($data) {
                    usort($data, function ($a, $b) {
                        if ($a['addtime'] == $b['addtime']) {
                            return 0;
                        }
                        return $a['addtime'] > $b['addtime'] ? 1 : -1;
                    });
                }

                $Eccache->set($cache_key, $data, 10);
                $list = $Eccache->get($cache_key);
            }
            //最后一条数据的key
            $sql = "SELECT count(*) as num FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime DESC";
            $num = $this->model->query($sql);

            $this->assign('list', $list);
            $this->assign('msg_count', $num[0]['num']);
            $this->assign('user_num', $user_num);
            $this->assign('user', $wechat_user);
            $this->assign('wall_id', $wall_id);
            $this->display('wall/wall_msg_wechat.dwt');
        }
    }

    /**
     * ajax请求留言
     */
    public function get_wall_msg()
    {
        if (IS_AJAX && IS_GET) {
            $start = I('get.start', 0, 'intval');
            $num = I('get.num', 5);
            $wall_id = I('get.wall_id');
            if ((!empty($start) || $start === 0) && $num) {
                $Eccache = new Cache();
                $cache_key = md5('cache_'.$start);
                //微信端数据单独存储
                if (isset($_SESSION['wechat_user']) && !empty($_SESSION['wechat_user']['openid'])) {
                    $cache_key = md5('cache_wechat_'.$start);
                }
                $list = $Eccache->get($cache_key);
                if (!$list) {
                    $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id, m.status FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE m.status = 1 AND u.wall_id = '$wall_id' ORDER BY m.addtime ASC LIMIT ".$start.", ".$num;
                    if (isset($_SESSION['wechat_user']) && !empty($_SESSION['wechat_user']['openid'])) {
                        $openid = $_SESSION['wechat_user']['openid'];
                        $sql = "SELECT m.content, m.addtime, u.nickname, u.headimg, u.id, m.status FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id WHERE (m.status = 1 OR u.openid = '$openid') AND u.wall_id = '$wall_id' ORDER BY m.addtime ASC LIMIT ".$start.", ".$num;
                    }
                    
                    $data = $this->model->query($sql);
                    $Eccache->set($cache_key, $data, 10);
                    $list = $Eccache->get($cache_key);
                }
                foreach ($list as $k=>$v) {
                    $list[$k]['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
                }
                $result = array('code'=>0, 'data'=>$list);
                exit(json_encode($result));
            }
        } else {
            $result = array('code'=>1, 'errMsg'=>'请求不合法');
            exit(json_encode($result));
        }
    }
}
