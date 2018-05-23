<?php

/**
 * 短信模块
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

$exc = new exchange($ecs->table('sms'), $db, 'sms_code', 'sms_name');

/*------------------------------------------------------ */
//-- 列出短信方式
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list') {

    /* 查询数据库中启用的短信方式 */
    $sms_list = array();
    $sql = "SELECT * FROM " . $ecs->table('sms') . " WHERE enabled = '1' ORDER BY sms_order";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res)) {
        $sms_list[$row['sms_code']] = $row;
    }

    /* 取得插件文件中的短信方式 */
    $modules = read_modules('../include/modules/sms');
    for ($i = 0; $i < count($modules); $i++) {
        $code = $modules[$i]['code'];
        $modules[$i]['sms_code'] = $modules[$i]['code'];
        /* 如果数据库中有，取数据库中的名称和描述 */
        if (isset($sms_list[$code])) {
            $modules[$i]['name'] = $sms_list[$code]['sms_name'];
            $modules[$i]['desc'] = $sms_list[$code]['sms_desc'];
            $modules[$i]['sms_order'] = $sms_list[$code]['sms_order'];
            $modules[$i]['install'] = '1';
        } else {
            $modules[$i]['name'] = $_LANG[$modules[$i]['code']];
            $modules[$i]['desc'] = $_LANG[$modules[$i]['desc']];
            $modules[$i]['install'] = '0';
        }
    }

    assign_query_info();

    $smarty->assign('ur_here', $_LANG['11_sms_list']);
    $smarty->assign('modules', $modules);
    $smarty->display('sms_list.htm');
}

/*------------------------------------------------------ */
//-- 安装短信方式 ?act=install&code=".$code."
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'install') {
    admin_priv('sms');

    /* 取相应插件信息 */
    $set_modules = true;
    include_once(BASE_PATH.'modules/sms/' . $_REQUEST['code'] . '.php');

    $data = $modules[0];

    $sms['sms_code']    = $data['code'];
    $sms['sms_name']    = $_LANG[$data['code']];
    $sms['sms_desc']    = $_LANG[$data['desc']];
    $sms['sms_config']  = array();

    foreach ($data['config'] as $key => $value) {
        $config_desc = (isset($_LANG[$value['name'] . '_desc'])) ? $_LANG[$value['name'] . '_desc'] : '';
        $sms['sms_config'][$key] = $value +
            array('label' => $_LANG[$value['name']], 'value' => $value['value'], 'desc' => $config_desc);

        if ($sms['sms_config'][$key]['type'] == 'select' ||
            $sms['sms_config'][$key]['type'] == 'radiobox') {
            $sms['sms_config'][$key]['range'] = $_LANG[$sms['sms_config'][$key]['name'] . '_range'];
        }
    }
//dump($sms);exit;
    assign_query_info();

    $smarty->assign('action_link', array('text' => $_LANG['11_sms_list'], 'href' => 'sms.php?act=list'));

    $smarty->assign('sms', $sms);
    $smarty->display('sms_edit.htm');
} 
/*------------------------------------------------------ */
//-- 编辑短信方式 ?act=edit&code={$code}
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit') {
    admin_priv('sms');

    /* 查询该短信方式内容 */
    if (isset($_REQUEST['code'])) {
        $_REQUEST['code'] = trim($_REQUEST['code']);
    } else {
        die('invalid parameter');
    }

    $sql = "SELECT * FROM " . $ecs->table('sms') . " WHERE sms_code = '$_REQUEST[code]' AND enabled = '1'";
    $sms = $db->getRow($sql);
    if (empty($sms)) {
        $links[] = array('text' => $_LANG['back_list'], 'href' => 'sms.php?act=list');
        sys_msg($_LANG['sms_not_available'], 0, $links);
    }

    /* 取相应插件信息 */
    $set_modules = true;
    include_once(BASE_PATH . 'modules/sms/' . $_REQUEST['code'] . '.php');
    $data = $modules[0];

    /* 取得配置信息 */
    if (is_string($sms['sms_config'])) {
        $store = unserialize($sms['sms_config']);
        /* 取出已经设置属性的code */
        $code_list = array();
        foreach ($store as $key=>$value) {
            $code_list[$value['name']] = $value['value'];
        }
        $sms['sms_config'] = array();

        /* 循环插件中所有属性 */
        foreach ($data['config'] as $key => $value) {
            $sms['sms_config'][$key]['desc'] = (isset($_LANG[$value['name'] . '_desc'])) ? $_LANG[$value['name'] . '_desc'] : '';
            $sms['sms_config'][$key]['label'] = $_LANG[$value['name']];
            $sms['sms_config'][$key]['name'] = $value['name'];
            $sms['sms_config'][$key]['type'] = $value['type'];

            if (isset($code_list[$value['name']])) {
                $sms['sms_config'][$key]['value'] = $code_list[$value['name']];
            } else {
                $sms['sms_config'][$key]['value'] = $value['value'];
            }

            if ($sms['sms_config'][$key]['type'] == 'select' ||
                $sms['sms_config'][$key]['type'] == 'radiobox') {
                $sms['sms_config'][$key]['range'] = $_LANG[$sms['sms_config'][$key]['name'] . '_range'];
            }
        }
    }



    assign_query_info();

    $smarty->assign('action_link', array('text' => $_LANG['11_sms_list'], 'href' => 'sms.php?act=list'));
    $smarty->assign('ur_here', $_LANG['edit'] . $_LANG['sms']);
    $smarty->assign('sms', $sms);
    $smarty->display('sms_edit.htm');
}

