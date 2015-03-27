<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticlecatController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticlecatController extends AdminController {

    /**
     * 文章分类
     */
    public function index() {
        $articlecat = model('ArticleBase')->article_cat_list(0, 0, false);
        foreach ($articlecat as $key => $cat) {
            $articlecat[$key]['type_name'] = L('type_name.' . $cat['cat_type']);
        }
        $this->assign('articlecat', $articlecat);
        $this->assign('ur_here', L('02_articlecat_list'));
        $this->assign('action_link', array('text' => L('articlecat_add'), 'href' => url('add')));
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
                        array(Check::must($data['cat_name']), L('cat_name')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $this->model->table('touch_article_cat')->data($data)->insert();
            clear_all_files();
            $this->message(L('catadd_succed'), url('index'));
        }
        /* 模板赋值 */
        $this->assign('cat_select',model('ArticleBase')->article_cat_list(0));
        $this->assign('ur_here', L('articlecat_add'));
        $this->assign('action_link', array('text' => L('02_articlecat_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 编辑文章
     */
    public function edit() {
        $id = I('cat_id');
        if (IS_POST) {
            $data = I('data');
            //更新数据库
            $touch_result = $this->model->table('touch_article_cat')->where('cat_id=' . $id)->find();
            if (!empty($touch_result)) {

                $this->model->table('touch_article_cat')->data($data)->where('cat_id=' . $id)->update();
            }
            clear_all_files();
            $this->message(L('catedit_succed'), url('index'));
        }
        $cat = $this->model->table('touch_article_cat')->field('*')->where(array('cat_id' => $id))->find();
        /* 模板赋值 */
        $options = model('ArticleBase')->article_cat_list(0, $cat['parent_id'], false);
        $select = '';
        $selected = $cat['parent_id'];
        foreach ($options as $var) {
            if ($var['cat_id'] == $id) {
                continue;
            }
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars($var['cat_name']) . '</option>';
        }
        
        unset($options);
        $this->assign('cat_select',$select );      
        $this->assign('cat', $cat);
        $this->assign('ur_here', L('articlecat_edit'));
        $this->display();
    }
    
    /**
     * 删除文章分类
     */
    public function del(){
        
        $id = I('get.cat_id');
        
        $count = $this->model->table('touch_article_cat')->field('COUNT(*)')->where("parent_id = '$id'")->getOne();
       
        if ($count > 0)
        {
            /* 还有子分类，不能删除 */
            $this->message(L('is_fullcat'), url('index'));
        }

        /* 非空的分类不允许删除 */
        $count = $this->model->table('touch_article')->field('COUNT(*)')->where("cat_id = '$id'")->getOne();
        if ($count > 0)
        {
            $this->message(L('not_emptycat'), url('index'));
        }
        else
        {            
            $condition['cat_id'] = $id;
            $this->model->table('touch_article_cat')->where($condition)->delete();
            clear_all_files();
            $this->message(L('drop_succeed'), url('index'));
        }
    }

}
