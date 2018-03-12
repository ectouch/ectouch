<?php

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

/* 检查权限 */
admin_priv('shop_config');

/*------------------------------------------------------ */
//-- 验证码设置
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'main') {
    if (gd_version() == 0) {
        sys_msg($_LANG['captcha_note'], 1);
    }

    assign_query_info();
    $captcha = intval($_CFG['captcha']);

    $captcha_check = array();
    if ($captcha & CAPTCHA_REGISTER) {
        $captcha_check['register']          = 'checked="checked"';
    }
    if ($captcha & CAPTCHA_LOGIN) {
        $captcha_check['login']             = 'checked="checked"';
    }
    if ($captcha & CAPTCHA_COMMENT) {
        $captcha_check['comment']           = 'checked="checked"';
    }
    if ($captcha & CAPTCHA_ADMIN) {
        $captcha_check['admin']             = 'checked="checked"';
    }
    if ($captcha & CAPTCHA_MESSAGE) {
        $captcha_check['message']    = 'checked="checked"';
    }
    if ($captcha & CAPTCHA_LOGIN_FAIL) {
        $captcha_check['login_fail_yes']    = 'checked="checked"';
    } else {
        $captcha_check['login_fail_no']     = 'checked="checked"';
    }

    $smarty->assign('captcha', $captcha_check);
    $smarty->assign('captcha_width', $_CFG['captcha_width']);
    $smarty->assign('captcha_height', $_CFG['captcha_height']);
    $smarty->assign('ur_here', $_LANG['captcha_manage']);
    $smarty->display('captcha_manage.htm');
}

/*------------------------------------------------------ */
//-- 保存设置
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'save_config') {
    $captcha = 0;
    $captcha = empty($_POST['captcha_register'])    ? $captcha : $captcha | CAPTCHA_REGISTER;
    $captcha = empty($_POST['captcha_login'])       ? $captcha : $captcha | CAPTCHA_LOGIN;
    $captcha = empty($_POST['captcha_comment'])     ? $captcha : $captcha | CAPTCHA_COMMENT;
    $captcha = empty($_POST['captcha_tag'])         ? $captcha : $captcha | CAPTCHA_TAG;
    $captcha = empty($_POST['captcha_admin'])       ? $captcha : $captcha | CAPTCHA_ADMIN;
    $captcha = empty($_POST['captcha_login_fail'])  ? $captcha : $captcha | CAPTCHA_LOGIN_FAIL;
    $captcha = empty($_POST['captcha_message'])     ? $captcha : $captcha | CAPTCHA_MESSAGE;

    $captcha_width = empty($_POST['captcha_width'])     ? 145 : intval($_POST['captcha_width']);
    $captcha_height = empty($_POST['captcha_height'])   ? 20 : intval($_POST['captcha_height']);

    $sql = "UPDATE " . $ecs->table('shop_config') . " SET value='$captcha' WHERE code='captcha'";
    $db->query($sql);
    $sql = "UPDATE " . $ecs->table('shop_config') . " SET value='$captcha_width' WHERE code='captcha_width'";
    $db->query($sql);
    $sql = "UPDATE " . $ecs->table('shop_config') . " SET value='$captcha_height' WHERE code='captcha_height'";
    $db->query($sql);

    clear_cache_files();

    sys_msg($_LANG['save_ok'], 0, array(array('href'=>'captcha_manage.php?act=main', 'text'=>$_LANG['captcha_manage'])));
}
