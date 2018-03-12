<?php

/**
 * 管理中心售后服务管理
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');
include_once(ROOT_PATH . 'include/classes/image.php');
$image = new image($_CFG['bgcolor']);

require_once(ROOT_PATH . 'include/helpers/order_helper.php');
require_once(ROOT_PATH . 'include/helpers/goods_helper.php');
require_once(ROOT_PATH . 'include/helpers/transaction_helper.php');


$exc = new exchange($ecs->table('order_return'), $db, 'ret_id', 'service_sn');
/* ------------------------------------------------------ */
//-- 服务类型列表
/* ------------------------------------------------------ */
if ($_REQUEST['act'] == 'aftermarket_list') {
    /* 检查权限 */
    admin_priv('aftermarket_list');
    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['12_back_apply']);
    $smarty->assign('full_page', 1);
    $order_list = return_order_list();
    $smarty->assign('order_list', $order_list['orders']);
    $smarty->assign('filter', $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count', $order_list['page_count']);
    assign_query_info();
    $smarty->display('aftermarket_list.htm');
}
/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query') {
    /* 检查权限 */
    admin_priv('order_view');

    $order_list = return_order_list();

    $smarty->assign('order_list', $order_list['orders']);
    $smarty->assign('filter', $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count', $order_list['page_count']);
    $sort_flag  = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    make_json_result($smarty->fetch('aftermarket_list.htm'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}
/* ------------------------------------------------------ */
//-- 服务类型详情
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'aftermarket_info') {
    /* 检查权限 */
    admin_priv('aftermarket_list');

    $ret_id = intval(trim($_REQUEST['ret_id']));
    /* 根据发货单id查询发货单信息 */
    if (!empty($ret_id)) {
        $back_order = aftermarket_info($ret_id);
        $order = order_info($back_order['order_id']);
    } else {
        die('order does not exist');
    }
    /* 如果管理员属于某个办事处，检查该订单是否也属于这个办事处 */
    $sql = "SELECT agency_id FROM " . $ecs->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
    $agency_id = $db->getOne($sql);
    if ($agency_id > 0) {
        if ($back_order['agency_id'] != $agency_id) {
            sys_msg($_LANG['priv_error']);
        }
        /* 取当前办事处信息 */
        $sql = "SELECT agency_name FROM " . $ecs->table('agency') . " WHERE agency_id = '$agency_id' LIMIT 0, 1";
        $agency_name = $db->getOne($sql);
        $back_order['agency_name'] = $agency_name;
    }
    /* 取得用户名 */
    if ($back_order['user_id'] > 0 || $order['user_id'] > 0) {
        $user = user_info($back_order['user_id']);
        if (!empty($user)) {
            $back_order['user_name'] = $user['user_name'];
            $order['user_name'] = $user['user_name'];
        }
    }
    /* 是否保价 */
    $back_order['insure_yn'] = empty($order['insure_fee']) ? 0 : 1; /* 取得发货单商品 */;
    $goods_list = get_return_order_goods_list($back_order['rec_id']);

    /* 取得能执行的操作列表 */
    $operable_list = operable_list($back_order);
    $smarty->assign('operable_list', $operable_list);

    /* 取得退换货订单商品 */
    $return_list = get_return_goods($ret_id);

    /* 取得可用的配送方式列表 */
    $region_id_list = array(
        $back_order['country'], $back_order['province'], $back_order['city'], $back_order['district']
    );
    $shipping_list = available_shipping_list($region_id_list);
    /* 是否存在实体商品 */
    $exist_real_goods = 0;
    if ($goods_list) {
        foreach ($goods_list as $value) {
            if ($value['is_real']) {
                $exist_real_goods++;
            }
        }
    }
    /* 取得配送费用 */
    $total = order_weight_price($order_id);
    foreach ($shipping_list as $key => $shipping) {
        $shipping_fee = shipping_fee($shipping['shipping_code'], unserialize($shipping['configure']), $total['weight'], $total['amount'], $total['number']); //计算运费
        $free_price = free_price($shipping['configure']);   //免费额度
        $shipping_list[$key]['shipping_fee'] = $shipping_fee;
        $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
        $shipping_list[$key]['free_money'] = price_format($free_price['configure']['free_money']);
    }
    $smarty->assign('shipping_list', $shipping_list);

    /* 其他处理 */
    $order['order_time'] = local_date($_CFG['time_format'], $order['add_time']);
    $order['pay_time'] = $order['pay_time'] > 0 ?
            local_date($_CFG['time_format'], $order['pay_time']) : $_LANG['ps'][PS_UNPAYED];
    $order['shipping_time'] = $order['shipping_time'] > 0 ?
            local_date($_CFG['time_format'], $order['shipping_time']) : $_LANG['ss'][SS_UNSHIPPED];
    $order['status'] = $_LANG['os'][$order['order_status']] . ',' . $_LANG['ps'][$order['pay_status']] . ',' . $_LANG['ss'][$order['shipping_status']];
    $order['invoice_no'] = $order['shipping_status'] == SS_UNSHIPPED || $order['shipping_status'] == SS_PREPARING ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];
    $order['sign_time'] = local_date($_CFG['time_format'], $order['sign_time']);

    /* 取得操作记录 */
    $action_list = get_return_action($ret_id);
    $smarty->assign('action_list', $action_list);
    /*服务详情*/
    $service = get_service_info($back_order['service_id']);
    /* 模板赋值 */
    $smarty->assign('back_order', $back_order);
    $smarty->assign('order', $order);
    $smarty->assign('service', $service);
    $smarty->assign('exist_real_goods', $exist_real_goods);
    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('return_list', $return_list);
    $smarty->assign('back_id', $back_id); // 发货单id

    /* 显示模板 */
    $smarty->assign('ur_here', $_LANG['back_operate'] . $_LANG['detail']);
    $smarty->assign('action_link', array('href' => 'aftermarket.php?act=aftermarket_list&' . list_link_postfix(), 'text' => $_LANG['10_back_order']));
    assign_query_info();
    $smarty->display('aftermarket_info.htm');
}

