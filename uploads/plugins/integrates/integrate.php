<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * ECSHOP 整合插件类的基类
 */
class integrate
{
    /* 整合对象使用的数据库主机 */
    public $db_host = '';
    /* 整合对象使用的数据库名 */
    public $db_name = '';
    /* 整合对象使用的数据库用户名 */
    public $db_user = '';
    /* 整合对象使用的数据库密码 */
    public $db_pass = '';
    /* 整合对象数据表前缀 */
    public $prefix = '';
    /* 数据库所使用编码 */
    public $charset = '';
    /* 整合对象使用的cookie的domain */
    public $cookie_domain = '';
    /* 整合对象使用的cookie的path */
    public $cookie_path = '/';
    /* 整合对象会员表名 */
    public $user_table = '';
    /* 会员ID的字段名 */
    public $field_id = '';
    /* 会员名称的字段名 */
    public $field_name = '';
    /* 会员密码的字段名 */
    public $field_pass = '';
    /* 会员邮箱的字段名 */
    public $field_email = '';
    /* 会员手机的字段名 */
    public $field_mobile = '';
    /* 会员性别 */
    public $field_gender = '';
    /* 会员生日 */
    public $field_bday = '';
    /* 注册日期的字段名 */
    public $field_reg_date = '';
    /* 用户设置的问题 */
    public $field_passwd_question = '';
    /* 是否需要同步数据到商城 */
    public $need_sync = true;

    public $error = 0;

    private $db;

