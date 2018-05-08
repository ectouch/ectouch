<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CommonControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：公共控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CommonController extends BaseController
{
    protected static $user = null;

    protected static $sess = null;

    protected static $view = null;

    protected $subscribe = 0;
    protected $custom = 0;
    protected $customs = 0;

    public function __construct()
    {
        parent::__construct();

        $this->ecshop_init();

        /* ecjia验证登录*/
        $this->ecjia_login();

        //开启的登录插件
        $this->ectouch_auth();

        $this->wechat_init();

        $this->assign('shop_url', dirname(__URL__));

        /* 语言包 */
        $this->assign('lang', L());
        /* 页面标题 */
        $page_info = get_page_title();
        self::$view->assign('page_title', $page_info['title']);
        self::$view->assign('meta_keywords', C('shop_keywords'));
        self::$view->assign('meta_description', C('shop_desc'));
        C('show_asynclist', 1);
        /* 模板赋值 */
        assign_template();
    }

    public static function user()
    {
        return self::$user;
    }

    public static function sess()
    {
        return self::$sess;
    }

    public static function view()
    {
        return self::$view;
    }

    protected function fetch($filename, $cache_id = '')
    {
        return self::$view->fetch($filename, $cache_id);
    }

    protected function assign($tpl_var, $value = '')
    {
        self::$view->assign($tpl_var, $value);
    }

    protected function display($tpl = '', $cache_id = '', $return = false)
    {
        self::$view->display($tpl, $cache_id);
    }

    protected function ecshop_init()
    {
        header('Cache-control: private');
        header('Content-type: text/html; charset=utf-8');

        $shop_closed = C('shop_closed');
        if (! empty($shop_closed)) {
            $close_comment = C('close_comment');
            $close_comment = empty($close_comment) ? 'closed.':$close_comment;
            exit('<h1 style="font-size: 5rem;text-align: center;margin-top: 40%;">'.$close_comment.'</h1>');
        }

        // 初始化session
        self::$sess = new EcsSession(self::$db, self::$ecs->table('sessions'), self::$ecs->table('sessions_data'), C('COOKIE_PREFIX').'touch_id');
        define('SESS_ID', self::$sess->get_session_id());

        // 创建 Smarty 对象
        self::$view = new EcsTemplate();
        self::$view->cache_lifetime = C('cache_time');
        self::$view->template_dir = ROOT_PATH . 'themes/' . C('template');
        self::$view->cache_dir = ROOT_PATH . 'data/caches/caches';
        self::$view->compile_dir = ROOT_PATH . 'data/caches/compiled';

        if ((DEBUG_MODE & 2) == 2) {
            self::$view->direct_output = true;
            self::$view->force_compile = true;
        } else {
            self::$view->direct_output = false;
            self::$view->force_compile = false;
        }
        self::$view->caching = true;

        // 会员信息
        self::$user = init_users();
        if (empty($_SESSION['user_id'])) {
            if (self::$user->get_cookie()) {
                // 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券
                if ($_SESSION['user_id'] > 0 && ! isset($_SESSION['user_money'])) {
                    model('Users')->update_user_info();
                }
            } else {
                $_SESSION['user_id'] = 0;
                $_SESSION['user_name'] = '';
                $_SESSION['email'] = '';
                $_SESSION['user_rank'] = 0;
                $_SESSION['discount'] = 1.00;
            }
        }

        // 判断是否支持gzip模式
        if (gzip_enabled()) {
            ob_start('ob_gzhandler');
        }

        // 设置推荐会员
        if (isset($_GET['u'])) {
            set_affiliate();
        }

        // session不存在，检查cookie
        if (! empty($_COOKIE['ECS']['user_id']) && ! empty($_COOKIE['ECS']['password'])) {
            // 找到cookie,验证信息
            $where['user_id'] = $_COOKIE['ECS']['user_id'];
            $where['password'] = $_COOKIE['ECS']['password'];
            $row = $this->model->table('users')
                ->field('user_id, user_name, password')
                ->where($where)
                ->find();
            if ($row) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['user_name'];
                model('Users')->update_user_info();
            } else {
                // 没有找到这个记录
                $time = time() - 3600;
                setcookie("ECS[user_id]", '', $time, '/');
                setcookie("ECS[password]", '', $time, '/');
            }
        }

        // search 关键词
        $search_keywords = C('search_keywords');
        if (!empty($search_keywords) && is_string($search_keywords)) {
            $keywords = explode(',', $search_keywords);
            $this->assign('hot_search_keywords', $keywords);
        }

        if (!empty($_COOKIE['ECS']['keywords'])) {
            $histroy = explode(',', $_COOKIE['ECS']['keywords']);
            foreach ($histroy as $key=>$val) {
                if ($key < 10) {
                    $histroy_list[$key] = $val;
                }
            }
            $this->assign('search_histroy', $histroy_list);
        }
        

        // 模板替换
        defined('__TPL__') or define('__TPL__', __ROOT__ . '/themes/' . C('template'));
        $stylename = C('stylename');
        if (! empty($stylename)) { 
            $this->assign('ecs_css_path', __ROOT__ . '/themes/' . C('template') . '/css/style_' . C('stylename') . '.css');
        } else {
            $this->assign('ecs_css_path', __ROOT__ . '/themes/' . C('template') . '/css/style.css');
        }

        // 设置parent_id
        session('parent_id', $_SESSION['user_id'] ? 0 : $_GET['u'] ? $_GET['u'] : 0);
    }

    /**
     * 初始化微信
     * @return
     */
    protected function wechat_init()
    {

        //兼容session丢失用户
        $_SESSION['unionid'] = $_SESSION['unionid'] ? $_SESSION['unionid'] : ($_COOKIE['unionid'] ? $_COOKIE['unionid'] : '');

        // 是否开启微信自动授权登录
        $res = get_auto_login();
        $this->assign('auto_login', $res);
  
        //用户是否已注册账号并绑定粉丝信息
        if($_SESSION['user_id'] && $_SESSION['unionid']){
          $this->assign('user_auto_login', 1);  
        }
        
        // 微信oauth处理
        $this->init_oauth();       

        if(class_exists('WechatController') && is_wechat_browser()){
            //是否显示关注按钮
            // $condition['openid'] = !empty($_SESSION['openid']) ? $_SESSION['openid'] : 0;
            $condition['ect_uid'] = !empty($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $userinfo = $this->model->table('wechat_user')->field('subscribe')->where($condition)->find();
            $_SESSION['subscribe'] = $userinfo['subscribe'];
        }
        // 关注按钮 是否显示
        $this->assign('subscribe', $_SESSION['subscribe']);

        // 判断微信通 用于JSSDK
        $is_wechat = class_exists('WechatController') && is_wechat_browser() ? 1 : 0;
        $this->assign('is_wechat', $is_wechat);
        if ($is_wechat == 1) {
            $share_data = $this->get_wechat_share_content();
            $this->assign('share_data', $share_data);
        }
    }

    /**
     * ecjia验证登录
     * &origin=app&openid=openid&token=token
     */
    private function ecjia_login()
    {
        if (isset($_GET['origin']) && $_GET['origin'] == 'app') {
            $openid = I('get.openid');
            $token = I('get.token');
            $sql= "select cu.access_token,u.user_name from " . $this->model->pre . "connect_user as cu LEFT JOIN "  . $this->model->pre . "users as u on cu.user_id = u.user_id where open_id = '$openid' ";
            $user = $this->model->getRow($sql);
            if ($token == $user['access_token']) {
                ECTouch::user()->set_cookie($user['user_name']);
                ECTouch::user()->set_session($user['user_name']);
                model('Users')->update_user_info();
            }
        }
    }

    

    /**
     * 微信JSSDK分享内容
     * Example: $share_data = array(
     *     'title' => '', //分享标题 默认商店名称
     *     'desc' => '', //分享描述 默认商店描述
     *     'link' => '', //分享链接 默认当前页面链接 含参数
     *     'img' => '', //分享图片 注意需要绝对路径 http://www.abc.com/mobile/public/img/wxsdk.png
     *     );
     * @param array $share_data 分享数据
     * @return
     */
    public function get_wechat_share_content($share_data= array())
    {
        if (!empty($share_data['img'])) {
            $share_img = (strtolower(substr($share_data['img'], 0, 4)) == 'http') ? $share_data['img'] : __HOST__ . $share_data['img'];
        } else {
            $share_img = elixir('images/wxsdk.png', true);
        }
        $data = array(
            'title' => !empty($share_data['title']) ? $share_data['title'] : C('shop_name'),
            'desc' => !empty($share_data['desc']) ? str_replace(array(" ", "　", "\t", "\n", "\r"), '', html_in($share_data['desc'])) : C('shop_desc'),
            'link' => !empty($share_data['link']) ? $share_data['link'] : $this->get_current_url(),
            'img' => $share_img,
        );
        return $data;
    }

    /**
     * 取当前页面地址
     * 如果用户登录 当前地址则需要加上此用户的uid,用于分享出去的地址（非显示在浏览器中的地址）
     * @return string
     */
    public function get_current_url()
    {
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
        $u = I('get.u', 0, 'intval');
        // 如果含u参数 并且不相同，取u参数 替换为登录用户u参数
        if (!empty($u) && !empty($_SESSION['user_id']) && $u != $_SESSION['user_id']) {
            $uri = url_set_value($uri, 'u', $_SESSION['user_id']);
        }

        return __HOST__ . $uri;
    }

    /**
     * 自动授权跳转
     */
    private function init_oauth()
    {
        if (is_wechat_browser() && (empty($_SESSION['unionid']) && empty($_SESSION['user_id']) && empty($_COOKIE['unionid'])) && strtolower(CONTROLLER_NAME) != 'oauth') {
            $sql = "SELECT `auth_config` FROM " . $this->model->pre . "touch_auth WHERE `from` = 'weixin' ";
            $auth_config = $this->model->getRow($sql);
            if ($auth_config) {
                $res = unserialize($auth_config['auth_config']);
                $config = array();
                foreach ($res as $key => $value) {
                    $config[$value['name']] = $value['value'];
                }
                $back_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : __HOST__ . $_SERVER['REQUEST_URI'];
                $url = url('oauth/index', array('type' => 'weixin', 'back_url' => $back_url));
                $this->redirect($url);
            }
        }
    }
    /**
    * 已安装的登录插件（除微信登录插件）
    **/
    public function ectouch_auth() {
        $sql = "SELECT `auth_config`, `from` FROM " . $this->model->pre . "touch_auth WHERE `from` != 'weixin' ";
        $auth_config = $this->model->query($sql);
        
        $res = array();
        if(!empty($auth_config)) {
            foreach ($auth_config as $key=>$value) {
                $res[$value['from']] = $value['from'];
            }
        }
       
        $this->assign('ectouch_auth', $res);
    }
}

class_alias('CommonController', 'ECTouch');