/* ------------------------------------------------------ */
//-- 显示图片
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'show_cert') {
    $rec_id = empty($_REQUEST['rec_id']) ? 0 : intval($_GET['rec_id']);
    $cert_img = get_cert_img($rec_id);
    $smarty->assign('cert_img', $cert_img);
    $smarty->display('aftermarket_show_image.htm');
}
/* ------------------------------------------------------ */
//-- 审核服务订单
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'check_service') {
    check_authz_json('aftermarket_list');

    $ret_id = intval($_POST['id']);
    $is_check = intval($_POST['val']);
    if ($exc->edit("is_check = '$is_check'", $ret_id)) {
        //审核通过更新到记录表
//        return_action($ret_id, RF_RECEIVE, '', $action_note);
        clear_cache_files();
        make_json_result($is_check);
    }
}
/* ------------------------------------------------------ */
//-- 操作订单状态（载入页面）
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'operate') {
    $order_id = '';
    $rec_id = ''; //by leah
    $ret_id = ''; //by Leah
    /* 检查权限 */
    admin_priv('aftermarket_rf_edit');

    /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
    if (isset($_REQUEST['order_id'])) {
        $order_id = $_REQUEST['order_id'];
        $rec_id = $_REQUEST['rec_id']; //by Leah
        $ret_id = $_REQUEST['ret_id']; //by Leah
    }
    $batch = isset($_REQUEST['batch']); // 是否批处理
    $action_note = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

    /* 确认 */
    if (isset($_POST['confirm'])) {
        $require_note = false;
        $action = $_LANG['op_confirm'];
        $operation = 'confirm';
    } /* 取消 */ elseif (isset($_POST['canceled'])) {
        $require_note = $_CFG['order_cancel_note'] == 1;
        $action = $_LANG['canceled'];
        $operation = 'canceled';
        $show_cancel_note = true;
        $order = order_info($order_id);
        if ($order['money_paid'] > 0) {
            $show_refund = true;
        }
        $anonymous = $order['user_id'] == 0;
    } /* 审核 */ elseif (isset($_POST['check'])) {
        $require_note = $_CFG['order_cancel_note'] == 1;
        $action = $_LANG['rf_check'];
        $operation = 'check';
    } /* 无效 */ elseif (isset($_POST['invalid'])) {
        $require_note = $_CFG['order_invalid_note'] == 1;
        $action = $_LANG['op_invalid'];
        $operation = 'invalid';
    } /* 售后 */ elseif (isset($_POST['after_service'])) {
        $require_note = true;
        $action = $_LANG['op_after_service'];
        $operation = 'after_service';
    } /* 退货 */ elseif (isset($_POST['return'])) {
        $require_note = $_CFG['order_return_note'] == 1;
        $order = order_info($order_id);

        if ($order['money_paid'] > 0) {
            $show_refund = true;
        }
        $anonymous = $order['user_id'] == 0;
        $action = $_LANG['op_return'];
        $operation = 'return';
    } /* 退款 */ elseif (isset($_POST['refund'])) {
        $require_note = $_CFG['order_return_note'] == 1;
        $order = order_info($order_id);
        $return = aftermarket_info($ret_id); //服务订单信息
        $return_refund = true;
        $anonymous = $order['user_id'] == 0;
        $action = $_LANG['op_return'];
        $operation = 'refund';
        $smarty->assign('refund_amount', $return['should_return']);
    } /* 收到退回商品 */ elseif (isset($_POST['receive_goods'])) {
        $require_note = false;
        $action = $_LANG['op_confirm'];
        $operation = 'receive_goods';
    } /* 换出商品 -- 快递信息 */ elseif (isset($_POST['swapped_out'])) {
        $require_note = true;
        $swapped_out = true;
        $order = order_info($order_id);
        $action = $_LANG['op_return'];
        $operation = 'swapped_out';

        /* 取得可用的配送方式列表 */
        $region_id_list = array(
            $order['country'], $order['province'], $order['city'], $order['district']
        );
        $shipping_list = available_shipping_list($region_id_list);
        /* 取得配送费用 */
        $smarty->assign('shipping_list', $shipping_list);
    }/* 完成退换货 */ elseif (isset($_POST['complete'])) {
        $require_note = false;
        $action = $_LANG['op_confirm'];
        $operation = 'complete';
    } /* 订单删除 */ elseif (isset($_POST['remove'])) {
        $require_note = false;
        $operation = 'remove';
        if (!$batch) {
            /* 检查能否操作 */
            $order = order_info($order_id);
            $operable_list = operable_list($order);
            if (!isset($operable_list['remove'])) {
                die('Hacking attempt');
            }
            /* 删除订单 */
            $db->query("DELETE FROM " . $ecs->table('order_info') . " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM " . $ecs->table('order_goods') . " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM " . $ecs->table('order_action') . " WHERE order_id = '$order_id'");
            $action_array = array('delivery', 'back');
            del_delivery($order_id, $action_array);

            /* todo 记录日志 */
            admin_log($order['order_sn'], 'remove', 'order');

            /* 返回 */
            sys_msg($_LANG['order_removed'], 0, array(array('href' => 'order.php?act=list&' . list_link_postfix(), 'text' => $_LANG['return_list'])));
        }
    }
    /* 直接处理还是跳到详细页面 */
    if (($require_note && $action_note == '') || isset($show_invoice_no) || isset($show_refund) || $swapped_out) {
        /* 模板赋值 */
        $smarty->assign('require_note', $require_note); // 是否要求填写备注
        $smarty->assign('action_note', $action_note);   // 备注
        $smarty->assign('show_cancel_note', isset($show_cancel_note)); // 是否显示取消原因
        $smarty->assign('show_invoice_no', isset($show_invoice_no)); // 是否显示发货单号
        $smarty->assign('show_return_refund', isset($return_refund)); // 是否显示退款 // by Leah
        $smarty->assign('swapped_out', isset($swapped_out)); // 换出商品寄出 // by Leah
        $smarty->assign('anonymous', isset($anonymous) ? $anonymous : true); // 是否匿名
        $smarty->assign('order_id', $order_id); // 订单id
        $smarty->assign('rec_id', $rec_id); // 订单商品id    //by Leah
        $smarty->assign('ret_id', $ret_id); // 订单商品id   // by Leah
        $smarty->assign('batch', $batch);   // 是否批处理
        $smarty->assign('operation', $operation); // 操作
        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['order_operate'] . $action);
        assign_query_info();
        $smarty->display('aftermarket_operate.htm');
    } else {
        /* 直接处理 */
        if (!$batch) {
            if ($_REQUEST['ret_id']) {
                ecs_header("Location: aftermarket.php?act=operate_post&order_id=" . $order_id .
                        "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "&rec_id=" . $rec_id . "&ret_id=" . $ret_id . "\n");
                exit;
            }
        } else {
            /* 多个订单 */

            ecs_header("Location: aftermarket.php?act=batch_operate_post&order_id=" . $order_id .
                    "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 操作订单状态（处理批量提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch_operate_post') {

    /* 检查权限 */
    admin_priv('aftermarket_rf_edit');

    /* 取得参数 */
    $service_sn   = $_REQUEST['order_id'];        // 订单id（逗号格开的多个订单id）
    $operation  = $_REQUEST['operation'];       // 订单操作
    $action_note= $_REQUEST['action_note'];     // 操作备注

    $order_id_list = explode(',', $service_sn);

    /* 初始化处理的订单sn */
    $sn_list = array();
    $sn_not_list = array();

    /* 确认 */
    if ('check' == $operation) {
        foreach ($order_id_list as $id_order) {
            $sql = "SELECT * FROM " . $ecs->table('order_return') .
                " WHERE service_sn = '$id_order'" .
                " AND is_check = '" . OS_UNCONFIRMED . "'";
            $order = $db->getRow($sql);

            if ($order) {
                $ret_id = $order['ret_id'];
                $rec_id = $order['rec_id'];
                $arr = array(
                    'return_status' => RF_APPLICATION,
                    'refund_status' => FF_NOREFUND,
                    'is_check' => RC_APPLY_SUCCESS,
                    'actual_return' => 0,
                );

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                //记录操作
                return_action($ret_id, RF_APPLICATION, FF_NOREFUND, RC_APPLY_SUCCESS, $action_note);
                $sn_list[] = $order['service_sn'];
            } else {
                $sn_not_list[] = $id_order;
            }
        }
        $sn_str = $_LANG['confirm_order'];
    }
    //取消
    elseif ('canceled' == $operation) {
        foreach ($order_id_list as $id_order) {
            $sql = "SELECT * FROM " . $ecs->table('order_return') .
                " WHERE service_sn = '$id_order'";
            $order = $db->getRow($sql);

            if ($order) {
                $ret_id = $order['ret_id'];
                $rec_id = $order['rec_id'];
                $cancel_note = isset($_REQUEST['cancel_note']) ? trim($_REQUEST['cancel_note']) : '';
                $arr = array(
                    'return_status' => RF_CANCELED,
                    'refund_status' => FF_NOREFUND,
                    'is_check' => RC_APPLY_FALSE,
                    'to_buyer' => $cancel_note, //商家给买家留言
                    'actual_return' => 0,
                );

                $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
                /*更新log*/
                return_action($ret_id, RF_CANCELED, FF_NOREFUND, RC_APPLY_FALSE, $action_note);

                $sn_list[] = $order['service_sn'];
            } else {
                $sn_not_list[] = $id_order;
            }
        }

        $sn_str = $_LANG['cancel_order'];
    }
    //售后
    elseif ('after_service' == $operation) {
        foreach ($order_id_list as $id_order) {
            $sql = "SELECT * FROM " . $ecs->table('order_return') .
                " WHERE service_sn = '$id_order'";
            $order = $db->getRow($sql);

            $return_info = return_order_info($order['ret_id']);
            if ($order) {
                $ret_id = $order['ret_id'];
                return_action($ret_id, $return_info['return_status'], FF_REFUND, RC_APPLY_SUCCESS, '[' . 售后 . '] ' . $action_note);

                $sn_list[] = $order['service_sn'];
            } else {
                $sn_not_list[] = $id_order;
            }
        }

        $sn_str = $_LANG['after_service_order'];
    } else {
        die('invalid params');
    }

    /* 取得备注信息 */
    if (empty($sn_not_list)) {
        $sn_list = empty($sn_list) ? '' : $_LANG['updated_order'] . join($sn_list, ',');
       
        $msg = $sn_list;
        $links[] = array('text' => $_LANG['return_list'], 'href' => 'aftermarket.php?act=aftermarket_list');
        sys_msg($msg, 0, $links);
    } else {
        $order_list_no_fail = array();
        $sql = "SELECT o.ret_id, o.rec_id, o.service_sn, o.order_sn, o.add_time, o.user_id, o.service_id, o.should_return , o.actual_return, o.is_check," .
                "o.return_status, o.refund_status, r.back_num, r.out_num, " .
                "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
                " FROM " . $GLOBALS['ecs']->table('order_return') . " AS o " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON u.user_id=o.user_id  " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('return_goods') . " AS r ON o.rec_id = r.rec_id " .
                " WHERE service_sn " . db_create_in($sn_not_list);
        $row = $GLOBALS['db']->getAll($sql);
        /* 格式话数据 */
        foreach ($row as $key => $value) {
            $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
            $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
            $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
            $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
            $row[$key]['add_time'] = local_date('m-d H:i', $value['add_time']);
            $row[$key]['back_num'] = number_format($value['back_num']);
            $row[$key]['service_id'] = $value['service_id'];
            $row[$key]['service_name'] = get_suppliers_name($value['seller_id']);
            $row[$key]['service_type'] = get_service_type($value['service_id'], true);
            $row[$key]['apply_user'] = get_user_info($value['user_id']);
            $row[$key]['is_check'] = $value['is_check'];
            if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
                /* 如果该订单为无效或取消则显示删除链接 */
                $row[$key]['can_remove'] = 1;
            } else {
                $row[$key]['can_remove'] = 0;
            }
        }

        /* 模板赋值 */
        $smarty->assign('order_info', $sn_str);
        $smarty->assign('action_link', array('href' => 'aftermarket.php?act=list', 'text' => $_LANG['return_list']));
        $smarty->assign('order_list', $row);

        /* 显示模板 */
        assign_query_info();
        $smarty->display('aftermarket_operate_info.htm');
    }
}

/* ------------------------------------------------------ */
//-- 操作订单状态（处理提交）
/* ------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'operate_post') {

    /* 检查权限 */
    admin_priv('aftermarket_rf_edit');

    /* 取得参数 */
    $order_id = intval(trim($_REQUEST['order_id']));        // 订单id
    $rec_id = empty($_REQUEST['rec_id']) ? 0 : $_REQUEST['rec_id'];     //by　Leah
    $ret_id = empty($_REQUEST['ret_id']) ? 0 : $_REQUEST['ret_id'];  //by Leah

    $operation = $_REQUEST['operation'];                 // 订单操作

    $order = order_info($order_id); //订单信息
    $back_order = aftermarket_info($ret_id); //服务订单信息

    /* 检查能否操作 */
    $operable_list = operable_list($back_order);
    if (!isset($operable_list[$operation])) {
        die('Hacking attempt');
    }
    /* 取得备注信息 */
    $action_note = $_REQUEST['action_note'];
    /* 初始化提示信息 */
    $msg = '';
    /* 审核通过售后服务 */
    if ('check' == $operation) {
        /* 标记服务订单为“审核通过” */
        $arr = array(
            'return_status' => RF_APPLICATION,
            'refund_status' => FF_NOREFUND,
            'is_check' => RC_APPLY_SUCCESS,
            'actual_return' => 0,
        );
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
        return_action($ret_id, RF_APPLICATION, FF_NOREFUND, RC_APPLY_SUCCESS, $action_note);
    } /* 收到退换货商品 */ elseif ('receive_goods' == $operation) {
        $arr = array('return_status' => RF_RECEIVE); //收到用户退回商品
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
        return_action($ret_id, RF_RECEIVE, FF_NOREFUND, RC_APPLY_SUCCESS, $action_note);
    } /* 换出商品寄出 */ elseif ('swapped_out' == $operation) {
        /*更新服务订单快递信息*/
        $shipping_id = $_REQUEST['shipping'];
        $invoice_no = $_REQUEST['invoice_no'];
        /*获取快递名称*/
        $shipping = shipping_info($shipping_id);
        
        $arr = array(
            'return_status' => RF_SWAPPED_OUT,
            'out_shipping_name' => $shipping['shipping_name'],
            'out_invoice_no' => $invoice_no
        ); /*换出商品寄出*/
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
       
        return_action($ret_id, RF_SWAPPED_OUT, FF_NOREFUND, RC_APPLY_SUCCESS, $action_note);
    } /* 完成退换货 */ elseif ('complete' == $operation) {
        $arr = array('return_status' => RF_COMPLETE); //换出商品寄出
        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
        return_action($ret_id, RF_COMPLETE, $back_order['refund_status'], RC_APPLY_SUCCESS, $action_note);
    } /* 取消 */ elseif ('canceled' == $operation) {
        /* 标记订单为“取消”，记录取消原因 */
        $cancel_note = isset($_REQUEST['cancel_note']) ? trim($_REQUEST['cancel_note']) : '';
        $arr = array(
            'return_status' => RF_CANCELED,
            'refund_status' => FF_NOREFUND,
            'is_check' => RC_APPLY_FALSE,
            'to_buyer' => $cancel_note, //商家给买家留言
            'actual_return' => 0,
        );

        $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
        /*更新log*/
        return_action($ret_id, RF_CANCELED, FF_NOREFUND, RC_APPLY_FALSE, $action_note);
    } /* 设为无效 */ elseif ('invalid' == $operation) {
        //TODO
    } /* 退款 */ elseif ('refund' == $operation) {
        //include_once(ROOT_PATH . 'includes/lib_transaction.php');
        /* 定义当前时间 */
        define('GMTIME_UTC', gmtime()); // 获取 UTC 时间戳
        $order_goods = get_order_goods($order);                     //订单商品
        foreach ($order_goods['goods_list'] as $key => $value) {
            $array_rec_id[] = $value['rec_id'];
        }
        $aftermarket_list = return_order_info_byId($order_id);        //退换货订单
        foreach ($aftermarket_list as $key => $value) {
            $array_rec_id1[] = $value['rec_id'];
        }
        $order_info = get_order_detail($order_id);
        if (!array_diff($array_rec_id, $array_rec_id1)) {
            //整单退换货
            $return_count = return_order_info_byId($order_id, 0);
        }
        /* 过滤数据 */
        $_REQUEST['refund'] = isset($_REQUEST['refund']) ? $_REQUEST['refund'] : ''; // 退款类型
        $_REQUEST['refund_amount'] = isset($_REQUEST['refund_amount']) ? $_REQUEST['refund_amount'] : 0; //退款金额
        $_REQUEST['refund_note'] = isset($_REQUEST['refund_note']) ? $_REQUEST['refund_note'] : ''; //退款说明

        /* 处理退款已付款订单 */
        $return_info = return_order_info($ret_id);        //退换货订单

        $refund_type = $_REQUEST['refund'];   //退款类型 1 线上 2 线下 默认为 1
        $refund_type = empty($refund_type) ? 1 : $refund_type;
        $refund_amount = $_REQUEST['refund_amount'] + $_REQUEST['shipping'];
        $refund_note = $_REQUEST['refund_note'];
        
        if ($return_info['refund_status'] == FF_NOREFUND) {
            /* 退款 */
            aftermarket_refund($order, $refund_type, $refund_amount, $refund_note);
                                     
            $arr = array(
                'refund_status' => FF_REFUND,
                'actual_return' => $refund_amount,
                'is_check' => RC_APPLY_SUCCESS,
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_return'), $arr, 'UPDATE', "rec_id = '$rec_id'");
        }
        /* 退货用积分 */
        return_surplus_integral_bonus($return_info['user_id'], $_REQUEST['refund_amount']);
        /* 记录log */
        return_action($ret_id, $return_info['return_status'], FF_REFUND, RC_APPLY_SUCCESS, $action_note);
    } elseif ('after_service' == $operation) {
        /* 记录log */
        return_action($ret_id, $return_info['return_status'], FF_REFUND, RC_APPLY_SUCCESS, '[' . 售后 . '] ' . $action_note);
    } else {
        die('invalid params');
    }

    $links[] = array('text' => $_LANG['aftermarket_info'], 'href' => 'aftermarket.php?act=aftermarket_info&ret_id=' . $ret_id . '&rec_id=' . $rec_id); //by Leah

    sys_msg($_LANG['act_ok'] . $msg, 0, $links);
} /* json */ elseif ($_REQUEST['act'] == 'json') {
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $func = $_REQUEST['func'];
    if ($func == 'get_goods_info') {
        /* 取得商品信息 */
        $goods_id = $_REQUEST['goods_id'];
        $sql = "SELECT goods_id, c.cat_name, goods_sn, goods_name, b.brand_name, " .
                "goods_number, market_price, shop_price, promote_price, " .
                "promote_start_date, promote_end_date, goods_brief, goods_type, is_promote " .
                "FROM " . $ecs->table('goods') . " AS g " .
                "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
                "LEFT JOIN " . $ecs->table('category') . " AS c ON g.cat_id = c.cat_id " .
                " WHERE goods_id = '$goods_id'";
        $goods = $db->getRow($sql);
        $today = gmtime();
        $goods['goods_price'] = ($goods['is_promote'] == 1 &&
                $goods['promote_start_date'] <= $today && $goods['promote_end_date'] >= $today) ?
                $goods['promote_price'] : $goods['shop_price'];

        /* 取得会员价格 */
        $sql = "SELECT p.user_price, r.rank_name " .
                "FROM " . $ecs->table('member_price') . " AS p, " .
                $ecs->table('user_rank') . " AS r " .
                "WHERE p.user_rank = r.rank_id " .
                "AND p.goods_id = '$goods_id' ";
        $goods['user_price'] = $db->getAll($sql);

        /* 取得商品属性 */
        $sql = "SELECT a.attr_id, a.attr_name, g.goods_attr_id, g.attr_value, g.attr_price, a.attr_input_type, a.attr_type " .
                "FROM " . $ecs->table('goods_attr') . " AS g, " .
                $ecs->table('attribute') . " AS a " .
                "WHERE g.attr_id = a.attr_id " .
                "AND g.goods_id = '$goods_id' ";
        $goods['attr_list'] = array();
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res)) {
            $goods['attr_list'][$row['attr_id']][] = $row;
        }
        $goods['attr_list'] = array_values($goods['attr_list']);

        echo $json->encode($goods);
    }
}

