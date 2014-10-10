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
                $available_templates[] = $this->get_template_info($file);
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
        $this->assign('ur_here', L('02_template_list'));
        $this->assign('curr_tpl_style', $curr_style);
        $this->assign('template_style', $templates_style);
        $this->assign('curr_template', $this->get_template_info($curr_template, $curr_style));
        $this->assign('available_templates', $available_templates);
        $this->display();
    }
    
    /**
     * 模板安装
     */
    public function install(){
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
     * 获得模版的信息
     *
     * @access private
     * @param string $template_name 模版名
     * @param string $template_style 模版风格名
     * @return array
     */
    private function get_template_info($template_name, $template_style = '')
    {
        if (empty($template_style) || $template_style == '') {
            $template_style = '';
        }
        
        $info = array();
        $ext = array(
            'png',
            'gif',
            'jpg',
            'jpeg'
        );
        
        $info['code'] = $template_name;
        $info['screenshot'] = '';
        $info['stylename'] = $template_style;
        
        if ($template_style == '') {
            foreach ($ext as $val) {
                if (file_exists(ROOT_PATH . 'themes/' . $template_name . "/images/screenshot.$val")) {
                    $info['screenshot'] = __URL__ . '/themes/' . $template_name . "/images/screenshot.$val";
                    break;
                }
            }
        } else {
            foreach ($ext as $val) {
                if (file_exists(ROOT_PATH . 'themes/' . $template_name . "/images/screenshot_$template_style.$val")) {
                    $info['screenshot'] = __URL__ . '/themes/' . $template_name . "/images/screenshot_$template_style.$val";
                    break;
                }
            }
        }
        
        $css_path = ROOT_PATH . 'themes/' . $template_name . '/css/ectouch.css';
        if ($template_style != '') {
            $css_path = ROOT_PATH . 'themes/' . $template_name . "/css/ectouch_$template_style.css";
        }
        if (file_exists($css_path) && ! empty($template_name)) {
            $arr = array_slice(file($css_path), 0, 10);
            
            $template_name = explode(': ', $arr[1]);
            $template_uri = explode(': ', $arr[2]);
            $template_desc = explode(': ', $arr[3]);
            $template_version = explode(': ', $arr[4]);
            $template_author = explode(': ', $arr[5]);
            $author_uri = explode(': ', $arr[6]);
            $logo_filename = explode(': ', $arr[7]);
            $template_type = explode(': ', $arr[8]);
            
            $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
            $info['uri'] = isset($template_uri[1]) ? trim($template_uri[1]) : '';
            $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
            $info['version'] = isset($template_version[1]) ? trim($template_version[1]) : '';
            $info['author'] = isset($template_author[1]) ? trim($template_author[1]) : '';
            $info['author_uri'] = isset($author_uri[1]) ? trim($author_uri[1]) : '';
            $info['logo'] = isset($logo_filename[1]) ? trim($logo_filename[1]) : '';
            $info['type'] = isset($template_type[1]) ? trim($template_type[1]) : '';
        } else {
            $info['name'] = '';
            $info['uri'] = '';
            $info['desc'] = '';
            $info['version'] = '';
            $info['author'] = '';
            $info['author_uri'] = '';
            $info['logo'] = '';
        }
        
        return $info;
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
                if (preg_match("/^(ectouch|ectouch_)(.*)*/i", $file))                 // 取模板风格缩略图
                {
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
                    $tpl_info = $this->get_template_info($tpl_name, $value);
                    
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
        $style_info = $this->get_template_info($tpl_name, $tpl_style);
    
        $tpl_style_info = array();
        $tpl_style_info = $this->read_tpl_style($tpl_name, 2);
        $tpl_style_list = '';
        if (count($tpl_style_info) > 1)
        {
            foreach ($tpl_style_info as $value)
            {
                $tpl_style_list .= '<span style="cursor:pointer;" onMouseOver="javascript:onSOver(\'screenshot\', \'' . $value . '\', this);" onMouseOut="onSOut(\'screenshot\', this, \'' . $style_info['screenshot'] . '\');" onclick="javascript:setupTemplateFG(\'' . $tpl_name . '\', \'' . $value . '\', \'\');" id="templateType_' . $value . '"><img src="'.__URL__.'/themes/' . $tpl_name . '/images/type' . $value . '_';
    
                if ($value == $tpl_style)
                {
                    $tpl_style_list .= '1';
                }
                else
                {
                    $tpl_style_list .= '0';
                }
                $tpl_style_list .= '.gif" border="0"></span>&nbsp;';
            }
        }
        $style_info['tpl_style'] = $tpl_style_list;
    
        return $style_info;
    }
}
