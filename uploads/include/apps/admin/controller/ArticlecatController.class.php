<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticlecatControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章分类管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticlecatController extends AdminController {

    /**
     * 文章列表
     */
    public function index() {
        $articlecat = model('ArticleBase')->article_cat_list(0, 0, false);
        foreach ($articlecat as $key => $cat) {
            $articlecat[$key]['type_name'] = L('type_name.' . $cat['cat_type']);
        }
        $this->assign('articlecat', $articlecat);
        $this->assign('ur_here', L('02_articlecat_list'));
        $this->display();
    }

    /**
     * 编辑文章
     */
    public function edit() {
        $id = I('cat_id');
        if (IS_POST) {
            $info = I('data');
            //更新数据库
            $data['is_mobile'] = $info['is_mobile'];
            $condition['cat_id'] = $id;
            //增加判断
            $touch_result = $this->model->table('touch_article_cat')->where('cat_id=' . $id)->find();
            if (empty($touch_result)) {
                $data['cat_id'] = $id;
                $this->model->table('touch_article_cat')->data($data)->insert();
            } else {
                 $this->model->table('touch_article_cat')->data($data)->where($condition)->update();
            }
            clear_all_files();
            $this->message(L('catedit_succed'), url('index'));
        }

        $cat = $this->model->table('article_cat')->field('cat_id , cat_name')->where(array('cat_id' => $id))->find();
        $touch_info = $this->model->table('touch_article_cat')->field('is_mobile')->where(array('cat_id' => $id))->find();
        $cat['is_mobile'] = $touch_info['is_mobile'];
        //print_r($cat);
        /* 模板赋值 */
        $this->assign('cat', $cat);
        $this->assign('ur_here', L('articlecat_edit'));
        $this->display();
    }

}
