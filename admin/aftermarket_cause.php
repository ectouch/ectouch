<?php

/**
 * 管理中心退换货原因
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'include/helpers/order_helper.php');
require_once(ROOT_PATH . 'include/helpers/goods_helper.php');


/* ------------------------------------------------------ */
//-- 退换货原因列表
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'back_cause_list') {
    /* 检查权限 */
    admin_priv('back_cause_list');
    /* 查询 */
    $result = cause_list(0, 0, false);
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['11_back_cause']);
    $smarty->assign('action_link', array('href' => 'aftermarket_cause.php?act=add_return_cause', 'text' => $_LANG['10_cause_add']));

    $smarty->assign('os_unconfirmed', OS_UNCONFIRMED);
    $smarty->assign('cs_await_pay', CS_AWAIT_PAY);
    $smarty->assign('cs_await_ship', CS_AWAIT_SHIP);
    $smarty->assign('full_page', 1);

    $smarty->assign('cause_list', $result);
    $smarty->assign('sort_update_time', '<img src="images/sort_desc.gif">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('back_cause_list.htm');
} /**
 * 退换货 搜索、排序、分页  by Leah
 */
elseif ($_REQUEST['act'] == 'cause_query') {
    /* 检查权限 */
    admin_priv('back_cause_list');
    $result = cause_list(0, 0, false);
    $smarty->assign('cause_list', $result);

    make_json_result($smarty->fetch('back_cause_list.htm'));
}
/* ------------------------------------------------------ */
//-- 添加退换货原因
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_return_cause') {
    /* 检查权限 */
    admin_priv('back_cause_list');

    /* 检查权限 */
    admin_priv('add_return_cause');
    $cause_select = cause_list(0, 0, true);

    $smarty->assign('cause_list', $cause_select);
    $smarty->assign('form_act', 'inser_cause');
    $smarty->display('back_cause_info.htm');
}
/* ------------------------------------------------------ */
//-- 添加退换货原因
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'inser_cause') {
    /* 检查权限 */
    admin_priv('back_cause_list');
    $cause['cause_name'] = !empty($_REQUEST['cause_name']) ? $_REQUEST['cause_name'] : '';
    $cause['parent_id'] = !empty($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
    $cause['sort_order'] = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
    $cause['is_show'] = !empty($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    if (cause_exists($cause['cause_name'], $cause['parent_id'])) {
        /* 同级别下不能有重复的分类名称 */
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg($_LANG['cause_not_repeated'], 0, $link);
    }
    if ($db->autoExecute($ecs->table('return_cause'), $cause) !== false) {
        /* 添加链接 */
        $link[0]['text'] = $_LANG['continue_add'];
        $link[0]['href'] = 'aftermarket_cause.php?act=add_return_cause';

        $link[1]['text'] = $_LANG['back_list'];
        $link[1]['href'] = 'aftermarket_cause.php?act=back_cause_list';

        sys_msg($_LANG['causeadd_succed'], 0, $link);
    }
}
/* ------------------------------------------------------ */
//-- 编辑退换货原因
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_cause') {

    /* 检查权限 */
    admin_priv('back_cause_list');

    $c_id = !empty($_REQUEST['c_id']) ? intval($_REQUEST['c_id']) : 0;
    $cause_info = cause_info($c_id);

    $cause_select = cause_list(0, $cause_info['parent_id'], true);
    $smarty->assign('c_id', $c_id);
    $smarty->assign('cause_info', $cause_info);
    $smarty->assign('cause_select', $cause_select);
    $smarty->assign('form_act', 'update_cause');
    $smarty->display('back_cause_info.htm');
}
/* ------------------------------------------------------ */
//-- 修改退换货原因
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_cause') {

    /* 检查权限 */
    admin_priv('back_cause_list');

    $c_id = !empty($_REQUEST['c_id']) ? $_REQUEST['c_id'] : 0;

    $cause['cause_name'] = !empty($_REQUEST['cause_name']) ? $_REQUEST['cause_name'] : '';
    $cause['parent_id'] = !empty($_REQUEST['parent_id']) ? intval($_REQUEST['parent_id']) : 0;
    $cause['sort_order'] = !empty($_REQUEST['sort_order']) ? intval($_REQUEST['sort_order']) : 50;
    $cause['is_show'] = !empty($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    if (cause_exists($cause['cause_name'], $cause['parent_id'])) {
        /* 同级别下不能有重复的名称 */
        $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
        sys_msg($_LANG['cause_not_repeated'], 0, $link);
    }
    if ($db->autoExecute($ecs->table('return_cause'), $cause, 'UPDATE', "cause_id='$c_id'") !== false) {
        /* 添加链接 */

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'aftermarket_cause.php?act=back_cause_list';

        sys_msg($_LANG['catedit_succed'], 0, $link);
    }
}
/* ------------------------------------------------------ */
//-- 删除退换货原因
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_cause') {
    /* 检查权限 */
    admin_priv('back_cause_list');
    $c_id = $_REQUEST['id'];
    /* 删除退货单 */
    $sql = "DELETE FROM " . $ecs->table('return_cause') . " WHERE cause_id = '$c_id'";
    $db->query($sql);

    $url = 'aftermarket_cause.php?act=cause_query&' . str_replace('act=remove_cause', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}
/**
 *
 * @param type $c_id
 * @return type
 * by　Leah
 */
function cause_info($c_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('return_cause') . " WHERE cause_id = " . $c_id;

    $res = $GLOBALS['db']->getRow($sql);

    if ($res) {
        return $res;
    } else {
        return array();
    }
}

/**
 * 检查退换货原因是否有存在 by ECTouch Leah
 * @param type $cause_name
 * @return type
 */
function cause_exists($cause_name, $cause_id = 0)
{
    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('return_cause') .
" WHERE cause_name = '" . $cause_name . "'";
    if ($cause_id) {
        $sql .= "AND cause_id = '" . $cause_id . "'";
    }
    return ($GLOBALS['db']->getOne($sql) > 0) ? true : false;
}
