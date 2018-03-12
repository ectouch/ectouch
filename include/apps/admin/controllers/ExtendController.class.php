<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ExtendController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信公众平台扩展
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
class ExtendController extends AdminController
{
    public $plugin_type = 'wechat';

    public $plugin_name = '';
    public $wechat_type = '';
    private $wechat_id  = 0;

    public function __construct()
    {
        parent::__construct();
        $this->plugin_name = I('get.ks');
        $this->assign('action', ACTION_NAME);
        $this->assign('controller', CONTROLLER_NAME);
        //公众号类型
        $this->wechat_id = 1; // $this->wechat_id;
        $this->wechat_type = $this->model->table('wechat')->field('type')->where('id='.$this->wechat_id)->getOne();
        $this->assign('type', $this->wechat_type);
    }

    /**
     * 功能扩展
     */
    public function index()
    {
        // 数据库中的数据
        $extends = $this->model->table('wechat_extend')
            ->field('name, keywords, command, config, enable, author, website')
            ->where('type = "function" and enable = 1 and wechat_id = ' . $this->wechat_id)
            ->order('id asc')
            ->select();
        if (! empty($extends)) {
            $kw = array();
            foreach ($extends as $key => $val) {
                $val['config'] = unserialize($val['config']);
                $kw[$val['command']] = $val;
            }
        }
        
        $modules = $this->read_wechat();
        if (! empty($modules)) {
            foreach ($modules as $k => $v) {
                $ks = $v['command'];
                // 数据库中存在，用数据库的数据
                if (isset($kw[$v['command']])) {
                    $modules[$k]['keywords'] = $kw[$ks]['keywords'];
                    $modules[$k]['config'] = $kw[$ks]['config'];
                    $modules[$k]['enable'] = $kw[$ks]['enable'];
                }
                if ($this->wechat_type == 0 || $this->wechat_type == 1) {
                    if ($modules[$k]['command'] == 'bd'  || $modules[$k]['command'] == 'bonus' || $modules[$k]['command'] == 'ddcx' || $modules[$k]['command'] == 'jfcx' || $modules[$k]['command'] == 'sign' || $modules[$k]['command'] == 'wlcx'  || $modules[$k]['command'] == 'zjd' || $modules[$k]['command'] == 'dzp' || $modules[$k]['command'] == 'ggk') {
                        unset($modules[$k]);
                    }
                }
            }
        }
        $this->assign('modules', $modules);
        $this->display();
    }

