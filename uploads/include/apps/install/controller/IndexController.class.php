<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 安装控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexController extends BaseController {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->lockFile = ROOT_PATH . 'data/install.lock';
        if (file_exists($this->lockFile)) {
            $this->redirect(__ROOT__ . '/');
        }
    }

    /**
     * 安装页面
     */
    public function index() {
        if (!function_exists("session_start")) {
            $error = '系统不支持session，无法进行安装！';
        }
        $this->assign('error', $error);
        $this->display('index');
    }

    /**
     * 安装处理
     */
    public function importing() {
        $data = in($_POST);
        $configDb = $data['DB'];
        $link = @mysql_connect($configDb['DB_HOST'] . ':' . $configDb['DB_PORT'], $configDb['DB_USER'], $configDb['DB_PWD']);
        if (!$link) {
            $this->msg('数据库连接失败，请检查连接信息是否正确！', false);
        }
        $mysqlInfo = mysql_get_server_info($link);
        if ($mysqlInfo < '5.1.0') {
            $this->msg('mysql版本低于5.1，无法继续安装！', false);
        }
        $status = @mysql_select_db($configDb['DB_NAME'], $link);
        if (!$status) {
            $this->msg('数据库' . $configDb['DB_NAME'] . '不存在，请检查数据库！', false);
        }
        if ($data['agree'] != 1) {
            $this->msg('请认真阅读并同意安装协议！', false);
        }

        $dbPrefix = $configDb['DB_PREFIX'];
        if (empty($dbPrefix)) {
            $dbPrefix = 'ecs_';
        }
        //更新安装sql文件
        if (!model('Install')->get_column($configDb, $dbPrefix . 'order_info', 'mobile_pay')) {
            $sql = "ALTER TABLE `".$dbPrefix."order_info` ADD COLUMN `mobile_order`  int(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `discount`,ADD COLUMN `mobile_pay`  int(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `mobile_order`;";
            $this->update_install_sql($sql);
        }
        $dbData = APP_PATH . APP_NAME . '/install.sql';
        $sqlData = Install::mysql($dbData, 'ecs_', $dbPrefix);
        if (!model('Install')->runSql($configDb, $sqlData)) {
            $this->msg('数据导入失败，请检查后手动删除数据库重新安装！', false);
        }
        model('Install')->filter_column($configDb, 'touch_shop_config'); //配置shop_config
        $this->set_config($configDb);
        $this->msg('安装成功！', true);
    }
	
    //更新安装sql文件
    private function update_install_sql($growing = ''){
        $fp = fopen(BASE_PATH . 'apps/install/install.sql', "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "\n\r".$growing);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
	
    /**
     * 安装成功
     */
    public function success() {

        $appid = $this->appid();
        $config_file = './data/version.php';
        require $config_file;
        
        $content = "<?php\n
		define('APPNAME', ".APPNAME.");
		define('VERSION', ".VERSION.");
		define('RELEASE', ".RELEASE.");
		define('ECTOUCH_AUTH_KEY', '".$appid."');";
        @file_put_contents($config_file, $content);
        
        $shop_config = model('Base')->load_config();
        
        $shop_country = model('RegionBase')->get_region_name($shop_config[shop_country]);
        $shop_province = model('RegionBase')->get_region_name($shop_config[shop_province]);
        $shop_city = model('RegionBase')->get_region_name($shop_config[shop_city]);
        $conn = mysql_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'));
        $data = array(
        	'domain'   =>  $_SERVER['HTTP_HOST'],
        	'appid'    =>  $appid,
        	'url'      =>  __URL__,
        	'shop_name'=>  $shop_config['shop_name'],
        	'shop_title'=> $shop_config['shop_title'],
        	'shop_desc'=>  $shop_config['shop_desc'],
        	'shop_keywords'=>($shop_config['shop_keywords']),
        	'country'  =>  $shop_country,
        	'province' =>  $shop_province,
        	'city'     =>  $shop_city,
        	'address'  =>  $shop_config['shop_address'],
        	'qq'       =>  $shop_config[qq],
        	'ww'       =>  $shop_config[ww],
        	'ym'       =>  $shop_config[ym],
        	'msn'      =>  $shop_config[msn],
        	'email'    =>  $shop_config[service_email],
        	'phone'    =>  $shop_config[service_phone],
        	'icp'      =>  $shop_config[icp_number],
        	'version'  =>  VERSION,
        	'language' =>  $shop_config[lang],
        	'php_ver'  =>  PHP_VERSION,
        	'mysql_ver'=>  mysql_get_server_info($conn),
        	'charset'  =>  EC_CHARSET,
        );
        $url = "http://ectouch.cn/api/install.html";
        http::doPost($url,$data);
        
        @fopen($this->lockFile, 'w');
        //生成二维码
        $mobile_url = __URL__; //二维码内容
        $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H 
        $matrixPointSize = 7; // 点的大小：1到10
        $mobile_qr = 'data/assets/' . APP_NAME . '/' . $errorCorrectionLevel . $matrixPointSize . '.png';
        Qrcode::png($mobile_url, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        //二维码路径赋值
        $this->assign('mobile_qr', $mobile_url . '/' . $mobile_qr);
        $this->display('success');
        if (C('run_after_del')) {
            del_dir(BASE_PATH . 'apps/' . APP_NAME);
            // if (is_dir('data/assets/' . APP_NAME)) {
            //del_dir('data/assets/' . APP_NAME);
            // }
        }
    }

    /**
     * ajax提示
     */
    protected function msg($info, $status = true) {
        @header('Content-type:text/html');
        if ($status) {
            exit(json_encode(array('status' => 'y', 'info' => $info)));
        } else {
            exit(json_encode(array('status' => 'n', 'info' => $info)));
        }
    }

    // 修改配置的函数
    protected function set_config($array, $config_file = './data/config.php') {
        if (empty($array) || !is_array($array)) {
            return false;
        }
        $config = file_get_contents($config_file); //
        $content = '<?' . "php\n";
        $content .= "return array(\n";
        foreach ($array as $name => $value) {
            $content .= "    '$name'   => '$value',\n";
        }
        $content .= "    'DB_CHARSET'   => 'utf8',\n";
        $content .= "    'DB_TYPE'   => 'mysql',\n";
        $content .= ");\n";
        $content .= '?>';
        // 写入配置
        if (@file_put_contents($config_file, $content)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 生成为一的appid
     */
    public function appid(){
        $data .= $_SERVER['REQUEST_TIME']; // 请求那一刻的时间戳
        $data .= $_SERVER['HTTP_USER_AGENT']; // 获取访问者在用什么操作系统
        $data .= $_SERVER['SERVER_ADDR']; // 服务器IP
        $data .= $_SERVER['SERVER_PORT']; // 端口号
        $data .= $_SERVER['REMOTE_ADDR']; // 远程IP
        $data .= $_SERVER['REMOTE_PORT']; // 端口信息
        return strtoupper(hash('ripemd128', time().md5($data)));
    }

}