/**
 * 退换货
 * @return type
 */
function return_order_list()
{
    $result = get_filter();

    if ($result === false) {
        /* 过滤信息 */
        $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
        if (!empty($_GET['is_ajax']) && $_GET['is_ajax'] == 1) {
            $_REQUEST['consignee'] = json_str_iconv($_REQUEST['consignee']);
            //$_REQUEST['address'] = json_str_iconv($_REQUEST['address']);
        }
        $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
        $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
        $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
        $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
        $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
        $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : intval($_REQUEST['mobile']);
        $filter['country'] = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
        $filter['province'] = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
        $filter['city'] = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
        $filter['district'] = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
        $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
        $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
        $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
        $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
        $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
        $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
        $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
        $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
        $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'add_time' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['start_time'] = empty($_REQUEST['start_time']) ? '' : (strpos($_REQUEST['start_time'], '-') > 0 ? local_strtotime($_REQUEST['start_time']) : $_REQUEST['start_time']);
        $filter['end_time'] = empty($_REQUEST['end_time']) ? '' : (strpos($_REQUEST['end_time'], '-') > 0 ? local_strtotime($_REQUEST['end_time']) : $_REQUEST['end_time']);

        $where = 'WHERE 1 ';
        if ($filter['order_sn']) {
            $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
        }
        if ($filter['consignee']) {
            $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
        }
        if ($filter['email']) {
            $where .= " AND o.email LIKE '%" . mysql_like_quote($filter['email']) . "%'";
        }
        if ($filter['address']) {
            $where .= " AND o.address LIKE '%" . mysql_like_quote($filter['address']) . "%'";
        }
        if ($filter['zipcode']) {
            $where .= " AND o.zipcode LIKE '%" . mysql_like_quote($filter['zipcode']) . "%'";
        }
        if ($filter['tel']) {
            $where .= " AND o.tel LIKE '%" . mysql_like_quote($filter['tel']) . "%'";
        }
        if ($filter['mobile']) {
            $where .= " AND o.mobile LIKE '%" . mysql_like_quote($filter['mobile']) . "%'";
        }
        if ($filter['country']) {
            $where .= " AND o.country = '$filter[country]'";
        }
        if ($filter['province']) {
            $where .= " AND o.province = '$filter[province]'";
        }
        if ($filter['city']) {
            $where .= " AND o.city = '$filter[city]'";
        }
        if ($filter['district']) {
            $where .= " AND o.district = '$filter[district]'";
        }
        if ($filter['shipping_id']) {
            $where .= " AND o.shipping_id  = '$filter[shipping_id]'";
        }
        if ($filter['pay_id']) {
            $where .= " AND o.pay_id  = '$filter[pay_id]'";
        }
        if ($filter['order_status'] != -1) {
            $where .= " AND o.order_status  = '$filter[order_status]'";
        }
        if ($filter['shipping_status'] != -1) {
            $where .= " AND o.shipping_status = '$filter[shipping_status]'";
        }
        if ($filter['pay_status'] != -1) {
            $where .= " AND o.pay_status = '$filter[pay_status]'";
        }
        if ($filter['user_id']) {
            $where .= " AND o.user_id = '$filter[user_id]'";
        }
        if ($filter['user_name']) {
            $where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
        }
        if ($filter['start_time']) {
            $where .= " AND o.add_time >= '$filter[start_time]'";
        }
        if ($filter['end_time']) {
            $where .= " AND o.add_time <= '$filter[end_time]'";
        }

        //综合状态
        switch ($filter['composite_status']) {
            case CS_AWAIT_PAY:
                $where .= order_query_sql('await_pay');
                break;

            case CS_AWAIT_SHIP:
                $where .= order_query_sql('await_ship');
                break;

            case CS_FINISHED:
                $where .= order_query_sql('finished');
                break;

            case PS_PAYING:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.pay_status = '$filter[composite_status]' ";
                }
                break;
            case OS_SHIPPED_PART:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.shipping_status  = '$filter[composite_status]'-2 ";
                }
                break;
            default:
                if ($filter['composite_status'] != -1) {
                    $where .= " AND o.order_status = '$filter[composite_status]' ";
                }
        }

        /* 团购订单 */
        if ($filter['group_buy_id']) {
            $where .= " AND o.extension_code = 'group_buy' AND o.extension_id = '$filter[group_buy_id]' ";
        }

        /* 如果管理员属于某个办事处，只列出这个办事处管辖的订单 */
        $sql = "SELECT agency_id FROM " . $GLOBALS['ecs']->table('admin_user') . " WHERE user_id = '$_SESSION[admin_id]'";
        $agency_id = $GLOBALS['db']->getOne($sql);
        if ($agency_id > 0) {
            $where .= " AND o.agency_id = '$agency_id' ";
        }

        /* 分页大小 */
        $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

        if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
            $filter['page_size'] = intval($_REQUEST['page_size']);
        } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
            $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
        } else {
            $filter['page_size'] = 15;
        }

        /* 记录总数 */
        if ($filter['user_name']) {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_return') . " AS o ," .
                    $GLOBALS['ecs']->table('users') . " AS u " . $where;
        } else {
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_return') . " AS o " . $where;
        }

        $filter['record_count'] = $GLOBALS['db']->getOne($sql);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

        /* 查询 */
        $sql = "SELECT o.ret_id, o.rec_id, o.service_sn, o.order_sn, o.add_time, o.user_id, o.service_id, o.should_return , o.actual_return, o.is_check," .
                "o.return_status, o.refund_status, r.back_num, r.out_num, " .
                "IFNULL(u.user_name, '" . $GLOBALS['_LANG']['anonymous'] . "') AS buyer " .
                " FROM " . $GLOBALS['ecs']->table('order_return') . " AS o " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('users') . " AS u ON u.user_id=o.user_id  " .
                " LEFT JOIN " . $GLOBALS['ecs']->table('return_goods') . " AS r ON o.rec_id = r.rec_id " .
                $where .
                " ORDER BY $filter[sort_by] $filter[sort_order] " .
                " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

        foreach (array('service_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name') as $val) {
            $filter[$val] = stripslashes($filter[$val]);
        }
        set_filter($filter, $sql);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }

    $row = $GLOBALS['db']->getAll($sql);
    /* 格式话数据 */
    foreach ($row as $key => $value) {
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['add_time'] = local_date('m-d H:i', $value['add_time']);
        $row[$key]['back_num'] = number_format($value['back_num']);
        $row[$key]['service_id'] = $value['service_id'];
        $row[$key]['service_name'] = get_suppliers_name($value['seller_id']);
        $row[$key]['service_type'] = get_service_type($value['service_id'], true);
        $row[$key]['apply_user'] = get_user_info($value['user_id']);
        $row[$key]['is_check'] = $value['is_check'];

        if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED) {
            /* 如果该订单为无效或取消则显示删除链接 */
            $row[$key]['can_remove'] = 1;
        } else {
            $row[$key]['can_remove'] = 0;
        }
    }
    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

