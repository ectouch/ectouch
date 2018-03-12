<?php

/**
 * 修改个人资料（Email, 性别，生日)
 *
 * @access  public
 * @param   array       $profile       array_keys(user_id int, email string, sex int, birthday string);
 *
 * @return  boolen      $bool
 */
function edit_profile($profile)
{
    $global = getInstance();
    if (empty($profile['user_id'])) {
        $global->err->add(L('not_login'));

        return false;
    }

    $cfg = array();
    $cfg['username'] = $global->db->getOne("SELECT user_name FROM " . $global->ecs->table('users') . " WHERE user_id='" . $profile['user_id'] . "'");
    if (isset($profile['sex'])) {
        $cfg['gender'] = intval($profile['sex']);
    }
    if (!empty($profile['email'])) {
        if (!is_email($profile['email'])) {
            $global->err->add(sprintf(L('email_invalid'), $profile['email']));

            return false;
        }
        $cfg['email'] = $profile['email'];
    }
    if (!empty($profile['birthday'])) {
        $cfg['bday'] = $profile['birthday'];
    }


    if (!$global->user->edit_user($cfg)) {
        if ($global->user->error == ERR_EMAIL_EXISTS) {
            $global->err->add(sprintf(L('email_exist'), $profile['email']));
        } else {
            $global->err->add('DB ERROR!');
        }

        return false;
    }

    /* 过滤非法的键值 */
    $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone');
    foreach ($profile['other'] as $key => $val) {
        //删除非法key值
        if (!in_array($key, $other_key_array)) {
            unset($profile['other'][$key]);
        } else {
            $profile['other'][$key] =  htmlspecialchars(trim($val)); //防止用户输入javascript代码
        }
    }
    /* 修改在其他资料 */
    if (!empty($profile['other'])) {
        $global->db->autoExecute($global->ecs->table('users'), $profile['other'], 'UPDATE', "user_id = '$profile[user_id]'");
    }

    return true;
}

/**
 * 获取用户帐号信息
 *
 * @access  public
 * @param   int       $user_id        用户user_id
 *
 * @return void
 */
function get_profile($user_id)
{
    $global = getInstance();

    /* 会员帐号信息 */
    $info  = array();
    $infos = array();
    $sql  = "SELECT user_name, email, birthday, sex, question, answer, rank_points, pay_points,user_money, user_rank,".
             " msn, qq, office_phone, home_phone, mobile_phone, passwd_question, passwd_answer ".
           "FROM " .$global->ecs->table('users') . " WHERE user_id = '$user_id'";
    $infos = $global->db->getRow($sql);
    $infos['user_name'] = addslashes($infos['user_name']);

    $row = $global->user->get_profile_by_name($infos['user_name']); //获取用户帐号信息
    $_SESSION['email'] = $row['email'];    //注册SESSION

    /* 会员等级 */
    if ($infos['user_rank'] > 0) {
        $sql = "SELECT rank_id, rank_name, discount FROM ".$global->ecs->table('user_rank') .
               " WHERE rank_id = '$infos[user_rank]'";
    } else {
        $sql = "SELECT rank_id, rank_name, discount, min_points".
               " FROM ".$global->ecs->table('user_rank') .
               " WHERE min_points<= " . intval($infos['rank_points']) . " ORDER BY min_points DESC";
    }

    if ($row = $global->db->getRow($sql)) {
        $info['rank_name']     = $row['rank_name'];
    } else {
        $info['rank_name'] = L('undifine_rank');
    }

    $cur_date = date('Y-m-d H:i:s');

    /* 会员红包 */
    $bonus = array();
    $sql = "SELECT type_name, type_money ".
           "FROM " .$global->ecs->table('bonus_type') . " AS t1, " .$global->ecs->table('user_bonus') . " AS t2 ".
           "WHERE t1.type_id = t2.bonus_type_id AND t2.user_id = '$user_id' AND t1.use_start_date <= '$cur_date' ".
           "AND t1.use_end_date > '$cur_date' AND t2.order_id = 0";
    $bonus = $global->db->getAll($sql);
    if ($bonus) {
        for ($i = 0, $count = count($bonus); $i < $count; $i++) {
            $bonus[$i]['type_money'] = price_format($bonus[$i]['type_money'], false);
        }
    }

    $info['discount']    = $_SESSION['discount'] * 100 . "%";
    $info['email']       = $_SESSION['email'];
    $info['user_name']   = $_SESSION['user_name'];
    $info['rank_points'] = isset($infos['rank_points']) ? $infos['rank_points'] : '';
    $info['pay_points']  = isset($infos['pay_points'])  ? $infos['pay_points']  : 0;
    $info['user_money']  = isset($infos['user_money'])  ? $infos['user_money']  : 0;
    $info['sex']         = isset($infos['sex'])      ? $infos['sex']      : 0;
    $info['birthday']    = isset($infos['birthday']) ? $infos['birthday'] : '';
    $info['question']    = isset($infos['question']) ? htmlspecialchars($infos['question']) : '';

    $info['user_money']  = price_format($info['user_money'], false);
    $info['pay_points']  = $info['pay_points'] . C('integral_name');
    $info['bonus']       = $bonus;
    $info['qq']          = $infos['qq'];
    $info['msn']          = $infos['msn'];
    $info['office_phone']= $infos['office_phone'];
    $info['home_phone']   = $infos['home_phone'];
    $info['mobile_phone'] = $infos['mobile_phone'];
    $info['passwd_question'] = $infos['passwd_question'];
    $info['passwd_answer'] = $infos['passwd_answer'];

    return $info;
}

