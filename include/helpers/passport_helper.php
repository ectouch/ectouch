<?php

/**
 * 用户注册，登录函数
 *
 * @access  public
 * @param   string       $username          注册用户名
 * @param   string       $password          用户密码
 * @param   string       $email             注册email
 * @param   array        $other             注册的其他信息
 *
 * @return  bool         $bool
 */
function register($username, $password, $email, $other = array())
{
    $global = getInstance();
    /* 检查注册是否关闭 */
    $shop_reg_closed = C('shop_reg_closed');
    if (!empty($shop_reg_closed)) {
        $global->err->add(L('shop_register_closed'));
    }
    /* 检查username */
    if (empty($username)) {
        $global->err->add(L('username_empty'));
    } else {
        if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username)) {
            $global->err->add(sprintf(L('username_invalid'), htmlspecialchars($username)));
        }
    }

    /* 检查email */
    if (empty($email)) {
        $global->err->add(L('email_empty'));
    } else {
        if (!is_email($email)) {
            $global->err->add(sprintf(L('email_invalid'), htmlspecialchars($email)));
        }
    }

    if ($global->err->error_no > 0) {
        return false;
    }

    /* 检查是否和管理员重名 */
    if (admin_registered($username)) {
        $global->err->add(sprintf(L('username_exist'), $username));
        return false;
    }

    if (!$global->user->add_user($username, $password, $email)) {
        if ($global->user->error == ERR_INVALID_USERNAME) {
            $global->err->add(sprintf(L('username_invalid'), $username));
        } elseif ($global->user->error == ERR_USERNAME_NOT_ALLOW) {
            $global->err->add(sprintf(L('username_not_allow'), $username));
        } elseif ($global->user->error == ERR_USERNAME_EXISTS) {
            $global->err->add(sprintf(L('username_exist'), $username));
        } elseif ($global->user->error == ERR_INVALID_EMAIL) {
            $global->err->add(sprintf(L('email_invalid'), $email));
        } elseif ($global->user->error == ERR_EMAIL_NOT_ALLOW) {
            $global->err->add(sprintf(L('email_not_allow'), $email));
        } elseif ($global->user->error == ERR_EMAIL_EXISTS) {
            $global->err->add(sprintf(L('email_exist'), $email));
        } else {
            $global->err->add('UNKNOWN ERROR!');
        }

        //注册失败
        return false;
    } else {
        //注册成功

        /* 设置成登录状态 */
        $global->user->set_session($username);
        $global->user->set_cookie($username);

        /* 注册送积分 */
        $register_points = C('register_points');
        if (!empty($register_points)) {
            log_account_change($_SESSION['user_id'], 0, 0, C('register_points'), C('register_points'), L('register_points'));
        }

        /*推荐处理*/
        $affiliate  = unserialize(C('affiliate'));
        if (isset($affiliate['on']) && $affiliate['on'] == 1) {
            // 推荐开关开启
            $up_uid     = get_affiliate();
            empty($affiliate) && $affiliate = array();
            $affiliate['config']['level_register_all'] = intval($affiliate['config']['level_register_all']);
            $affiliate['config']['level_register_up'] = intval($affiliate['config']['level_register_up']);
            if ($up_uid) {
                if (!empty($affiliate['config']['level_register_all'])) {
                    if (!empty($affiliate['config']['level_register_up'])) {
                        $rank_points = $global->db->getOne("SELECT rank_points FROM " . $global->ecs->table('users') . " WHERE user_id = '$up_uid'");
                        if ($rank_points + $affiliate['config']['level_register_all'] <= $affiliate['config']['level_register_up']) {
                            log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, sprintf(L('register_affiliate'), $_SESSION['user_id'], $username));
                        }
                    } else {
                        log_account_change($up_uid, 0, 0, $affiliate['config']['level_register_all'], 0, L('register_affiliate'));
                    }
                }

                //设置推荐人
                $sql = 'UPDATE '. $global->ecs->table('users') . ' SET parent_id = ' . $up_uid . ' WHERE user_id = ' . $_SESSION['user_id'];

                $global->db->query($sql);
            }
        }

        //定义other合法的变量数组
        $other_key_array = array('msn', 'qq', 'office_phone', 'home_phone', 'mobile_phone');
        $update_data['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));
        if ($other) {
            foreach ($other as $key=>$val) {
                //删除非法key值
                if (!in_array($key, $other_key_array)) {
                    unset($other[$key]);
                } else {
                    $other[$key] =  htmlspecialchars(trim($val)); //防止用户输入javascript代码
                }
            }
            $update_data = array_merge($update_data, $other);
        }
        $global->db->autoExecute($global->ecs->table('users'), $update_data, 'UPDATE', 'user_id = ' . $_SESSION['user_id']);

        update_user_info();      // 更新用户信息
        recalculate_price();     // 重新计算购物车中的商品价格

        return true;
    }
}

/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function logout()
{
    /* todo */
}

/**
 *  将指定user_id的密码修改为new_password。可以通过旧密码和验证字串验证修改。
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   string  $new_password   用户新密码
 * @param   string  $old_password   用户旧密码
 * @param   string  $code           验证码（md5($user_id . md5($password))）
 *
 * @return  boolen  $bool
 */
