<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：sign.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-签到送积分
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

/**
 * 热卖
 *
 * @author wanglu
 *
 */
class sign extends PluginWechatController
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
        $this->plugin_display('install', $this->cfg);
    }

    /**
     * 获取数据
     */
    public function show($fromusername, $info)
    {
        $articles = array('type'=>'text', 'content'=>'签到失败');
        // 配置信息
        $config = array();
        $config = unserialize($info['config']);
        if (isset($config['point_status']) && $config['point_status'] == 1) {
            // 签到次数
            $where = 'openid = "' . $fromusername . '" and keywords = "' . $info['command'] . '" and createtime > (UNIX_TIMESTAMP(NOW())- ' . $config['point_interval'] . ')';
            $num = model('Base')->model->table('wechat_point')
                ->field('createtime')
                ->where($where)
                ->order('createtime desc')
                ->count();
            // 当前时间减去时间间隔得到的历史时间之后赠送的次数
            if ($num < $config['point_num']) {
                // 积分赠送
                $this->give_point($fromusername, $info);
                $articles['content'] = '签到成功';
            } else {
                $articles['content'] = '签到次数已用完';
            }
        } else {
            $articles['content'] = '未启用签到送积分';
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
                $num = model('Base')->model->table('wechat_point')
                    ->field('createtime')
                    ->where($where)
                    ->order('createtime desc')
                    ->count();
                // 当前时间减去时间间隔得到的历史时间之后赠送的次数
                if ($num < $config['point_num']) {
                    $this->do_point($fromusername, $info, $config['rank_point_value'], $config['pay_point_value']);
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
