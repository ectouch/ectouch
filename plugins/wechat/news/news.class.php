<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：news.php
 * ----------------------------------------------------------------------------
 * 功能描述：微信通-新品查询
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

/**
 * 新品查询类
 *
 * @author wanglu
 *
 */
class news extends PluginWechatController
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
        $articles = array('type'=>'text', 'content'=>'暂无新品');
        $data = model('base')->model->table('goods')
            ->field('goods_id, goods_name, goods_img')
            ->where('is_new = 1 and is_on_sale = 1 and is_delete = 0 and last_update > (UNIX_TIMESTAMP(NOW()) - 3600*24*30)')
            ->order('last_update desc')
            ->limit(4)
            ->select();
        if (! empty($data)) {
            $articles = array();
            $articles['type'] = 'news';
            foreach ($data as $key => $val) {
                // 不是远程图片
                if (! preg_match('/(http:|https:)/is', $val['goods_img'])) {
                    $articles['content'][$key]['PicUrl'] = get_image_path('', $val['goods_img']);
                } else {
                    $articles['content'][$key]['PicUrl'] = $val['goods_img'];
                }
                $articles['content'][$key]['Title'] = $val['goods_name'];
                $articles['content'][$key]['Url'] = __HOST__ . url('goods/index', array(
                    'id' => $val['goods_id']
                ));
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
                $num = model('Base')->model->table('wechat_point')
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
