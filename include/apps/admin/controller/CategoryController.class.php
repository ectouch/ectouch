<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CategoryControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：分类栏目管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CategoryController extends AdminController {

    /**
     * 获取分类列表
     */
    public function index() {
        $list = cat_list(0, 0, false);
        /* 模板赋值 */
        $this->assign('ur_here', L('03_category_list'));
        $this->assign('cat_list', $list);
        $this->display();
    }

    /**
     * 编辑分类信息
     */
    public function edit() {
        if (IS_POST) {
            $cat_id = I('cat_id');
            $cat_info = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($cat_info['cat_name']), L('catname_empty')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            /* 判断上级目录是否合法 */
            $children = array_keys(cat_list($cat_id, 0, false)); // 获得当前分类的所有下级分类
            if (in_array($cat_info['parent_id'], $children)) {
                $this->message(L('is_leaf_error'), NULL, 'error');
            }
            /* 更新栏目 */
            $this->cat_update($cat_id, $cat_info);
            /* 更新栏目图标 */
            if ($_FILES['cat_image']['name']) {
                /* cat_image图标 */
                $result = $this->ectouchUpload('cat_image', 'cat_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                $data['cat_image'] = substr($result['message']['cat_image']['savepath'], 2) . $result['message']['cat_image']['savename'];
                $this->model->table('touch_category')->data($data)->where('cat_id=' . $cat_id)->update();
            }
            /* 清除缓存 */
            clear_all_files();
            $this->message(L('catedit_succed'), url('index'));
        }
        $cat_id = I('cat_id');
        //查询附表信息           
        $result = $this->model->table('touch_category')->where('cat_id=' . $cat_id)->find();
        if (empty($result)) {
            $data['cat_id'] = $cat_id;
            $this->model->table('touch_category')->data($data)->insert();
        }
        // 查询分类信息数据
        $cat_info = $this->get_cat_info($cat_id);
        /* 模板赋值 */
        $this->assign('ur_here', L('category_edit'));
        $this->assign('cat_info', $cat_info);
        $this->assign('cat_select', cat_list(0, $cat_info['parent_id'], true));
        $this->display();
    }

    /**
     * 获得商品分类的所有信息
     * @param   integer     $cat_id     指定的分类ID
     * @return  mix
     */
    private function get_cat_info($cat_id) {
        return $this->model->table('category as a, ' . $this->model->pre . 'touch_category as b')->where('a.cat_id=b.cat_id and a.cat_id=' . $cat_id)->find();
    }

    /**
     * 更新商品分类
     * @param   integer $cat_id
     * @param   array   $args
     * @return  mix
     */
    private function cat_update($cat_id, $args) {
        if (empty($args) || empty($cat_id)) {
            return false;
        }
        return $this->model->table('category')->data($args)->where('cat_id=' . $cat_id)->update();
    }

    /**
     * 获取属性列表
     * @access  public
     * @param
     * @return void
     */
    private function get_attr_list() {
        $result = $this->model->table('attribute as a, ' . $this->model->pre . 'goods_type as c')
                        ->field('a.attr_id, a.cat_id, a.attr_name')
                        ->where('a.cat_id = c.cat_id AND c.enabled = 1')
                        ->order('a.cat_id , a.sort_order')->select();

        $list = array();
        foreach ($result as $val) {
            $list[$val['cat_id']][] = array($val['attr_id'] => $val['attr_name']);
        }
        return $list;
    }

}
