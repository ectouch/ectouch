<?php

/**
 * 管理中心优惠活动管理
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
require(BASE_PATH . 'helpers/goods_helper.php');
$image = new image($_CFG['bgcolor']);
$exc = new exchange($ecs->table('favourable_activity'), $db, 'act_id', 'act_name');

/*------------------------------------------------------ */
//-- 活动列表页
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list') {
    admin_priv('favourable');

    /* 模板赋值 */
    $smarty->assign('full_page', 1);
    $smarty->assign('ur_here', $_LANG['favourable_list']);
    $smarty->assign('action_link', array('href' => 'favourable.php?act=add', 'text' => $_LANG['add_favourable']));

    $list = favourable_list();

    $smarty->assign('favourable_list', $list['item']);
    $smarty->assign('filter', $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count', $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    /* 显示商品列表页面 */
    assign_query_info();
    $smarty->display('favourable_list.htm');
}

/*------------------------------------------------------ */
//-- 分页、排序、查询
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'query') {
    $list = favourable_list();

    $smarty->assign('favourable_list', $list['item']);
    $smarty->assign('filter', $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count', $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result(
        $smarty->fetch('favourable_list.htm'),
        '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count'])
    );
}

/*------------------------------------------------------ */
//-- 删除
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove') {
    check_authz_json('favourable');

    $id = intval($_GET['id']);
    $favourable = favourable_info($id);
    if (empty($favourable)) {
        make_json_error($_LANG['favourable_not_exist']);
    }
    $name = $favourable['act_name'];
    $exc->drop($id);

    /* 记日志 */
    admin_log($name, 'remove', 'favourable');

    /* 清除缓存 */
    clear_cache_files();

    $url = 'favourable.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量操作
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'batch') {
    /* 取得要操作的记录编号 */
    if (empty($_POST['checkboxes'])) {
        sys_msg($_LANG['no_record_selected']);
    } else {
        /* 检查权限 */
        admin_priv('favourable');

        $ids = $_POST['checkboxes'];

        if (isset($_POST['drop'])) {
            /* 删除记录 */
            $sql = "DELETE FROM " . $ecs->table('favourable_activity') .
                    " WHERE act_id " . db_create_in($ids);
            $db->query($sql);

            /* 记日志 */
            admin_log('', 'batch_remove', 'favourable');

            /* 清除缓存 */
            clear_cache_files();

            $links[] = array('text' => $_LANG['back_favourable_list'], 'href' => 'favourable.php?act=list&' . list_link_postfix());
            sys_msg($_LANG['batch_drop_ok']);
        }
    }
}

/*------------------------------------------------------ */
//-- 修改排序
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_sort_order') {
    check_authz_json('favourable');

    $id  = intval($_POST['id']);
    $val = intval($_POST['val']);

    $sql = "UPDATE " . $ecs->table('favourable_activity') .
            " SET sort_order = '$val'" .
            " WHERE act_id = '$id' LIMIT 1";
    $db->query($sql);

    make_json_result($val);
}

