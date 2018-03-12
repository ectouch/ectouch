<?php

/**
 * 管理中心售后服务管理
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
require_once(ROOT_PATH . 'include/helpers/order_helper.php');
require_once(ROOT_PATH . 'include/helpers/goods_helper.php');


/*初始化数据交换对象 */
$exc = new exchange($ecs->table("service_type"), $db, 'service_id', 'service_name');

/* ------------------------------------------------------ */
//-- 售后服务类型
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'service_type') {

    /* 检查权限 */
    admin_priv('service_type'); //TODO
    /* 查询 */
    $result = service_type_list();
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['13_service_type']);
    $smarty->assign('full_page', 1);
    $smarty->assign('list', $result['list']);
    $smarty->assign('filter', $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count', $result['page_count']);
    $smarty->assign('sort_update_time', '<img src="images/sort_desc.gif">');
    /* 显示模板 */
    assign_query_info();
    $smarty->display('aftermarket_type_list.htm');
}
/* ------------------------------------------------------ */
//-- 添加服务类型
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add_service_type') {

    /* 检查权限 */
    admin_priv('service_type'); //TODO
    $smarty->assign('ur_here', $_LANG['02_add_service_type']);
    $smarty->assign('action_link', array('text' => $_LANG['13_service_type'], 'href' => 'aftermarket_type.php?act=service_type'));
    $smarty->assign('form_action', 'insert_service_type');
    $smarty->assign('service_type_list', $GLOBALS['_LANG']['st']);
    assign_query_info();

    $smarty->display('aftermarket_info.htm');
}
/* ------------------------------------------------------ */
//-- 插入服务类型
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert_service_type') {

    /* 检查权限 */
    admin_priv('service_type'); //TODO
    /* 检查服务类型名称是否相同 */
    $is_only = $exc->is_only('service_type', $_POST['service_type_name'], $_POST['id']);
    
    $_POST['service_name'] = $GLOBALS['_LANG']['st'][$_POST['service_type_name']]; //服务类型
    
    if (!$is_only) {
        sys_msg(sprintf($_LANG['servicetypename_exist'], stripslashes($_POST['service_name'])), 1);
    }
    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    /* 对描述处理 */
    if (!empty($_POST['service_desc'])) {
        $_POST['service_desc'] = $_POST['service_desc'];
    }
    /* 插入数据 */
    $sql = "INSERT INTO " . $ecs->table('service_type') . "(service_name, service_desc,received_days, unreceived_days, is_show, sort_order,service_type) " .
        "VALUES ('$_POST[service_name]', '$_POST[service_desc]', '$_POST[received_days]','$_POST[unreceived_days]','$is_show', '$_POST[sort_order]','$_POST[service_type_name]')";
    $db->query($sql);

    admin_log($_POST['service_type_name'], 'add', 'aftermarket');

    /* 清除缓存 */
    clear_cache_files();

    $link[0]['text'] = $_LANG['continue_add'];
    $link[0]['href'] = 'aftermarket_type.php?act=add_service_type';

    $link[1]['text'] = $_LANG['back_list'];
    $link[1]['href'] = 'aftermarket_type.php?act=service_type';

    sys_msg($_LANG['serviceadd_succed'], 0, $link);
}

/* ------------------------------------------------------ */
//-- 编辑服务类型
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_service_type') {
    /* 权限判断 */
    admin_priv('service_type');

    $sql = "SELECT * " .
        "FROM " . $ecs->table('service_type') . " WHERE service_id ='$_REQUEST[id]'";
    $service_type = $db->GetRow($sql);

    $smarty->assign('ur_here', $_LANG['servicetype_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['13_service_type'], 'href' => 'aftermarket_type.php?act=service_type&'));
    $smarty->assign('service_type', $service_type);
    $smarty->assign('service_type_list', $GLOBALS['_LANG']['st']);
    $smarty->assign('type_id', $_REQUEST[id]);
    $smarty->assign('form_action', 'update_service_type');
    assign_query_info();
    $smarty->display('aftermarket_type_info.htm');
}
/* ------------------------------------------------------ */
//-- 更新服务类型
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_service_type') {
    admin_priv('service_type');

    $_POST['service_name'] = $GLOBALS['_LANG']['st'][$_POST['service_type_name']]; //服务类型
    
    if ($_POST['service_name'] != $_POST['old_service_name']) {
        /* 检查服务类型名称是否相同 */
        $is_only = $exc->is_only('service_name', $_POST['service_name'], $_POST['id']);
        if (!$is_only) {
            sys_msg(sprintf($_LANG['servicetypename_exist'], stripslashes($_POST['service_name'])), 1);
        }
    }
    /* 对描述处理 */
    if (!empty($_POST['service_desc'])) {
        $_POST['service_desc'] = $_POST['service_desc'];
    }

    $is_show = isset($_REQUEST['is_show']) ? intval($_REQUEST['is_show']) : 0;

    $param = "service_name = '$_POST[service_name]', service_desc = '$_POST[service_desc]',received_days = '$_POST[received_days]', unreceived_days = '$_POST[unreceived_days]', is_show='$is_show', sort_order='$_POST[sort_order]',service_type='$_POST[service_type_name]'";
    
    if ($exc->edit($param, $_POST['id'])) {
        /* 清除缓存 */
        clear_cache_files();

        admin_log($_POST['service_name'], 'edit', 'service_type');

        $link[0]['text'] = $_LANG['back_list'];
        $link[0]['href'] = 'aftermarket_type.php?act=service_type';
        $note = vsprintf($_LANG['servicetypeedit_succed'], $_POST['service_name']);
        sys_msg($note, 0, $link);
    } else {
        die($db->error());
    }
}

/* ------------------------------------------------------ */
//-- 删除服务类型
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_service_type') {
    check_authz_json('service_type');

    $id = intval($_GET['id']);
    $sql = 'DELETE FROM ' . $ecs->table('service_type') . " WHERE service_id = '$id'";
    $db->query($sql);

    $url = 'aftermarket_type.php?act=query&' . str_replace('act=remove_service_type', '', $_SERVER['QUERY_STRING']);
    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query') {
    $result = service_type_list();
    /* 模板赋值 */
    $smarty->assign('list', $result['list']);
    $smarty->assign('filter', $result['filter']);
    $smarty->assign('record_count', $result['record_count']);
    $smarty->assign('page_count', $result['page_count']);

    make_json_result(
        $smarty->fetch('service_type_list.htm'),
        '',
        array('filter' => $result['filter'], 'page_count' => $result['page_count'])
    );
}

/*------------------------------------------------------ */
//-- 切换是否显示
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'toggle_show') {
    check_authz_json('service_type');

    $id = intval($_POST['id']);
    $val = intval($_POST['val']);
    $exc->edit("is_show = '$val'", $id);
    clear_cache_files();

    make_json_result($val);
}
