<?php

/**
 * 注册短信
 */

$url = '';
if (isset($GLOBALS['_CFG']['certificate_id'])) {
    if ($GLOBALS['_CFG']['certificate_id']  == '') {
        $certi_id='error';
    } else {
        $certi_id=$GLOBALS['_CFG']['certificate_id'];
    }

    $sess_id = $GLOBALS['sess']->get_session_id();

    $auth = time();
    $ac = md5($certi_id.'SHOPEX_SMS'.$auth);
    $url = 'http://service.shopex.cn/sms/index.php?certificate_id='.$certi_id.'&sess_id='.$sess_id.'&auth='.$auth.'&ac='.$ac;
}