/*------------------------------------------------------ */
//-- 添加、编辑
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
    /* 检查权限 */
    admin_priv('favourable');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'add';
    $smarty->assign('form_action', $is_add ? 'insert' : 'update');

    /* 初始化、取得优惠活动信息 */
    if ($is_add) {
        $favourable = array(
            'act_id'        => 0,
            'act_name'      => '',
            'start_time'    => date('Y-m-d', time() + 86400),
            'end_time'      => date('Y-m-d', time() + 4 * 86400),
            'user_rank'     => '',
            'act_range'     => FAR_ALL,
            'act_range_ext' => '',
            'min_amount'    => 0,
            'max_amount'    => 0,
            'act_type'      => FAT_GOODS,
            'act_type_ext'  => 0,
            'gift'          => array(),
            'touch_img'     => '',
        );
    } else {
        if (empty($_GET['id'])) {
            sys_msg('invalid param');
        }
        $id = intval($_GET['id']);
        $favourable = favourable_info($id);
        if (empty($favourable)) {
            sys_msg($_LANG['favourable_not_exist']);
        }
    }
    $smarty->assign('favourable', $favourable);

    /* 取得用户等级 */
    $user_rank_list = array();
    $user_rank_list[] = array(
        'rank_id'   => 0,
        'rank_name' => $_LANG['not_user'],
        'checked'   => strpos(',' . $favourable['user_rank'] . ',', ',0,') !== false
    );
    $sql = "SELECT rank_id, rank_name FROM " . $ecs->table('user_rank');
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res)) {
        $row['checked'] = strpos(',' . $favourable['user_rank'] . ',', ',' . $row['rank_id']. ',') !== false;
        $user_rank_list[] = $row;
    }
    $smarty->assign('user_rank_list', $user_rank_list);

    /* 取得优惠范围 */
    $act_range_ext = array();
    if ($favourable['act_range'] != FAR_ALL && !empty($favourable['act_range_ext'])) {
        if ($favourable['act_range'] == FAR_CATEGORY) {
            $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
                " WHERE cat_id " . db_create_in($favourable['act_range_ext']);
        } elseif ($favourable['act_range'] == FAR_BRAND) {
            $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
                " WHERE brand_id " . db_create_in($favourable['act_range_ext']);
        } else {
            $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
                " WHERE goods_id " . db_create_in($favourable['act_range_ext']);
        }
        $act_range_ext = $db->getAll($sql);
    }
    $smarty->assign('act_range_ext', $act_range_ext);

    /* 赋值时间控件的语言 */
    $smarty->assign('cfg_lang', $_CFG['lang']);

    /* 显示模板 */
    if ($is_add) {
        $smarty->assign('ur_here', $_LANG['add_favourable']);
    } else {
        $smarty->assign('ur_here', $_LANG['edit_favourable']);
    }
    $href = 'favourable.php?act=list';
    if (!$is_add) {
        $href .= '&' . list_link_postfix();
    }
    $smarty->assign('action_link', array('href' => $href, 'text' => $_LANG['favourable_list']));
    assign_query_info();
    $smarty->display('favourable_info.htm');
}

