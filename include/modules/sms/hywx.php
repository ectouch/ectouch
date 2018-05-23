<?php
defined('IN_ECTOUCH') or die('Deny Access');

$hywx_lang = BASE_PATH . 'languages/' .C('lang'). '/sms/hywx.php';

if (file_exists($hywx_lang)) {
    global $_LANG;
    include_once($hywx_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'hywx_desc';
    /* 作者 */
    $modules[$i]['author']  = 'ECTouch TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecmoban.com';
    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';
    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'hywx_account',           'type' => 'text',   'value' => ''),
        array('name' => 'hywx_key',               'type' => 'text',   'value' => ''),
        array('name' => 'hywx_mobile',               'type' => 'text',   'value' => ''),
    );
    return;
}
