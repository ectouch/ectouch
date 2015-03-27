<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ConfigControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：系统配置控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ConfigController extends AdminController {

    /**
     * 基本设置
     */
    public function index() {
        /* 可选语言 */
        $path = APP_PATH . APP_NAME . '/language/';
        $dir = opendir($path);
        $lang_list = array();
        while (@$file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path . $file)) {
                $lang_list[] = $file;
            }
        }
        @closedir($dir);
        /* 模板赋值 */
        $this->assign('ur_here', L('01_shop_config'));
        $this->assign('lang_list', $lang_list);
        $this->assign('group_list', $this->get_settings(null, array('5')));
        $this->assign('countries', model('RegionBase')->get_regions());

        if (strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'iis') !== false) {
            $rewrite_confirm = L('rewrite_confirm_iis');
        } else {
            $rewrite_confirm = L('rewrite_confirm_apache');
        }
        $this->assign('rewrite_confirm', $rewrite_confirm);

        if (C('shop_country') > 0) {
            $this->assign('provinces', model('RegionBase')->get_regions(1, C('shop_country')));
            if (C('shop_province')) {
                $this->assign('cities', model('RegionBase')->get_regions(2, C('shop_province')));
            }
        }
        $this->assign('cfg', C('CFG'));
        $this->display();
    }

    /**
     * 更新系统配置
     */
    public function post() {
        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|CERT|';
        $arr = array();
        $res = $this->model->table('touch_shop_config')->field('id, value')->select();
        if (is_array($res)) {
            foreach ($res as $vo) {
                $arr[$vo['id']] = $vo['value'];
            }
        }
        foreach (I('value') AS $key => $val) {
            if ($arr[$key] != $val) {
                $data['value'] = $val;
                $condition['id'] = $key;
                $this->model->table('touch_shop_config')->data($data)->where($condition)->update();
            }
        }

        /* 处理上传文件 */
        $file_var_list = array();
        $res = $this->model->table('touch_shop_config')->where("parent_id > 0 AND type = 'file'")->select();
        if (is_array($res)) {
            foreach ($res as $vo) {
                $file_var_list[$vo['code']] = $vo;
            }
        }
        foreach ($_FILES AS $code => $file) {
            /* 判断用户是否选择了文件 */
            if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                /* 检查上传的文件类型是否合法 */
                if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                    $this->message(sprintf(L('msg_invalid_file'), $file['name']), NULL, 'error');
                } else {
                    if ($code == 'shop_logo') {
                        $info = get_template_info(C('template'));
                        $info['logo'] = empty($info['logo']) ? 'logo.png' : $info['logo'];
                        $file_name = str_replace('{$template}', C('template'), $file_var_list[$code]['store_dir']) . $info['logo'];
                        
                    } elseif ($code == 'watermark') {
                        $name = explode('.', $file['name']);
                        $ext = array_pop($name);
                        $file_name = $file_var_list[$code]['store_dir'] . 'watermark.' . $ext;
                        if (file_exists($file_var_list[$code]['value'])) {
                            @unlink($file_var_list[$code]['value']);
                        }
                    } elseif ($code == 'no_picture') {
                        $name = explode('.', $file['name']);
                        $ext = array_pop($name);
                        $file_name = $file_var_list[$code]['store_dir'] . 'no_picture.' . $ext;
                        if (file_exists($file_var_list[$code]['value'])) {
                            @unlink($file_var_list[$code]['value']);
                        }
                    } else {
                        $file_name = $file_var_list[$code]['store_dir'] . $file['name'];
                    }

                    /* 判断是否上传成功 */
                    if (move_upload_file($file['tmp_name'], $file_name)) {
                        $data2['value'] = __ROOT__ . str_replace(array('./', '../'), '/', $file_name);
                        $this->model->table('touch_shop_config')->data($data2)->where("code = '$code'")->update();
                    } else {
                        $this->message(sprintf(L('msg_upload_failed'), $file['name'], $file_var_list[$code]['store_dir']), NULL, 'error');
                    }
                }
            }
        }

        /* 处理发票类型及税率 */
        $invoice_rate = I('invoice_rate');
        if (!empty($invoice_rate)) {
            foreach ($invoice_rate as $key => $rate) {
                $rate = round(floatval($rate), 2);
                if ($rate < 0) {
                    $rate = 0;
                }
                I('invoice_rate.' . $key, $rate);
            }
            $invoice = array(
                'type' => I('invoice_type'),
                'rate' => I('invoice_rate')
            );
            $data3['value'] = serialize($invoice);
            $this->model->table('touch_shop_config')->data($data3)->where("code = 'invoice_type'")->update();
        }

        /* 清除缓存 */
        clear_all_files();
        $site_info = site_info();
        $this->cloud->data($site_info)->act('post.record');
        $this->message(L('save_success'), url('index'));
    }

    /**
     * 删除上传文件
     */
    public function del() {
        /* 取得参数 */
        $code = I('code');
        $filename = C($code);
        //删除文件
        @unlink($filename);
        //更新设置
        $this->update_configure($code, '');
        /* 清除缓存 */
        clear_all_files();
        $this->message(L('save_success'));
    }

    /**
     * 设置系统设置
     * @param   string  $key
     * @param   string  $val
     * @return  boolean
     */
    private function update_configure($key, $val = '') {
        if (!empty($key)) {
            $data['value'] = $val;
            $condition['code'] = $key;
            return $this->model->table('touch_shop_config')->data($data)->where($condition)->update();
        }
        return true;
    }

    /**
     * 获得设置信息
     * @param   array   $groups     需要获得的设置组
     * @param   array   $excludes   不需要获得的设置组
     * @return  array
     */
    private function get_settings($groups = null, $excludes = null) {
        $config_groups = '';
        $excludes_groups = '';

        if (!empty($groups)) {
            foreach ($groups AS $key => $val) {
                $config_groups .= " AND (id='$val' OR parent_id='$val')";
            }
        }

        if (!empty($excludes)) {
            foreach ($excludes AS $key => $val) {
                $excludes_groups .= " AND (parent_id<>'$val' AND id<>'$val')";
            }
        }

        /* 取出全部数据：分组和变量 */
        $condition = "type<>'hidden' $config_groups $excludes_groups";
        $item_list = $this->model->table('touch_shop_config')->where($condition)->order('parent_id, sort_order, id')->select();

        /* 整理数据 */
        $group_list = array();
        foreach ($item_list AS $key => $item) {
            $pid = $item['parent_id'];
            $cfg_name = L('cfg_name.' . $item['code']);
            $cfg_desc = L('cfg_desc.' . $item['code']);
            $item['name'] = isset($cfg_name) ? $cfg_name : $item['code'];
            $item['desc'] = isset($cfg_desc) ? $cfg_desc : '';

            if ($item['code'] == 'sms_shop_mobile') {
                $item['url'] = 1;
            }
            if ($pid == 0) {
                /* 分组 */
                if ($item['type'] == 'group') {
                    $group_list[$item['id']] = $item;
                }
            } else {
                /* 变量 */
                if (isset($group_list[$pid])) {
                    if ($item['store_range']) {
                        $item['store_options'] = explode(',', $item['store_range']);

                        foreach ($item['store_options'] AS $k => $v) {
                            $cfg_range = L('cfg_range.' . $item['code']);
                            $item['display_options'][$k] = isset($cfg_range[$v]) ? $cfg_range[$v] : $v;
                        }
                    }
                    $group_list[$pid]['vars'][] = $item;
                }
            }
        }

        return $group_list;
    }

}