    /**
     * 功能扩展安装/编辑
     */
    public function edit()
    {
        if (IS_POST) {
            $handler = I('post.handler');
            $cfg_value = I('post.cfg_value');
            $data = I('post.data');
            if (empty($data['keywords'])) {
                $this->message('请填写扩展词', null, 'error');
            }

            $data['type'] = 'function';
            $data['wechat_id'] = $this->wechat_id;
            // 数据库是否存在该数据
            $rs = $this->model->table('wechat_extend')
                ->field('name, config, enable')
                ->where('command = "' . $data['command'] . '" and wechat_id = ' . $this->wechat_id)
                ->find();
            if (! empty($rs)) {
                // 已安装
                if (empty($handler) && !empty($rs['enable'])) {
                    $this->message('插件已安装', null, 'error');
                } else {
                    //缺少素材
                    if (empty($cfg_value['media_id'])) {
                        $media_id = $this->model->table('wechat_media')->field('id')->where('command = "'.$this->plugin_name.'"')->getOne();
                        if ($media_id) {
                            $cfg_value['media_id'] = $media_id;
                        } else {
                            //安装sql(暂时只提供素材数据表)
                            $sql_file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/install.sql';
                            if (file_exists($sql_file)) {
                                //添加素材
                                $sql = file_get_contents($sql_file);
                                $sql = str_replace(array('ecs_wechat_media', '(0', 'http://', 'view/images'), array($this->model->pre.'wechat_media', '('.$this->wechat_id, __HOST__.url('default/wechat/plugin_show', array('name'=>$this->plugin_name)), 'plugins/'. $this->plugin_type . '/' . $this->plugin_name.'/view/images'), $sql);
                                $this->model->query($sql);
                                //获取素材id
                                $cfg_value['media_id'] = $this->model->table('wechat_media')->field('id')->where('command = "'.$this->plugin_name.'"')->getOne();
                            }
                        }
                    }
                    $data['config'] = serialize($cfg_value);
                    $data['enable'] = 1;
                    $this->model->table('wechat_extend')
                        ->data($data)
                        ->where('command = "' . $data['command'] . '" and wechat_id = ' . $this->wechat_id)
                        ->update();
                }
            } else {
                //安装sql(暂时只提供素材数据表)
                $sql_file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/install.sql';
                if (file_exists($sql_file)) {
                    //添加素材
                    $sql = file_get_contents($sql_file);
                    $sql = str_replace(array('ecs_wechat_media', '(0', 'http://', 'view/images'), array($this->model->pre.'wechat_media', '('.$this->wechat_id, __HOST__.url('default/wechat/plugin_show', array('name'=>$this->plugin_name)), 'plugins/'. $this->plugin_type . '/' . $this->plugin_name.'/view/images'), $sql);
                    $this->model->query($sql);
                    //获取素材id
                    $cfg_value['media_id'] = $this->model->table('wechat_media')->field('id')->where('command = "'.$this->plugin_name.'"')->getOne();
                }
                $data['config'] = serialize($cfg_value);
                $data['enable'] = 1;
                $this->model->table('wechat_extend')
                    ->data($data)
                    ->insert();
            }
            $this->message('安装编辑成功', url('index'));
        }
        $handler = I('get.handler');
        // 编辑操作
        if (! empty($handler)) {
            // 获取配置信息
            $info = $this->model->table('wechat_extend')
                ->field('name, keywords, command, config, enable, author, website')
                ->where('command = "' . $this->plugin_name . '" and enable = 1 and wechat_id = ' . $this->wechat_id)
                ->find();
            // 修改页面显示
            if (empty($info)) {
                $this->message('请选择要编辑的功能扩展', null, 'error');
            }
            $info['config'] = unserialize($info['config']);
        }

        // 插件文件
        $file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/' . $this->plugin_name . '.class.php';
        // 插件配置
        $config_file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/config.php';
        if (file_exists($file)) {
            require_once($file);
            //编辑
            if (!empty($info['config'])) {
                $config = $info;
                $config['handler'] = 'edit';
            } else {
                $config = require_once($config_file);
            }
            
            if (! is_array($config)) {
                $config = array();
            }
            $obj = new $this->plugin_name($config);
            $obj->install();
        }
    }


    /**
     * 功能扩展卸载
     */
    public function uninstall()
    {
        $keywords = I('get.ks');
        if (empty($keywords)) {
            $this->message('请选择要卸载的功能扩展', null, 'error');
        }
        $config = $this->model->table('wechat_extend')
            ->field('enable')
            ->where('command = "' . $keywords . '" and wechat_id = ' . $this->wechat_id)
            ->getOne();
        $data['enable'] = 0;
        
        $this->model->table('wechat_extend')
            ->data($data)
            ->where('command = "' . $keywords . '" and wechat_id = ' . $this->wechat_id)
            ->update();
        //删除素材
        $media_count = $this->model->table('wechat_media')->where('command = "'.$keywords.'"')->count();
        if ($media_count > 0) {
            $this->model->table('wechat_media')->where('command = "'.$keywords.'"')->delete();
        }
        
        $this->message('卸载成功', url('index'));
    }

