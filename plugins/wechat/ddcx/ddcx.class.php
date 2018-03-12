<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ddcx.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-订单查询
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
class ddcx extends PluginWechatController
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
        $articles = array('type'=>'text', 'content'=>'暂无订单信息');
        $uid = model('Base')->model->table('wechat_user')->field('ect_uid')->where('openid = "'.$fromusername.'"')->getOne();
        if (!empty($uid)) {
            //订单ID
            $order_id = model('Base')->model->table('order_info')->field('order_id')->where('user_id = '.$uid)->order('add_time desc')->getOne();
            if (! empty($order_id)) {
                //订单信息
                $order = model('Order')->order_info($order_id);
                //订单商品
                $order_goods = model('Order')->order_goods($order_id);
                $goods = '';
                if (!empty($order_goods)) {
                    foreach ($order_goods as $key=>$val) {
                        $goods_attr = !empty($val['goods_attr']) ? '(' . $val['goods_attr'] .')' : '';
                        $goods_number= !empty($val['goods_number']) ? '(' . $val['goods_number'] .'),' : '';
                        $goods .= $val['goods_name'] . $goods_attr . $goods_number;
                    }
                    $goods = substr($goods, 0, -1);
                }
                if (file_exists(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/user.php')) {
                    require(APP_PATH . C('_APP_NAME') . '/languages/' . C('LANG') . '/user.php');
                }
                L($_LANG);
                $order['order_status'] = L('os.' . $order['order_status']) . ',';
                $order['pay_status'] = L('ps.' . $order['pay_status']) . ',';
                $order['shipping_status'] = L('ss.' . $order['shipping_status']) . ',';

                $articles = array();
                $articles['type'] = 'news';
                $articles['content'][0]['Title'] = '订单号：'.$order['order_sn'];
                $articles['content'][0]['Description'] = '商品信息：'. $goods ."\r\n". '总金额：'. $order['total_fee'] ."\r\n". '支付状态：'. $order['order_status'] . $order['pay_status'] . $order['shipping_status'] ."\r\n". '快递公司：'. $order['shipping_name'] ."\r\n". '物流单号：' . $order['invoice_no'];
                $articles['content'][0]['Url'] = __HOST__ . url('user/order_detail', array('order_id'=>$order['order_id']));
                // 积分赠送
                $this->give_point($fromusername, $info);
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
