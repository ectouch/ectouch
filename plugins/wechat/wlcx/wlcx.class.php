<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：wlcx.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-物流查询
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

/**
 * 物流查询类
 *
 * @author wanglu
 *
 */
class wlcx extends PluginWechatController
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
        $articles = array('type'=>'text', 'content'=>'暂无物流信息');
        $uid = model('Base')->model->table('wechat_user')->field('ect_uid')->where('openid = "'.$fromusername.'"')->getOne();
        if (!empty($uid)) {
            //订单ID
            $order = model('Base')->model->table('order_info')->field('order_id, order_sn, invoice_no, shipping_name, shipping_id, shipping_status')->where('user_id = '.$uid)->order('add_time desc')->find();
            if (! empty($order)) {
                //已发货
                if ($order['shipping_status'] > 0) {
                    $articles = array();
                    $articles['type'] = 'news';
                    $articles['content'][0]['Title'] = '物流信息';
                    $articles['content'][0]['Description'] = '快递公司：'. $order['shipping_name'] ."\r\n". '物流单号：' . $order['invoice_no'];
                    $articles['content'][0]['Url'] = __HOST__ . url('user/order_detail', array('order_id'=>$order['order_id']));
                }
            }
            // 积分赠送
            $this->give_point($fromusername, $info);
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
