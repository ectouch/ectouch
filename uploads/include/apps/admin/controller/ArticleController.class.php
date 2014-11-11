<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticlecatController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章列表管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticleController extends AdminController {

    /**
     * 文章列表
     */
    public function index() {
        $articlecat = model('ArticleBase')->article_cat_list(0, 0, false);
        foreach ($articlecat as $key => $cat) {
            $articlecat[$key]['type_name'] = L('type_name.' . $cat['cat_type']);
        }
        $article_list = model('ArticleBase')->get_articleslist();
        $this->assign('article_list', $article_list['arr']);
        $this->assign('filter', $article_list['filter']);
        $this->assign('record_count', $article_list['record_count']);
        $this->assign('page_count', $article_list['page_count']);
        $this->assign('ur_here', L('02_articlecat_list'));
        $this->assign('action_link', array('text' => L('article_add'), 'href' => url('add')));
        $this->display();
    }

    /**
     * 添加文章分类
     */
    public function add() {
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['title']), L('no_title')),
                        array(Check::must($data['cat_id']), L('cat')),
                        array(Check::must($data['is_open']), L('is_open')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $data['add_time'] = gmtime();
            ;
            $this->model->table('touch_article')->data($data)->insert();
            clear_all_files();
            $this->message(L('brandadd_succed'), url('index'));
        }
        /* 模板赋值 */
        $this->assign('cat_select', model('ArticleBase')->article_cat_list(0));
        $this->assign('ur_here', L('article_add'));
        $this->assign('action_link', array('text' => L('06_goods_brand_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 编辑文章
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            $data['content'] = I('post.content');
            //更新数据库
            $touch_result = $this->model->table('touch_article')->where('article_id=' . $id)->find();
            if (!empty($touch_result)) {
                $this->model->table('touch_article')->data($data)->where('article_id=' . $id)->update();
            }
            clear_all_files();
            $this->message(L('articleedit_succeed'), url('index'));
        }
        $article = $this->model->table('touch_article')->field('*')->where(array('article_id' => $id))->find();
        $article['content'] = html_out($article['content']);
        /* 模板赋值 */
        $this->assign('cat_select', model('ArticleBase')->article_cat_list(0, $article['cat_id']));
        $this->assign('article', $article);
        $this->assign('ur_here', L('articlecat_edit'));
        $this->display();
    }

    /**
     * 删除文章
     */
    public function del() {
        $id = I('id');
        $condition['article_id'] = $id;
        $this->model->table('touch_article')->where($condition)->delete();
        clear_all_files();
        $this->message(L('drop_succeed'), url('index'));
    }

}
