<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：PaymentControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：支付方式控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class PaymentController extends AdminController {

    /**
     * 支付方式列表
     */
    public function index() {
        // 查询数据库中启用的支付方式
        $pay_list = array();
        $where ['enabled'] = 1;
        $rs = $this->model->table('touch_payment')->where($where)->order('pay_order')->select();
        if ($rs) {
            foreach ($rs as $key => $val) {
                $pay_list [$val ['pay_code']] = $val;
            }
        }

        // 获取目录中支付插件列表
        $modules = read_modules(ROOT_PATH . 'plugins/payment');
        foreach ($modules as $key => $val) {
            $code = $val ['code'];
            $modules [$key] ['pay_code'] = $val ['code'];
            // 如果数据库中存在，用数据库中的数据
            if (isset($pay_list [$code])) {
                $modules [$key] ['name'] = $pay_list [$code] ['pay_name'];
                $modules [$key] ['pay_fee'] = $pay_list [$code] ['pay_fee'];
                $modules [$key] ['is_cod'] = $pay_list [$code] ['is_cod'];
                $modules [$key] ['desc'] = $pay_list [$code] ['pay_desc'];
                $modules [$key] ['pay_order'] = $pay_list [$code] ['pay_order'];
                $modules [$key] ['install'] = '1';
            } else {
                $modules [$key] ['name'] = L($val ['code']);
                if (!isset($val ['pay_fee'])) {
                    $modules [$key] ['pay_fee'] = 0;
                }
                $modules [$key] ['desc'] = L($val ['desc']);
                $modules [$key] ['install'] = '0';
            }
        }
        $this->assign('ur_here', L('02_payment_list'));
        $this->assign('modules', $modules);
        $this->display();
    }

    /**
     * 安装支付方式
     */
    public function install() {
        if (IS_POST) {
            // 数据过滤
            $data = I('post.data');
            $cfg_value = I('cfg_value');
            $cfg_name = I('cfg_name');
            $cfg_type = I('cfg_type');
            $cfg_lang = I('cfg_lang');
            // 检查数据
            if (empty($data ['pay_name'])) {
                $this->message(L('payment_name') . L('empty'), NULL, 'error');
            }
            $where = 'pay_name = "' . $data ['pay_name'] . '" AND pay_code <> "' . $data ['pay_code'] . '"';
            $count = $this->model->table('touch_payment')->where($where)->count();
            if ($count > 0) {
                $this->message(L('payment_name') . L('repeat'), NULL, 'error');
            }
            // 取得配置信息
            $pay_config = array();
            if (isset($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $pay_config [] = array(
                        'name' => trim($cfg_name [$i]),
                        'type' => trim($cfg_type [$i]),
                        'value' => trim($cfg_value [$i])
                    );
                }
            }
            $data ['pay_config'] = serialize($pay_config);
            $data ['pay_fee'] = empty($data ['pay_fee']) ? 0 : $data ['pay_fee'];

            // 检查该支付方式是否曾经安装过
            $where1 ['pay_code'] = $data ['pay_code'];
            $rs = $this->model->table('touch_payment')->where($where1)->find();
            if ($rs) {
                // 该支付方式已经安装过, 将该支付方式的状态设置为 enable
                $where2 ['pay_code'] = $data ['pay_code'];
                $data ['enabled'] = 1;
                $this->model->table('touch_payment')->data($data)->where($where2)->update();
            } else {
                $data ['enabled'] = 1;
                $this->model->table('touch_payment')->data($data)->insert();
            }
            $this->message(L('install_ok'), url('index'));
        }
        // 查询电脑版支付方式
        $pc_pay_type = array('默认关联支付方式');
        $where ['enabled'] = 1;
        $where ['is_online'] = 1;
        $pc_pay_list = $this->model->table('payment')->field('pay_id, pay_code, pay_name')->where($where)->select();
        if (is_array($pc_pay_list)) {
            foreach ($pc_pay_list as $key => $vo) {
                if ($vo ['pay_code'] !== 'balance') {
                    $pc_pay_type [$vo ['pay_id']] = $vo ['pay_name'];
                }
            }
        }
        // 取相应插件信息
        $set_modules = true;
        include_once (ROOT_PATH . 'plugins/payment/' . $_REQUEST ['code'] . '.php');

        $data = $modules [0];
        // 对支付费用判断。如果data['pay_fee']为false无支付费用，为空则说明以配送有关，其它可以修改
        isset($data ['pay_fee']) ? trim($data ['pay_fee']) : 0;
        $pay ['pay_code'] = $data ['code'];
        $pay ['pay_name'] = L($data ['code']);
        $pay ['pay_desc'] = L($data ['desc']);
        $pay ['is_cod'] = $data ['is_cod'];
        $pay ['pay_fee'] = $data ['pay_fee'];
        $pay ['is_online'] = $data ['is_online'];
        $pay ['pay_config'] = array();

        foreach ($data ['config'] as $key => $value) {
            $desc = L($value ['name'] . '_desc');
            $config_desc = (isset($desc)) ? $desc : '';
            $pay ['pay_config'] [$key] = $value + array(
                'label' => L($value ['name']),
                'value' => $value ['value'],
                'desc' => $config_desc
            );

            if ($pay ['pay_config'] [$key] ['type'] == 'select' || $pay ['pay_config'] [$key] ['type'] == 'radiobox') {
                $pay ['pay_config'] [$key] ['range'] = L($pay ['pay_config'] [$key] ['name'] . '_range');
            }
        }
        $this->assign('pay', $pay);
        $this->assign('ur_here', L('install') . L('02_payment_list'));
        $this->display();
    }

    /**
     * 编辑支付方式
     */
    public function edit() {
        if (IS_POST) {
            // 数据过滤
            $data = I('data');
            $cfg_value = I('cfg_value');
            $cfg_name = I('cfg_name');
            $cfg_type = I('cfg_type');
            $cfg_lang = I('cfg_lang');
            // 检查数据
            if (empty($data ['pay_name'])) {
                $this->message(L('payment_name') . L('empty'), NULL, 'error');
            }
            $where = 'pay_name = "' . $data ['pay_name'] . '" AND pay_code <> "' . $data ['pay_code'] . '"';
            $count = $this->model->table('touch_payment')->where($where)->count();
            if ($count > 0) {
                $this->message(L('payment_name') . L('repeat'), NULL, 'error');
            }
            // 取得配置信息
            $pay_config = array();
            if (isset($cfg_value) && is_array($cfg_value)) {
                for ($i = 0; $i < count($cfg_value); $i++) {
                    $pay_config [] = array(
                        'name' => trim($cfg_name [$i]),
                        'type' => trim($cfg_type [$i]),
                        'value' => trim($cfg_value [$i])
                    );
                }
            }
            $data ['pay_config'] = serialize($pay_config);
            $data ['pay_fee'] = empty($data ['pay_fee']) ? 0 : $data ['pay_fee'];

            $where1['pay_code'] = $data['pay_code'];
            $this->model->table('touch_payment')->data($data)->where($where1)->update();

            $this->message(L('edit_ok'), url('index'));
        }
        if (!isset($_GET ['code']) || empty($_GET ['code'])) {
            $this->message(L('payment_not_available'), NULL, 'error');
        }
        $code = I('code');
        // 查询支付方式信息
        $where ['pay_code'] = $code;
        $where ['enabled'] = 1;
        $pay = $this->model->table('touch_payment')->where($where)->find();
        if (empty($pay)) {
            $this->message(L('payment_not_available'), NULL, 'error');
        }

        // 查询电脑端支付方式
        $pc_pay_type = array('默认关联支付方式');
        $where1 ['enabled'] = 1;
        $where1 ['is_online'] = 1;
        $pc_pay_list = $this->model->table('payment')->field('pay_id, pay_code, pay_name')->where($where1)->select();
        if (is_array($pc_pay_list)) {
            foreach ($pc_pay_list as $key => $vo) {
                if ($vo ['pay_code'] !== 'balance') {
                    $pc_pay_type [$vo ['pay_id']] = $vo ['pay_name'];
                }
            }
        }
        // 取相应插件信息
        $set_modules = true;
        include_once (ROOT_PATH . 'plugins/payment/' . $code . '.php');
        $data = $modules [0];

        // 取得配置信息
        if (is_string($pay ['pay_config'])) {
            $store = unserialize($pay ['pay_config']);
            // 取出已经设置属性的code
            $code_list = array();
            foreach ($store as $key => $value) {
                $code_list [$value ['name']] = $value ['value'];
            }
            $pay ['pay_config'] = array();
            // 循环配置插件中所有属性
            foreach ($data ['config'] as $key => $value) {
                $desc = L($value ['name'] . '_desc');
                $pay ['pay_config'] [$key] ['desc'] = (isset($desc)) ? $desc : '';
                $pay ['pay_config'] [$key] ['label'] = L($value ['name']);
                $pay ['pay_config'] [$key] ['name'] = $value ['name'];
                $pay ['pay_config'] [$key] ['type'] = $value ['type'];

                if (isset($code_list [$value ['name']])) {
                    $pay ['pay_config'] [$key] ['value'] = $code_list [$value ['name']];
                } else {
                    $pay ['pay_config'] [$key] ['value'] = $value ['value'];
                }

                if ($pay ['pay_config'] [$key] ['type'] == 'select' || $pay ['pay_config'] [$key] ['type'] == 'radiobox') {
                    $pay ['pay_config'] [$key] ['range'] = L($pay ['pay_config'] [$key] ['name'] . '_range');
                }
            }
        }
        // 如果以前没设置支付费用，编辑时补上
        if (!isset($pay ['pay_fee'])) {
            if (isset($data ['pay_fee'])) {
                $pay ['pay_fee'] = $data ['pay_fee'];
            } else {
                $pay ['pay_fee'] = 0;
            }
        }
        $this->assign('ur_here', L('edit') . L('02_payment_list'));
        $this->assign('pay', $pay);
        $this->display();
    }

    /**
     * 卸载支付方式
     */
    public function uninstall() {
        if (!isset($_GET ['code']) || empty($_GET ['code'])) {
            $this->message(L('payment_not_available'), NULL, 'error');
        }
        $code = I('code');
        $where ['pay_code'] = $code;
        $data ['enabled'] = 0;
        $this->model->table('touch_payment')->data($data)->where($where)->update();

        $this->message(L('uninstall_ok'), url('index'));
    }

}