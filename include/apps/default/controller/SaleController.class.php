<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SaleController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SaleController extends CommonController {

    protected $user_id;
    protected $action;
    protected $back_act = '';

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        // 属性赋值
        $this->user_id = $_SESSION['user_id'];
        $this->action = ACTION_NAME;
        // 验证登录
        $this->check_login();
        // 用户信息
        $info = model('ClipsBase')->get_user_default($this->user_id);
        //判断用户类型，不是分销用户跳转到user控制器中
        if ($info['user_rank'] != 100 && $this->user_id > 0){
            ecs_header("Location: ".url('user/index'));
        }
        // 如果是显示页面，对页面进行相应赋值
        assign_template();
        $this->assign('action', $this->action);
        $this->assign('info', $info);
        
    }

    /**
     * 会员中心欢迎页
     */
    public function index() {
        // 用户类型
        $this->assign('rank_name', sprintf(L('your_level'), '分销用户'));
        // 用户余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        // 信息中心是否有新回复
        $sql = 'SELECT msg_id FROM ' . $this->model->pre . 'feedback WHERE parent_id IN (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 and f.user_id = ' . $this->user_id . ' and t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
        $rs = $this->model->query($sql);
        if ($rs) {
            $this->assign('new_msg', 1);
        }
        $this->assign('user_notice', C('user_notice'));
        $this->assign('title', L('user_center'));
        $this->display('sale.dwt');
    }

    /**
     * 账户中心
     */
    public function profile() {
        // 修改个人资料的处理
        if (IS_POST) {
            $email = I('post.email');
            $other['qq'] = $qq = I('post.extend_field2');
            $other['mobile_phone'] = $mobile_phone = I('post.mobile_phone');
           
            if (!empty($office_phone) && !preg_match('/^[\d|\_|\-|\s]+$/', $office_phone)) {
                show_message(L('passport_js.office_phone_invalid'));
            }
            if (!is_email($email)) {
                show_message(L('msg_email_format'));
            }
            if (!empty($qq) && !preg_match('/^\d+$/', $qq)) {
                show_message(L('passport_js.qq_invalid'));
            }
            if (!empty($mobile_phone) && !preg_match("/^1[0-9]{10}$/", $mobile_phone)) {
                show_message(L('passport_js.mobile_phone_invalid'));
            }
            
           

            // 写入密码提示问题和答案
            if (!empty($passwd_answer) && !empty($sel_question)) {
                $where_up['user_id'] = $this->user_id;
                $data_up['passwd_question'] = $sel_question;
                $data_up['passwd_answer'] = $passwd_answer;
                $this->model->table('users')
                        ->data($data_up)
                        ->where($where_up)
                        ->update();
            }

            $profile = array(
                'user_id' => $this->user_id,
                'email' => I('post.email'),
                'sex' => I('post.sex', 0),
                'other' => isset($other) ? $other : array()
            );

            if (model('Sale')->edit_profile($profile)) {
                show_message(L('edit_profile_success'), L('profile_lnk'), url('profile'), 'info');
            } else {
                if (self::$user->error == ERR_EMAIL_EXISTS) {
                    $msg = sprintf(L('email_exist'), $profile['email']);
                } else {
                    $msg = L('edit_profile_failed');
                }
                show_message($msg, '', '', 'info');
            }
            exit();
        }
        // 用户资料
        $user_info = model('Sale')->get_profile($this->user_id);
        // 取出注册扩展字段
        $where = 'type < 2 and display = 1';
        $extend_info_list = $this->model->table('reg_fields')
                ->where($where)
                ->order('dis_order, id')
                ->select();

        $condition['user_id'] = $this->user_id;
        $extend_info_arr = $this->model->table('reg_extend_info')
                ->field('reg_field_id, content')
                ->where($condition)
                ->select();
        if (empty($extend_info_arr)) {
            $extend_info_arr = array();
        }

        $temp_arr = array();
        foreach ($extend_info_arr as $val) {
            $temp_arr[$val['reg_field_id']] = $val['content'];
        }

        foreach ($extend_info_list as $key => $val) {
            switch ($val['id']) {
                case 1:
                    unset($extend_info_list[$key]);
                    break;
                case 2:
                    $extend_info_list[$key]['content'] = $user_info['qq'];
                    break;
                case 3:
                    $extend_info_list[$key]['content'] = $user_info['office_phone'];
                    break;
                case 4:
                    unset($extend_info_list[$key]);
                    break;
                case 5:
                    $extend_info_list[$key]['content'] = $user_info['mobile_phone'];
                    break;
                default:
                    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
            }
        }

        $this->assign('title', L('profile'));
        $this->assign('extend_info_list', $extend_info_list);
        // 密码提示问题
        $this->assign('passwd_questions', L('passwd_questions'));
        $this->assign('profile', $user_info);
        $this->display('sale_profile.dwt');
    }


    /**
     * 登录
     */
    public function login() {
        // 登录处理
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password');
            $this->back_act = urldecode(I('post.back_act'));

            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'referer' => urlencode($this->back_act)
                            )), 'error');
                }
                // 检查验证码
                if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'referer' => urlencode($this->back_act)
                            )), 'error');
                }
            }

            // 用户名是邮箱格式
            if (is_email($username)) {
                $where['email'] = $username;
                $username_try = $this->model->table('users')
                        ->field('user_name')
                        ->where($where)
                        ->getOne();
                $username = $username_try ? $username_try : $username;
            }

            // 用户名是手机格式
            if (is_mobile($username)) {
                $where['mobile_phone'] = $username;
                $username_try = $this->model->table('users')
                        ->field('user_name')
                        ->where($where)
                        ->getOne();
                $username = $username_try ? $username_try : $username;
            }

            if (self::$user->login($username, $password, isset($_POST['remember']))) {
                model('Sale')->update_user_info();
                model('Sale')->recalculate_price();

                $jump_url = empty($this->back_act) ? url('index') : $this->back_act;
                $this->redirect($jump_url);
            } else {
                $_SESSION['login_fail']++;
                show_message(L('login_failure'), L('relogin_lnk'), url('login', array(
                    'referer' => urlencode($this->back_act)
                        )), 'error');
            }
            exit();
        }

        // 登录页面显示
        if (isset($_GET['referer']) && !empty($_GET['referer'])) {
            $this->back_act = $_GET['referer'];
        }

        if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
            $this->back_act = urlencode($this->back_act);
        }

        // 验证码相关设置
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
		
        $this->assign('step', I('get.step'));
        $this->assign('anonymous_buy', C('anonymous_buy'));
        $this->assign('title', L('login'));
        $this->assign('back_act', $this->back_act);
        $this->display('sale_login.dwt');
    }

    /**
     * 注册
     */
    public function register() {
        // 注册处理
        if (IS_POST) {
            $enabled_sms = isset($_POST['enabled_sms']) ? intval($_POST['enabled_sms']) : 0;
            $this->back_act = isset($_POST['back_act']) ? in($_POST['back_act']) : '';

            // 邮箱注册处理
            if (0 == $enabled_sms) {
                // 数据处理
                $username = isset($_POST['username']) ? in($_POST['username']) : '';
                $email = isset($_POST['email']) ? in($_POST['email']) : '';
                $password = isset($_POST['password']) ? in($_POST['password']) : '';
                $other = array();
                $other['mobile_phone'] = isset($_POST['mobile']) ? in($_POST['mobile']) : '';

                // 验证码检查
                if (intval(C('captcha')) & CAPTCHA_REGISTER) {
                    if (empty($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                    // 检查验证码
                    if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                }

                if (empty($_POST['agreement'])) {
                    show_message(L('passport_js.agreement'));
                }

                if (strlen($username) < 3) {
                    show_message(L('passport_js.username_shorter'));
                }
                if (strlen($username) > 15) {
                    show_message(L('passport_js.username_longer'));
                }

                if (strlen($password) < 6) {
                    show_message(L('passport_js.password_shorter'));
                }

                if (strpos($password, ' ') > 0) {
                    show_message(L('passwd_balnk'));
                }
            }else {
                ECTouch::err()->show(L('sign_up'), url('register'));
            }
            
            $other['user_rank'] = 100;
            if (model('Sale')->register($username, $password, $email, $other , C('send_type_rand') !== false) !== false) {
                

                $sel_question = I('post.sel_question');
                $passwd_answer = I('post.passwd_answer');
                
                // 写入密码提示问题和答案
                if (!empty($passwd_answer) && !empty($sel_question)) {
                    $where_up['user_id'] = $_SESSION['user_id'];
                    $data_up['passwd_question'] = $sel_question;
                    $data_up['passwd_answer'] = $passwd_answer;
                    $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
                }
                
                
                // 判断是否需要自动发送注册邮件
                if (C('member_email_validate') && C('send_verify_email')) {
                    model('Sale')->send_regiter_hash($_SESSION['user_id']);
                }
                $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
                show_message(sprintf(L('register_success'), $username . $ucdata), array(
                    L('back_up_page'),
                    L('profile_lnk')
                        ), array(
                    $this->back_act,
                    url('index')
                        ), 'info');
            } else {
                ECTouch::err()->show(L('sign_up'), url('register'));
            }
            exit();
        }
         // 密码提示问题
        $this->assign('password_question', L('passwd_questions'));
        
        // 注册页面显示

        if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        }

        // 验证码相关设置
        if (intval(C('captcha')) & CAPTCHA_REGISTER) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }

        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
            // 随机code
            $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            $this->assign('sms_code', $sms_code);
        }

        $this->assign('title', L('register'));
        $this->assign('back_act', $this->back_act);
        
        /* 是否关闭注册 */
        $this->assign('shop_reg_closed', C('shop_reg_closed'));
        
        $this->display('sale_register.dwt');
    }

    /**
     * 邮件验证
     */
    public function validate_email() {
        $hash = I('get.hash');
        if ($hash) {
            $id = model('Sale')->register_hash('decode', $hash);
            if ($id > 0) {
                $this->model->table('users')->data('is_validated = 1')->where('user_id = ' . $id)->update();
                $row = $this->model->table('users')->field('user_name, email')->where('user_id = ' . $id)->find();
                show_message(sprintf(L('validate_ok'), $row['user_name'], $row['email']), L('profile_lnk'), url('index'));
            }
        }
        show_message(L('validate_fail'));
    }


    /**
     * 手机找回密码
     */
    public function get_password_phone() {
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            // 手机找回密码处理
            if (IS_POST) {

                $mobile = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
                $mobile_code = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
                $sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';

                if ($sms_code != $_SESSION['sms_code']) {
                    show_message(L('sms_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                }

                if ($mobile_code != $_SESSION['sms_mobile_code']) {
                    show_message(L('mobile_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                }

                $where['mobile_phone'] = $mobile;
                $user_id = $this->model->table('users')
                        ->field('user_id')
                        ->where($where)
                        ->getOne();

                $this->assign('uid', $user_id);
                $this->assign('mobile', base64_encode($mobile));
                $this->display('user_reset_password.dwt');
                exit();
            }

            // 随机code
            $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));

            $this->assign('title', L('get_password'));
            $this->assign('enabled_sms_signin', C('sms_signin'));
            $this->assign('sms_code', $sms_code);
            $this->display('sale_get_password.dwt');
        } else {
            $this->redirect(url('get_password_email'));
        }
    }

    /**
     * 邮件找回密码
     */
    public function get_password_email() {
        if (isset($_GET['code']) && isset($_GET['uid'])) { // 从邮件处获得的act
            $code = in($_GET['code']);
            $uid = intval($_GET['uid']);

            // 判断链接的合法性
            $user_info = self::$user->get_profile_by_id($uid);
            if (empty($user_info) || ($user_info && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) != $code)) {
                show_message(L('parm_error'), L('back_home_lnk'), url('index/index'), 'info');
            }

            $this->assign('uid', $uid);
            $this->assign('code', $code);
            $this->assign('title', L('reset_password'));
            $this->display('user_reset_password.dwt');
        } else {
            // 验证码相关设置
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }
            // 短信开启
            if (intval(C('sms_signin')) > 0) {
                $this->assign('enabled_sms_signin', C('sms_signin'));
            }
            $this->assign('title', L('get_password'));
            $this->display('sale_get_password.dwt');
        }
    }

    /**
     * 发送密码修改确认邮件
     */
    public function send_pwd_email() {
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            if (empty($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }

            // 检查验证码
            if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }
        }

        // 初始化会员用户名和邮件地址
        $user_name = !empty($_POST['user_name']) ? in($_POST['user_name']) : '';
        $email = !empty($_POST['email']) ? in($_POST['email']) : '';

        // 用户信息
        $user_info = self::$user->get_user_info($user_name);

        if ($user_info && $user_info['email'] == $email) {
            // 生成code
            $code = md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']);
            // 发送邮件的函数
            if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
                show_message(L('send_success') . $email, L('relogin_lnk'), url('login'), 'info');
            } else {
                // 发送邮件出错
                show_message(L('fail_send_password'), L('back_page_up'), url('get_password_email'), 'info');
            }
        } else {
            // 用户名与邮件地址不匹配
            show_message(L('username_no_email'), L('back_page_up'), url('get_password_email'), 'info');
        }
    }


    /**
     * 修改密码
     */
    public function edit_password() {
        // 修改密码处理
        if (IS_POST) {
            $old_password = isset($_POST['old_password']) ? in($_POST['old_password']) : null;
            $new_password = isset($_POST['new_password']) ? in($_POST['new_password']) : '';
            $comfirm_password = isset($_POST['comfirm_password']) ? in($_POST['comfirm_password']) : '';
            $user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $this->user_id;
            $code = isset($_POST['code']) ? in($_POST['code']) : ''; // 邮件code
            $mobile = isset($_POST['mobile']) ? base64_decode(in($_POST['mobile'])) : ''; // 手机号
            $question = isset($_POST['question']) ? base64_decode(in($_POST['question'])) : ''; // 问题

            if ($comfirm_password != $new_password){
                show_message(L('password_js.both_password_error'),L('back_page_up'), '', 'info');
            }
            if (strlen($new_password) < 6) {
                show_message(L('passport_js.password_shorter'),L('back_page_up'), '', 'info');
            }

            $user_info = self::$user->get_profile_by_id($user_id); // 论坛记录
            // 短信找回，邮件找回，问题找回，登录修改密码
            if ((!empty($mobile) && $user_info['mobile'] == $mobile) || ($user_info && (!empty($code) && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) == $code)) || (!empty($question) && $user_info['passwd_question'] == $question) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && self::$user->check_user($_SESSION['user_name'], $old_password))) {

                if (self::$user->edit_user(array(
                            'username' => ((empty($code) && empty($mobile) && empty($question)) ? $_SESSION['user_name'] : $user_info['user_name']),
                            'old_password' => $old_password,
                            'password' => $new_password
                                ), empty($code) ? 0 : 1)) {
                    $data['ec_salt'] = 0;
                    $where['user_id'] = $user_id;
                    $this->model->table('users')
                            ->data($data)
                            ->where($where)
                            ->update();

                    self::$user->logout();
                    show_message(L('edit_password_success'), L('relogin_lnk'), url('login'), 'info');
                } else {
                    show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
                }
            } else {
                show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
            }
        }

        // 显示修改密码页面
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $this->assign('title', L('edit_password'));
            // 判断登录方式
            if (model('Sale')->is_third_user($_SESSION['user_id'])) {
                $this->assign('is_third', 1);
            }
            $this->display('sale_edit_password.dwt');
        } else {
            $this->redirect(url('login', array(
                'referer' => urlencode(url($this->action))
            )));
        }
    }

    /**
     * 退出
     */
    public function logout() {
        if ((!isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        } else {
            $this->back_act = url('login');
        }

        self::$user->logout();
        $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
        show_message(L('logout') . $ucdata, array(
            L('back_up_page'),
            L('back_home_lnk')
                ), array(
            $this->back_act,
            url('index/index')
                ), 'info');
    }


    /**
     * 未登录验证
     */
    private function check_login() {
        // 不需要登录的操作或自己验证是否登录（如ajax处理）的方法
        $without = array(
            'login',
            'register',
            'get_password_phone',
            'get_password_email',
            'get_password_question',
            'pwd_question_name',
            'send_pwd_email',
            'edit_password',
            'check_answer',
            'logout',
            'clear_histroy',
            'add_collection',
            'third_login'
        );
        // 未登录处理
        if (empty($_SESSION['user_id']) && !in_array($this->action, $without)) {
            $url = __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('login', array(
                'referer' => urlencode($url)
            )));
            exit();
        }

        // 已经登录，不能访问的方法
        $deny = array(
            'login',
            'register'
        );
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
            $this->redirect(url('index/index'));
            exit();
        }
    }
    
    /**
     * 我要分销
     */
    public function to_sale(){
        //生成分享连接
        $shopurl = __HOST__.url('index/index',array('sale'=>$this->user_id));
        $this->assign('shopurl', $shopurl);
        $this->assign('domain', __HOST__);
        $this->assign('shopdesc', C('shop_desc'));
        
        // 生成二维码
        $mobile_url = __URL__; // 二维码内容
        $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H
        $matrixPointSize = 7; // 点的大小：1到10
        $mobile_qr = 'data/sale/sale_qrcode_'.$this->user_id.'.png';
        QRcode::png($shopurl, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        // 二维码路径赋值
        $this->assign('mobile_qr', $mobile_qr);
        
        $this->assign('title','我要分销');
        $this->display('to_sale.dwt');
    }
    
    /**
     * 我的下线
     */
    public function line(){
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $count = model('Sale')->get_line_count();
        $this->pageLimit(url('sale/line'), $size);
        $this->assign('pager', $this->pageShow($count));
        
        //获取用户下线
        $list = model('Sale')->get_line_list($size, ($page-1)*$size);
        //模板赋值
        $this->assign('list',    $list);
        $this->assign('title','我的下线');
        $this->display('sale_line.dwt');
    }
    
    /**
     * 获取全部分销订单
     */
    public function order_list() {
        
        $where = 'parent_id = ' . $this->user_id;
        if (I('get.uid') > 0){
            $where = $where.' and user_id ='.I('get.uid');
        }
        $pay = 1;
        $size = I(C('page_size'), 10);
        $count = $this->model->table('order_info')->where($where)->count();
        $filter['page'] = '{page}';
        $filter['uid'] = I('get.uid');
        $offset = $this->pageLimit(url('order_list', $filter), $size);
        $offset_page = explode(',', $offset);
        $orders = model('Sale')->get_user_orders($this->user_id, $pay, $offset_page[1], $offset_page[0],I('get.uid'));
        $this->assign('pay', $pay);
        $this->assign('title', L('order_list'));
        $this->assign('pager', $this->pageShow($count));
        $this->assign('orders_list', $orders);
        // 获取下线信息
        if (I('get.uid') > 0){
             $this->assign('uname', model('Sale')->get_user_by_id(I('get.uid')));
        }
        $this->display('sale_order_list.dwt');
    }
    
    /**
     * 分销订单详情
     */
    public function order_detail() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    
        // 订单详情
        $order = model('Sale')->get_order_detail($order_id, $this->user_id);
        
        if ($order === false) {
            ECTouch::err()->show(L('back_home_lnk'), './');
            exit();
        }
    
        // 订单商品
        $goods_list = model('Order')->order_goods($order_id);
        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
            $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
            $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
            $goods_list[$key]['tags'] = model('ClipsBase')->get_tags($value['goods_id']);
            $goods_list[$key]['goods_thumb'] = get_image_path($order_id, $value['goods_thumb']);
        }

        // 订单 支付 配送 状态语言项
        $order['order_status'] = L('os.' . $order['order_status']);
        $order['pay_status'] = L('ps.' . $order['pay_status']);
        $order['shipping_status'] = L('ss.' . $order['shipping_status']);
      

        $this->assign('title', L('order_detail'));
        $this->assign('order', $order);
        $this->assign('goods_list', $goods_list);
        $this->display('sale_order_detail.dwt');
    }
    
    /**
     * 资金管理
     */
    public function account_detail() {
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $where = 'user_id = ' . $this->user_id . ' AND user_money <> 0';
        $count = $this->model->table('account_log')->field('COUNT(*)')->where($where)->getOne();
        $this->pageLimit(url('sale/account_detail'), $size);
        $this->assign('pager', $this->pageShow($count));
    
        $account_detail = model('Sale')->get_account_detail($this->user_id, $size, ($page-1)*$size);
    
        $this->assign('title', L('label_user_surplus'));
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log', $account_detail);
        $this->display('sale_account_detail.dwt');
    }
    
    
    /**
     *  会员充值和提现申请记录
     */
    public function  account_log(){
    
        $size = I(C('page_size'), 5);
        $page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $count = $this->model->table('user_account')->field('COUNT(*)')->where("user_id = $this->user_id AND process_type ". db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN)))->getOne();
        $this->pageLimit(url('sale/account_log'), $size);
        $this->assign('pager', $this->pageShow($count));
    
        //获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount))
        {
            $surplus_amount = 0;
        }
        //获取余额记录
        $account_log = model('ClipsBase')->get_account_log($this->user_id, $size, ($page-1)*$size);
    
        //模板赋值
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log',    $account_log);
        $this->assign('title', L('label_user_surplus'));
        $this->display('sale_account_log.dwt');
    }
    
    /**
     *  删除会员余额
     */
    public function cancel(){
    
        $id = I('get.id',0);
        if ($id == 0 || $this->user_id == 0)
        {
            ecs_header("Location: ".url('sale/account_log'));
            exit;
        }
    
        $result = model('ClipsBase')->del_user_account($id, $this->user_id);
        ecs_header("Location: ".url('sale/account_log'));
    }
    
    /**
     *  会员退款申请界面
     */
    public function account_raply(){
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('title', L('label_user_surplus'));
        $this->display('sale_account_raply.dwt');
    }
    
    /**
     *  会员预付款界面
     */
    public function account_deposit(){
        $this->assign('title', L('label_user_surplus'));
        $surplus_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $account    = model('ClipsBase')->get_surplus_info($surplus_id);
    
        $this->assign('payment', model('ClipsBase')->get_online_payment_list(false));
        $this->assign('order',   $account);
        $this->display('sale_account_deposit.dwt');
    }
    
    /**
     *  对会员余额申请的处理
     */
    public function act_account()
    {
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        if ($amount <= 0)
        {
            show_message($_LANG['amount_gt_zero']);
        }
    
        /* 变量初始化 */
        $surplus = array(
            'user_id'      => $this->user_id,
            'rec_id'       => !empty($_POST['rec_id'])      ? intval($_POST['rec_id'])       : 0,
            'process_type' => isset($_POST['surplus_type']) ? intval($_POST['surplus_type']) : 0,
            'payment_id'   => isset($_POST['payment_id'])   ? intval($_POST['payment_id'])   : 0,
            'user_note'    => isset($_POST['user_note'])    ? trim($_POST['user_note'])      : '',
            'amount'       => $amount
        );
    
        /* 退款申请的处理 */
        if ($surplus['process_type'] == 1)
        {
            /* 判断是否有足够的余额的进行退款的操作 */
            $sur_amount = model('ClipsBase')->get_user_surplus($this->user_id);
            if ($amount > $sur_amount)
            {
                $content = L('surplus_amount_error');
                show_message($content, L('back_page_up'), '', 'info');
            }
    
            //插入会员账目明细
            $amount = '-'.$amount;
            $surplus['payment'] = '';
            $surplus['rec_id']  = model('ClipsBase')->insert_user_account($surplus, $amount);
    
            /* 如果成功提交 */
            if ($surplus['rec_id'] > 0)
            {
                $content = L('surplus_appl_submit');
                show_message($content, L('back_account_log'), url('sale/account_log'), 'info');
            }
            else
            {
                $content = $L('process_false');
                show_message($content, L('back_page_up'), '', 'info');
            }
        }
        /* 如果是会员预付款，跳转到下一步，进行线上支付的操作 */
        else
        {
            if ($surplus['payment_id'] <= 0)
            {
                show_message(L('select_payment_pls'));
            }
    
    
            //获取支付方式名称
            $payment_info = array();
            $payment_info = model('Order')->payment_info($surplus['payment_id']);
            $surplus['payment'] = $payment_info['pay_name'];
    
            if ($surplus['rec_id'] > 0)
            {
                //更新会员账目明细
                $surplus['rec_id'] = model('ClipsBase')->update_user_account($surplus);
            }
            else
            {
                //插入会员账目明细
                $surplus['rec_id'] = model('ClipsBase')->insert_user_account($surplus, $amount);
            }
    
            //取得支付信息，生成支付代码
            $payment = unserialize_config($payment_info['pay_config']);
    
            //生成伪订单号, 不足的时候补0
            $order = array();
            $order['order_sn']       = $surplus['rec_id'];
            $order['user_name']      = $_SESSION['user_name'];
            $order['surplus_amount'] = $amount;
    
            //计算支付手续费用
            $payment_info['pay_fee'] = pay_fee($surplus['payment_id'], $order['surplus_amount'], 0);
    
            //计算此次预付款需要支付的总金额
            $order['order_amount']   = $amount + $payment_info['pay_fee'];
    
            //记录支付log
            $order['log_id'] = model('ClipsBase')->insert_pay_log($surplus['rec_id'], $order['order_amount'], $type=PAY_SURPLUS, 0);
    
            /* 调用相应的支付方式文件 */
            include_once (ROOT_PATH . 'plugins/payment/' . $payment_info ['pay_code'] . '.php');
    
            /* 取得在线支付方式的支付按钮 */
            $pay_obj = new $payment_info ['pay_code'] ();
            $payment_info['pay_button'] = $pay_obj->get_code($order, $payment);
    
            /* 模板赋值 */
            $this->assign('payment', $payment_info);
            $this->assign('pay_fee', price_format($payment_info['pay_fee'], false));
            $this->assign('amount',  price_format($amount, false));
            $this->assign('order',   $order);
            $this->display('sale_act_account.dwt');
        }
    }

}