/*------------------------------------------------------ */
//-- 添加、编辑后提交
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
    /* 检查权限 */
    admin_priv('favourable');

    /* 是否添加 */
    $is_add = $_REQUEST['act'] == 'insert';

    /** 验证一个商品只能参加一个活动start**/
    $now = gmtime();
    $act_id = intval($_POST['id']);
    $act_range = intval($_POST['act_range']);
    $act_range_ext = isset($_POST['act_range_ext']) && !empty($_POST['act_range_ext']) ? implode(",", $_POST['act_range_ext']) : '';

    //全部商品
    $act_range_all_goods = get_all_goods($act_id, $_POST['act_type']);
    if ($act_range_all_goods == 1)
    {
        sys_msg($_LANG['lab_act_range_desc'][4], 1);
    }

    // 按分类优惠活动包含的所有商品
    $act_range_ext_cat = get_act_range_ext(FAR_CATEGORY, $act_id);
    $goods_list_cat = get_range_goods(FAR_CATEGORY, $act_range_ext_cat, 'cat_id');
  
    // 按品牌优惠活动包含的所有商品
    $act_range_ext_brand = get_act_range_ext(FAR_BRAND, $act_id);
    $goods_list_brand = get_range_goods(FAR_BRAND, $act_range_ext_brand, 'brand_id');
    
    // 按商品优惠活动包含的所有商品
    $act_range_ext_goods = get_act_range_ext(FAR_GOODS, $act_id);
    $goods_list_goods = get_range_goods(FAR_GOODS, $act_range_ext_goods, 'goods_id');

    switch ($act_range)
    {
        case 0:// 全部商品
            $where = '';
            if($act_id){
                $where .= " AND act_id <> '$act_id'";
            }
            $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('favourable_activity'). " WHERE  end_time >= '$now' AND act_range = 0  $where";
            $num = $GLOBALS['db']->getOne($sql);
            if ($num)
            {
                sys_msg($_LANG['lab_act_range_desc'][3], 1);
            }
            break;     
        case 1: // 按分类
            $goods_list_cat_new = get_range_goods(FAR_CATEGORY, $_POST['act_range_ext'], 'cat_id');
            $arr = array_intersect($goods_list_cat, $goods_list_cat_new);
            $arr1 = array_intersect($goods_list_brand, $goods_list_cat_new);
            $arr2 = array_intersect($goods_list_goods, $goods_list_cat_new);
            if ($arr || $arr1 || $arr2)
            {
                sys_msg($_LANG['lab_act_range_desc'][1], 1);
            }
            break;
        case 2: // 按品牌
            $goods_list_brand_new = get_range_goods(FAR_BRAND, $_POST['act_range_ext'], 'brand_id');
            $arr = array_intersect($goods_list_cat, $goods_list_brand_new);
            $arr1 = array_intersect($goods_list_brand, $goods_list_brand_new);
            $arr2 = array_intersect($goods_list_brand_new, $goods_list_goods);
            if ($arr || $arr1 || $arr2)
            {
                sys_msg($_LANG['lab_act_range_desc'][2], 1);
            }
            break;
        case 3: // 按商品
            $goods_list_goods_new = get_range_goods(FAR_GOODS, $_POST['act_range_ext'], 'goods_id');
            $arr = array_intersect($goods_list_cat, $goods_list_goods_new);
            $arr1 = array_intersect($goods_list_brand, $goods_list_goods_new);
            $arr2 = array_intersect($goods_list_goods, $goods_list_goods_new);    
            if ($arr || $arr1 || $arr2)
            {
                sys_msg($_LANG['lab_act_range_desc'][0], 1);
            }
            break;
        default:
            break;
    }

    /** 验证一个商品只能参加一个活动end**/

    /* 检查名称是否重复 */
    $act_name = sub_str($_POST['act_name'], 255, false);
    if (!$exc->is_only('act_name', $act_name, intval($_POST['id']))) {
        sys_msg($_LANG['act_name_exists']);
    }

    /* 检查享受优惠的会员等级 */
    if (!isset($_POST['user_rank'])) {
        sys_msg($_LANG['pls_set_user_rank']);
    }

    /* 检查优惠范围扩展信息 */
    if (intval($_POST['act_range']) > 0 && !isset($_POST['act_range_ext'])) {
        sys_msg($_LANG['pls_set_act_range']);
    }

    /* 检查金额上下限 */
    $min_amount = floatval($_POST['min_amount']) >= 0 ? floatval($_POST['min_amount']) : 0;
    $max_amount = floatval($_POST['max_amount']) >= 0 ? floatval($_POST['max_amount']) : 0;
    if ($max_amount > 0 && $min_amount > $max_amount) {
        sys_msg($_LANG['amount_error']);
    }

    /* 取得赠品 */
    $gift = array();
    if (intval($_POST['act_type']) == FAT_GOODS && isset($_POST['gift_id'])) {
        foreach ($_POST['gift_id'] as $key => $id) {
            $gift[] = array('id' => $id, 'name' => $_POST['gift_name'][$key], 'price' => $_POST['gift_price'][$key]);
        }
    }
    /*处理图片*/
    if ($_FILES['touch_img']['error'] == 0) {
        $img_name = basename($image->upload_image($_FILES['touch_img'], 'favourable'));
        /* 提交值 */
        $favourable = array(
        'act_id'        => intval($_POST['id']),
        'act_name'      => $act_name,
        'start_time'    => local_strtotime($_POST['start_time']),
        'end_time'      => local_strtotime($_POST['end_time']),
        'user_rank'     => isset($_POST['user_rank']) ? join(',', $_POST['user_rank']) : '0',
        'act_range'     => intval($_POST['act_range']),
        'act_range_ext' => intval($_POST['act_range']) == 0 ? '' : join(',', $_POST['act_range_ext']),
        'min_amount'    => floatval($_POST['min_amount']),
        'max_amount'    => floatval($_POST['max_amount']),
        'act_type'      => intval($_POST['act_type']),
        'act_type_ext'  => floatval($_POST['act_type_ext']),
        'gift'          => serialize($gift),
        'touch_img'     => $img_name,
    );
    } else {
        $sql = "SELECT touch_img FROM " .$ecs->table('favourable_activity'). " WHERE act_id = '".$_POST['id']."'";
        $img_name = $db->getOne($sql);
        $favourable = array(
        'act_id'        => intval($_POST['id']),
        'act_name'      => $act_name,
        'start_time'    => local_strtotime($_POST['start_time']),
        'end_time'      => local_strtotime($_POST['end_time']),
        'user_rank'     => isset($_POST['user_rank']) ? join(',', $_POST['user_rank']) : '0',
        'act_range'     => intval($_POST['act_range']),
        'act_range_ext' => intval($_POST['act_range']) == 0 ? '' : join(',', $_POST['act_range_ext']),
        'min_amount'    => floatval($_POST['min_amount']),
        'max_amount'    => floatval($_POST['max_amount']),
        'act_type'      => intval($_POST['act_type']),
        'act_type_ext'  => floatval($_POST['act_type_ext']),
        'gift'          => serialize($gift),
        'touch_img'     => $img_name,
    );
    }
    if ($favourable['act_type'] == FAT_GOODS) {
        $favourable['act_type_ext'] = round($favourable['act_type_ext']);
    }

    /* 保存数据 */
    if ($is_add) {
        $db->autoExecute($ecs->table('favourable_activity'), $favourable, 'INSERT');
        $favourable['act_id'] = $db->insert_id();
    } else {
        $db->autoExecute($ecs->table('favourable_activity'), $favourable, 'UPDATE', "act_id = '$favourable[act_id]'");
    }

    /* 记日志 */
    if ($is_add) {
        admin_log($favourable['act_name'], 'add', 'favourable');
    } else {
        admin_log($favourable['act_name'], 'edit', 'favourable');
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 提示信息 */
    if ($is_add) {
        $links = array(
            array('href' => 'favourable.php?act=add', 'text' => $_LANG['continue_add_favourable']),
            array('href' => 'favourable.php?act=list', 'text' => $_LANG['back_favourable_list'])
        );
        sys_msg($_LANG['add_favourable_ok'], 0, $links);
    } else {
        $links = array(
            array('href' => 'favourable.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_favourable_list'])
        );
        sys_msg($_LANG['edit_favourable_ok'], 0, $links);
    }
}