/*------------------------------------------------------ */
//-- 提交短信方式 post
/*------------------------------------------------------ */
elseif (isset($_POST['Submit'])) {
   
    admin_priv('sms');

    /* 检查输入 */
    if (empty($_POST['sms_name'])) {
        sys_msg($_LANG['sms_name'] . $_LANG['empty']);
    }

    $sql = "SELECT COUNT(*) FROM " . $ecs->table('sms') .
            " WHERE sms_name = '$_POST[sms_name]' AND sms_code <> '$_POST[sms_code]'";
    if ($db->GetOne($sql) > 0) {
        sys_msg($_LANG['sms_name'] . $_LANG['repeat'], 1);
    }

    /* 取得配置信息 */
    $sms_config = array();
    if (isset($_POST['cfg_value']) && is_array($_POST['cfg_value'])) {
        for ($i = 0; $i < count($_POST['cfg_value']); $i++) {
            $sms_config[] = array('name'  => trim($_POST['cfg_name'][$i]),
                                  'type'  => trim($_POST['cfg_type'][$i]),
                                  'value' => trim($_POST['cfg_value'][$i])
            );
        }
    }

    $sms_config = serialize($sms_config);

    /* 检查是编辑还是安装 */
    $link[] = array('text' => $_LANG['back_list'], 'href' => 'sms.php?act=list');
    if ($_POST['sms_id']) {
        /* 编辑 */
        $sql = "UPDATE " . $ecs->table('sms') .
               "SET sms_name = '$_POST[sms_name]'," .
               "    sms_desc = '$_POST[sms_desc]'," .
               "    sms_config = '$sms_config', " .
               "WHERE sms_code = '$_POST[sms_code]' LIMIT 1";
        $db->query($sql);

        /* 记录日志 */
        admin_log($_POST['sms_name'], 'edit', 'sms');

        sys_msg($_LANG['edit_ok'], 0, $link);
    } else {
        /* 安装，检查该短信方式是否曾经安装过 */
        $sql = "SELECT COUNT(*) FROM " . $ecs->table('sms') . " WHERE sms_code = '$_REQUEST[sms_code]'";
        if ($db->GetOne($sql) > 0) {
            /* 该短信方式已经安装过, 将该短信方式的状态设置为 enable */
            $sql = "UPDATE " . $ecs->table('sms') .
                   "SET sms_name = '$_POST[sms_name]'," .
                   "    sms_desc = '$_POST[sms_desc]'," .
                   "    sms_config = '$sms_config'," .
                   "    enabled = '1' " .
                   "WHERE sms_code = '$_POST[sms_code]' LIMIT 1";
            $db->query($sql);
        } else {
            /* 该短信方式没有安装过, 将该短信方式的信息添加到数据库 */
            $sql = "INSERT INTO " . $ecs->table('sms') . " (sms_code, sms_name, sms_desc, sms_config, enabled)" .
                   "VALUES ('$_POST[sms_code]', '$_POST[sms_name]', '$_POST[sms_desc]', '$sms_config', 1)";
                  
            $db->query($sql);
        }

        /* 记录日志 */
        admin_log($_POST['sms_name'], 'install', 'sms');

        sys_msg($_LANG['install_ok'], 0, $link);
    }
}

