<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：PluginWechatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通插件抽象控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/d ocs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

abstract class PluginWechatController
{
    private $layout = 'wechat_layout';
    private $_data = array();

    /**
     * 数据显示返回
     */
    abstract protected function show();

    /**
     * 积分赠送
     */
    abstract protected function give_point($fromusername, $info);

    /**
     * 行为处理
     */
    abstract protected function action();

    /**
     * 积分赠送处理
     */
    public function do_point($fromusername, $info, $point_value)
    {
        $time = time();
        $user_id = model('Base')->model->table('wechat_user')
            ->field('ecs_uid')
            ->where('openid = "' . $fromusername . '"')
            ->getOne();
        // 增加积分
        $point = 'rank_points = rank_points +' . intval($point_value);
        model('Base')->model->table('users')
            ->data($point)
            ->where('user_id = ' . $user_id)
            ->update();
        // 积分记录
        $data['user_id'] = $user_id;
        $data['user_money'] = 0;
        $data['frozen_money'] = 0;
        $data['rank_points'] = $point_value;
        $data['pay_points'] = 0;
        $data['change_time'] = $time;
        $data['change_desc'] = $info['name'] . '积分赠送';
        $data['change_type'] = ACT_OTHER;
        
        $log_id = model('Base')->model->table('account_log')
            ->data($data)
            ->insert();
        // 从表记录
        $data1['log_id'] = $log_id;
        $data1['openid'] = $fromusername;
        $data1['keywords'] = $info['keywords'];
        $data1['createtime'] = $time;
        $log_id = model('Base')->model->table('wechat_point')
            ->data($data1)
            ->insert();
    }

    public function plugin_display($tpl = '', $config = array())
    {
        $view = new EcTemplate(C('TPL'));
        $this->_data['config'] = $config;
        //插件视图目录
        $view->config['TPL_TEMPLATE_PATH'] = ADDONS_PATH . 'wechat/'.$this->plugin_name.'/view/';
        $view->assign($this->_data);
        $this->template_content = $view->display($tpl, true);
        //layout目录
        $view->config['TPL_TEMPLATE_PATH'] = BASE_PATH . 'apps/' . C('_APP_NAME') . '/view/';
        $tpl = $this->layout;
        $view->assign($this->_data);
        return $view->display($tpl);
    }
    
    
    public function __get($name) {
        return isset($this->_data [$name]) ? $this->_data [$name] : NULL;
    }
    
    public function __set($name, $value) {
        $this->_data [$name] = $value;
    }
}