/*------------------------------------------------------ */
//-- 删除品牌图片
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_img') {
    /* 权限判断 */
    admin_priv('favourable');
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    /* 取得logo名称 */
    $sql = "SELECT touch_img FROM " .$ecs->table('favourable_activity'). " WHERE act_id = '$id'";
    $img_name = $db->getOne($sql);

    if (!empty($img_name)) {
        @unlink(ROOT_PATH . DATA_DIR . '/attached/favourable/' .$img_name);
        $sql = "UPDATE " .$ecs->table('favourable_activity'). " SET touch_img = '' WHERE act_id = '$id'";
        $db->query($sql);
    }
    $links = array(
        array('href' => 'favourable.php?act=list&' . list_link_postfix(), 'text' => $_LANG['back_favourable_list'])
    );
    sys_msg($_LANG['edit_favourable_ok'], 0, $links);
}

/*------------------------------------------------------ */
//-- 搜索商品
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search') {
    /* 检查权限 */
    check_authz_json('favourable');

    // include_once(ROOT_PATH . 'includes/cls_json.php');

    $json   = new JSON;
    $filter = $json->decode($_GET['JSON']);
    $filter->keyword = json_str_iconv($filter->keyword);
    if ($filter->act_range == FAR_ALL) {
        $arr[0] = array(
            'id'   => 0,
            'name' => $_LANG['js_languages']['all_need_not_search']
        );
    } elseif ($filter->act_range == FAR_CATEGORY) {
        $sql = "SELECT cat_id AS id, cat_name AS name FROM " . $ecs->table('category') .
            " WHERE cat_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
        $arr = $db->getAll($sql);
    } elseif ($filter->act_range == FAR_BRAND) {
        $sql = "SELECT brand_id AS id, brand_name AS name FROM " . $ecs->table('brand') .
            " WHERE brand_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
        $arr = $db->getAll($sql);
    } else {
        $sql = "SELECT goods_id AS id, goods_name AS name FROM " . $ecs->table('goods') .
            " WHERE goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%'" .
            " OR goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' LIMIT 50";
        $arr = $db->getAll($sql);
    }
    if (empty($arr)) {
        $arr = array(0 => array(
            'id'   => 0,
            'name' => $_LANG['search_result_empty']
        ));
    }

    make_json_result($arr);
}