/**
 *  获取用户信息数组
 *
 * @access  public
 * @param
 *
 * @return array        $user       用户信息数组
 */
function get_user_info($id)
{
    $sql = 'SELECT  user_name' .
            ' FROM ' . $GLOBALS['ecs']->table('users') .
            " WHERE user_id = '$id'";
    $user = $GLOBALS['db']->getRow($sql);

    return $user['user_name'];
}

/**  by ecmoban Leah
 * 返回某个订单可执行的操作列表，包括权限判断
 * @param   array $order 订单信息 order_status, shipping_status, pay_status
 * @param   bool $is_cod 支付方式是否货到付款
 * @return  array   可执行的操作  confirm, pay, unpay, prepare, ship, unship, receive, cancel, invalid, return, drop
 * 格式 array('confirm' => true, 'pay' => true)
 */
function operable_list($order)
{
    /* 取得订单状态、发货状态、付款状态 */
    $rf = $order['return_status'];
    $rc = $order['is_check'];
    $ff = $order['refund_status'];
    /* 取得订单操作权限 */
    $actions = $_SESSION['action_list'];
    if ($actions == 'all') {
        $priv_list = array('rf' => true, 'rc' => true, 'ff' => true, 'edit' => true);
    } else {
        $actions = ',' . $actions . ',';
        $priv_list = array(
            'rf' => strpos($actions, ',aftermarket_rf_edit,') !== false,
            'rc' => strpos($actions, ',aftermarket_rc_edit,') !== false,
            'ff' => strpos($actions, ',aftermarket_ff_edit,') !== false,
            'edit' => strpos($actions, ',aftermarket_edit,') !== false
        );
    }
    
    $service_info = get_service_type($order['service_id']);
    /* 根据状态返回可执行操作 */
    $list = array();
    if (RC_APPLY_FALSE == $rc) {
        //未通过审核
        if ($priv_list['rc']) {
            /* 状态：申请=> 未通过审核 */
            $list['check'] = true; // 确认
            if (RF_CANCELED != $rf) {
                $list['canceled'] = true;
            }
        }
    } elseif (RC_APPLY_SUCCESS == $rc) {
        //通过审核
        if ($priv_list['rf']) {
            if ($service_info['service_type'] == ST_RETURN_GOODS) {
                /* 状态 退货退款 */
                if (RF_APPLICATION == $rf) {
                    /* 状态：申请=> 通过审核 */
                    $list['receive_goods'] = true;
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_SEND_OUT == $rf) {
                    /* 状态 买家商品寄出 */
                    $list['receive_goods'] = true;
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_RECEIVE == $rf) {
                    /* 状态:收到退回商品 */
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_COMPLETE == $rf) {
                    $list['canceled'] = true;
                } else {
                    $list['complete'] = true;
                }
                /* 退款状态 */
                if (FF_NOREFUND == $ff) {
                    //未退款
                    $list['refund'] = true;
                } elseif (FF_REFUND == $ff) {
                    //已退款
                }
            } elseif ($service_info['service_type'] == ST_EXCHANGE) {
                if (RF_APPLICATION == $rf) {
                    /* 状态：申请=> 通过审核 */
                    $list['receive_goods'] = true;
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_SEND_OUT == $rf) {
                    /* 状态 买家商品寄出 */
                    $list['receive_goods'] = true;
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_RECEIVE == $rf) {
                    /* 状态:收到退回商品 */
                    $list['swapped_out'] = true;
                    $list['complete'] = true;
                    $list['canceled'] = true;
                } elseif (RF_COMPLETE == $rf) {
                    $list['canceled'] = true;
                } else {
                    $list['complete'] = true;
                }
                /* 退款状态 */
                if (FF_NOREFUND == $ff) {
                    //未退款
                    $list['refund'] = true;
                } elseif (FF_REFUND == $ff) {
                    //已退款
                }
            }
        }
    }
    $list['after_service'] = true;
    return $list;
}