/**
 * 取得收货人地址列表
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee_list($user_id)
{
    $sql = "SELECT * FROM " . $global->ecs->table('user_address') .
            " WHERE user_id = '$user_id' LIMIT 5";

    return $global->db->getAll($sql);
}

/**
 *  给指定用户添加一个指定红包
 *
 * @access  public
 * @param   int         $user_id        用户ID
 * @param   string      $bouns_sn       红包序列号
 *
 * @return  boolen      $result
 */
function add_bonus($user_id, $bouns_sn)
{
    $global = getInstance();
    if (empty($user_id)) {
        $global->err->add(L('not_login'));

        return false;
    }

    /* 查询红包序列号是否已经存在 */
    $sql = "SELECT bonus_id, bonus_sn, user_id, bonus_type_id FROM " .$global->ecs->table('user_bonus') .
           " WHERE bonus_sn = '$bouns_sn'";
    $row = $global->db->getRow($sql);
    if ($row) {
        if ($row['user_id'] == 0) {
            //红包没有被使用
            $sql = "SELECT send_end_date, use_end_date ".
                   " FROM " . $global->ecs->table('bonus_type') .
                   " WHERE type_id = '" . $row['bonus_type_id'] . "'";

            $bonus_time = $global->db->getRow($sql);

            $now = gmtime();
            if ($now > $bonus_time['use_end_date']) {
                $global->err->add(L('bonus_use_expire'));
                return false;
            }

            $sql = "UPDATE " .$global->ecs->table('user_bonus') . " SET user_id = '$user_id' ".
                   "WHERE bonus_id = '$row[bonus_id]'";
            $result = $global->db ->query($sql);
            if ($result) {
                return true;
            } else {
                return $global->db->errorMsg();
            }
        } else {
            if ($row['user_id']== $user_id) {
                //红包已经添加过了。
                $global->err->add(L('bonus_is_used'));
            } else {
                //红包被其他人使用过了。
                $global->err->add(L('bonus_is_used_by_other'));
            }

            return false;
        }
    } else {
        //红包不存在
        $global->err->add(L('bonus_not_exist'));
        return false;
    }
}

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @return  array       $order_list     订单列表
 */
function get_user_orders($user_id, $num = 10, $start = 0)
{
    $global = getInstance();
    /* 取得订单列表 */
    $arr    = array();

    $sql = "SELECT order_id, order_sn, order_status, shipping_status, pay_status, add_time, " .
           "(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + tax - discount) AS total_fee ".
           " FROM " .$global->ecs->table('order_info') .
           " WHERE user_id = '$user_id' ORDER BY add_time DESC";
    $res = $global->db->SelectLimit($sql, $num, $start);

    while ($row = $global->db->fetchRow($res)) {
        if ($row['order_status'] == OS_UNCONFIRMED) {
            $row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".L('confirm_cancel')."')) return false;\">".L('cancel')."</a>";
        } elseif ($row['order_status'] == OS_SPLITED) {
            /* 对配送状态的处理 */
            if ($row['shipping_status'] == SS_SHIPPED) {
                @$row['handler'] = "<a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".L('confirm_received')."')) return false;\">".L('received')."</a>";
            } elseif ($row['shipping_status'] == SS_RECEIVED) {
                @$row['handler'] = '<span style="color:red">'.L('ss_received') .'</span>';
            } else {
                if ($row['pay_status'] == PS_UNPAYED) {
                    @$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .L('pay_money'). '</a>';
                } else {
                    @$row['handler'] = "<a href=\"user.php?act=order_detail&order_id=" .$row['order_id']. '">' .L('view_order'). '</a>';
                }
            }
        } else {
            $row['handler'] = '<span style="color:red">'.L('os.'.$row['order_status']) .'</span>';
        }

        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        $row['order_status'] = L('os.'.$row['order_status']) . ',' . L('ps.'.$row['pay_status']) . ',' . L('ss.'.$row['shipping_status']);

        $arr[] = array('order_id'       => $row['order_id'],
                       'order_sn'       => $row['order_sn'],
                       'order_time'     => local_date(C('time_format'), $row['add_time']),
                       'order_status'   => $row['order_status'],
                       'total_fee'      => price_format($row['total_fee'], false),
                       'handler'        => $row['handler']);
    }

    return $arr;
}

/**
 * 取消一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return void
 */
