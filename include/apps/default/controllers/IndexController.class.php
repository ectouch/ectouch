<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch首页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexController extends CommonController
{

    /**
     * 首页信息
     */
    public function index()
    {
        $cache_id = sprintf('%X', crc32($_SESSION['user_rank'] . '-subscribe' . $_SESSION['subscribe'] . '-' . C('lang')));
        if (!ECTouch::view()->is_cached('index.dwt', $cache_id)) {
            // 自定义导航栏
            $navigator = model('Common')->get_navigator();
            $this->assign('navigator', $navigator['middle']);
            $this->assign('best_goods', model('Index')->goods_list('best', C('page_size')));
            $this->assign('new_goods', model('Index')->goods_list('new', C('page_size')));
            $this->assign('hot_goods', model('Index')->goods_list('hot', C('page_size')));
            // 调用促销商品
            $this->assign('promotion_goods', model('Index')->goods_list('promotion', C('page_size')));
            //首页推荐分类
            $cat_rec = model('Index')->get_recommend_res(10, 4);
            $this->assign('cat_best', $cat_rec[1]);
            $this->assign('cat_new', $cat_rec[2]);
            $this->assign('cat_hot', $cat_rec[3]);
            // 促销活动
            $this->assign('promotion_info', model('GoodsBase')->get_promotion_info());
            // 团购商品
            $this->assign('group_buy_goods', model('Groupbuy')->group_buy_list(C('page_size'), 1, 'goods_id', 'ASC'));
            // 获取分类
            $this->assign('categories', model('CategoryBase')->get_categories_tree());
            // 获取品牌
            $this->assign('brand_list', model('Brand')->get_brands($app = 'brand', C('page_size'), 1));
            // 分类下的文章
            $this->assign('cat_articles', model('Article')->assign_articles(1, 5)); // 1 是文章分类id ,5 是文章显示数量
        }
        // 关注按钮 是否显示
        $this->assign('subscribe', $_SESSION['subscribe']);
        $this->display('index.dwt', $cache_id);
    }

    /**
     * ajax获取商品
     */
    public function ajax_goods()
    {
        if (IS_AJAX) {
            $type = I('get.type');
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            $goods_list = model('Index')->goods_list($type, $limit, $start);
            $list = array();
            // 热卖商品
            if ($goods_list) {
                foreach ($goods_list as $key => $value) {
                    $value['iteration'] = $key + 1;
                    $this->assign('goods', $value);
                    $list [] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_index.lbi')
                    );
                }
            }
            echo json_encode($list);
            exit();
        } else {
            $this->redirect(url('index'));
        }
    }
}