/*------------------------------------------------------ */
//-- 卸载短信方式 ?act=uninstall&code={$code}
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'uninstall') {
    admin_priv('sms');

    /* 把 enabled 设为 0 */
    $sql = "UPDATE " . $ecs->table('sms') .
           "SET enabled = '0' " .
           "WHERE sms_code = '$_REQUEST[code]' LIMIT 1";
    $db->query($sql);

    /* 记录日志 */
    admin_log($_REQUEST['code'], 'uninstall', 'sms');

    $link[] = array('text' => $_LANG['back_list'], 'href' => 'sms.php?act=list');
    sys_msg($_LANG['uninstall_ok'], 0, $link);
}

/*------------------------------------------------------ */
//-- 修改短信方式名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_name') {
    /* 检查权限 */
    check_authz_json('sms');

    /* 取得参数 */
    $code = json_str_iconv(trim($_POST['id']));
    $name = json_str_iconv(trim($_POST['val']));

    /* 检查名称是否为空 */
    if (empty($name)) {
        make_json_error($_LANG['name_is_null']);
    }

    /* 检查名称是否重复 */
    if (!$exc->is_only('sms_name', $name, $code)) {
        make_json_error($_LANG['name_exists']);
    }

    /* 更新短信方式名称 */
    $exc->edit("sms_name = '$name'", $code);
    make_json_result(stripcslashes($name));
}


/*------------------------------------------------------ */
//-- 修改短信方式排序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_order') {
    /* 检查权限 */
    check_authz_json('sms');

    /* 取得参数 */
    $code = json_str_iconv(trim($_POST['id']));
    $order = intval($_POST['val']);

    /* 更新排序 */
    $exc->edit("sms_order = '$order'", $code);
    make_json_result(stripcslashes($order));
}

$action = isset($_REQUEST['act']) ? $_REQUEST['act'] : 'display_my_info';
if (isset($_POST['sms_sign_update'])) {
    $action ='sms_sign_update';
} elseif (isset($_POST['sms_sign_default'])) {
    $action ='sms_sign_default';
}

$sms = new sms();