function cancel_order($order_id, $user_id = 0)
{
    $global = getInstance();
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_id, order_sn , surplus , integral , bonus_id, order_status, shipping_status, pay_status FROM " .$global->ecs->table('order_info') ." WHERE order_id = '$order_id'";
    $order = $global->db->GetRow($sql);

    if (empty($order)) {
        $global->err->add(L('order_exist'));
        return false;
    }

    // 如果用户ID大于0，检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id) {
        $global->err ->add(L('no_priv'));

        return false;
    }

    // 订单状态只能是“未确认”或“已确认”
    if ($order['order_status'] != OS_UNCONFIRMED && $order['order_status'] != OS_CONFIRMED) {
        $global->err->add(L('current_os_not_unconfirmed'));

        return false;
    }

    //订单一旦确认，不允许用户取消
    if ($order['order_status'] == OS_CONFIRMED) {
        $global->err->add(L('current_os_already_confirmed'));

        return false;
    }

    // 发货状态只能是“未发货”
    if ($order['shipping_status'] != SS_UNSHIPPED) {
        $global->err->add(L('current_ss_not_cancel'));

        return false;
    }

    // 如果付款状态是“已付款”、“付款中”，不允许取消，要取消和商家联系
    if ($order['pay_status'] != PS_UNPAYED) {
        $global->err->add(L('current_ps_not_cancel'));

        return false;
    }

    //将用户订单设置为取消
    $sql = "UPDATE ".$global->ecs->table('order_info') ." SET order_status = '".OS_CANCELED."' WHERE order_id = '$order_id'";
    if ($global->db->query($sql)) {
        /* 记录log */
        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, L('buyer_cancel'), 'buyer');
        /* 退货用户余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0) {
            $change_desc = sprintf(L('return_surplus_on_cancel'), $order['order_sn']);
            log_account_change($order['user_id'], $order['surplus'], 0, 0, 0, $change_desc);
        }
        if ($order['user_id'] > 0 && $order['integral'] > 0) {
            $change_desc = sprintf(L('return_integral_on_cancel'), $order['order_sn']);
            log_account_change($order['user_id'], 0, 0, 0, $order['integral'], $change_desc);
        }
        if ($order['user_id'] > 0 && $order['bonus_id'] > 0) {
            change_user_bonus($order['bonus_id'], $order['order_id'], false);
        }

        /* 如果使用库存，且下订单时减库存，则增加库存 */
        if (C('use_storage') == '1' && C('stock_dec_time') == SDT_PLACE) {
            change_order_goods_storage($order['order_id'], false, 1);
        }

        /* 修改订单 */
        $arr = array(
            'bonus_id'  => 0,
            'bonus'     => 0,
            'integral'  => 0,
            'integral_money'    => 0,
            'surplus'   => 0
        );
        update_order($order['order_id'], $arr);

        return true;
    } else {
        die($global->db->errorMsg());
    }
}

/**
 * 确认一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return  bool        $bool
 */
function affirm_received($order_id, $user_id = 0)
{
    $global = getInstance();
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM ".$global->ecs->table('order_info') ." WHERE order_id = '$order_id'";

    $order = $global->db->GetRow($sql);

    // 如果用户ID大于 0 。检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id) {
        $global->err -> add(L('no_priv'));

        return false;
    }
    /* 检查订单 */
    elseif ($order['shipping_status'] == SS_RECEIVED) {
        $global->err ->add(L('order_already_received'));

        return false;
    } elseif ($order['shipping_status'] != SS_SHIPPED) {
        $global->err->add(L('order_invalid'));

        return false;
    }
    /* 修改订单发货状态为“确认收货” */
    else {
        $sql = "UPDATE " . $global->ecs->table('order_info') . " SET shipping_status = '" . SS_RECEIVED . "' WHERE order_id = '$order_id'";
        if ($global->db->query($sql)) {
            /* 记录日志 */
            order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', L('buyer'));

            return true;
        } else {
            die($global->db->errorMsg());
        }
    }
}

/**
 * 保存用户的收货人信息
 * 如果收货人信息中的 id 为 0 则新增一个收货人信息
 *
 * @access  public
 * @param   array   $consignee
 * @param   boolean $default        是否将该收货人信息设置为默认收货人信息
 * @return  boolean
 */
function save_consignee($consignee, $default=false)
{
    $global = getInstance();
    if ($consignee['address_id'] > 0) {
        /* 修改地址 */
        $res = $global->db->autoExecute($global->ecs->table('user_address'), $consignee, 'UPDATE', 'address_id = ' . $consignee['address_id']." AND `user_id`= '".$_SESSION['user_id']."'");
    } else {
        /* 添加地址 */
        $res = $global->db->autoExecute($global->ecs->table('user_address'), $consignee, 'INSERT');
        $consignee['address_id'] = $global->db->insert_id();
    }

    if ($default) {
        /* 保存为用户的默认收货地址 */
        $sql = "UPDATE " . $global->ecs->table('users') .
            " SET address_id = '$consignee[address_id]' WHERE user_id = '$_SESSION[user_id]'";

        $res = $global->db->query($sql);
    }

    return $res !== false;
}

/**
 * 删除一个收货地址
 *
 * @access  public
 * @param   integer $id
 * @return  boolean
 */
function drop_consignee($id)
{
    $global = getInstance();
    $sql = "SELECT user_id FROM " .$global->ecs->table('user_address') . " WHERE address_id = '$id'";
    $uid = $global->db->getOne($sql);

    if ($uid != $_SESSION['user_id']) {
        return false;
    } else {
        $sql = "DELETE FROM " .$global->ecs->table('user_address') . " WHERE address_id = '$id'";
        $res = $global->db->query($sql);

        return $res;
    }
}

/**
 *  添加或更新指定用户收货地址
 *
 * @access  public
 * @param   array       $address
 * @return  bool
 */
function update_address($address)
{
    $global = getInstance();
    $address_id = intval($address['address_id']);
    unset($address['address_id']);

    if ($address_id > 0) {
        /* 更新指定记录 */
        $global->db->autoExecute($global->ecs->table('user_address'), $address, 'UPDATE', 'address_id = ' .$address_id . ' AND user_id = ' . $address['user_id']);
    } else {
        /* 插入一条新记录 */
        $global->db->autoExecute($global->ecs->table('user_address'), $address, 'INSERT');
        $address_id = $global->db->insert_id();
    }

    if (isset($address['defalut']) && $address['default'] > 0 && isset($address['user_id'])) {
        $sql = "UPDATE ".$global->ecs->table('users') .
                " SET address_id = '".$address_id."' ".
                " WHERE user_id = '" .$address['user_id']. "'";
        $global->db ->query($sql);
    }
    //修改收货地址后删除保存的session
    if (isset($_SESSION['flow_consignee'])) {
        unset($_SESSION['flow_consignee']);
    }
    return true;
}