    /**
     * 会员数据整合插件类的构造函数
     *
     * @access public
     * @param string $db_host
     *            数据库主机
     * @param string $db_name
     *            数据库名
     * @param string $db_user
     *            数据库用户名
     * @param string $db_pass
     *            数据库密码
     * @return void
     */
    function __construct($cfg)
    {
        $this->charset = isset($cfg['db_charset']) ? $cfg['db_charset'] : 'UTF8';
        $this->prefix = isset($cfg['prefix']) ? $cfg['prefix'] : '';
        $this->db_name = isset($cfg['db_name']) ? $cfg['db_name'] : '';
        $this->cookie_domain = isset($cfg['cookie_domain']) ? $cfg['cookie_domain'] : '';
        $this->cookie_path = isset($cfg['cookie_path']) ? $cfg['cookie_path'] : '/';
        $this->need_sync = true;
        
        $quiet = empty($cfg['quiet']) ? 0 : 1;
        
        /* 初始化数据库 */
        $db_config = C('DB');
        if (empty($cfg['db_host'])) {
            $this->db_name = $db_config['DB_NAME'];
            $this->prefix = $db_config['DB_PREFIX'];
            if (class_exists('ECTouch')){
                $this->db = & ECTouch::db();
            }else{
                $this->db = $GLOBALS['db'];
            }
        } else {
            if (empty($cfg['is_latin1'])) {
                $this->db = new cls_mysql($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'], $this->charset, NULL, $quiet);
            } else {
                $this->db = new cls_mysql($cfg['db_host'], $cfg['db_user'], $cfg['db_pass'], $cfg['db_name'], 'latin1', NULL, $quiet);
            }
        }
    }

    /**
     * 用户登录函数
     *
     * @access public
     * @param string $username            
     * @param string $password            
     *
     * @return void
     */
    function login($username, $password, $remember = null)
    {
        if ($this->check_user($username, $password) > 0) {
            if ($this->need_sync) {
                $this->sync($username, $password);
            }
            $this->set_session($username);
            $this->set_cookie($username, $remember);
            
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function logout()
    {
        $this->set_cookie(); // 清除cookie
        $this->set_session(); // 清除session
    }

    /**
     * 添加一个新用户
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return int
     */
    function add_user($username, $password, $email, $gender = -1, $bday = 0, $reg_date = 0, $md5password = '')
    {
        /* 将用户添加到整合方 */
        if ($this->check_user($username) > 0) {
            $this->error = ERR_USERNAME_EXISTS;
            
            return false;
        }
        /* 检查email是否重复 */
        $sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$email'";
        if ($this->db->getOne($sql, true) > 0) {
            $this->error = ERR_EMAIL_EXISTS;
            
            return false;
        }
        
        $post_username = $username;
        
        if ($md5password) {
            $post_password = $this->compile_password(array(
                'md5password' => $md5password
            ));
        } else {
            $post_password = $this->compile_password(array(
                'password' => $password
            ));
        }
        
        $fields = array(
            $this->field_name,
            $this->field_email,
            $this->field_pass
        );
        $values = array(
            $post_username,
            $email,
            $post_password
        );
        
        if ($gender > - 1) {
            $fields[] = $this->field_gender;
            $values[] = $gender;
        }
        if ($bday) {
            $fields[] = $this->field_bday;
            $values[] = $bday;
        }
        if ($reg_date) {
            $fields[] = $this->field_reg_date;
            $values[] = $reg_date;
        }
        
        $sql = "INSERT INTO " . $this->table($this->user_table) . " (" . implode(',', $fields) . ")" . " VALUES ('" . implode("', '", $values) . "')";
        
        $this->db->query($sql);
        
        if ($this->need_sync) {
            $this->sync($username, $password);
        }
        
        return true;
    }

    /**
     * 编辑用户信息($password, $email, $gender, $bday)
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function edit_user($cfg)
    {
        if (empty($cfg['username'])) {
            return false;
        } else {
            $cfg['post_username'] = $cfg['username'];
        }
        
        $values = array();
        if (! empty($cfg['password']) && empty($cfg['md5password'])) {
            $cfg['md5password'] = md5($cfg['password']);
        }
        if ((! empty($cfg['md5password'])) && $this->field_pass != 'NULL') {
            $values[] = $this->field_pass . "='" . $this->compile_password(array(
                'md5password' => $cfg['md5password']
            )) . "'";
        }
        
        if ((! empty($cfg['email'])) && $this->field_email != 'NULL') {
            /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id . " FROM " . ECTouch::ecs()->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' " . " AND " . $this->field_name . " != '$cfg[post_username]'";
            if (ECTouch::db()->getOne($sql, true) > 0) {
                $this->error = ERR_EMAIL_EXISTS;
                
                return false;
            }
            // 检查是否为新E-mail
            $sql = "SELECT count(*)" . " FROM " . ECTouch::ecs()->table($this->user_table) . " WHERE " . $this->field_email . " = '$cfg[email]' ";
            if (ECTouch::db()->getOne($sql, true) == 0) {
                // 新的E-mail
                $sql = "UPDATE " . ECTouch::ecs()->table('users') . " SET is_validated = 0 WHERE user_name = '$cfg[post_username]'";
                $this->db->query($sql);
            }
            $values[] = $this->field_email . "='" . $cfg['email'] . "'";
        }
        
        if (isset($cfg['gender']) && $this->field_gender != 'NULL') {
            $values[] = $this->field_gender . "='" . $cfg['gender'] . "'";
        }
        
        if ((! empty($cfg['bday'])) && $this->field_bday != 'NULL') {
            $values[] = $this->field_bday . "='" . $cfg['bday'] . "'";
        }
        
        if ($values) {
            $sql = "UPDATE " . ECTouch::ecs()->table($this->user_table) . " SET " . implode(', ', $values) . " WHERE " . $this->field_name . "='" . $cfg['post_username'] . "' LIMIT 1";
            
            ECTouch::db()->query($sql);
            
            if ($this->need_sync) {
                if (empty($cfg['md5password'])) {
                    $this->sync($cfg['username']);
                } else {
                    $this->sync($cfg['username'], '', $cfg['md5password']);
                }
            }
        }
        
        return true;
    }

    /**
     * 删除用户
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function remove_user($id)
    {
        $post_id = $id;
        
        if ($this->need_sync || (isset($this->is_ecshop) && $this->is_ecshop)) {
            /* 如果需要同步或是ecshop插件执行这部分代码 */
            $sql = "SELECT user_id FROM " . ECTouch::ecs()->table('users') . " WHERE ";
            $sql .= (is_array($post_id)) ? db_create_in($post_id, 'user_name') : "user_name='" . $post_id . "' LIMIT 1";
            $col = ECTouch::db()->getCol($sql);
            
            if ($col) {
                $sql = "UPDATE " . ECTouch::ecs()->table('users') . " SET parent_id = 0 WHERE " . db_create_in($col, 'parent_id'); // 将删除用户的下级的parent_id 改为0
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('users') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户
                ECTouch::db()->query($sql);
                /* 删除用户订单 */
                $sql = "SELECT order_id FROM " . ECTouch::ecs()->table('order_info') . " WHERE " . db_create_in($col, 'user_id');
                ECTouch::db()->query($sql);
                $col_order_id = ECTouch::db()->getCol($sql);
                if ($col_order_id) {
                    $sql = "DELETE FROM " . ECTouch::ecs()->table('order_info') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    ECTouch::db()->query($sql);
                    $sql = "DELETE FROM " . ECTouch::ecs()->table('order_goods') . " WHERE " . db_create_in($col_order_id, 'order_id');
                    ECTouch::db()->query($sql);
                }
                
                $sql = "DELETE FROM " . ECTouch::ecs()->table('booking_goods') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('collect_goods') . " WHERE " . db_create_in($col, 'user_id'); // 删除会员收藏商品
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('feedback') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户留言
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('user_address') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户地址
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('user_bonus') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户红包
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('user_account') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户帐号金额
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('tag') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户标记
                ECTouch::db()->query($sql);
                $sql = "DELETE FROM " . ECTouch::ecs()->table('account_log') . " WHERE " . db_create_in($col, 'user_id'); // 删除用户日志
                ECTouch::db()->query($sql);
            }
        }
        
        if (isset($this->ecshop) && $this->ecshop) {
            /* 如果是ecshop插件直接退出 */
            return;
        }
        
        $sql = "DELETE FROM " . $this->table($this->user_table) . " WHERE ";
        if (is_array($post_id)) {
            $sql .= db_create_in($post_id, $this->field_name);
        } else {
            $sql .= $this->field_name . "='" . $post_id . "' LIMIT 1";
        }
        
        $this->db->query($sql);
    }

    /**
     * 获取指定用户的信息
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function get_profile_by_name($username)
    {
        $post_username = $username;
        
        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_mobile . " AS mobile," . $this->field_gender . " AS sex," . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_passwd_question . " AS passwd_question," . $this->field_pass . " AS password " . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='$post_username'";
        $row = $this->db->getRow($sql);
        
        return $row;
    }

    /**
     * 获取指定用户的信息
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function get_profile_by_id($id)
    {
        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_mobile . " AS mobile," . $this->field_gender . " AS sex," . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_passwd_question . " AS passwd_question," . $this->field_pass . " AS password " . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_id . "='$id'";
        $row = $this->db->getRow($sql);
        
        return $row;
    }

    /**
     * 根据登录状态设置cookie
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function get_cookie()
    {
        $id = $this->check_cookie();
        if ($id) {
            if ($this->need_sync) {
                $this->sync($id);
            }
            $this->set_session($id);
            
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查指定用户是否存在及密码是否正确
     *
     * @access public
     * @param string $username
     *            用户名
     *            
     * @return int
     */
    function check_user($username, $password = null)
    {
        $post_username = $username;
        
        /* 如果没有定义密码则只检查用户名 */
        if ($password === null) {
            $sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='" . $post_username . "'";
            
            return $this->db->getOne($sql);
        } else {
            $sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='" . $post_username . "' AND " . $this->field_pass . " ='" . $this->compile_password(array(
                'password' => $password
            )) . "'";
            
            return $this->db->getOne($sql);
        }
    }

    /**
     * 检查指定邮箱是否存在
     *
     * @access public
     * @param string $email
     *            用户邮箱
     *            
     * @return boolean
     */
    function check_email($email)
    {
        if (! empty($email)) {
            /* 检查email是否重复 */
            $sql = "SELECT " . $this->field_id . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_email . " = '$email' ";
            if ($this->db->getOne($sql, true) > 0) {
                $this->error = ERR_EMAIL_EXISTS;
                return true;
            }
            return false;
        }
    }

    /**
     * 检查cookie是正确，返回用户名
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function check_cookie()
    {
        return '';
    }

    /**
     * 设置cookie
     *
     * @access public
     * @param            
     * @return void
     */
    function set_cookie($username = '', $remember = null)
    {
        if (empty($username)) {
            /* 摧毁cookie */
            $time = time() - 3600;
            setcookie("ECS[user_id]", '', $time, $this->cookie_path);
            setcookie("ECS[password]", '', $time, $this->cookie_path);
        } elseif ($remember) {
            /* 设置cookie */
            $time = time() + 3600 * 24 * 15;
            
            setcookie("ECS[username]", $username, $time, $this->cookie_path, $this->cookie_domain);
            $sql = "SELECT user_id, password FROM " . ECTouch::ecs()->table('users') . " WHERE user_name='$username' LIMIT 1";
            $row = ECTouch::db()->getRow($sql);
            if ($row) {
                setcookie("ECS[user_id]", $row['user_id'], $time, $this->cookie_path, $this->cookie_domain);
                setcookie("ECS[password]", $row['password'], $time, $this->cookie_path, $this->cookie_domain);
            }
        }
    }

    /**
     * 设置指定用户SESSION
     *
     * @access public
     * @param            
     * @return void
     */
    function set_session($username = '')
    {
        if (empty($username)) {
            ECTouch::sess()->destroy_session();
        } else {
            $sql = "SELECT user_id, password, email FROM " . ECTouch::ecs()->table('users') . " WHERE user_name='$username' LIMIT 1";
            $row = ECTouch::db()->getRow($sql);
            
            if ($row) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $username;
                $_SESSION['email'] = $row['email'];
            }
        }
    }

    /**
     * 在给定的表名前加上数据库名以及前缀
     *
     * @access private
     * @param string $str
     *            表名
     *            
     * @return void
     */
    function table($str)
    {
        return '`' . $this->db_name . '`.`' . $this->prefix . $str . '`';
    }

    /**
     * 编译密码函数
     *
     * @access public
     * @param array $cfg
     *            包含参数为 $password, $md5password, $salt, $type
     *            
     * @return void
     */
    function compile_password($cfg)
    {
        if (isset($cfg['password'])) {
            $cfg['md5password'] = md5($cfg['password']);
        }
        if (empty($cfg['type'])) {
            $cfg['type'] = PWD_MD5;
        }
        
        switch ($cfg['type']) {
            case PWD_MD5:
                if (! empty($cfg['ec_salt'])) {
                    return md5($cfg['md5password'] . $cfg['ec_salt']);
                } else {
                    return $cfg['md5password'];
                }
            
            case PWD_PRE_SALT:
                if (empty($cfg['salt'])) {
                    $cfg['salt'] = '';
                }
                
                return md5($cfg['salt'] . $cfg['md5password']);
            
            case PWD_SUF_SALT:
                if (empty($cfg['salt'])) {
                    $cfg['salt'] = '';
                }
                
                return md5($cfg['md5password'] . $cfg['salt']);
            
            default:
                return '';
        }
    }

    /**
     * 会员同步
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function sync($username, $password = '', $md5password = '')
    {
        if ((! empty($password)) && empty($md5password)) {
            $md5password = md5($password);
        }
        
        $main_profile = $this->get_profile_by_name($username);
        
        if (empty($main_profile)) {
            return false;
        }
        
        $sql = "SELECT user_name, email, password, sex, birthday" . " FROM " . ECTouch::ecs()->table('users') . " WHERE user_name = '$username'";
        
        $profile = ECTouch::db()->getRow($sql);
        if (empty($profile)) {
            /* 向商城表插入一条新记录 */
            if (empty($md5password)) {
                $sql = "INSERT INTO " . ECTouch::ecs()->table('users') . "(user_name, email, sex, birthday, reg_time)" . " VALUES('$username', '" . $main_profile['email'] . "','" . $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" . $main_profile['reg_time'] . "')";
            } else {
                $sql = "INSERT INTO " . ECTouch::ecs()->table('users') . "(user_name, email, sex, birthday, reg_time, password)" . " VALUES('$username', '" . $main_profile['email'] . "','" . $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" . $main_profile['reg_time'] . "', '$md5password')";
            }
            
            ECTouch::db()->query($sql);
            
            return true;
        } else {
            $values = array();
            if ($main_profile['email'] != $profile['email']) {
                $values[] = "email='" . $main_profile['email'] . "'";
            }
            if ($main_profile['sex'] != $profile['sex']) {
                $values[] = "sex='" . $main_profile['sex'] . "'";
            }
            if ($main_profile['birthday'] != $profile['birthday']) {
                $values[] = "birthday='" . $main_profile['birthday'] . "'";
            }
            if ((! empty($md5password)) && ($md5password != $profile['password'])) {
                $values[] = "password='" . $md5password . "'";
            }
            
            if (empty($values)) {
                return true;
            } else {
                $sql = "UPDATE " . ECTouch::ecs()->table('users') . " SET " . implode(", ", $values) . " WHERE user_name='$username'";
                
                ECTouch::db()->query($sql);
                
                return true;
            }
        }
    }

    /**
     * 获取论坛有效积分及单位
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function get_points_name()
    {
        return array();
    }

    /**
     * 获取用户积分
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function get_points($username)
    {
        $credits = $this->get_points_name();
        $fileds = array_keys($credits);
        if ($fileds) {
            $sql = "SELECT " . $this->field_id . ', ' . implode(', ', $fileds) . " FROM " . $this->table($this->user_table) . " WHERE " . $this->field_name . "='$username'";
            $row = $this->db->getRow($sql);
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 设置用户积分
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function set_points($username, $credits)
    {
        $user_set = array_keys($credits);
        $points_set = array_keys($this->get_points_name());
        
        $set = array_intersect($user_set, $points_set);
        
        if ($set) {
            $tmp = array();
            foreach ($set as $credit) {
                $tmp[] = $credit . '=' . $credit . '+' . $credits[$credit];
            }
            $sql = "UPDATE " . $this->table($this->user_table) . " SET " . implode(', ', $tmp) . " WHERE " . $this->field_name . " = '$username'";
            $this->db->query($sql);
        }
        
        return true;
    }

    function get_user_info($username)
    {
        return $this->get_profile_by_name($username);
    }

    /**
     * 检查有无重名用户，有则返回重名用户
     *
     * @access public
     * @param            
     *
     *
     *
     *
     * @return void
     */
    function test_conflict($user_list)
    {
        if (empty($user_list)) {
            return array();
        }
        
        $sql = "SELECT " . $this->field_name . " FROM " . $this->table($this->user_table) . " WHERE " . db_create_in($user_list, $this->field_name);
        $user_list = $this->db->getCol($sql);
        
        return $user_list;
    }
}
