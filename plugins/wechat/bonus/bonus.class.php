<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：bonus.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-关注送红包
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

/**
 * 订单查询类
 *
 * @author wanglu
 *
 */
class bonus extends PluginWechatController
{
    // 插件名称
    protected $plugin_name = '';
    // 配置
    protected $cfg = array();

    /**
     * 构造方法
     *
     * @param unknown $cfg
     */
    public function __construct($cfg = array())
    {
        $name = basename(__FILE__, '.class.php');
        $this->plugin_name = $name;
        $this->cfg = $cfg;
    }

    /**
     * 安装
     */
    public function install()
    {
        //红包数据，线下发放类型
        $time = gmtime();
        $bonus = model('Base')->model->table('bonus_type')->field('type_id, type_name, type_money')->where('send_type = 3 AND send_end_date > "' . $time. '" ')->select();
        $this->cfg['bonus'] = $bonus;
        $this->plugin_display('install', $this->cfg);
    }

    /**
     * 获取数据
     */
    public function show($fromusername, $info)
    {
        $articles = array('type'=>'text', 'content'=>'');
        if (!empty($info)) {
            // 配置信息
            $config = array();
            $config = unserialize($info['config']);
            //开启红包赠送
            if (isset($config['bonus_status']) && $config['bonus_status'] == 1) {
                //用户第一次关注赠送红包并且设置了赠送的红包
                $uid = model('Base')->model->table('wechat_user')->field('ect_uid')->where('openid = "'.$fromusername.'"')->getOne();
                if (!empty($uid) && !empty($config['bonus'])) {
                    $time = gmtime();
                    $sql = "SELECT count(*) as num FROM {pre}user_bonus u LEFT JOIN {pre}bonus_type b ON u.bonus_type_id = b.type_id WHERE u.user_id = $uid AND b.send_type = 3 AND b.type_id = " . $config['bonus'] . " AND b.send_end_date > " .$time;
                    $bonus_num = model('Base')->model->query($sql);
                    if ($bonus_num[0]['num'] > 0) {
                        $articles['content'] = '红包已经赠送过了，不要重复领取哦！';
                    } else {
                        $data['bonus_type_id'] = $config['bonus'];
                        $data['bonus_sn'] = 0;
                        $data['user_id'] = $uid;
                        $data['used_time'] = 0;
                        $data['order_id'] = 0;
                        $data['emailed'] = 0;
                        model('Base')->model->table('user_bonus')->data($data)->insert();

                        $articles['content'] = '感谢您的关注，赠送您一个红包';
                        // 积分赠送
                        $this->give_point($fromusername, $info);
                    }
                }
            }
        }
        return $articles;
    }

    /**
     * 积分赠送
     *
     * @param unknown $fromusername
     * @param unknown $info
     */
    public function give_point($fromusername, $info)
    {
        if (! empty($info)) {
            // 配置信息
            $config = array();
            $config = unserialize($info['config']);
            // 开启积分赠送
            if (isset($config['point_status']) && $config['point_status'] == 1) {
                $where = 'openid = "' . $fromusername . '" and keywords = "' . $info['command'] . '" and createtime > (UNIX_TIMESTAMP(NOW())- ' . $config['point_interval'] . ')';
                $num = model('base')->model->table('wechat_point')
                    ->field('createtime')
                    ->where($where)
                    ->order('createtime desc')
                    ->count();
                // 当前时间减去时间间隔得到的历史时间之后赠送的次数
                if ($num < $config['point_num']) {
                    $this->do_point($fromusername, $info, $config['point_value']);
                }
            }
        }
    }

    /**
     * 行为操作
     */
    public function action()
    {
    }
}
