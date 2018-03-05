<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：NavigatorControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：菜单管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class NavigatorController extends AdminController {

    /**
     * 菜单列表
     */
    public function index() {
        $list = $this->get_list();
        /* 模板赋值 */
        $this->assign('list', $list);
        $this->assign('ur_here', L('navigator'));
        $this->assign('action_link', array('text' => L('add_new'), 'href' => url('add')));
        $this->display();
    }

    /**
     * 新增菜单
     */
    public function add() {
        if (IS_POST) {
            $data = I('data');
            //数据验证
            $msg = Check::rule(array(
                        array(Check::must($data['name']), L('namecannotnull')),
                        array(Check::must($data['url']), L('linkcannotnull')),
            ));
            //提示信息
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            /* 更新图标 */
            if ($_FILES['pic']['name']) {
                $result = $this->ectouchUpload('pic', 'nav');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                $data['pic'] = substr($result['message']['pic']['savepath'], 2) . $result['message']['pic']['savename'];
            }
            $this->model->table('touch_nav')->data($data)->insert();
            $this->message(L('edit_ok'), url('index'));
        }
        /* 模板赋值 */
        $this->assign('ur_here', L('navigator'));
        $this->assign('action_link', array('text' => L('go_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 编辑菜单
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            //数据验证
            $msg = Check::rule(array(
                        array(Check::must($data['name']), L('namecannotnull')),
                        array(Check::must($data['url']), L('linkcannotnull')),
            ));
            //提示信息
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            /* 更新图标 */
            if ($_FILES['pic']['name']) {
                $result = $this->ectouchUpload('pic', 'nav');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                $data['pic'] = substr($result['message']['pic']['savepath'], 2) . $result['message']['pic']['savename'];
            }
            $this->model->table('touch_nav')->data($data)->where('id=' . $id)->update();
            $this->message(L('edit_ok'), url('index'));
        }
        //查询附表信息           
        $result = $this->model->table('touch_nav')->where('id=' . $id)->find();
        /* 模板赋值 */
        $this->assign('info', $result);
        $this->assign('ur_here', L('navigator'));
        $this->assign('action_link', array('text' => L('go_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 删除菜单
     */
    public function del() {
        $condition['id'] = I('id');
        $this->model->table('touch_nav')->where($condition)->delete();
        clear_all_files();
        $this->message(L('edit_ok'), url('index'));
    }

    /**
     * 返回菜单列表
     * @return array
     */
    private function get_list() {
        /* 查询 */
        $result = $this->model->table('touch_nav')->field('id, name, ifshow, vieworder, opennew, url, pic, type')->order('vieworder asc,id asc')->select();
        return $result;
    }

}