function edit_password($user_id, $old_password, $new_password='', $code ='')
{
    $global = getInstance();
    if (empty($user_id)) {
        $global->err->add(L('not_login'));
    }

    if ($global->user->edit_password($user_id, $old_password, $new_password, $code)) {
        return true;
    } else {
        $global->err->add(L('edit_password_failure'));

        return false;
    }
}

/**
 *  会员找回密码时，对输入的用户名和邮件地址匹配
 *
 * @access  public
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 *
 * @return  boolen
 */
function check_userinfo($user_name, $email)
{
    $global = getInstance();
    if (empty($user_name) || empty($email)) {
        ecs_header("Location: user.php?act=get_password\n");

        exit;
    }

    /* 检测用户名和邮件地址是否匹配 */
    $user_info = $global->user->check_pwd_info($user_name, $email);
    if (!empty($user_info)) {
        return $user_info;
    } else {
        return false;
    }
}

/**
 *  用户进行密码找回操作时，发送一封确认邮件
 *
 * @access  public
 * @param   string  $uid          用户ID
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 * @param   string  $code         key
 *
 * @return  boolen  $result;
 */
function send_pwd_email($uid, $user_name, $email, $code)
{
    $global = getInstance();
    if (empty($uid) || empty($user_name) || empty($email) || empty($code)) {
        ecs_header("Location: user.php?act=get_password\n");

        exit;
    }

    /* 设置重置邮件模板所需要的内容信息 */
    $template    = get_mail_template('send_password');
    $reset_email = $global->ecs->url() . 'user.php?act=get_password&uid=' . $uid . '&code=' . $code;

    $global->tpl->assign('user_name', $user_name);
    $global->tpl->assign('reset_email', $reset_email);
    $global->tpl->assign('shop_name', C('shop_name'));
    $global->tpl->assign('send_date', date('Y-m-d'));
    $global->tpl->assign('sent_date', date('Y-m-d'));

    $content = $global->tpl->fetch('str:' . $template['template_content']);

    /* 发送确认重置密码的确认邮件 */
    if (send_mail($user_name, $email, $template['template_subject'], $content, $template['is_html'])) {
        return true;
    } else {
        return false;
    }
}

/**
 *  发送激活验证邮件
 *
 * @access  public
 * @param   int     $user_id        用户ID
 *
 * @return boolen
 */
function send_regiter_hash($user_id)
{
    $global = getInstance();
    /* 设置验证邮件模板所需要的内容信息 */
    $template    = get_mail_template('register_validate');
    $hash = register_hash('encode', $user_id);
    $validate_email = $global->ecs->url() . 'user.php?act=validate_email&hash=' . $hash;

    $sql = "SELECT user_name, email FROM " . $global->ecs->table('users') . " WHERE user_id = '$user_id'";
    $row = $global->db->getRow($sql);

    $global->tpl->assign('user_name', $row['user_name']);
    $global->tpl->assign('validate_email', $validate_email);
    $global->tpl->assign('shop_name', C('shop_name'));
    $global->tpl->assign('send_date', date(C('date_format')));

    $content = $global->tpl->fetch('str:' . $template['template_content']);

    /* 发送激活验证邮件 */
    if (send_mail($row['user_name'], $row['email'], $template['template_subject'], $content, $template['is_html'])) {
        return true;
    } else {
        return false;
    }
}

/**
 *  生成邮件验证hash
 *
 * @access  public
 * @param
 *
 * @return void
 */
function register_hash($operation, $key)
{
    $global = getInstance();
    if ($operation == 'encode') {
        $user_id = intval($key);
        $sql = "SELECT reg_time ".
               " FROM " . $global->ecs ->table('users').
               " WHERE user_id = '$user_id' LIMIT 1";
        $reg_time = $global->db->getOne($sql);

        $hash = substr(md5($user_id . C('hash_code') . $reg_time), 16, 4);

        return base64_encode($user_id . ',' . $hash);
    } else {
        $hash = base64_decode(trim($key));
        $row = explode(',', $hash);
        if (count($row) != 2) {
            return 0;
        }
        $user_id = intval($row[0]);
        $salt = trim($row[1]);

        if ($user_id <= 0 || strlen($salt) != 4) {
            return 0;
        }

        $sql = "SELECT reg_time ".
               " FROM " . $global->ecs ->table('users').
               " WHERE user_id = '$user_id' LIMIT 1";
        $reg_time = $global->db->getOne($sql);

        $pre_salt = substr(md5($user_id . C('hash_code') . $reg_time), 16, 4);

        if ($pre_salt == $salt) {
            return $user_id;
        } else {
            return 0;
        }
    }
}

/**
 * 判断超级管理员用户名是否存在
 * @param   string      $adminname 超级管理员用户名
 * @return  boolean
 */
function admin_registered($adminname)
{
    $global = getInstance();
    $res = $global->db->getOne("SELECT COUNT(*) FROM " . $global->ecs->table('admin_user') .
                                  " WHERE user_name = '$adminname'");
    return $res;
}