    /**
     * 微信墙
     */
    public function wall()
    {
        /*$list = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support, status')->select();*/
        $sql = "SELECT w.*, count(DISTINCT u.id) as user_count, count(m.id) as msg_count  FROM ".$this->model->pre."wechat_wall w LEFT JOIN ".$this->model->pre."wechat_wall_user u ON w.id = u.wall_id LEFT JOIN ".$this->model->pre."wechat_wall_msg m ON u.id = m.user_id ORDER BY w.id DESC";
        $list = $this->model->query($sql);

        if ($list[0]['id']) {
            foreach ($list as $k=>$v) {
                $list[$k]['starttime'] = date('Y-m-d H:i', $v['starttime']);
                $list[$k]['endtime'] = date('Y-m-d H:i', $v['endtime']);
                $list[$k]['prize'] = unserialize($v['prize']);
                if ($v['status'] == 0) {
                    $list[$k]['status'] = '未开始';
                } elseif ($v['status'] == 1) {
                    $list[$k]['status'] = '进行中';
                } elseif ($v['status'] == 2) {
                    $list[$k]['status'] = '已结束';
                }
            }
        } else {
            $list = array();
        }

        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 微信墙活动编辑
     */
    public function wall_edit()
    {
        if (IS_POST) {
            $id = I('post.id');
            $data = I('post.data');
            $prize = I('post.prize');
            if (!$data['name']) {
                $this->message('活动名称不能为空', null, 'error');
            }
            $data['starttime'] = strtotime($data['starttime']);
            $data['endtime'] = strtotime($data['endtime']);
            if ($data['starttime'] > time()) {
                $data['status'] = 0; //未开始
            } elseif ($data['starttime'] < time() && $data['endtime'] > time()) {
                $data['status'] = 1; //进行中
            } elseif ($data['endtime'] < time()) {
                $data['status'] = 2; //已结束
            }
            //上传logo
            if ($_FILES['logo']['name']) {
                $logo = $this->ectouchUpload('logo', 'wall');
                if ($logo['error'] > 0) {
                    $this->message($logo['message'], null, 'error');
                }
                $data['logo'] = substr($logo['message']['logo']['savepath'], 2).$logo['message']['logo']['savename'];
            }

            //上传背景图片
            if ($_FILES['background']['name']) {
                $background = $this->ectouchUpload('background', 'wall');
                if ($background['error'] > 0) {
                    $this->message($background['message'], null, 'error');
                }
                $data['background'] = substr($background['message']['background']['savepath'], 2).$background['message']['background']['savename'];
            }

            //奖项
            if ($prize) {
                // 奖品处理
                if (is_array($prize['prize_level']) && is_array($prize['prize_count']) && is_array($prize['prize_name'])) {
                    foreach ($prize['prize_level'] as $key => $val) {
                        $prize_arr[] = array(
                            'prize_level' => $val,
                            'prize_name' => $prize['prize_name'][$key],
                            'prize_count' => $prize['prize_count'][$key]
                        );
                    }
                }
                $data['prize'] = serialize($prize_arr);
            }
            //更新
            if ($id) {
                $this->model->table('wechat_wall')->data($data)->where(array('id'=>$id))->update();

                $this->redirect(url('wall'));
            }
            //添加
            $this->model->table('wechat_wall')->data($data)->insert();
            $this->redirect(url('wall'));
        }

        $id = I('get.id');
        if ($id) {
            $wall = $this->model->table('wechat_wall')->field('id, name, logo, background, starttime, endtime, prize, content, support, status')->where(array('id'=>$id))->find();
            if ($wall) {
                $wall['starttime'] = date('Y-m-d H:i', $wall['starttime']);
                $wall['endtime'] = date('Y-m-d H:i', $wall['endtime']);
                $wall['prize_arr'] = unserialize($wall['prize']);
            }
            $this->assign('wall', $wall);
        }

        $this->display();
    }

    /**
     * 删除微信墙活动
     */
    public function wall_del()
    {
        $id = I('get.id');
        if (!$id) {
            $this->message('请选择要删除的活动', null, 'error');
        }

        $this->model->table('wechat_wall')->where(array('id'=>$id))->delete();

        $this->redirect(url('wall'));
    }

    /**
     * 微信墙数据
     */
    public function wall_user()
    {
        $id = I('get.id');
        if (!$id) {
            $this->message('请选择要查看的活动', null, 'error');
        }

        //分页
        $filter['page'] = '{page}';
        $filter['id'] = $id;
        $offset = $this->pageLimit(url('wall_user', $filter), 12);
        $total = $this->model->table('wechat_wall_user')->where(array('wall_id'=>$id))->count();
        $this->assign('page', $this->pageShow($total));

        //$list = $this->model->table('wechat_wall_user')->field('id, nickname, sex, headimg, status, addtime')->where(array('wall_id'=>$id))->order('addtime desc, id desc')->limit($offset)->select();
        $sql = "SELECT id, nickname, sex, headimg, status, addtime FROM ".$this->model->pre."wechat_wall_user ORDER BY addtime DESC limit $offset";
        $list = $this->model->query($sql);
        if ($list[0]['id']) {
            foreach ($list as $k=>$v) {
                if ($v['sex'] == 0) {
                    $list[$k]['sex'] = '女';
                } elseif ($v['sex'] == 1) {
                    $list[$k]['sex'] = '男';
                } else {
                    $list[$k]['sex'] = '保密';
                }
                if ($v['status'] == 1) {
                    $list[$k]['status'] = '已审核';
                    $list[$k]['handler'] = '';
                } else {
                    $list[$k]['status'] = '未审核';
                    $list[$k]['handler'] = '<a class="btn btn-primary" href="'.url('wall_check', array('wall_id'=>$id, 'user_id'=>$v['id'])).'">审核</a>';
                }
                $list[$k]['nocheck'] = $this->model->table('wechat_wall_msg')->where(array('status'=>0, 'user_id'=>$v['id']))->count();
                $list[$k]['addtime'] = $v['addtime'] ? date('Y-m-d H:i', $v['addtime']) : '';
            }
        } else {
            $list = array();
        }

        $this->assign('wall_id', $id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 中奖名单
     */
    public function wall_prize()
    {
        $id = I('get.id');
        if (!$id) {
            $this->message('请选择要查看的活动', null, 'error');
        }

        //分页
        $filter['page'] = '{page}';
        $filter['id'] = $id;
        $offset = $this->pageLimit(url('wall_prize', $filter), 12);
        $sql = 'SELECT count(*) as count FROM '.$this->model->pre.'wechat_prize p LEFT JOIN '.$this->model->pre.'wechat_user u ON p.openid = u.openid WHERE p.activity_type = "wall" ORDER BY dateline desc';
        $count = $this->model->query($sql);
        $total = $count[0]['count'];
        $this->assign('page', $this->pageShow($total));

        $sql = 'SELECT p.id, p.prize_name, p.issue_status, p.winner, p.dateline, p.openid, u.nickname FROM '.$this->model->pre.'wechat_prize p LEFT JOIN '.$this->model->pre.'wechat_user u ON p.openid = u.openid WHERE p.activity_type = "wall" ORDER BY dateline desc limit '.$offset;
        $list = $this->model->query($sql);

        if ($list) {
            foreach ($list as $k=>$v) {
                $list[$k]['dateline'] = $v['dateline'] ? date('Y-m-d H:i:s', $v['dateline']) : '';
                $list[$k]['winner'] = unserialize($v['winner']);
                if ($v['issue_status'] == 1) {
                    $list[$k]['issue_status'] = '已发放';
                    $list[$k]['handler'] = '<a href="'.url('winner_issue', array('id'=>$v['id'], 'cancel'=>1)).'" class="btn btn-primary">取消发放</a>';
                } else {
                    $list[$k]['issue_status'] = '未发放';
                    $list[$k]['handler'] = '<a href="'.url('winner_issue', array('id'=>$v['id'])).'" class="btn btn-primary">立即发放</a>';
                }
            }
        }

        $this->assign('wall_id', $id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 审核以及未审核消息记录
     */
    public function wall_msg_check()
    {
        $wall_id = I('get.id');
        if (empty($wall_id)) {
            $this->message('请选择需要查看的数据', null, 'error');
        }
        $status = I('get.status');
        $where = "";
        if (empty($status)) {
            $where = " AND m.status = 0";
        }

        $sql = "SELECT COUNT(*) as num FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id LEFT JOIN ".$this->model->pre."wechat_wall w ON u.wall_id = w.id WHERE w.id = ".$wall_id.$where;
        $num  = $this->model->query($sql);
        //分页
        $filter['page'] = '{page}';
        $filter['id'] = $wall_id;
        $filter['status'] = $status;
        $offset = $this->pageLimit(url('wall_msg_check', $filter), 12);
        $total = $num[0]['num'];
        $this->assign('page', $this->pageShow($total));

        $sql = "SELECT m.id, m.user_id, m.content, m.addtime, m.checktime, m.status, u.nickname FROM ".$this->model->pre."wechat_wall_msg m LEFT JOIN ".$this->model->pre."wechat_wall_user u ON m.user_id = u.id LEFT JOIN ".$this->model->pre."wechat_wall w ON u.wall_id = w.id WHERE w.id = ".$wall_id.$where ." ORDER BY m.addtime ASC LIMIT $offset";
        $list =  $this->model->query($sql);
        if ($list) {
            foreach ($list as $k=>$v) {
                if ($v['status'] == 1) {
                    $list[$k]['status'] = '已审核';
                    $list[$k]['handler'] = '';
                } else {
                    $list[$k]['status'] = '未审核';
                    $list[$k]['handler'] = '<a class="btn btn-primary" href="'.url('wall_check', array('wall_id'=>$wall_id, 'msg_id'=>$v['id'], 'user_id'=>$v['user_id'], 'status'=>$status)).'">审核</a>';
                }
                $list[$k]['addtime'] = $v['addtime'] ? date('Y-m-d H:i', $v['addtime']) : '';
                $list[$k]['checktime'] = $v['checktime'] ? date('Y-m-d H:i', $v['checktime']) : '';
            }
        }

        $this->assign('status', $status);
        $this->assign('wall_id', $wall_id);
        $this->assign('list', $list);
        $this->display('extend_wall_msg_check');
    }

    /**
     * 用户留言记录
     */
    public function wall_msg()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        if (empty($wall_id) || empty($user_id)) {
            $this->message('请选择需要查看的数据', null, 'error');
        }
        //分页
        $filter['page'] = '{page}';
        $filter['wall_id'] = $wall_id;
        $filter['user_id'] = $user_id;
        $offset = $this->pageLimit(url('wall_msg', $filter), 12);
        $total = $this->model->table('wechat_wall_msg')->where(array('user_id'=>$user_id))->count();
        $this->assign('page', $this->pageShow($total));

        $list = $this->model->table('wechat_wall_msg')->field('id, content, addtime, checktime, status')->where(array('user_id'=>$user_id))->order('addtime asc, checktime asc')->limit($offset)->select();
        if ($list) {
            foreach ($list as $k=>$v) {
                if ($v['status'] == 1) {
                    $list[$k]['status'] = '已审核';
                    $list[$k]['handler'] = '';
                } else {
                    $list[$k]['status'] = '未审核';
                    $list[$k]['handler'] = '<a class="btn btn-primary" href="'.url('wall_check', array('wall_id'=>$wall_id, 'msg_id'=>$v['id'], 'user_id'=>$user_id)).'">审核</a>';
                }
                $list[$k]['addtime'] = $v['addtime'] ? date('Y-m-d H:i', $v['addtime']) : '';
                $list[$k]['checktime'] = $v['checktime'] ? date('Y-m-d H:i', $v['checktime']) : '';
            }
        }

        $this->assign('wall_id', $wall_id);
        $this->assign('user_id', $user_id);
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 审核处理
     */
    public function wall_check()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        $msg_id = I('get.msg_id');

        if (empty($user_id) || empty($wall_id)) {
            $this->message('请选择需要审核的数据', null, 'error');
        }
        //用户审核
        if (!empty($wall_id) && !empty($user_id) && empty($msg_id)) {
            $this->model->table('wechat_wall_user')->data(array('status'=>1, 'checktime'=>time()))->where(array('wall_id'=>$wall_id, 'id'=>$user_id, 'status'=>0))->update();

            $this->redirect(url('wall_user', array('id'=>$wall_id)));
        }

        //留言审核
        if (!empty($user_id) && !empty($msg_id)) {
            $this->model->table('wechat_wall_msg')->data(array('status'=>1, 'checktime'=>time()))->where(array('user_id'=>$user_id, 'id'=>$msg_id, 'status'=>0))->update();
            //审核用户
            $this->model->table('wechat_wall_user')->data(array('status'=>1, 'checktime'=>time()))->where(array('id'=>$user_id, 'status'=>0))->update();
            if (isset($_GET['status'])) {
                $status = I('get.status');
                $this->redirect(url('wall_msg_check', array('id'=>$wall_id, 'status'=>$status)));
            }

            $this->redirect(url('wall_msg', array('wall_id'=>$wall_id, 'user_id'=>$user_id)));
        }

        $this->redirect(url('wall'));
    }

    /**
     * 数据删除
     */
    public function wall_data_del()
    {
        $wall_id = I('get.wall_id');
        $user_id = I('get.user_id');
        $msg_id = I('get.msg_id');

        if (empty($user_id) || empty($wall_id)) {
            $this->message('请选择需要删除的数据', null, 'error');
        }

        //用户删除
        if (!empty($wall_id) && !empty($user_id) && empty($msg_id)) {
            $this->model->table('wechat_wall_user')->where(array('wall_id'=>$wall_id, 'id'=>$user_id))->delete();
            $this->model->table('wechat_wall_msg')->where(array('user_id'=>$user_id))->delete();
            $this->redirect(url('wall_user', array('id'=>$wall_id)));
        }

        //留言删除
        if (!empty($user_id) && !empty($msg_id)) {
            $this->model->table('wechat_wall_msg')->where(array('user_id'=>$user_id, 'id'=>$msg_id))->delete();

            if (isset($_GET['status'])) {
                $status = I('get.status');
                $this->redirect(url('wall_msg_check', array('id'=>$wall_id, 'status'=>$status)));
            }
            
            $this->redirect(url('wall_msg', array('wall_id'=>$wall_id, 'user_id'=>$user_id)));
        }
        $this->redirect(url('wall'));
    }

    /**
     * 上墙地址（微信二维码生成链接）
     */
    public function towall()
    {
        $wall_id = I('get.id');
        if (empty($wall_id)) {
            exit(json_encode(array(
                'status' => 0,
                'msg' => '请选择微信墙'
            )));
        }
        $url = __HOST__.url('default/wall/wall_user_wechat', array('wall_id'=>$wall_id));
        //$this->assign('content', $url);
        /* $wall_qrcode = $this->model->table('wechat_wall')->field('qrcode')->where(array('id'=>$wall_id))->getOne();
         if($wall_qrcode){
             $qrcode_url = $wall_qrcode;
         }
         else{
             $url = __HOST__.url('default/wall/wall_user_wechat', array('wall_id'=>$wall_id));
             $wxconfig = $this->model->table('wechat')->field('token, appid, appsecret')->where(array('id'=>$this->wechat_id, 'status'=>1))->find();
             if($wxconfig){
                 $wxObj  = new Wechat($wxconfig);
                 $shorturl = $wxObj->getShortUrl($url);
                 if(empty($shorturl)){
                     exit(json_encode(array(
                         'status' => 0,
                         'msg' => $wxObj->errMsg
                     )));
                 }
                 $ticket = $wxObj->getQRCode($shorturl, 2);
                 if(empty($ticket)){
                     exit(json_encode(array(
                         'status' => 0,
                         'msg' => $wxObj->errMsg
                     )));
                 }
                 $qrcode = $wxObj->getQRUrl($ticket['ticket']);
                 if(empty($qrcode)){
                     exit(json_encode(array(
                         'status' => 0,
                         'msg' => $wxObj->errMsg
                     )));
                 }
                 $this->model->table('wechat_wall')->data(array('qrcode'=>$qrcode))->where(array('id'=>$wall_id))->update();

                 $qrcode_url = $wall_qrcode;
              }
             else{
                 exit(json_encode(array(
                     'status' => 0,
                     'msg' => '请先配置公众号'
                 )));
             }
         }*/
        //二维码内容
        // 纠错级别：L、M、Q、H
        $errorCorrectionLevel = 'L';
        // 点的大小：1到10
        $matrixPointSize = 3;
        // 生成的文件名
        $tmp = ROOT_PATH .'data/attached/wall/';
        if (!is_dir($tmp)) {
            @mkdir($tmp);
        }
        $filename = $tmp . $errorCorrectionLevel . $matrixPointSize . $wall_id. '.png';
        QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $qrcode_url = __URL__.'/data/attached/wall/'.basename($filename).'?t='.time();
        $this->assign('qrcode_url', $qrcode_url);
        $this->display('wechat_qrcode_get');
    }

    
    /**
     * 获取中奖记录
     */
    public function winner_list()
    {
        $ks = I('get.ks');
        if (empty($ks)) {
            $this->message('请选择插件', null, 'error');
        }
        $sql = 'SELECT p.id, p.prize_name, p.issue_status, p.winner, p.dateline, p.openid, u.nickname FROM '.$this->model->pre.'wechat_prize p LEFT JOIN '.$this->model->pre.'wechat_user u ON p.openid = u.openid WHERE p.activity_type = "'.$ks.'" and p.prize_type = 1 ORDER BY dateline desc';
        $list = $this->model->query($sql);
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key=>$val) {
            $list[$key]['winner'] = unserialize($val['winner']);
        }

        $this->assign('list', $list);
        $this->display();
    }
    
    /**
     * 发放奖品
     */
    public function winner_issue()
    {
        $id = I('get.id');
        $cancel = I('get.cancel');
        if (empty($id)) {
            $this->message('请选择中奖记录', null, 'error');
        }
        if (!empty($cancel)) {
            $data['issue_status'] = 0;
            $this->model->table('wechat_prize')->data($data)->where('id = '.$id)->update();
            
            $this->message('取消成功');
        } else {
            $data['issue_status'] = 1;
            $this->model->table('wechat_prize')->data($data)->where('id = '.$id)->update();
            
            $this->message('发放成功');
        }
    }
    
    /**
     * 删除记录
     */
    public function winner_del()
    {
        $id = I('get.id');
        if (empty($id)) {
            $this->message('请选择中奖记录', null, 'error');
        }
        $this->model->table('wechat_prize')->where('id = '.$id)->delete();
        
        $this->message('删除成功');
    }
    
    

    /**
     * 获取插件配置
     *
     * @return multitype:
     */
    private function read_wechat()
    {
        $modules = glob(ROOT_PATH . 'plugins/wechat/*/config.php');
        foreach ($modules as $file) {
            $config[] = require_once($file);
        }
        return $config;
    }
}