/*
 * 取得优惠活动列表
 * @return   array
 */
function favourable_list()
{
    $result = get_filter();
    if ($result === false) {
        /* 过滤条件 */
        $filter['keyword']    = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['is_going']   = empty($_REQUEST['is_going']) ? 0 : 1;
        $filter['sort_by']    = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

        $where = "";
        if (!empty($filter['keyword'])) {
            $where .= " AND act_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
        }
        if ($filter['is_going']) {
            $now = gmtime();
            $where .= " AND start_time <= '$now' AND end_time >= '$now' ";
        }

        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('favourable_activity') .
                " WHERE 1 $where";
        $filter['record_count'] = $GLOBALS['db']->getOne($sql);

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */
        $sql = "SELECT * ".
                "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
                " WHERE 1 $where ".
                " ORDER BY $filter[sort_by] $filter[sort_order] ".
                " LIMIT ". $filter['start'] .", $filter[page_size]";

        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql);
    } else {
        $sql    = $result['sql'];
        $filter = $result['filter'];
    }
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        $row['start_time']  = local_date('Y-m-d H:i', $row['start_time']);
        $row['end_time']    = local_date('Y-m-d H:i', $row['end_time']);

        $list[] = $row;
    }

    return array('item' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
}

// 同一类型优惠范围（分类或品牌） -qin
function get_act_range_ext($act_range, $act_id)
{
    if ($act_range > 0)
    {
        $a_range = " AND act_range = '$act_range' ";
    }
    $now = gmtime();
   
    $sql = "SELECT act_range_ext " .
            "FROM " . $GLOBALS['ecs']->table('favourable_activity') .
            " WHERE  end_time >= '$now' " . $a_range ." AND act_id <> '$act_id'  ";

    $res = $GLOBALS['db']->getAll($sql);

    $arr=array();
    foreach ($res as $key => $row)
    {
        $arr = array_merge($arr, explode(',', $row['act_range_ext']));
    }
    
    return array_unique($arr);
}

// 获取分类或品牌下得所有商品
function get_range_goods($act_range, $act_range_ext_list, $create_in)
{
    if (empty($act_range_ext_list))
    {
        return array();
    }
    
    switch ($act_range)
    {
        case FAR_CATEGORY:

            $id_list = array();
            foreach ($act_range_ext_list as $id)
            {

                /**
                * 当前分类下的所有子分类
                * 返回一维数组
                */

                $cat_keys = get_array_keys_cat(intval($id));
               
                $id_list = array_merge($id_list, $cat_keys);
            }
            break;
        case FAR_BRAND:
            $id_list = $act_range_ext_list;
            break;
        case FAR_GOODS:
            $id_list = $act_range_ext_list;
            break; 

        default:
            break;
    }
   
    $sql = "SELECT goods_id FROM ".$GLOBALS['ecs']->table('goods')." WHERE  ".  db_create_in($id_list, $create_in);
    $res = $GLOBALS['db']->query($sql);
    $arr_goods_id = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $arr_goods_id[] = $row['goods_id'];                                                                                                      
    }
    return $arr_goods_id;
}

/**
 *获取分类id的一维数组
 */
function get_array_keys_cat($cat_id = 0, $type = 0, $table = 'category'){
    $res = cat_list($cat_id, 0, false);
    $arr = array();
    foreach ($res as $key => $val) {      
            $arr[] = $val['cat_id'];
    }
    return $arr;   
}

/**
*获取全部商品的优惠活动与现在添加/编辑的优惠活动是否冲突
**/
function get_all_goods($act_id, $act_type){
    $now = gmtime();
    $sql = "SELECT act_type, act_id  FROM ".$GLOBALS['ecs']->table('favourable_activity')." WHERE act_range = 0 and end_time >= '$now' and  act_id <> '$act_id'";       
    $res = $GLOBALS['db']->getRow($sql);

    if($res['act_type']){
        $result = $res['act_type'] == $act_type ? 1 : 0;
        if($result == 1){
            return 1;
        }else{
            return false;
        }
    }
    return false;
}
