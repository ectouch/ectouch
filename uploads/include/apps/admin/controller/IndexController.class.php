<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：管理中心首页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexController extends AdminController
{
    
    // 管理中心
    public function index()
    {
        $this->display('index');
    }
    
    // 欢迎页
    public function welcome()
    {
        /* 系统信息 */
        $conn = mysql_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'));
        $gd = gd_version();
        $sys_info['os'] = PHP_OS;
        $sys_info['ip'] = $_SERVER['SERVER_ADDR'];
        $sys_info['web_server'] = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['php_ver'] = PHP_VERSION;
        $sys_info['mysql_ver'] = mysql_get_server_info($conn);
        $sys_info['zlib'] = function_exists('gzclose') ? L('yes') : L('no');
        $sys_info['safe_mode'] = (boolean) ini_get('safe_mode') ? L('yes') : L('no');
        $sys_info['safe_mode_gid'] = (boolean) ini_get('safe_mode_gid') ? L('yes') : L('no');
        $sys_info['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : L('no_timezone');
        $sys_info['socket'] = function_exists('fsockopen') ? L('yes') : L('no');
        
        if ($gd == 0) {
            $sys_info['gd'] = 'N/A';
        } else {
            if ($gd == 1) {
                $sys_info['gd'] = 'GD1';
            } else {
                $sys_info['gd'] = 'GD2';
            }
            
            $sys_info['gd'] .= ' (';
            
            /* 检查系统支持的图片类型 */
            if ($gd && (imagetypes() & IMG_JPG) > 0) {
                $sys_info['gd'] .= ' JPEG';
            }
            
            if ($gd && (imagetypes() & IMG_GIF) > 0) {
                $sys_info['gd'] .= ' GIF';
            }
            
            if ($gd && (imagetypes() & IMG_PNG) > 0) {
                $sys_info['gd'] .= ' PNG';
            }
            
            $sys_info['gd'] .= ')';
        }
        
        /* IP库版本 */
        $sys_info['ip_version'] = ecs_geoip('255.255.255.0');
        
        /* 允许上传的最大文件大小 */
        $sys_info['max_filesize'] = ini_get('upload_max_filesize');
        $this->assign('sys_info', $sys_info);
        
        $this->assign('ecs_version', VERSION);
        $this->assign('ecs_release', RELEASE);
        $this->assign('ecs_charset', strtoupper(EC_CHARSET));
        $this->assign('install_date', local_date(C('date_format'), C('install_date')));
        // 检测是否授权
        $data = array('appid' => ECTOUCH_AUTH_KEY);
        $empower = $this->cloud->data($data)->act('get.license');
        $this->assign('empower', $empower);
        $this->display('welcome');
    }
    
    // 关于程序
    public function aboutus()
    {
        $this->display();
    }
    
    // 查看网店
    public function demo()
    {
        // 生成二维码
        $mobile_url = __URL__; // 二维码内容
        $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H
        $matrixPointSize = 7; // 点的大小：1到10
        $mobile_qr = 'data/cache/demo_qrcode.png';
        QRcode::png($mobile_url, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        // 二维码路径赋值
        $this->assign('mobile_qr', $mobile_url . '/' . $mobile_qr);
        $this->assign('ur_here', L('preview'));
        $this->display();
    }
    
    // 登录页
    public function login()
    {
        if (IS_POST) {
            // POST数据
            $username = in($_POST['username']);
            $password = in($_POST['password']);
            $captcha = strtoupper(in($_POST['captcha']));
            $remember = in($_POST['remember']);
            $result = array(
                'err' => 1,
                'msg' => 'ERROR'
            );
            // 数据验证
            $msg = Check::rule(array(
                array(
                    Check::must($username),
                    L('login_faild')
                ),
                array(
                    Check::must($password),
                    L('login_faild')
                ),
                array(
                    Check::same($captcha, $_SESSION['ectouch_verify']),
                    L('captcha_error')
                )
            ));
            // 提示信息
            if ($msg !== true) {
                $result = array(
                    'err' => 1,
                    'msg' => $msg
                );
                exit(json_encode($result));
            }
            // 用户信息
            $userInfo = model('Admin')->getUserInfo($username, $password);
            if (! empty($userInfo)) {
                $this->setLogin($userInfo);
                // 保存登录状态
                if (! empty($remember)) {
                    $time = gmtime() + 3600 * 24 * 365;
                    setcookie('ECTOUCHCP[ADMIN_ID]', $userInfo['user_id'], $time);
                    setcookie('ECTOUCHCP[ADMIN_PWD]', md5(md5($userInfo['user_id'] . $userInfo['user_name']) . C('hash_code')), $time);
                }
                $result = array(
                    'err' => 0,
                    'msg' => 'login success'
                );
                exit(json_encode($result));
            } else {
                $result = array(
                    'err' => 1,
                    'msg' => L('login_faild')
                );
                exit(json_encode($result));
            }
        } else {
            // 已登录直接进入管理中心
            if ($this->isLogin()) {
                $this->redirect(url('index'));
            }
            $this->display('login');
        }
    }
    
    // 退出登录
    public function logout()
    {
        $this->clearLogin(url('login'));
    }
    
    // 找回密码
    public function forget()
    {
        if (IS_POST) {
            // POST数据
            $username = in($_POST['username']);
            $email = in($_POST['email']);
            $captcha = strtoupper(in($_POST['captcha']));
            $result = array(
                'err' => 1,
                'msg' => 'ERROR'
            );
            // 数据验证
            $msg = Check::rule(array(
                array(
                    Check::must($username),
                    L('forget_faild')
                ),
                array(
                    Check::must($email),
                    L('forget_faild')
                ),
                array(
                    Check::email($email),
                    L('email_format_faild')
                ),
                array(
                    Check::same($captcha, $_SESSION['ectouch_verify']),
                    L('captcha_error')
                )
            ));
            // 提示信息
            if ($msg !== true) {
                $result = array(
                    'err' => 1,
                    'msg' => $msg
                );
                exit(json_encode($result));
            }
            // 用户信息
            $userInfo = model('Admin')->getUserInfoNoPwd($username, $email);
            if (! empty($userInfo)) {
                /* 生成验证的code */
                $user_id = $userInfo['user_id'];
                $token_code = md5($user_id . $userInfo['password']);
                
                /* 设置重置邮件模板所需要的内容信息 */
                $template = model('Base')->get_mail_template('send_password');
                $reset_url = __HOST__ . url('reset', array(
                    'uid' => $user_id,
                    'token' => $token_code
                ));
                
                $this->assign('user_name', $username);
                $this->assign('reset_email', $reset_url);
                $this->assign('shop_name', C('shop_name'));
                $this->assign('send_date', local_date(C('date_format')));
                $this->assign('sent_date', local_date(C('date_format')));
                
                $content = $this->display('str:' . $template['template_content'], true, false);
                
                /* 发送确认重置密码的确认邮件 */
                if (! send_mail($username, $email, $template['template_subject'], $content, $template['is_html'])) {
                    $result = array(
                        'err' => 1,
                        'msg' => L('send_email_error')
                    );
                    exit(json_encode($result));
                }
                
                $result = array(
                    'err' => 1,
                    'msg' => 'send success'
                );
                exit(json_encode($result));
            } else {
                $result = array(
                    'err' => 1,
                    'msg' => L('forget_faild')
                );
                exit(json_encode($result));
            }
        } else {
            // 已登录直接进入管理中心
            if ($this->isLogin()) {
                $this->redirect(url('index'));
            }
            $this->display('forget');
        }
    }
    
    // 生成验证码
    public function verify()
    {
        Image::buildImageVerify();
    }
    
    // 更新缓存
    public function clearCache()
    {
        clear_all_files();
        $this->message(L('caches_cleared'));
    }
    
    // 修改密码
    public function modify()
    {
        $user_id = $_SESSION[APP_NAME . '_USERINFO']['user_id'];
        /* 不能编辑demo这个管理员 */
        if ($_SESSION[APP_NAME . '_USERINFO']['user_name'] == 'demo') {
            $this->message(L('edit_admininfo_cannot'), NULL, 'error');
        }
        if (IS_POST) {
            $data = I('post.data');
            $password = I('post.password');
            $old_password = I('post.old_password');
            /* 判断管理员是否已经存在 */
            if (! empty($data['user_name'])) {
                $condition = 'user_name="' . $data['user_name'] . '" and user_id <> ' . $user_id;
                $total = model('Admin')->getUserTotal($condition);
                if ($total > 0) {
                    $this->message(L('user_name_exist'), NULL, 'error');
                }
            }
            /* Email地址是否有重复 */
            if (! empty($data['email'])) {
                $condition = 'email="' . $data['email'] . '" and user_id <> ' . $user_id;
                $total = model('Admin')->getUserTotal($condition);
                if ($total > 0) {
                    $this->message(L('email_exist'), NULL, 'error');
                }
            }
            // 获取加密因子
            $ec_salt = $this->model->table('admin_user')
                ->field('ec_salt')
                ->where("user_id = '$user_id'")
                ->getOne();
            /* 检查密码是否正确 */
            if (empty($ec_salt)) {
                $old_password = md5($old_password);
            } else {
                $old_password = md5(md5($old_password) . $ec_salt);
            }
            /* 查询旧密码并与输入的旧密码比较是否相同 */
            $old_password2 = $this->model->table('admin_user')
                ->field('password')
                ->where("user_id = '$user_id'")
                ->getOne();
            if ($old_password2 != $old_password) {
                $this->message(L('pwd_error'), NULL, 'error');
            }
            /* 比较新密码和确认密码是否相同 */
            if ($password != I('post.pwd_confirm')) {
                $this->message(L('password_error'), NULL, 'error');
            }
            if (! empty($password)) {
                $data['ec_salt'] = rand(1, 9999);
                $data['password'] = md5(md5($password) . $data['ec_salt']);
                $message = L('edit_password_succeed');
            } else {
                $message = L('edit_profile_succeed');
            }
            $condition2['user_id'] = $user_id;
            $this->model->table('admin_user')
                ->data($data)
                ->where($condition2)
                ->update();
            $this->message($message, url('modify'));
            return;
        }
        $condition['user_id'] = $user_id;
        $userInfo = $this->model->table('admin_user')
            ->where($condition)
            ->find();
        ;
        $this->assign('ur_here', L('modif_info'));
        $this->assign('info', $userInfo);
        $this->display();
    }

    /**
     * 站点授权
     */
    public function license()
    {
        if (IS_POST) {
            $license = I('license');
            // 数据验证
            $msg = Check::rule(array(
                array(
                    Check::must($license),
                    '授权码不能为空'
                )
            ));
            // 提示信息
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $data = array('license'=>$license, 'appid' => ECTOUCH_AUTH_KEY);
            $result = $this->cloud->data($data)->act('post.dolicense');
            if ($result['error'] > 0) {
                $this->message($result['msg'], NULL, 'error');
            } else {
                $this->message('授权成功', NULL, 'success');
            }
        } else {
            $this->assign('ur_here', L('empower'));
            $this->display();
        }
    }
}