/**
 *  获取指订单的详情
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return   arr        $order          订单所有信息的数组
 */
function get_order_detail($order_id, $user_id = 0)
{
    $global = getInstance();
    include_once(BASE_PATH . 'helpers/order_helper.php');

    $order_id = intval($order_id);
    if ($order_id <= 0) {
        $global->err->add(L('invalid_order_id'));

        return false;
    }
    $order = order_info($order_id);

    //检查订单是否属于该用户
    if ($user_id > 0 && $user_id != $order['user_id']) {
        $global->err->add(L('no_priv'));

        return false;
    }

    /* 对发货号处理 */
    if (!empty($order['invoice_no'])) {
        $shipping_code = $global->db->GetOne("SELECT shipping_code FROM ".$global->ecs->table('shipping') ." WHERE shipping_id = '$order[shipping_id]'");
        $plugin = BASE_PATH.'modules/shipping/'. $shipping_code. '.php';
        if (file_exists($plugin)) {
            include_once($plugin);
            $shipping = new $shipping_code;
            $order['invoice_no'] = $shipping->query($order['invoice_no']);
        }
    }

    /* 只有未确认才允许用户修改订单地址 */
    if ($order['order_status'] == OS_UNCONFIRMED) {
        $order['allow_update_address'] = 1; //允许修改收货地址
    } else {
        $order['allow_update_address'] = 0;
    }

    /* 获取订单中实体商品数量 */
    $order['exist_real_goods'] = exist_real_goods($order_id);

    /* 如果是未付款状态，生成支付按钮 */
    if ($order['pay_status'] == PS_UNPAYED &&
        ($order['order_status'] == OS_UNCONFIRMED ||
        $order['order_status'] == OS_CONFIRMED)) {
        /*
         * 在线支付按钮
         */
        //支付方式信息
        $payment_info = array();
        $payment_info = payment_info($order['pay_id']);

        //无效支付方式
        if ($payment_info === false) {
            $order['pay_online'] = '';
        } else {
            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);

            //获取需要支付的log_id
            $order['log_id']    = get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
            $order['user_name'] = $_SESSION['user_name'];
            $order['pay_desc']  = $payment_info['pay_desc'];

            /* 调用相应的支付方式文件 */
            include_once(BASE_PATH . 'modules/payment/' . $payment_info['pay_code'] . '.php');

            /* 取得在线支付方式的支付按钮 */
            $pay_obj    = new $payment_info['pay_code'];
            $order['pay_online'] = $pay_obj->get_code($order, $payment);
        }
    } else {
        $order['pay_online'] = '';
    }

    /* 无配送时的处理 */
    $order['shipping_id'] == -1 and $order['shipping_name'] = L('shipping_not_need');

    /* 其他信息初始化 */
    $order['how_oos_name']     = $order['how_oos'];
    $order['how_surplus_name'] = $order['how_surplus'];

    /* 虚拟商品付款后处理 */
    if ($order['pay_status'] != PS_UNPAYED) {
        /* 取得已发货的虚拟商品信息 */
        $virtual_goods = get_virtual_goods($order_id, true);
        $virtual_card = array();
        foreach ($virtual_goods as $code => $goods_list) {
            /* 只处理虚拟卡 */
            if ($code == 'virtual_card') {
                foreach ($goods_list as $goods) {
                    if ($info = virtual_card_result($order['order_sn'], $goods)) {
                        $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>$info);
                    }
                }
            }
            /* 处理超值礼包里面的虚拟卡 */
            if ($code == 'package_buy') {
                foreach ($goods_list as $goods) {
                    $sql = 'SELECT g.goods_id FROM ' . $global->ecs->table('package_goods') . ' AS pg, ' . $global->ecs->table('goods') . ' AS g ' .
                           "WHERE pg.goods_id = g.goods_id AND pg.package_id = '" . $goods['goods_id'] . "' AND extension_code = 'virtual_card'";
                    $vcard_arr = $global->db->getAll($sql);

                    foreach ($vcard_arr as $val) {
                        if ($info = virtual_card_result($order['order_sn'], $val)) {
                            $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>$info);
                        }
                    }
                }
            }
        }
        $var_card = deleteRepeat($virtual_card);
        $global->tpl->assign('virtual_card', $var_card);
    }

    /* 确认时间 支付时间 发货时间 */
    if ($order['confirm_time'] > 0 && ($order['order_status'] == OS_CONFIRMED || $order['order_status'] == OS_SPLITED || $order['order_status'] == OS_SPLITING_PART)) {
        $order['confirm_time'] = sprintf(L('confirm_time'), local_date(C('time_format'), $order['confirm_time']));
    } else {
        $order['confirm_time'] = '';
    }
    if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED) {
        $order['pay_time'] = sprintf(L('pay_time'), local_date(C('time_format'), $order['pay_time']));
    } else {
        $order['pay_time'] = '';
    }
    if ($order['shipping_time'] > 0 && in_array($order['shipping_status'], array(SS_SHIPPED, SS_RECEIVED))) {
        $order['shipping_time'] = sprintf(L('shipping_time'), local_date(C('time_format'), $order['shipping_time']));
    } else {
        $order['shipping_time'] = '';
    }

    return $order;
}