switch ($action) {
//    /* 注册短信服务。*/
//    case 'register' :
//        $email      = isset($_POST['email'])    ? $_POST['email']       : '';
//        $password   = isset($_POST['password']) ? $_POST['password']    : '';
//        $domain     = isset($_POST['domain'])   ? $_POST['domain']      : '';
//        $phone      = isset($_POST['phone'])    ? $_POST['phone']       : '';
//
//        $result = $sms->register($email, $password, $domain, $phone);
//
//        $link[] = array('text'  =>  $_LANG['back'],
//                        'href'  =>  'sms.php?act=display_my_info');
//
//        if ($result === true)//注册成功
//        {
//            sys_msg($_LANG['register_ok'], 0, $link);
//        }
//        else
//        {
//            @$error_detail = $_LANG['server_errors'][$sms->errors['server_errors']['error_no']]
//                          . $_LANG['api_errors']['register'][$sms->errors['api_errors']['error_no']];
//            sys_msg($_LANG['register_error'] . $error_detail, 1, $link);
//        }
//
//        break;
//
//    /* 启用短信服务。 */
//    case 'enable' :
//        $username = isset($_POST['email'])      ? $_POST['email']       : '';
//        //由于md5函数对空串也加密，所以要进行判空操作
//        $password = isset($_POST['password']) && $_POST['password'] !== ''
//                ? md5($_POST['password'])
//                : '';
//
//        $result = $sms->restore($username, $password);
//
//        $link[] = array('text'  =>  $_LANG['back'],
//                        'href'  =>  'sms.php?act=display_my_info');
//
//        if ($result === true)//启用成功
//        {
//            sys_msg($_LANG['enable_ok'], 0, $link);
//        }
//        else
//        {
//            @$error_detail = $_LANG['server_errors'][$sms->errors['server_errors']['error_no']]
//                          . $_LANG['api_errors']['auth'][$sms->errors['api_errors']['error_no']];
//            sys_msg($_LANG['enable_error'] . $error_detail, 1, $link);
//        }
//
//        break;
//
//    /* 注销短信特服信息 */
//    case 'disable' :
//        $result = $sms->clear_my_info();
//
//        $link[] = array('text'  =>  $_LANG['back'],
//                        'href'  =>  'sms.php?act=display_my_info');
//
//        if ($result === true)//注销成功
//        {
//            sys_msg($_LANG['disable_ok'], 0, $link);
//        }
//        else
//        {
//            sys_msg($_LANG['disable_error'], 1, $link);
//        }
//
//        break;

    /* 显示短信发送界面，如果尚未注册或启用短信服务则显示注册界面。 */
    case 'display_send_ui':
        /* 检查权限 */
         admin_priv('sms_send');

        if ($sms->has_registered()) {
            $smarty->assign('ur_here', $_LANG['03_sms_send']);
            $special_ranks = get_rank_list();
            $send_rank['1_0'] = $_LANG['user_list'];
            foreach ($special_ranks as $rank_key => $rank_value) {
                $send_rank['2_' . $rank_key] = $rank_value;
            }
            assign_query_info();
            $smarty->assign('send_rank', $send_rank);
            $smarty->display('sms_send_ui.htm');
        } else {
            $smarty->assign('ur_here', $_LANG['register_sms']);
            $smarty->assign('sms_site_info', $sms->get_site_info());
            assign_query_info();
            $smarty->display('sms_register_ui.htm');
        }

        break;
      case 'sms_sign':
         admin_priv('sms_send');
         
        if ($sms->has_registered()) {
            $sql="SELECT * FROM ". $ecs->table('shop_config') . "WHERE  code='sms_sign'";
            $row=$db->getRow($sql);
            if (!empty($row['id'])) {
                $sms_sign=unserialize($row['value']);
                $t=array();
                if (is_array($sms_sign) && isset($sms_sign[$_CFG[ent_id]])) {
                    foreach ($sms_sign[$_CFG[ent_id]] as $key=>$val) {
                        $t[$_CFG[ent_id]][$key]['key']=$key;
                        $t[$_CFG[ent_id]][$key]['value']=$val;
                    }
                    $smarty->assign('sms_sign', $t[$_CFG[ent_id]]);
                }
            } else {
                shop_config_update('sms_sign', '');
                shop_config_update('default_sms_sign', '');
            }
            $sql="SELECT * FROM ". $ecs->table('shop_config') . "WHERE  code='default_sms_sign'";
            $default_sms_sign=$db->getRow($sql);
            $smarty->assign('default_sign', $default_sms_sign['value']);



            $smarty->display('sms_sign.htm');
        } else {
            $smarty->assign('ur_here', $_LANG['register_sms']);
            $smarty->assign('sms_site_info', $sms->get_site_info());
            assign_query_info();
            $smarty->display('sms_register_ui.htm');
        }
        break;

        case 'sms_sign_add':
        admin_priv('sms_send');

        if ($sms->has_registered()) {
            $sql="SELECT * FROM ". $ecs->table('shop_config') . "WHERE  code='sms_sign'";
            $row=$db->getRow($sql);
            if (empty($_POST['sms_sign'])) {
                sys_msg($_LANG['insert_sign'], 1, array(), false);
            }

            if (!empty($row['id'])) {
                $sms_sign=unserialize($row['value']);
                $smarty->assign('sms_sign', $sms_sign);
                $data=array();
                $data['shopexid']=$_CFG['ent_id'];
                $data['passwd']=$_CFG['ent_ac'];

                $content_t=$content_y=trim($_POST['sms_sign']);
                if (CHARSET != 'utf-8') {
                    $content_t= iconv('gb2312', 'utf-8', $content_y);
                }

                $url = 'https://openapi.shopex.cn';
                $key = 'qufoxtpr';
                $secret = 't66moqjixb2nntiy2io2';
                $c = new prism_client($url, $key, $secret);
                $params=array(
                'shopexid'=>$_CFG['ent_id'],
                'passwd'=>$_CFG['ent_ac'],
                'content'=>$content_t,
                'content-type'=>'application/x-www-form-urlencoded'
                );
                $result=$c->post('api/addcontent/new', $params);
                $result=json_decode($result, true);
                if ($result['res']=='succ' && !empty($result['data']['extend_no'])) {
                    $extend_no=$result['data']['extend_no'];
                    $sms_sign[$_CFG['ent_id']][$extend_no]=$content_y;
                    $sms_sign=serialize($sms_sign);
                    if (empty($_CFG['default_sms_sign'])) {
                        shop_config_update('default_sms_sign', $content_y);
                    }
                    shop_config_update('sms_sign', $sms_sign);
                    /* 清除缓存 */
                    clear_all_files();
                    sys_msg($_LANG['insert_succ'], 1, array(), false);
                } else {
                    $error_smg=$result['data'];
                    if (CHARSET != 'utf-8') {
                        $error_smg= iconv('utf-8', 'gb2312', $error_smg);
                    }
                    sys_msg($error_smg, 1, array(), false);
                }
            } else {
                shop_config_update('default_sms_sign', $content_y);
                shop_config_update('sms_sign', '');
                /* 清除缓存 */
                clear_all_files();
                sys_msg($_LANG['error_smg'], 1, array(), false);
            }
        } else {
            $smarty->assign('ur_here', $_LANG['register_sms']);
            $smarty->assign('sms_site_info', $sms->get_site_info());
            assign_query_info();
            $smarty->display('sms_register_ui.htm');
        }
         break;  


        case 'sms_sign_update':
        admin_priv('sms_send');
        if ($sms->has_registered()) {
            $sql="SELECT * FROM ". $ecs->table('shop_config') . "WHERE  code='sms_sign'";
            $row=$db->getRow($sql);
            if (!empty($row['id'])) {
                $sms_sign=unserialize($row['value']);
                $smarty->assign('sms_sign', $sms_sign);
                $data=array();
                $data['shopexid']=$_CFG['ent_id'];
                $data['passwd']=$_CFG['ent_ac'];
                
                $extend_no=$_POST['extend_no'];

                $content_t=$content_y=$sms_sign[$_CFG['ent_id']][$extend_no];
                $new_content_t=$new_content_y=$_POST['new_sms_sign'];

                if (!isset($sms_sign[$_CFG[ent_id]][$extend_no]) || empty($extend_no)) {
                    sys_msg($_LANG['error_smg'], 1, array(), false);
                }
                if (CHARSET != 'utf-8') {
                    $content_t= iconv('gb2312', 'utf-8', $content_y);
                    $new_content_t= iconv('gb2312', 'utf-8', $new_content_y);
                }
                $url = 'https://openapi.shopex.cn';
                $key = 'qufoxtpr';
                $secret = 't66moqjixb2nntiy2io2';
                $c = new prism_client($url, $key, $secret);
                $params=array(
                'shopexid'=>$_CFG['ent_id'],
                'passwd'=>$_CFG['ent_ac'],
                'old_content'=>$content_t,
                'new_content'=>$new_content_t,
                'content-type'=>'application/x-www-form-urlencoded'
                );
                $result=$c->post('api/addcontent/update', $params);
                $result=json_decode($result, true);

                if ($result['res']=='succ' && !empty($result['data']['new_extend_no'])) {
                    $new_extend_no=$result['data']['new_extend_no'];
                    unset($sms_sign[$_CFG['ent_id']][$extend_no]);
                    $sms_sign[$_CFG['ent_id']][$new_extend_no]=$new_content_y;

                    $sms_sign=serialize($sms_sign);
                    if (empty($_CFG['default_sms_sign'])) {
                        shop_config_update('default_sms_sign', $new_content_y);
                    }
                    shop_config_update('sms_sign', $sms_sign);

                    /* 清除缓存 */
                    clear_all_files();
                    sys_msg($_LANG['edit_succ'], 1, array(), false);
                } else {
                    $error_smg=$result['data'];
                    if (CHARSET != 'utf-8') {
                        $error_smg= iconv('utf-8', 'gb2312', $error_smg);
                    }
                    sys_msg($error_smg, 1, array(), false);
                }
            } else {
                shop_config_update('default_sms_sign', $content_y);
                shop_config_update('sms_sign', '');
                /* 清除缓存 */
                clear_all_files();
                sys_msg($_LANG['error_smg'], 1, array(), false);
            }
        } else {
            $smarty->assign('ur_here', $_LANG['register_sms']);
            $smarty->assign('sms_site_info', $sms->get_site_info());
            assign_query_info();
            $smarty->display('sms_register_ui.htm');
        }
         break;

        case 'sms_sign_default':
        admin_priv('sms_send');
        if ($sms->has_registered()) {
            $sql="SELECT * FROM ". $ecs->table('shop_config') . "WHERE  code='sms_sign'";
            $row=$db->getRow($sql);
            if (!empty($row['id'])) {
                $sms_sign=unserialize($row['value']);
                $smarty->assign('sms_sign', $sms_sign);
                $data=array();
                $data['shopexid']=$_CFG['ent_id'];
                $data['passwd']=$_CFG['ent_ac'];
                
                $extend_no=$_POST['extend_no'];

                $sms_sign_default=$sms_sign[$_CFG[ent_id]][$extend_no];
                if (!empty($sms_sign_default)) {
                    shop_config_update('default_sms_sign', $sms_sign_default);
                    /* 清除缓存 */
                    clear_all_files();
                    sys_msg($_LANG['default_succ'], 1, array(), false);
                } else {
                    sys_msg($_LANG['no_default'], 1, array(), false);
                }
            } else {
                shop_config_update('default_sms_sign', $content_y);
                shop_config_update('sms_sign', '');
                /* 清除缓存 */
                clear_all_files();
                sys_msg($_LANG['error_smg'], 1, array(), false);
            }
        } else {
            $smarty->assign('ur_here', $_LANG['register_sms']);
            $smarty->assign('sms_site_info', $sms->get_site_info());
            assign_query_info();
            $smarty->display('sms_register_ui.htm');
        }
         break;





    /* 发送短信 */
    case 'send_sms':
        $send_num = isset($_POST['send_num'])   ? $_POST['send_num']    : '';

        if (isset($send_num)) {
            $phone = $send_num.',';
        }

        $send_rank = isset($_POST['send_rank'])     ? $_POST['send_rank'] : 0;

        if ($send_rank != 0) {
            $rank_array = explode('_', $send_rank);

            if ($rank_array['0'] == 1) {
                $sql = 'SELECT mobile_phone FROM ' . $ecs->table('users') . "WHERE mobile_phone <>'' ";
                $row = $db->query($sql);
                while ($rank_rs = $db->fetch_array($row)) {
                    $value[] = $rank_rs['mobile_phone'];
                }
            } else {
                $rank_sql = "SELECT * FROM " . $ecs->table('user_rank') . " WHERE rank_id = '" . $rank_array['1'] . "'";
                $rank_row = $db->getRow($rank_sql);
                //$sql = 'SELECT mobile_phone FROM ' . $ecs->table('users') . "WHERE mobile_phone <>'' AND rank_points > " .$rank_row['min_points']." AND rank_points < ".$rank_row['max_points']." ";

                if ($rank_row['special_rank']==1) {
                    $sql = 'SELECT mobile_phone FROM ' . $ecs->table('users') . " WHERE mobile_phone <>'' AND user_rank = '" . $rank_array['1'] . "'";
                } else {
                    $sql = 'SELECT mobile_phone FROM ' . $ecs->table('users') . "WHERE mobile_phone <>'' AND rank_points > " .$rank_row['min_points']." AND rank_points < ".$rank_row['max_points']." ";
                }
                
                $row = $db->query($sql);
                
                while ($rank_rs = $db->fetch_array($row)) {
                    $value[] = $rank_rs['mobile_phone'];
                }
            }
            if (isset($value)) {
                $phone .= implode(',', $value);
            }
        }
      
        $msg       = isset($_POST['msg'])       ? $_POST['msg']         : '';
        

        $send_date = isset($_POST['send_date']) ? $_POST['send_date']   : '';
               
        $result = $sms->send($phone, $msg, $send_date, $send_num = 13);

        $link[] = array('text'  =>  $_LANG['back'] . $_LANG['03_sms_send'],
                        'href'  =>  'sms.php?act=display_send_ui');

        if ($result === true) {//发送成功
            sys_msg($_LANG['send_ok'], 0, $link);
        } else {
            @$error_detail = $_LANG['server_errors'][$sms->errors['server_errors']['error_no']]
                          . $_LANG['api_errors']['send'][$sms->errors['api_errors']['error_no']];
            sys_msg($_LANG['send_error'] . $error_detail, 1, $link);
        }

        break;

//    /* 显示发送记录的查询界面，如果尚未注册或启用短信服务则显示注册界面。 */
//    case 'display_send_history_ui' :
//        /* 检查权限 */
//         admin_priv('send_history');
//        if ($sms->has_registered())
//        {
//            $smarty->assign('ur_here', $_LANG['05_sms_send_history']);
//            assign_query_info();
//            $smarty->display('sms_send_history_query_ui.htm');
//        }
//        else
//        {
//            $smarty->assign('ur_here', $_LANG['register_sms']);
//            $smarty->assign('sms_site_info', $sms->get_site_info());
//            assign_query_info();
//            $smarty->display('sms_register_ui.htm');
//        }
//
//        break;
//
//    /* 获得发送记录，如果客户端支持XSLT，则直接发送XML格式的文本到客户端；
//       否则在服务器端把XML转换成XHTML后发送到客户端。
//    */
//    case 'get_send_history' :
//        $start_date = isset($_POST['start_date'])   ? $_POST['start_date']  : '';
//        $end_date   = isset($_POST['end_date'])     ? $_POST['end_date']    : '';
//        $page_size  = isset($_POST['page_size'])    ? $_POST['page_size']   : 20;
//        $page       = isset($_POST['page'])         ? $_POST['page']        : 1;
//
//        $is_xslt_supported = isset($_POST['is_xslt_supported']) ? $_POST['is_xslt_supported'] : 'no';
//        if ($is_xslt_supported === 'yes')
//        {
//            $xml = $sms->get_send_history_by_xml($start_date, $end_date, $page_size, $page);
//            header('Content-Type: application/xml; charset=utf-8');
//            //TODO:判断错误信息，链上XSLT
//            echo $xml;
//        }
//        else
//        {
//            $result = $sms->get_send_history($start_date, $end_date, $page_size, $page);
//
//            if ($result !== false)
//            {
//                $smarty->assign('sms_send_history', $result);
//                $smarty->assign('ur_here', $_LANG['05_sms_send_history']);
//
//                /* 分页信息 */
//                $turn_page = array( 'total_records' => $result['count'],
//                                    'total_pages'   => intval(ceil($result['count']/$page_size)),
//                                    'page'          => $page,
//                                    'page_size'     => $page_size);
//                $smarty->assign('turn_page', $turn_page);
//                $smarty->assign('start_date', $start_date);
//                $smarty->assign('end_date', $end_date);
//
//                assign_query_info();
//
//                $smarty->display('sms_send_history.htm');
//            }
//            else
//            {
//                $link[] = array('text'  =>  $_LANG['back_send_history'],
//                                'href'  =>  'sms.php?act=display_send_history_ui');
//
//                @$error_detail = $_LANG['server_errors'][$sms->errors['server_errors']['error_no']]
//                              . $_LANG['api_errors']['get_history'][$sms->errors['api_errors']['error_no']];
//
//                sys_msg($_LANG['history_query_error'] . $error_detail, 1, $link);
//            }
//        }
//
//        break;
//
//    /* 显示充值页面 */
//    case 'display_charge_ui' :
//        /* 检查权限 */
//         admin_priv('sms_charge');
//        if ($sms->has_registered())
//        {
//            $smarty->assign('ur_here', $_LANG['04_sms_charge']);
//            assign_query_info();
//            $sms_charge = array();
//            $sms_charge['charge_url'] = $sms->get_url('charge');
//            $sms_charge['login_info'] = $sms->get_login_info();
//            $smarty->assign('sms_charge', $sms_charge);
//            $smarty->display('sms_charge_ui.htm');
//        }
//        else
//        {
//            $smarty->assign('ur_here', $_LANG['register_sms']);
//            $smarty->assign('sms_site_info', $sms->get_site_info());
//            assign_query_info();
//            $smarty->display('sms_register_ui.htm');
//        }
//
//        break;
//
//    /* 显示充值记录的查询界面，如果尚未注册或启用短信服务则显示注册界面。 */
//    case 'display_charge_history_ui' :
//         /* 检查权限 */
//         admin_priv('charge_history');
//        if ($sms->has_registered())
//        {
//            $smarty->assign('ur_here', $_LANG['06_sms_charge_history']);
//            assign_query_info();
//            $smarty->display('sms_charge_history_query_ui.htm');
//        }
//        else
//        {
//            $smarty->assign('ur_here', $_LANG['register_sms']);
//            $smarty->assign('sms_site_info', $sms->get_site_info());
//            assign_query_info();
//            $smarty->display('sms_register_ui.htm');
//        }
//
//        break;
//
//    /* 获得充值记录，如果客户端支持XSLT，则直接发送XML格式的文本到客户端；
//       否则在服务器端把XML转换成XHTML后发送到客户端。
//    */
//    case 'get_charge_history' :
//        $start_date = isset($_POST['start_date'])   ? $_POST['start_date']  : '';
//        $end_date   = isset($_POST['end_date'])     ? $_POST['end_date']    : '';
//        $page_size  = isset($_POST['page_size'])    ? $_POST['page_size']   : 20;
//        $page       = isset($_POST['page'])         ? $_POST['page']        : 1;
//
//        $is_xslt_supported = isset($_POST['is_xslt_supported']) ? $_POST['is_xslt_supported'] : 'no';
//        if ($is_xslt_supported === 'yes')
//        {
//            $xml = $sms->get_charge_history_by_xml($start_date, $end_date, $page_size, $page);
//            header('Content-Type: application/xml; charset=utf-8');
//            //TODO:判断错误信息，链上XSLT
//            echo $xml;
//        }
//        else
//        {
//            $result = $sms->get_charge_history($start_date, $end_date, $page_size, $page);
//            if ($result !== false)
//            {
//                $smarty->assign('sms_charge_history', $result);
//
//                /* 分页信息 */
//                $turn_page = array( 'total_records' => $result['count'],
//                                    'total_pages'   => intval(ceil($result['count']/$page_size)),
//                                    'page'          => $page,
//                                    'page_size'     => $page_size);
//                $smarty->assign('turn_page', $turn_page);
//                $smarty->assign('start_date', $start_date);
//                $smarty->assign('end_date', $end_date);
//
//                assign_query_info();
//
//                $smarty->display('sms_charge_history.htm');
//            }
//            else
//            {
//                $link[] = array('text'  =>  $_LANG['back_charge_history'],
//                                'href'  =>  'sms.php?act=display_charge_history_ui');
//
//                @$error_detail = $_LANG['server_errors'][$sms->errors['server_errors']['error_no']]
//                              . $_LANG['api_errors']['get_history'][$sms->errors['api_errors']['error_no']];
//
//                sys_msg($_LANG['history_query_error'] . $error_detail, 1, $link);
//            }
//        }
//
//        break;
//
//    /* 显示我的短信服务个人信息 */
//    default :
//        /* 检查权限 */
//         admin_priv('my_info');
//        $sms_my_info = $sms->get_my_info();
//        if (!$sms_my_info)
//        {
//            $link[] = array('text'  =>  $_LANG['back'], 'href'  =>  './');
//            sys_msg($_LANG['empty_info'], 1, $link);
//        }
//
//        if (!$sms_my_info['sms_user_name'])//此处不用$sms->has_registered()能够减少一次数据库查询
//        {
//            $smarty->assign('ur_here', $_LANG['register_sms']);
//            $smarty->assign('sms_site_info', $sms->get_site_info());
//            assign_query_info();
//            $smarty->display('sms_register_ui.htm');
//        }
//        else
//        {
//            /* 立即更新短信特服信息 */
//            $sms->restore($sms_my_info['sms_user_name'], $sms_my_info['sms_password']);
//
//            /* 再次获取个人数据，保证显示的数据是最新的 */
//            $sms_my_info = $sms->get_my_info();//这里不再进行判空处理，主要是因为如果前个式子不出错，这里一般不会出错
//
//            /* 格式化时间输出 */
//            $sms_last_request = $sms_my_info['sms_last_request']
//                    ? $sms_my_info['sms_last_request']
//                    : 0;//赋0防出错
//            $sms_my_info['sms_last_request'] = local_date('Y-m-d H:i:s O', $sms_my_info['sms_last_request']);
//
//            $smarty->assign('sms_my_info', $sms_my_info);
//            $smarty->assign('ur_here', $_LANG['02_sms_my_info']);
//            assign_query_info();
//            $smarty->display('sms_my_info.htm');
//        }
}




function shop_config_update($config_code, $config_value)
{
    $sql="SELECT `id` FROM ".$GLOBALS['ecs']->table(shop_config)." WHERE `code`='$config_code'";
    $c_node_id=$GLOBALS['db']->getOne($sql);
    if (empty($c_node_id)) {
        for ($i=247;$i<=270;$i++) {
            $sql="SELECT `id` FROM ".$GLOBALS['ecs']->table(shop_config)." WHERE `id`='$i'";
            $c_id=$GLOBALS['db']->getOne($sql);
            if (empty($c_id)) {
                $sql="INSERT INTO ".$GLOBALS['ecs']->table(shop_config)."(`id`,`parent_id`,`code`,`type`,`value`,`sort_order`) VALUES ('$i','2','$config_code','hidden','$config_value','1')";
                $GLOBALS['db']->query($sql);
                break;
            }
        }
    } else {
        $sql="UPDATE ".$GLOBALS['ecs']->table(shop_config)." SET `value`='$config_value'  WHERE `code`='$config_code'";
        $GLOBALS['db']->query($sql);
    }
}
