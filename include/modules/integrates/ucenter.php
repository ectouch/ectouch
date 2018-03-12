<?php

class ucenter
{
    /**
     * 用户登录
     * @param $username
     * @param $password
     * @param null $remember
     * @return mixed
     */
    public function login($username, $password, $remember)
    {
    }

    /**
     * 用户注销
     * @return mixed
     */
    public function logout()
    {
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
    }

    /**
     * 编辑用户信息($password, $email, $gender, $bday)
     * @param $cfg
     * @return mixed
     */
    public function edit_user($cfg)
    {
    }

    /**
     * 删除用户
     * @param $id
     * @return mixed
     */
    public function remove_user($id)
    {
    }

    /**
     * 获取指定用户的信息
     * @param $username
     * @return mixed
     */
    public function get_profile_by_name($username)
    {
    }

    /**
     * 获取指定用户的信息
     * @param $id
     * @return mixed
     */
    public function get_profile_by_id($id)
    {
    }

    /**
     * 根据登录状态设置cookie
     * @return mixed
     */
    public function get_cookie()
    {
    }

    /**
     * 检查指定用户是否存在及密码是否正确
     * @param $username
     * @param null $password
     * @return mixed
     */
    public function check_user($username, $password = null)
    {
    }

    /**
     * 检查指定邮箱是否存在
     * @param $email
     * @return mixed
     */
    public function check_email($email)
    {
    }

    /**
     * 检查cookie是正确，返回用户名
     * @return mixed
     */
    public function check_cookie()
    {
    }

    /**
     * 设置cookie
     * @param string $username
     * @param null $remember
     * @return mixed
     */
    public function set_cookie($username = '', $remember = null)
    {
    }

    /**
     * 设置指定用户SESSION
     * @param string $username
     * @return mixed
     */
    public function set_session($username = '')
    {
    }

    /**
     * 编译密码函数
     * @param $cfg
     * @return mixed
     */
    public function compile_password($cfg)
    {
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
    }

    /**
     * 设置用户积分
     * @param $username
     * @param $credits
     * @return mixed
     */
    public function set_points($username, $credits)
    {
    }

    public function get_user_info($username)
    {
    }

    /**
     * 检查有无重名用户，有则返回重名用户
     * @param $user_list
     * @return mixed
     */
    public function test_conflict($user_list)
    {
    }
}