/**
 *  获取用户可以和并的订单数组
 *
 * @access  public
 * @param   int         $user_id        用户ID
 *
 * @return  array       $merge          可合并订单数组
 */
function get_user_merge($user_id)
{
    $global = getInstance();
    include_once(BASE_PATH . 'helpers/order_helper.php');
    $sql  = "SELECT order_sn FROM ".$global->ecs->table('order_info') .
            " WHERE user_id  = '$user_id' " . order_query_sql('unprocessed') .
                "AND extension_code = '' ".
            " ORDER BY add_time DESC";
    $list = $global->db->GetCol($sql);

    $merge = array();
    foreach ($list as $val) {
        $merge[$val] = $val;
    }

    return $merge;
}

/**
 *  合并指定用户订单
 *
 * @access  public
 * @param   string      $from_order         合并的从订单号
 * @param   string      $to_order           合并的主订单号
 *
 * @return  boolen      $bool
 */
function merge_user_order($from_order, $to_order, $user_id = 0)
{
    $global = getInstance();
    if ($user_id > 0) {
        /* 检查订单是否属于指定用户 */
        if (strlen($to_order) > 0) {
            $sql = "SELECT user_id FROM " .$global->ecs->table('order_info').
                   " WHERE order_sn = '$to_order'";
            $order_user = $global->db->getOne($sql);
            if ($order_user != $user_id) {
                $global->err->add(L('no_priv'));
            }
        } else {
            $global->err->add(L('order_sn_empty'));
            return false;
        }
    }

    $result = merge_order($from_order, $to_order);
    if ($result === true) {
        return true;
    } else {
        $global->err->add($result);
        return false;
    }
}

/**
 *  将指定订单中的商品添加到购物车
 *
 * @access  public
 * @param   int         $order_id
 *
 * @return  mix         $message        成功返回true, 错误返回出错信息
 */
function return_to_cart($order_id)
{
    $global = getInstance();
    /* 初始化基本件数量 goods_id => goods_number */
    $basic_number = array();

    /* 查订单商品：不考虑赠品 */
    $sql = "SELECT goods_id, product_id,goods_number, goods_attr, parent_id, goods_attr_id" .
            " FROM " . $global->ecs->table('order_goods') .
            " WHERE order_id = '$order_id' AND is_gift = 0 AND extension_code <> 'package_buy'" .
            " ORDER BY parent_id ASC";
    $res = $global->db->query($sql);

    $time = gmtime();
    while ($row = $global->db->fetchRow($res)) {
        // 查该商品信息：是否删除、是否上架

        $sql = "SELECT goods_sn, goods_name, goods_number, market_price, " .
                "IF(is_promote = 1 AND '$time' BETWEEN promote_start_date AND promote_end_date, promote_price, shop_price) AS goods_price," .
                "is_real, extension_code, is_alone_sale, goods_type " .
                "FROM " . $global->ecs->table('goods') .
                " WHERE goods_id = '$row[goods_id]' " .
                " AND is_delete = 0 LIMIT 1";
        $goods = $global->db->getRow($sql);

        // 如果该商品不存在，处理下一个商品
        if (empty($goods)) {
            continue;
        }
        if ($row['product_id']) {
            $order_goods_product_id=$row['product_id'];
            $sql="SELECT product_number from ".$global->ecs->table('products')."where product_id='$order_goods_product_id'";
            $product_number=$global->db->getOne($sql);
        }
        // 如果使用库存，且库存不足，修改数量
        if (C('use_storage') == 1 && ($row['product_id']?($product_number<$row['goods_number']):($goods['goods_number'] < $row['goods_number']))) {
            if ($goods['goods_number'] == 0 || $product_number=== 0) {
                // 如果库存为0，处理下一个商品
                continue;
            } else {
                if ($row['product_id']) {
                    $row['goods_number']=$product_number;
                } else {
                    // 库存不为0，修改数量
                    $row['goods_number'] = $goods['goods_number'];
                }
            }
        }

        //检查商品价格是否有会员价格
        $sql = "SELECT goods_number FROM" . $global->ecs->table('cart') . " " .
                "WHERE session_id = '" . SESS_ID . "' " .
                "AND goods_id = '" . $row['goods_id'] . "' " .
                "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
        $temp_number = $global->db->getOne($sql);
        $row['goods_number'] += $temp_number;

        $attr_array           = empty($row['goods_attr_id']) ? array() : explode(',', $row['goods_attr_id']);
        $goods['goods_price'] = get_final_price($row['goods_id'], $row['goods_number'], true, $attr_array);

        // 要返回购物车的商品
        $return_goods = array(
            'goods_id'      => $row['goods_id'],
            'goods_sn'      => addslashes($goods['goods_sn']),
            'goods_name'    => addslashes($goods['goods_name']),
            'market_price'  => $goods['market_price'],
            'goods_price'   => $goods['goods_price'],
            'goods_number'  => $row['goods_number'],
            'goods_attr'    => empty($row['goods_attr']) ? '' : addslashes($row['goods_attr']),
            'goods_attr_id'    => empty($row['goods_attr_id']) ? '' : addslashes($row['goods_attr_id']),
            'is_real'       => $goods['is_real'],
            'extension_code'=> addslashes($goods['extension_code']),
            'parent_id'     => '0',
            'is_gift'       => '0',
            'rec_type'      => CART_GENERAL_GOODS
        );

        // 如果是配件
        if ($row['parent_id'] > 0) {
            // 查询基本件信息：是否删除、是否上架、能否作为普通商品销售
            $sql = "SELECT goods_id " .
                    "FROM " . $global->ecs->table('goods') .
                    " WHERE goods_id = '$row[parent_id]' " .
                    " AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 LIMIT 1";
            $parent = $global->db->getRow($sql);
            if ($parent) {
                // 如果基本件存在，查询组合关系是否存在
                $sql = "SELECT goods_price " .
                        "FROM " . $global->ecs->table('group_goods') .
                        " WHERE parent_id = '$row[parent_id]' " .
                        " AND goods_id = '$row[goods_id]' LIMIT 1";
                $fitting_price = $global->db->getOne($sql);
                if ($fitting_price) {
                    // 如果组合关系存在，取配件价格，取基本件数量，改parent_id
                    $return_goods['parent_id']      = $row['parent_id'];
                    $return_goods['goods_price']    = $fitting_price;
                    $return_goods['goods_number']   = $basic_number[$row['parent_id']];
                }
            }
        } else {
            // 保存基本件数量
            $basic_number[$row['goods_id']] = $row['goods_number'];
        }

        // 返回购物车：看有没有相同商品
        $sql = "SELECT goods_id " .
                "FROM " . $global->ecs->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' " .
                " AND goods_id = '$return_goods[goods_id]' " .
                " AND goods_attr = '$return_goods[goods_attr]' " .
                " AND parent_id = '$return_goods[parent_id]' " .
                " AND is_gift = 0 " .
                " AND rec_type = '" . CART_GENERAL_GOODS . "'";
        $cart_goods = $global->db->getOne($sql);
        if (empty($cart_goods)) {
            // 没有相同商品，插入
            $return_goods['session_id'] = SESS_ID;
            $return_goods['user_id']    = $_SESSION['user_id'];
            $global->db->autoExecute($global->ecs->table('cart'), $return_goods, 'INSERT');
        } else {
            // 有相同商品，修改数量
            $sql = "UPDATE " . $global->ecs->table('cart') . " SET " .
                    "goods_number = '" . $return_goods['goods_number'] . "' " .
                    ",goods_price = '" . $return_goods['goods_price'] . "' " .
                    "WHERE session_id = '" . SESS_ID . "' " .
                    "AND goods_id = '" . $return_goods['goods_id'] . "' " .
                    "AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
            $global->db->query($sql);
        }
    }

    // 清空购物车的赠品
    $sql = "DELETE FROM " . $global->ecs->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND is_gift = 1";
    $global->db->query($sql);

    return true;
}

