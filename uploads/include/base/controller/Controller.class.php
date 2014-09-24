<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：Controoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：底层动控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class Controller {

    protected $model = NULL; // 数据库模型
    protected $layout = NULL; // 布局视图
    private $_data = array();

    public function __construct() {
        $this->model = model('Base')->model;
        $this->cloud = Cloud::getInstance();
        // 定义当前请求的系统常量
        define('NOW_TIME', $_SERVER ['REQUEST_TIME']);
        define('REQUEST_METHOD', $_SERVER ['REQUEST_METHOD']);
        define('IS_GET', REQUEST_METHOD == 'GET' ? true : false );
        define('IS_POST', REQUEST_METHOD == 'POST' ? true : false );
        define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false );
        define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false );
        define('IS_AJAX', (isset($_SERVER ['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER ['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));
    }

    public function __get($name) {
        return isset($this->_data [$name]) ? $this->_data [$name] : NULL;
    }

    public function __set($name, $value) {
        $this->_data [$name] = $value;
    }

    // 获取模板对象
    protected function tpl() {
        static $view = NULL;
        if (empty($view)) {
            $view = new EcTemplate(C('TPL'));
        }
        return $view;
    }

    // 模板赋值
    protected function assign($name, $value) {
        return $this->tpl()->assign($name, $value);
    }

    // 模板显示
    protected function display($tpl = '', $return = false, $is_tpl = true) {
        if ($is_tpl) {
            $tpl = empty($tpl) ? strtolower(CONTROLLER_NAME . '_' . ACTION_NAME) : $tpl;
            if ($is_tpl && $this->layout) {
                $this->__template_file = $tpl;
                $tpl = $this->layout;
            }
        }
        $this->tpl()->config ['TPL_TEMPLATE_PATH'] = BASE_PATH . 'apps/' . C('_APP_NAME') . '/view/';
        $this->tpl()->assign($this->_data);
        return $this->tpl()->display($tpl, $return, $is_tpl);
    }

    // 直接跳转
    protected function redirect($url, $code = 302) {
        header('location:' . $url, true, $code);
        exit();
    }

    // 操作成功之后跳转,默认三秒钟跳转
    protected function message($msg, $url = NULL, $type = 'succeed', $waitSecond = 2) {
        if ($url == NULL)
            $url = 'javascript:history.back();';
        if ($type == 'error') {
            $title = '错误信息';
        } else {
            $title = '提示信息';
        }
        $data ['title'] = $title;
        $data ['message'] = $msg;
        $data ['type'] = $type;
        $data ['url'] = $url;
        $data ['second'] = $waitSecond;
        $this->assign('data', $data);
        $this->display('message');
        exit();
    }

    // 弹出信息
    protected function alert($msg, $url = NULL, $parent = false) {
        header("Content-type: text/html; charset=utf-8");
        $alert_msg = "alert('$msg');";
        if (empty($url)) {
            $gourl = 'history.go(-1);';
        } else {
            $gourl = ($parent ? 'parent' : 'window') . ".location.href = '{$url}'";
        }
        echo "<script>$alert_msg $gourl</script>";
        exit();
    }

    // 出错之后返回json数据
    protected function jserror($msg) {
        echo json_encode(array(
            "msg" => $msg,
            "result" => '0'
        ));
        exit();
    }

    // 成功之后返回json
    protected function jssuccess($msg, $url = 'back') {
        echo json_encode(array(
            "msg" => $msg,
            "url" => $url,
            "result" => '1'
        ));
        exit();
    }

    // 获取分页查询limit
    protected function pageLimit($url, $num = 10) {
        $url = str_replace(urlencode('{page}'), '{page}', $url);
        $page = is_object($this->pager ['obj']) ? $this->pager ['obj'] : new Page ();
        $cur_page = $page->getCurPage($url);
        $limit_start = ($cur_page - 1) * $num;
        $limit = $limit_start . ',' . $num;
        $this->pager = array(
            'obj' => $page,
            'url' => $url,
            'num' => $num,
            'cur_page' => $cur_page,
            'limit' => $limit
        );
        return $limit;
    }

    // 分页结果显示
    protected function pageShow($count) {
        return $this->pager ['obj']->show($this->pager ['url'], $count, $this->pager ['num']);
    }

}