/**
 * 取得订单商品
 * @param   array $order 订单数组
 * @return array
 */
function get_order_goods($order)
{
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, g.suppliers_id AS suppliers_id,IF(o.product_id > 0, p.product_number, g.goods_number) AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name, p.product_sn " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON o.product_id = p.product_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('goods') . " AS g ON o.goods_id = g.goods_id " .
            "LEFT JOIN " . $GLOBALS['ecs']->table('brand') . " AS b ON g.brand_id = b.brand_id " .
            "WHERE o.order_id = '$order[order_id]' ";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res)) {
        // 虚拟商品支持
        if ($row['is_real'] == 0) {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $GLOBALS['_CFG']['lang'] . '.php';
            if (file_exists($filename)) {
                include_once($filename);
                if (!empty($GLOBALS['_LANG'][$row['extension_code'] . '_link'])) {
                    $row['goods_name'] = $row['goods_name'] . sprintf($GLOBALS['_LANG'][$row['extension_code'] . '_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal'] = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price'] = price_format($row['goods_price']);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组

        if ($row['extension_code'] == 'package_buy') {
            $row['storage'] = '';
            $row['brand_name'] = '';
            $row['package_goods_list'] = get_package_goods_list($row['goods_id']);
        }

        //处理货品id
        $row['product_id'] = empty($row['product_id']) ? 0 : $row['product_id'];

        $goods_list[] = $row;
    }

    $attr = array();
    $arr = array();
    foreach ($goods_attr as $index => $array_val) {
        foreach ($array_val as $value) {
            $arr = explode(':', $value); //以 : 号将属性拆开
            $attr[$index][] = @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    return array('goods_list' => $goods_list, 'attr' => $attr);
}
