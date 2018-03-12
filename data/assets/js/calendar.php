<?php

/**
 * 调用日历 JS
 */

$lang = (!empty($_GET['lang'])) ? trim($_GET['lang']) : 'zh_cn';
define('ROOT_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
if (!file_exists(ROOT_PATH . 'include/languages/' . $lang . '/calendar.php') || strrchr($lang, '.')) {
    $lang = 'zh_cn';
}

require(ROOT_PATH . '/data/config.php');

header('Content-type: application/x-javascript; charset=utf-8');

include_once(ROOT_PATH . '/include/languages/' . $lang . '/calendar.php');

foreach ($_LANG['calendar_lang'] as $cal_key => $cal_data) {
    echo 'var ' . $cal_key . " = \"" . $cal_data . "\";\r\n";
}

include_once('./calendar/calendar.js');
