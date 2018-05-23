<?php
defined('IN_ECTOUCH') or die('Deny Access');

$ecmoban_lang = BASE_PATH . 'languages/' .C('lang'). '/sms/ecmoban.php';

if (file_exists($ecmoban_lang)) {
    global $_LANG;
    include_once($ecmoban_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    $i = isset($modules) ? count($modules) : 0;
    /* 代码 */
    $modules[$i]['code']    = basename(__FILE__, '.php');
    /* 描述对应的语言项 */
    $modules[$i]['desc']    = 'ecmoban_desc';
    /* 作者 */
    $modules[$i]['author']  = 'ECTouch TEAM';
    /* 网址 */
    $modules[$i]['website'] = 'http://www.ecmoban.com';
    /* 版本号 */
    $modules[$i]['version'] = '1.0.0';
    /* 配置信息 */
    $modules[$i]['config']  = array(
        array('name' => 'ecmoban_account',           'type' => 'text',   'value' => ''),
        array('name' => 'ecmoban_key',               'type' => 'text',   'value' => ''),
        array('name' => 'ecmoban_mobile',               'type' => 'text',   'value' => ''),
    );
    return;
}
