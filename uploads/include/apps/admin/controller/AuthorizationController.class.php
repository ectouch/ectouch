<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AuhtorizationControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：授权管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AuthorizationController extends AdminController {

    /**
     * 授权列表
     */
    public function index() {
        $modules = read_modules(ROOT_PATH . 'plugins/connect');
        foreach ($modules as $key => $value) {

            $modules[$key]['install'] = $this->model->table('touch_auth')->where(array('from' => $value['type']))->count();
        }
        $this->assign('ur_here', L('09_authorization_list'));
        $this->assign('modules', $modules);
        $this->display();
    }

    /**
     * 安装授权登录
     */
    public function install() {
        if (IS_POST) {
            $cfg_value = I('cfg_value');
            $data['from'] = I('from');
            $cfg_name = I('cfg_name');
            $cfg_type = I('cfg_type');
            $cfg_label = I('cfg_label');
            // 取得配置信息
            $auth_config = array();
            if (isset($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $auth_config [] = array(
                        'name' => trim($cfg_name [$i]),
                        'type' => trim($cfg_type [$i]),
                        'value' => trim($cfg_value [$i])
                    );
                }
            }
            $data ['auth_config'] = serialize($auth_config);
            //插入配置信息
            $this->model->table('touch_auth')->data($data)->insert();
            $this->message(L('reinstall'), url('index'));
        } else {
            $data = model('ClipsBase')->get_third_user_info(I('from'));
        }
        $count = $this->model->table('touch_auth')->where(array('from' => I('from')))->count();
        if ($count > 0) {
            //安装过跳转到列表页面
            $this->redirect(url('index'));
        }
        $filepath = ROOT_PATH . 'plugins/connect/' . I('from') . '.php';
        if (file_exists($filepath)) {
            $set_modules = true;
            include_once($filepath);
            $info = $modules[$i];
            foreach ($info['config'] as $key => $value) {
                $info['config'][$key] = $value + array(
                    'label' => L($value ['name']),
                );
            }
        }
        $this->assign('data', $data);
        $this->assign('info', $info);
        $this->assign('ur_here', L('install'));
        $this->display();
    }

    /**
     * 编辑授权
     */
    public function edit() {
        if (IS_POST) {
            $cfg_value = I('cfg_value');
            $data['from'] = I('from');
            $cfg_name = I('cfg_name');
            $cfg_type = I('cfg_type');
            $cfg_label = I('cfg_label');
            // 取得配置信息
            $auth_config = array();
            if (isset($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $auth_config [] = array(
                        'name' => trim($cfg_name [$i]),
                        'type' => trim($cfg_type [$i]),
                        'value' => trim($cfg_value [$i])
                    );
                }
            }
            $data ['auth_config'] = serialize($auth_config);
            $count = $this->model->table('touch_auth')->where(array('from' => $data['from']))->count();
            if ($count > 0) {
                //安装过 更新配置信息
                $this->model->table('touch_auth')->data($data)->where(array('from' => $data['from']))->update();
                $this->message(L('edit_success'), url('index'));
            }
        } else {
            $data = model('ClipsBase')->get_third_user_info(I('from'));
        }
        $count = $this->model->table('touch_auth')->where(array('from' => I('from')))->count();
        if (!$count) {
            //没有安装过回到安装页面
            $this->redirect(url('install'));
        }
        $filepath = ROOT_PATH . 'plugins/connect/' . I('from') . '.php';
        if (file_exists($filepath)) {
            $set_modules = true;
            include_once($filepath);
            $info = $modules[$i];
            foreach ($info['config'] as $key => $value) {
                $info['config'][$key] = $value + array(
                    'label' => L($value ['name']),
                );
            }
        }
        // 循环配置插件中所有属性
        foreach ($info ['config'] as $key => $value) {

            if (isset($data [$value ['name']])) {
                $info ['config'] [$key] ['value'] = $data [$value['name']];
            } else {
                $info ['config'] [$key] ['value'] = $value ['value'];
            }
        }
        $this->assign('data', $data);
        $this->assign('info', $info);
        $this->assign('ur_here', L('edit'));
        $this->display();
    }

    /**
     * 卸载授权
     */
    public function uninstall() {
        if (!isset($_GET ['type']) || empty($_GET ['type'])) {
            $this->message(L('yes_uninstall'), NULL, 'error');
        }
        $where ['from'] = I('type');
        $this->model->table('touch_auth')->data($data)->where($where)->delete();

        $this->message(L('yes_uninstall'), url('index'));
    }

}