/**
 *  保存用户收货地址
 *
 * @access  public
 * @param   array   $address        array_keys(consignee string, email string, address string, zipcode string, tel string, mobile stirng, sign_building string, best_time string, order_id int)
 * @param   int     $user_id        用户ID
 *
 * @return  boolen  $bool
 */
function save_order_address($address, $user_id)
{
    $global = getInstance();
    $global->err->clean();
    /* 数据验证 */
    empty($address['consignee']) and $global->err->add(L('consigness_empty'));
    empty($address['address']) and $global->err->add(L('address_empty'));
    $address['order_id'] == 0 and $global->err->add(L('order_id_empty'));
    if (empty($address['email'])) {
        $global->err->add($GLOBALS['email_empty']);
    } else {
        if (!is_email($address['email'])) {
            $global->err->add(sprintf(L('email_invalid'), $address['email']));
        }
    }
    if ($global->err->error_no > 0) {
        return false;
    }

    /* 检查订单状态 */
    $sql = "SELECT user_id, order_status FROM " .$global->ecs->table('order_info'). " WHERE order_id = '" .$address['order_id']. "'";
    $row = $global->db->getRow($sql);
    if ($row) {
        if ($user_id > 0 && $user_id != $row['user_id']) {
            $global->err->add(L('no_priv'));
            return false;
        }
        if ($row['order_status'] != OS_UNCONFIRMED) {
            $global->err->add(L('require_unconfirmed'));
            return false;
        }
        $global->db->autoExecute($global->ecs->table('order_info'), $address, 'UPDATE', "order_id = '$address[order_id]'");
        return true;
    } else {
        /* 订单不存在 */
        $global->err->add(L('order_exist'));
        return false;
    }
}

/**
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             红保列表
 */
function get_user_bouns_list($user_id, $num = 10, $start = 0)
{
    $global = getInstance();
    $sql = "SELECT u.bonus_sn, u.order_id, b.type_name, b.type_money, b.min_goods_amount, b.use_start_date, b.use_end_date ".
           " FROM " .$global->ecs->table('user_bonus'). " AS u ,".
           $global->ecs->table('bonus_type'). " AS b".
           " WHERE u.bonus_type_id = b.type_id AND u.user_id = '" .$user_id. "'";
    $res = $global->db->selectLimit($sql, $num, $start);
    $arr = array();

    $day = getdate();
    $cur_date = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);

    while ($row = $global->db->fetchRow($res)) {
        /* 先判断是否被使用，然后判断是否开始或过期 */
        if (empty($row['order_id'])) {
            /* 没有被使用 */
            if ($row['use_start_date'] > $cur_date) {
                $row['status'] = L('not_start');
            } elseif ($row['use_end_date'] < $cur_date) {
                $row['status'] = L('overdue');
            } else {
                $row['status'] = L('not_use');
            }
        } else {
            $row['status'] = '<a href="user.php?act=order_detail&order_id=' .$row['order_id']. '" >' .L('had_use'). '</a>';
        }

        $row['use_startdate']   = local_date(C('date_format'), $row['use_start_date']);
        $row['use_enddate']     = local_date(C('date_format'), $row['use_end_date']);

        $arr[] = $row;
    }
    return $arr;
}

