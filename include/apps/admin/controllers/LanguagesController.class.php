<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：LanguagesController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：语言项编辑控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class LanguagesController extends AdminController
{
    public function index()
    {
        //从languages目录下获取语言项文件 - 默认是前台模板语言包
        $lang_arr    = array();

        $lang_path   = APP_PATH . 'default/languages/' . C('lang');
        $lang_dir    = @opendir($lang_path);

        while ($file = @readdir($lang_dir)) {
            if (substr($file, -3) == "php") {
                $filename = substr($file, 0, -4);
                $language_files = L('language_files');
                $lang_arr[$filename] = $file. ' - ' .@$language_files[$filename];
            }
        }

        ksort($lang_arr);
        @closedir($lang_dir);
        // print_r($lang_arr);

        /* 获得需要操作的语言包文件 */
        $lang_file = isset($_POST['lang_file']) ? trim($_POST['lang_file']) : '';
        if ($lang_file == 'common') {
            $file_path = $lang_path .'/common.php';
        } else {
            $file_path = $lang_path .'/user.php';
        }

        $file_attr = '';
        if (file_mode_info($file_path) < 7) {
            $file_attr = $lang_file .'.php：'. L('file_attribute');
        }

        /* 搜索的关键字 */
        $keyword = !empty($_POST['keyword']) ? trim(stripslashes($_POST['keyword'])) : '';

        /* 调用函数 */
        $language_arr = $this->get_language_item_list($file_path, $keyword);
        foreach ($language_arr as $key => $value) {
            $language_arr[$key]['item_content'] = html_out($language_arr[$key]['item_content']);
        }
        // echo '<pre>';
        // print_r($language_arr);
        // echo '</pre>';

        /* 模板赋值 */
        $this->assign('ur_here', L('edit_languages'));
        $this->assign('keyword', $keyword);  //关键字
        $this->assign('action_link', array());
        $this->assign('file_attr', $file_attr);//文件权限
        $this->assign('lang_arr', $lang_arr); //语言文件列表
        $this->assign('file_path', $file_path);//语言文件
        $this->assign('lang_file', $lang_file);//语言文件
        $this->assign('language_arr', $language_arr); //需要编辑的语言项列表

        // assign_query_info();
        $this->display('language_list');
    }

    public function edit()
    {
        /* 语言项的路径 */
        $lang_file = isset($_POST['file_path']) ? trim($_POST['file_path']) : '';

        /* 替换前的语言项 */
        $src_items = !empty($_POST['item']) ? stripslashes_deep($_POST['item']) : '';

        /* 修改过后的语言项 */
        $dst_items = array();
        $_POST['item_id'] = stripslashes_deep($_POST['item_id']);

        for ($i = 0; $i < count($_POST['item_id']); $i++) {
            /* 语言项内容如果为空，不修改 */
            if (trim($_POST['item_content'][$i]) == '') {
                unset($src_items[$i]);
            } else {
                $_POST['item_content'][$i] = str_replace('\\\\n', '\\n', $_POST['item_content'][$i]);
                $dst_items[$i] = $_POST['item_id'][$i] .' = '. '"' .$_POST['item_content'][$i]. '";';
            }
        }

        /* 调用函数编辑语言项 */
        $result = $this->set_language_items($lang_file, $src_items, $dst_items);

        if ($result === false) {
            /* 修改失败提示信息 */
            $link[] = array('text' => L('back_list'), 'href' => 'javascript:history.back(-1)');
            $this->message(L('edit_languages_false'), null, $link);
        } else {
            /* 记录管理员操作 */
            // admin_log('', 'edit', 'languages');

            /* 清除缓存 */
            clear_cache_files();

            /* 成功提示信息 */
            $this->message(L('edit_languages_success'), url('editlanguages/index'));
        }
    }


    /**
     * 获得语言项列表
     * @access  public
     * @exception           如果语言项中包含换行符，将发生异常。
     * @param   string      $file_path   存放语言项列表的文件的绝对路径
     * @param   string      $keyword    搜索时指定的关键字
     * @return  array       正确返回语言项列表，错误返回false
     */
    private function get_language_item_list($file_path, $keyword)
    {
        if (empty($keyword)) {
            return array();
        }

        /* 获取文件内容 */
        $line_array = file($file_path);
        if (!$line_array) {
            return false;
        } else {
            /* 防止用户输入敏感字符造成正则引擎失败 */
            $keyword = preg_quote($keyword, '/');

            $matches    = array();
            $pattern    = '/\\[[\'|"](.*?)'.$keyword.'(.*?)[\'|"]\\]\\s|=\\s?[\'|"](.*?)'.$keyword.'(.*?)[\'|"];/';
            $regx       = '/(?P<item>(?P<item_id>\\$_LANG\\[[\'|"].*[\'|"]\\])\\s*?=\\s*?[\'|"](?P<item_content>.*)[\'|"];)/';

            foreach ($line_array as $lang) {
                if (preg_match($pattern, $lang)) {
                    $out = array();

                    if (preg_match($regx, $lang, $out)) {
                        $matches[] = $out;
                    }
                }
            }

            return $matches;
        }
    }

    /**
     * 设置语言项
     * @access  public
     * @param   string      $file_path     存放语言项列表的文件的绝对路径
     * @param   array       $src_items     替换前的语言项
     * @param   array       $dst_items     替换后的语言项
     * @return  void        成功就把结果写入文件，失败返回false
     */
    private function set_language_items($file_path, $src_items, $dst_items)
    {
        /* 检查文件是否可写（修改） */
        if (file_mode_info($file_path) < 2) {
            return false;
        }

        /* 获取文件内容 */
        $line_array = file($file_path);
        if (!$line_array) {
            return false;
        } else {
            $file_content = implode('', $line_array);
        }

        $snum = count($src_items);
        $dnum = count($dst_items);
        if ($snum != $dnum) {
            return false;
        }
        /* 对索引进行排序，防止错位替换 */
        ksort($src_items);
        ksort($dst_items);
        for ($i = 0; $i < $snum; $i++) {
            $file_content = str_replace($src_items[$i], $dst_items[$i], $file_content);
        }

        /* 写入修改后的语言项 */
        $f = fopen($file_path, 'wb');
        if (!$f) {
            return false;
        }
        if (!fwrite($f, $file_content)) {
            return false;
        } else {
            return true;
        }
    }
}
