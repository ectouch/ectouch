<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TemplateController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：模板管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class TemplateController extends AdminController
{

    /**
     * 模板管理首页
     */
    public function index()
    {
        /* 获得当前的模版的信息 */
        $curr_template = C('template');
        $curr_style = C('stylename');

        /* 获得可用的模版 */
        $available_templates = array();
        $template_dir = @opendir(ROOT_PATH . 'themes/');
        while ($file = readdir($template_dir)) {
            if ($file != '.' && $file != '..' && is_dir(ROOT_PATH . 'themes/' . $file) && $file != '.svn' && $file != 'index.htm') {
                $available_templates[] = get_template_info($file);
            }
        }
        @closedir($template_dir);

        /* 获得可用的模版的可选风格数组 */
        $templates_style = array();
        if (count($available_templates) > 0) {
            foreach ($available_templates as $value) {
                $templates_style[$value['code']] = $this->read_tpl_style($value['code'], 2);
            }
        }
        $this->assign('ur_here', L('template_manage'));
        $this->assign('curr_tpl_style', $curr_style);
        $this->assign('template_style', $templates_style);
        $this->assign('curr_template', get_template_info($curr_template, $curr_style));
        $this->assign('available_templates', $available_templates);
        $this->display();
    }

    /**
     * 模板安装
     */
    public function install()
    {
        $tpl_name = I('get.tpl_name');
        $tpl_fg = I('get.tpl_fg', 0);
        $data['value'] = $tpl_name;
        $condition['code'] = 'template';
        $this->model->table('touch_shop_config')->data($data)->where($condition)->update();
        $data['value'] = $tpl_fg;
        $condition['code'] = 'stylename';
        $this->model->table('touch_shop_config')->data($data)->where($condition)->update();

        clear_all_files(); //清除模板编译文件

        make_json_result($this->read_style_and_tpl($tpl_name, $tpl_fg), L('install_template_success'));
    }


    /**
     * 读取模板风格列表
     *
     * @access public
     * @param string $tpl_name
     *            模版名称
     * @param int $flag
     *            1，AJAX数据；2，Array
     * @return
     *
     */
    private function read_tpl_style($tpl_name, $flag = 1)
    {
        if (empty($tpl_name) && $flag == 1) {
            return 0;
        }

        /* 获得可用的模版 */
        $temp = '';
        $start = 0;
        $available_templates = array();
        $dir = ROOT_PATH . 'themes/' . $tpl_name . '/css/';
        $tpl_style_dir = @opendir($dir);
        while ($file = readdir($tpl_style_dir)) {
            if ($file != '.' && $file != '..' && is_file($dir . $file) && $file != '.svn' && $file != 'index.htm') {
                if (preg_match("/^(ectouch|ectouch_)(.*)*/i", $file)) {                 // 取模板风格缩略图
                    $start = strpos($file, '.');
                    $temp = substr($file, 0, $start);
                    $temp = explode('_', $temp);
                    if (count($temp) == 2) {
                        $available_templates[] = $temp[1];
                    }
                }
            }
        }
        @closedir($tpl_style_dir);

        if ($flag == 1) {
            $ec = '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(0, this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(0);"  bgcolor="#FFFFFF"><tr><td>&nbsp;</td></tr></table>';
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $tpl_info = get_template_info($tpl_name, $value);

                    $ec .= '<table border="0" width="100%" cellpadding="0" cellspacing="0" class="colortable" onMouseOver="javascript:onSOver(\'' . $value . '\', this);" onMouseOut="onSOut(this);" onclick="javascript:setupTemplateFG(\'' . $value . '\');"  bgcolor="' . $tpl_info['type'] . '"><tr><td>&nbsp;</td></tr></table>';

                    unset($tpl_info);
                }
            } else {
                $ec = '0';
            }

            return $ec;
        } elseif ($flag == 2) {
            $templates_temp = array(
                ''
            );
            if (count($available_templates) > 0) {
                foreach ($available_templates as $value) {
                    $templates_temp[] = $value;
                }
            }
            return $templates_temp;
        }
    }

    /**
     * 读取当前风格信息与当前模板风格列表
     *
     * @access  public
     * @param   string  $tpl_name       模版名称
     * @param   string  $tpl_style 模版风格名
     * @return
     */
    private function read_style_and_tpl($tpl_name, $tpl_style)
    {
        $style_info = array();
        $style_info = get_template_info($tpl_name, $tpl_style);

        $tpl_style_info = array();
        $tpl_style_info = $this->read_tpl_style($tpl_name, 2);
        $tpl_style_list = '';
        if (count($tpl_style_info) > 1) {
            foreach ($tpl_style_info as $value) {
                $tpl_style_list .= '<span style="cursor:pointer;" onMouseOver="javascript:onSOver(\'screenshot\', \'' . $value . '\', this);" onMouseOut="onSOut(\'screenshot\', this, \'' . $style_info['screenshot'] . '\');" onclick="javascript:setupTemplateFG(\'' . $tpl_name . '\', \'' . $value . '\', \'\');" id="templateType_' . $value . '"><img src="'.__URL__.'/themes/' . $tpl_name . '/images/type' . $value . '_';

                if ($value == $tpl_style) {
                    $tpl_style_list .= '1';
                } else {
                    $tpl_style_list .= '0';
                }
                $tpl_style_list .= '.gif" border="0"></span>&nbsp;';
            }
        }
        $style_info['tpl_style'] = $tpl_style_list;
    
        return $style_info;
    }
    /**
     * 库项目管理
     * @return
     */
    public function library()
    {
        $curr_template = C('template');
        $arr_library   = array();
        $library_path  = ROOT_PATH .'themes/' . $curr_template . '/library';
        $library_dir   = @opendir($library_path);
        $curr_library  = '';

        while ($file = @readdir($library_dir)) {
            if (substr($file, -3) == "lbi") {
                $filename               = substr($file, 0, -4);
                $template_libs = L('template_libs');
                $arr_library[$filename] = $file. ' - ' . $template_libs[$filename];

                if ($curr_library == '') {
                    $curr_library = $filename;
                }
            }
        }

        ksort($arr_library);

        @closedir($library_dir);
        // print_r($curr_library);

        $lib = $this->load_library($curr_template, $curr_library);
        // print_r($arr_library);
        $this->assign('ur_here', L('04_template_library'));
        $this->assign('curr_library', $curr_library);
        $this->assign('libraries', $arr_library);
        $this->assign('library_html', $lib['html']);
        $this->display('template_library');
    }
    /**
     * 动态加载库项目内容
     *
     */
    public function load_library_ajax()
    {
        if (IS_AJAX && IS_GET) {
            $lib = I('get.lib');
            $library = $this->load_library(C('template'), $lib);
            $message = ($library['mark'] & 7) ? '' : L('library_not_written');

            make_json_result($library['html'], $message);
        }
    }

    /**
     * 更新库项目内容
     * @return [type] [description]
     */
    public function update_library()
    {
        if (IS_POST) {
            $html = stripslashes(json_str_iconv($_POST['html']));
            $lib = I('post.lib');
            $lib_file = ROOT_PATH . 'themes/' . C('template') . '/library/' . $lib . '.lbi';
            $lib_file = str_replace("0xa", '', $lib_file); // 过滤 0xa 非法字符

            $org_html = str_replace("\xEF\xBB\xBF", '', file_get_contents($lib_file));

            if (@file_exists($lib_file) === true && @file_put_contents($lib_file, $html)) {
                @file_put_contents(ROOT_PATH . 'data/backup/library/' . C('template') . '-' . $lib . '.lbi', $org_html);

                make_json_result('', L('update_lib_success'));
            } else {
                make_json_error(sprintf(L('update_lib_failed'), 'themes/' . C('template') . '/library'));
            }
        }
    }

    /**
     * 还原库项目
     * @return [type] [description]
     */
    public function restore_library()
    {
        $lib_name   = I('get.lib');
        $lib_file   = ROOT_PATH . 'themes/' . C('template') . '/library/' . $lib_name . '.lbi';
        $lib_file   = str_replace("0xa", '', $lib_file); // 过滤 0xa 非法字符
        $lib_backup = ROOT_PATH . 'data/backup/library/' . C('template') . '-' . $lib_name . '.lbi';
        $lib_backup = str_replace("0xa", '', $lib_backup); // 过滤 0xa 非法字符

        if (file_exists($lib_backup) && filemtime($lib_backup) >= filemtime($lib_file)) {
            make_json_result(str_replace("\xEF\xBB\xBF", '', file_get_contents($lib_backup)));
        } else {
            make_json_result(str_replace("\xEF\xBB\xBF", '', file_get_contents($lib_file)));
        }
    }

    /**
    * 载入库项目内容
    *
    * @access  public
    * @param   string  $curr_template  模版名称
    * @param   string  $lib_name       库项目名称
    * @return  array
    */
    private function load_library($curr_template, $lib_name)
    {
        $lib_name = str_replace("0xa", '', $lib_name); // 过滤 0xa 非法字符

        $lib_file    = ROOT_PATH . 'themes/' . $curr_template . '/library/' . $lib_name . '.lbi';
        $arr['mark'] = file_mode_info($lib_file);
        $arr['html'] = str_replace("\xEF\xBB\xBF", '', file_get_contents($lib_file));

        return $arr;
    }
}