/**
 * 获得会员的团购活动列表
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             团购活动列表
 */
function get_user_group_buy($user_id, $num = 10, $start = 0)
{
    return true;
}

 /**
  * 获得团购详细信息(团购订单信息)
  *
  *
  */
 function get_group_buy_detail($user_id, $group_buy_id)
 {
     return true;
 }

 /**
  * 去除虚拟卡中重复数据
  *
  *
  */
function deleteRepeat($array)
{
    $_card_sn_record = array();
    foreach ($array as $_k => $_v) {
        foreach ($_v['info'] as $__k => $__v) {
            if (in_array($__v['card_sn'], $_card_sn_record)) {
                unset($array[$_k]['info'][$__k]);
            } else {
                array_push($_card_sn_record, $__v['card_sn']);
            }
        }
    }
    return $array;
}

/**
 * 供货商信息 服务订单  ECTouch Leah
 *
 * @param       string      $conditions
 * @return      array
 */
function get_suppliers_name($seller_id)
{
    $where = '';
    if (!empty($seller_id)) {
        $where .= 'WHERE ';
        $where .= " suppliers_id = ".$seller_id;
    }
    if ($seller_id == 0) {
        return $GLOBALS['_CFG']['shop_name'];
    }
    /* 查询 */
    $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc
            FROM " . $GLOBALS['ecs']->table("suppliers") . "
            $where";
    $result = $GLOBALS['db']->getRow($sql);
    
    return $result['suppliers_name'];
}

/**
 *  获取用户服务订单列表
 *
 * @access  public
 * @param   int $user_id 用户ID号
 * @param   int $num 列表最大数量
 * @param   int $start 列表起始位置
 * @return  array       $after_list     订单列表
 */
function get_user_aftermarket($user_id, $num = 10, $start = 0)
{
    /* 取得订单列表 */
    $arr = array();

    $sql = "SELECT ret_id ,rec_id, goods_id, service_sn, order_sn, order_id,add_time, should_return, return_status, refund_status, is_check, service_id, cause_id, seller_id " .
            " FROM " . $GLOBALS['ecs']->table('order_return') .
            " WHERE user_id = '$user_id'  ORDER BY add_time DESC ";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

    while ($row = $GLOBALS['db']->fetchRow($res)) {
        if ($row['order_status'] == RF_APPLICATION) {
            $row['handler'] = "<a href=\"user.php?act=cancel_aftermarket&ret=" .$row['ret_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_cancel']."')) return false;\">".$GLOBALS['_LANG']['cancel']."</a>";
        } elseif ($row['is_check'] == RC_APPLY_SUCCESS) {
            /* 对配送状态的处理 */
            if ($row['shipping_status'] == SS_SHIPPED) {
                @$row['handler'] = "<a href=\"user.php?act=affirm_received&order_id=" .$row['order_id']. "\" onclick=\"if (!confirm('".$GLOBALS['_LANG']['confirm_received']."')) return false;\">".$GLOBALS['_LANG']['received']."</a>";
            } elseif ($row['shipping_status'] == SS_RECEIVED) {
                @$row['handler'] = '<span style="color:red">' . L('ss_received') . '</span>';
            } else {
                if ($row['pay_status'] == PS_UNPAYED) {
                    @$row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['pay_money']. '</a>';
                } else {
                    @$row['handler'] = "<a href=\"user.php?act=cancel_order&order_id=" .$row['order_id']. '">' .$GLOBALS['_LANG']['pay_money']. '</a>';
                }
            }
        } else {
            $row['handler'] = '<span>' .$GLOBALS['_LANG']['os'][$row['order_status']]. '</span>';
        }

        $row['shipping_status'] = ($row['shipping_status'] == SS_SHIPPED_ING) ? SS_PREPARING : $row['shipping_status'];
        $row['order_status'] = $GLOBALS['_LANG']['os'][$row['order_status']] . ',' . $GLOBALS['_LANG']['ps'][$row['pay_status']] . ',' . $GLOBALS['_LANG']['ss'][$row['shipping_status']];

        $arr[] = array(
            'ret_id' => $row['ret_id'],
            'order_id' => $row['order_id'],
            'service_sn' => $row['service_sn'],
            'order_sn' => $row['order_sn'],
            'add_time' => local_date($GLOBALS['_CFG']['time_format'], $row['add_time']),
            'seller_name' => get_suppliers_name($row['seller_id']),
            'service_type' => get_service_type($row['service_id'], true),
            'cause_name'   =>get_service_cause_name($row['cause_id']),
            'return_status' => $GLOBALS['_LANG']['rf'][$row['return_status']],
            'refund_status' => $GLOBALS['_LANG']['ff'][$row['refund_status']],
            'total_fee' => price_format($row['total_fee'], false),
            'handler' => $row['handler']);
    }
    return $arr;
}
/**
 * 售后服务订单详情
 *
 * @param       string      $conditions
 * @return      array
 */
