<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TopicControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：专题页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class TopicController extends CommonController {

    protected $id;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->id = isset($_REQUEST ['id']) ? intval($_REQUEST ['id']) : 0;
    }

    /**
     * 专题详情
     */
    public function index() {
        $topic = $this->model->table('touch_topic')->field('*')->where('topic_id =' . $this->id)->find();
        $topic['intro'] = html_out($topic['intro']);
        $topic['data'] = addcslashes($topic['data'], "'");
        $tmp = @unserialize($topic["data"]);
        $arr = (array) $tmp;

        $goods_id = array();

        foreach ($arr AS $key => $value) {
            foreach ($value AS $k => $val) {
                $opt = explode('|', $val);
                $arr[$key][$k] = $opt[1];
                $goods_id[] = $opt[1];
            }
        }

        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " .
                'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' .
                'FROM ' . $this->model->pre . 'goods AS g ' .
                'LEFT JOIN ' . $this->model->pre . 'member_price AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE " . db_create_in($goods_id, 'g.goods_id');

        $res = $this->model->query($sql);

        $sort_goods_arr = array();

        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $row['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $row['promote_price'] = '';
            }

            if ($row['shop_price'] > 0) {
                $row['shop_price'] = price_format($row['shop_price']);
            } else {
                $row['shop_price'] = '';
            }

            $row['url'] = url('goods/index', array('id' => $row['goods_id']));
            $row['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $row['short_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            $row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $row['short_style_name'] = add_style($row['short_name'], $row['goods_name_style']);
            $row['sales_count'] = model('GoodsBase')->get_sales_count($row['goods_id']);
            $row['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $row['mysc'] = 0;
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION['user_id']) {
                unset($where);
                // 用户自己有没有收藏过
                $where['goods_id'] = $row['goods_id'];
                $where['user_id'] = $_SESSION['user_id'];
                $rs = $this->model->table('collect_goods')
                        ->where($where)
                        ->count();
                $row['mysc'] = $rs;
            }
            $row['promotion'] = model('GoodsBase')->get_promotion_show($row['goods_id']);
            $row['comment_count'] = model('Comment')->get_goods_comment($row['goods_id'], 0);  //商品总评论数量
            $row['favorable_count'] = model('Comment')->favorable_comment($row['goods_id'], 0);  //获得商品好评数量
            foreach ($arr AS $key => $value) {
                foreach ($value AS $val) {
                    if ($val == $row['goods_id']) {
                        $key = $key == 'default' ? L('all_goods') : $key;
                        $sort_goods_arr[$key][] = $row;
                    }
                }
            }
        }
        $this->assign('sort_goods_arr', $sort_goods_arr);          // 商品列表
        $this->assign('topic', $topic);                   // 专题信息
        $this->assign('tile', $topic['title']);
        $this->assign('show_asynclist', C('show_asynclist'));
        $templates = empty($topic['template']) ? 'topic.dwt' : $topic['template'];
        $this->display($templates);
    }

}
