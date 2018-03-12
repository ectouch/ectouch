<?php

class passport
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
     * 构造函数
     *
     * @param unknown $cfg
     */
    public function __construct($cfg)
    {
        $this->user_table = 'users';
        $this->field_id = 'user_id';
        $this->ec_salt = 'ec_salt';
        $this->field_name = 'user_name';
        $this->field_pass = 'password';
        $this->field_email = 'email';
        $this->field_mobile = 'mobile_phone';
        $this->field_gender = 'sex';
        $this->field_bday = 'birthday';
        $this->field_reg_date = 'reg_time';
        $this->field_passwd_question = 'passwd_question';
        $this->need_sync = false;
        $this->is_ecshop = 1;

        $this->db = new Model();
    }
    /**
     * 用户登录
     * @param $username
     * @param $password
     * @param null $remember
     * @return mixed
     */
    public function login($username, $password, $remember)
    {
        if ($this->check_user($username, $password) > 0) {
//             if ($this->need_sync) {
//                 $this->sync($username, $password);
//             }
            $this->set_session($username);
            $this->set_cookie($username, $remember);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 用户注销
     * @return mixed
     */
    public function logout()
    {
        $this->set_cookie(); // 清除cookie
        $this->set_session(); // 清除session
    }

    /**
     * 添加一个新用户
     * @param $username
     * @param $password
     * @param $email
     * @param int $gender
     * @param int $bday
     * @param int $reg_date
     * @param string $md5password
     * @return mixed
     */
    public function add_user($username, $password, $email, $gender = -1, $bday = 0, $reg_date = 0, $md5password = '')
    {

        /* 将用户添加到整合方 */
        if ($this->check_user($username) > 0) {
            $this->error = ERR_USERNAME_EXISTS;
        
            return false;
        }
        /* 检查email是否重复 */
        $sql = "SELECT " . $this->field_id . " FROM {pre}" . $this->user_table . " WHERE " . $this->field_email . " = '$email'";
        if ($this->db->getOne($sql) > 0) {
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
        
        $sql = "INSERT INTO {pre}" . $this->user_table . " (" . implode(',', $fields) . ")" . " VALUES ('" . implode("', '", $values) . "')";
        
        $this->db->query($sql);
        
        if ($this->need_sync) {
            $this->sync($username, $password);
        }
        
        return true;
    }

    /**
     * 编辑用户信息($password, $email, $gender, $bday)
     * @param $cfg
     * @return mixed
     */
    public function edit_user($cfg)
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
            $sql = "SELECT $this->field_id FROM {pre}".$this->user_table." WHERE ".$this->field_email." = '".$cfg['email']."' AND
".$this->field_name." <> '".$cfg['post_username']."'";
            $rs = $this->db->queryOne($sql);
            /*$rs = $this->db->table($this->user_table)->field($this->field_id)->where($this->field_email . " ='$cfg[email]' " . " AND " .$this->field_name . " != '$cfg[post_username]'")->getOne();*/
            if (!empty($rs) && $rs > 0) {
                $this->error = ERR_EMAIL_EXISTS;
        
                return false;
            }
            // 检查是否为新E-mail
            if ($this->db->table($this->user_table)->field('count(*)')->where(array($this->field_email=>$cfg['email']))->getField() == 0) {
                // 新的E-mail
                $this->db->table('users')->data(array('is_validated'=>0))->where(array('user_name'=>$cfg['post_username']))->update();
                //$sql = "UPDATE {pre}users SET is_validated = 0 WHERE user_name = '$cfg[post_username]'";
                        //$this->db->query($sql);
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
            $sql = "UPDATE {pre}" . $this->user_table . " SET " . implode(', ', $values) . " WHERE " . $this->field_name . "='" . $cfg['post_username'] . "' LIMIT 1";
        
            $this->db->query($sql);
        
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
     * @param $id
     * @return mixed
     */
    public function remove_user($id)
    {
        $post_id = $id;
        
        if ($this->need_sync || (isset($this->is_ecshop) && $this->is_ecshop)) {
            /* 如果需要同步或是ecshop插件执行这部分代码 */
            $where = (is_array($post_id)) ? db_create_in($post_id, 'user_name') : "user_name='" . $post_id . "'";
            $col = $this->db->table('users')
            ->field('user_id')
            ->where($where)
            ->getCol();
        
            if ($col) {
                $sql = "UPDATE {pre}users SET parent_id = 0 WHERE " . db_create_in($col, 'parent_id'); // 将删除用户的下级的parent_id 改为0
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}users WHERE " . db_create_in($col, 'user_id'); // 删除用户
                $this->db->query($sql);
                /* 删除用户订单 */
                $sql = "SELECT order_id FROM {pre}order_info WHERE " . db_create_in($col, 'user_id');
                $this->db->query($sql);
                $col_order_id = $this->db->table('order_info')
                ->field('order_id')
                ->where(db_create_in($col, 'user_id'))
                ->getCol();
                if ($col_order_id) {
                    $sql = "DELETE FROM {pre}order_info WHERE " . db_create_in($col_order_id, 'order_id');
                    $this->db->query($sql);
                    $sql = "DELETE FROM {pre}order_goods WHERE " . db_create_in($col_order_id, 'order_id');
                    $this->db->query($sql);
                }
        
                $sql = "DELETE FROM {pre}booking_goods WHERE " . db_create_in($col, 'user_id'); // 删除用户
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}collect_goods WHERE " . db_create_in($col, 'user_id'); // 删除会员收藏商品
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}feedback  WHERE " . db_create_in($col, 'user_id'); // 删除用户留言
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}user_address  WHERE " . db_create_in($col, 'user_id'); // 删除用户地址
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}user_bonus WHERE " . db_create_in($col, 'user_id'); // 删除用户红包
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}user_account WHERE " . db_create_in($col, 'user_id'); // 删除用户帐号金额
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}tag WHERE " . db_create_in($col, 'user_id'); // 删除用户标记
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}account_log  WHERE " . db_create_in($col, 'user_id'); // 删除用户日志
                $this->db->query($sql);
                $sql = "DELETE FROM {pre}wechat_user  WHERE " . db_create_in($col, 'ect_uid'); // 删除微信用户
                $this->db->query($sql);
                
                $col_connect_id = $this->db->table('connect_user')->field('id')->where(db_create_in($col, 'user_id'))->getCol();
                if ($col_connect_id) {
                    $sql = "DELETE FROM {pre}connect_user  WHERE " . db_create_in($col, 'user_id'); // 删除connect_user表关联数据
                    $this->db->query($sql);
                }
            }
        }
    }

    /**
     * 获取指定用户的信息
     * @param $username
     * @return mixed
     */
    public function get_profile_by_name($username)
    {
        $post_username = $username;
        
        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_mobile . " AS mobile," . $this->field_gender . " AS sex," . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_passwd_question . " AS passwd_question," . $this->field_pass . " AS password " . " FROM {pre}" . $this->user_table . " WHERE " . $this->field_name . "='$post_username'";
        $row = $this->db->queryRow($sql);
        
        return $row;
    }

    /**
     * 获取指定用户的信息
     * @param $id
     * @return mixed
     */
    public function get_profile_by_id($id)
    {
        $sql = "SELECT " . $this->field_id . " AS user_id," . $this->field_name . " AS user_name," . $this->field_email . " AS email," . $this->field_mobile . " AS mobile," . $this->field_gender . " AS sex," . $this->field_bday . " AS birthday," . $this->field_reg_date . " AS reg_time, " . $this->field_passwd_question . " AS passwd_question," . $this->field_pass . " AS password " . " FROM {pre}" . $this->user_table . " WHERE " . $this->field_id . "='$id'";
        $row = $this->db->queryRow($sql);
        
        return $row;
    }

    /**
     * 根据登录状态设置cookie
     * @return mixed
     */
    public function get_cookie()
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
     * @param $username
     * @param null $password
     * @return mixed
     */
    public function check_user($username, $password = null)
    {
        if ($this->charset != 'UTF8') {
            $post_username = ecs_iconv('UTF8', $this->charset, $username);
        } else {
            $post_username = $username;
        }
        if ($password === null) {
            $condition[$this->field_name] = $post_username;
            return $this->db->table($this->user_table)->field($this->field_id)->where($condition)->find();
        } else {
            $condition['user_name'] = $post_username;
            $row = $this->db->table($this->user_table)->field('user_id, password, salt, ec_salt')->where($condition)->find();

            if (empty($row)) {
                return 0;
            }
            $ec_salt = $row['ec_salt'];
            if (empty($row['salt'])) {
                if ($row['password'] != $this->compile_password(array(
                    'password' => $password,
                    'ec_salt' => $ec_salt
                ))) {
                    return 0;
                } else {
                    if (empty($ec_salt)) {
                        $data['ec_salt'] = rand(1, 9999);
                        $data['password'] = md5(md5($password) . $data['ec_salt']);
                        $this->db->table($this->user_table)
                        ->data($data)
                        ->where($condition)
                        ->update();
                    }
                    return $row['user_id'];
                }
            } else {
                /* 如果salt存在，使用salt方式加密验证，验证通过洗白用户密码 */
                $encrypt_type = substr($row['salt'], 0, 1);
                $encrypt_salt = substr($row['salt'], 1);
        
                /* 计算加密后密码 */
                $encrypt_password = '';
                switch ($encrypt_type) {
                    case ENCRYPT_ZC:
                        $encrypt_password = md5($encrypt_salt . $password);
                        break;
                        /* 如果还有其他加密方式添加到这里 */
                        // case other :
                        // ----------------------------------
                        // break;
                    case ENCRYPT_UC:
                        $encrypt_password = md5(md5($password) . $encrypt_salt);
                        break;
        
                    default:
                        $encrypt_password = '';
                }
        
                if ($row['password'] != $encrypt_password) {
                    return 0;
                }
        
                $sql = "UPDATE {pre}". $this->user_table . " SET password = '" . $this->compile_password(array(
                    'password' => $password
                )) . "', salt=''" . " WHERE user_id = '$row[user_id]'";
                $this->db->query($sql);
        
                return $row['user_id'];
            }
        }
    }

    /**
     * 检查指定邮箱是否存在
     * @param $email
     * @return mixed
     */
    public function check_email($email)
    {
        if (! empty($email)) {
            /* 检查email是否重复 */
            $result = $this->db->table($this->user_table)->field($this->field_id)->where($this->field_email . " = '$email' ")->find();
            if (count($result) > 0) {
                $this->error = ERR_EMAIL_EXISTS;
                return true;
            }
            return false;
        }
    }

    /**
     * 检查cookie是正确，返回用户名
     * @return mixed
     */
    public function check_cookie()
    {
        return '';
    }

    /**
     * 设置cookie
     * @param string $username
     * @param null $remember
     * @return mixed
     */
    public function set_cookie($username = '', $remember = null)
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
            $sql = "SELECT user_id, password FROM {pre}users  WHERE user_name='$username' LIMIT 1";
            $row = $this->db->queryRow($sql);
            if ($row) {
                setcookie("ECS[user_id]", $row['user_id'], $time, $this->cookie_path, $this->cookie_domain);
                setcookie("ECS[password]", $row['password'], $time, $this->cookie_path, $this->cookie_domain);
            }
        }
    }

    /**
     * 设置指定用户SESSION
     * @param string $username
     * @return mixed
     */
    public function set_session($username = '')
    {
        if (empty($username)) {
            $touch = get_Instance();
            $touch->load->sess->destroy_session();
        } else {
            $sql = 'SELECT user_id, password, email FROM {pre}users ' . " WHERE user_name='$username' LIMIT 1";
            $row = $this->db->queryRow($sql);
            
            if ($row) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $username;
                $_SESSION['email'] = $row['email'];
            }
        }
    }

    /**
     * 编译密码函数
     *
     * @access public
     * @param array $cfg
     * 包含参数为 $password, $md5password, $salt, $type
     *
     * @return void
     */
    public function compile_password($cfg)
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
            
                // no break
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
     * @param $username
     * @param string $password
     * @param string $md5password
     * @return mixed
     */
    public function sync($username, $password = '', $md5password = '')
    {
        if ((! empty($password)) && empty($md5password)) {
            $md5password = md5($password);
        }
        
        $main_profile = $this->get_profile_by_name($username);
        
        if (empty($main_profile)) {
            return false;
        }
        
        $sql = "SELECT user_name, email, password, sex, birthday  FROM {pre}users WHERE user_name = '$username'";
        
        $profile = $this->db->getRow($sql);
        if (empty($profile)) {
            /* 向商城表插入一条新记录 */
            if (empty($md5password)) {
                $sql = "INSERT INTO {pre}users (user_name, email, sex, birthday, reg_time)" . " VALUES('$username', '" . $main_profile['email'] . "','" . $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" . $main_profile['reg_time'] . "')";
            } else {
                $sql = "INSERT INTO {pre}users (user_name, email, sex, birthday, reg_time, password)" . " VALUES('$username', '" . $main_profile['email'] . "','" . $main_profile['sex'] . "','" . $main_profile['birthday'] . "','" . $main_profile['reg_time'] . "', '$md5password')";
            }
        
            $this->db->query($sql);
        
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
                $sql = "UPDATE {pre}users  SET " . implode(", ", $values) . " WHERE user_name='$username'";
        
                $this->db->query($sql);
        
                return true;
            }
        }
    }

    /**
     * 获取论坛有效积分及单位
     * @return mixed
     */
    public function get_points_name()
    {
    }

    /**
     * 获取用户积分
     * @param $username
     * @return mixed
     */
    public function get_points($username)
    {
        $credits = $this->get_points_name();
        $fileds = array_keys($credits);
        if ($fileds) {
            $sql = "SELECT " . $this->field_id . ', ' . implode(', ', $fileds) . " FROM {pre}" . $this->user_table . " WHERE " . $this->field_name . "='$username'";
            $row = $this->db->getRow($sql);
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 设置用户积分
     * @param $username
     * @param $credits
     * @return mixed
     */
    public function set_points($username, $credits)
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

    public function get_user_info($username)
    {
        return $this->get_profile_by_name($username);
    }

    /**
     * 检查有无重名用户，有则返回重名用户
     * @param $user_list
     * @return mixed
     */
    public function test_conflict($user_list)
    {
        if (empty($user_list)) {
            return array();
        }
        
        $user_list = $this->db->table($this->user_table)
        ->field($this->field_name)
        ->where(db_create_in($user_list, $this->field_name))
        ->getCol();
        
        return $user_list;
    }
}