function get_aftermarket_detail($ret_id, $user_id = 0)
{
    $ret_id = intval($ret_id);
    if ($ret_id <= 0) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['invalid_order_id']);
        return false;
    }
    $order = aftermarket_info($ret_id);

    //检查订单是否属于该用户
    if ($user_id > 0 && $user_id != $order['user_id']) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
        return false;
    }
    return $order;
}

/**
 * 取消一个用户订单
 *
 * @access  public
 * @param   int         $ret_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return void
 */
function cancel_service($ret_id, $user_id = 0)
{
    /* 查询订单信息，检查状态 */
    $sql = "SELECT user_id, order_sn ,return_status, refund_status, is_check FROM " . $GLOBALS['ecs']->table('order_return') . " WHERE ret_id = '$ret_id'";
    $order = $GLOBALS['db']->GetRow($sql);

    if (empty($order)) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
        return false;
    }

    // 如果用户ID大于0，检查订单是否属于该用户
    if ($user_id > 0 && $order['user_id'] != $user_id) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
        return false;
    }
    // 服务订单状态是 已审核
    if ($order['is_check'] == RC_APPLY_SUCCESS) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['current_rc_apply_success']);

        return false;
    }

    //将用户订单设置为取消
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_return') . " SET return_status = '" . RF_CANCELED . "' WHERE ret_id = '$ret_id'";

    if ($GLOBALS['db']->query($sql)) {
        /* 记录log */
        return_action($ret_id, RF_CANCELED, FF_NOREFUND, RC_APPLY_FALSE, $GLOBALS['_LANG']['cancel_service_mess'], $GLOBALS['_LANG']['buyer'], '', $GLOBALS['_LANG']['cancel_service_mess']);
        return true;
    } else {
        die($GLOBALS['db']->errorMsg());
    }
}

/**
 * 获得服务订单可执行操作
 * @param $service_type  服务订单类型
 * @param $return_staus  服务订单状态
 * @param is_check  审核是否通过
 * */
function get_return_operate($order)
{
    if ($order['is_check'] == RC_APPLY_FALSE && $order['return_status'] == RF_APPLICATION) {
        //申请 未审核 可以取消申请服务
        @$handler = "<a href=\"user.php?act=cancel_service&ret_id=" . $order['ret_id'] . "\" class=\"btn btn-5\" onclick=\"if (!confirm('" . $GLOBALS['_LANG']['confirm_cancel_aftermarket'] . "')) return false;\">" . $GLOBALS['_LANG']['cancel'] . "</a>";
    } else {
        /* 审核通过 */
        $server = get_service_type($order['service_id']);
        /* 快递公司 */
        $consignee = get_consignee($_SESSION['user_id']);
        $region = array($consignee['country'], $consignee['province'], $consignee['city'], $consignee['district']);
        $shipping_list = available_shipping_list($region);
        foreach ($shipping_list as $key => $val) {
            $shipping_cfg = unserialize_config($val['configure']);
            $shipping_fee = ($shipping_count == 0 and $cart_weight_price['free_shipping'] == 1) ? 0 : shipping_fee($val['shipping_code'], unserialize($val['configure']), $cart_weight_price['weight'], $cart_weight_price['amount'], $cart_weight_price['number']);

            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee, false);
            $shipping_list[$key]['shipping_fee'] = $shipping_fee;
            $shipping_list[$key]['free_money'] = price_format($shipping_cfg['free_money'], false);
            $shipping_list[$key]['insure_formated'] = strpos($val['insure'], '%') === false ? price_format($val['insure'], false) : $val['insure'];
        }
        /* 退货退款 */
        if ($server['service_type'] == ST_RETURN_GOODS) {
            if ($order['return_status'] == RF_APPLICATION) {
                $goods_info = order_goods_info($order['rec_id']);
                
                $GLOBALS['smarty']->assign('ret_id', $order['ret_id']);
                $GLOBALS['smarty']->assign('shipping_list', $shipping_list);
                $GLOBALS['smarty']->assign('business_address', get_business_address($goods_info['suppliers_id']));
                
                @$handler = $GLOBALS['smarty']->fetch('library/send_info.lbi');
            }
        }
        /* 换货 */ elseif ($server['service_type'] == ST_EXCHANGE) {
            if ($order['return_status'] == RF_APPLICATION) {
                $goods_info = order_goods_info($order['rec_id']);
                
                $GLOBALS['smarty']->assign('ret_id', $order['ret_id']);
                $GLOBALS['smarty']->assign('shipping_list', $shipping_list);
                $GLOBALS['smarty']->assign('business_address', get_business_address($goods_info['suppliers_id']));
                
                @$handler = $GLOBALS['smarty']->fetch('library/send_info.lbi');
            }
        }
    }
    return $handler;
}
/**
 * 服务订单进程
 * @param type $order
 */
function get_aftermarket_progress($order)
{
    if ($order['return_status'] == RF_APPLICATION) {
        //申请
        $list['apply'] = 1;
    } elseif ($order['return_status'] == RF_SEND_OUT) {
        $list['send_out'] = 1;
    } elseif ($order['return_status'] == RF_RECEIVE) {
        $list['receive'] = 1;
    } elseif ($order['return_status'] == RF_SWAPPED_OUT) {
        $list['swapped_out'] = 1;
    } elseif ($order['return_status'] == RF_COMPLETE) {
        $list['complete'] = 1;
    } elseif ($order['return_status'] == RF_CANCELED) {
        $list['apply'] =  1;
        $list['canceled'] = 1;
    } elseif ($order['return_status'] == RF_APPLY_FALSE) {
        $list['apply_false'] = 1;
    }
    return $list;
}
