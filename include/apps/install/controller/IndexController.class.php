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

class IndexController extends Controller {

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
        if (! function_exists("session_start")) {
            $error = '系统不支持session，无法进行安装！<br>';
        }
        $dir_list = array(
            'data/'
        );
        foreach($dir_list as $dir)
        {
            $err = 0;
            $test_dir = ROOT_PATH.$dir;
            if(! is_writable($test_dir)){
                $w = '[×]写';
                $err++;
            }
            if(! is_readable($test_dir)){
                $r = '[×]读';
                $err++;
            }
            $error .= $err ? $dir .'目录 '. $w .' '. $r .'<br>' : '';
        }
        //自动读取pc端配置
        if (file_exists('../data/config.php')) {
            require '../data/config.php';
            $data['db_host'] = $db_host;
            $data['db_name'] = $db_name;
            $data['db_user'] = $db_user;
            $data['db_pass'] = $db_pass;
            $data['db_pre'] = $prefix;
            $this->assign('data', $data);
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
        if(strpos($configDb['DB_HOST'], ':') !== false){
            $db_host = explode(':', $configDb['DB_HOST']);
            $configDb['DB_HOST'] = $db_host[0];
            $configDb['DB_PORT'] = $db_host[1];
        }else{
            $configDb['DB_PORT'] = '3306';
        }
        $link = @mysql_connect($configDb['DB_HOST'].':'.$configDb['DB_PORT'], $configDb['DB_USER'], $configDb['DB_PWD']);
        if (!$link) {
            $this->msg('数据库连接失败，请检查连接信息是否正确！', false);
        }
        $mysqlInfo = @mysql_get_server_info($link);
        if ($mysqlInfo < '5.0') {
            $this->msg('MySql版本低于5.0，无法继续安装！', false);
        }
        $status = @mysql_select_db($configDb['DB_NAME'], $link);
        if (!$status) {
            $this->msg('数据库' . $configDb['DB_NAME'] . '不存在，请检查数据库！', false);
        }
        if ($data['agree'] != 1) {
            $this->msg('请认真阅读并同意安装协议！', false);
        }
        //设置表前缀
        $dbPrefix = $configDb['DB_PREFIX'];
        if (empty($dbPrefix)) {
            $dbPrefix = 'ecs_';
        }
        $dbData = ROOT_PATH . 'data/install.sql';
        $sqlData = Install::mysql($dbData, 'ecs_', $dbPrefix);
        //更新安装sql文件
        if (!model('Install')->get_column($configDb, $dbPrefix . 'order_info', 'mobile_pay')) {
            $sqlData[] = "ALTER TABLE `".$dbPrefix."order_info` ADD COLUMN `mobile_order` int(1) UNSIGNED NOT NULL DEFAULT 0,ADD COLUMN `mobile_pay` int(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `discount`;";
        }
        $sqlData[] = "UPDATE `".$dbPrefix."touch_shop_config` SET `value` = '".str_replace('/mobile', '', __URL__)."' where `code`='shop_url';";
        if (!model('Install')->runSql($configDb, $sqlData)) {
            $this->msg('数据导入失败，请检查后手动删除数据库重新安装！', false);
        }
        model('Install')->filter_column($configDb, 'touch_shop_config'); //配置shop_config
        $this->set_config($configDb);
        $this->msg('安装成功！', true);
    }
	
    /**
     * 安装成功
     */
    public function success() {
        $appid = $this->appid();
        $config_file = './data/version.php';
        require $config_file;
        $content = "<?php\ndefine('APPNAME', '".APPNAME."');\ndefine('VERSION', '".VERSION."');\ndefine('RELEASE', '".RELEASE."');\ndefine('ECTOUCH_AUTH_KEY', '".$appid."');";
        @file_put_contents($config_file, $content);
        @fopen($this->lockFile, 'w');
        if (ECTOUCH_AUTH_KEY == '') {
            $site_info = site_info($appid);
            $this->cloud->data($site_info)->act('post.install');
        }
        //生成二维码
        $mobile_url = __URL__; //二维码内容
        $errorCorrectionLevel = 'L'; // 纠错级别：L、M、Q、H 
        $matrixPointSize = 7; // 点的大小：1到10
        $mobile_qr = 'data/cache/demo_qrcode.png';
        QRcode::png($mobile_url, ROOT_PATH . $mobile_qr, $errorCorrectionLevel, $matrixPointSize, 2);
        //二维码路径赋值
        $this->assign('mobile_qr', $mobile_url . '/' . $mobile_qr);
        $this->display('success');
        if (C('run_after_del')) {
            del_dir(BASE_PATH . 'apps/' . APP_NAME);
            if (is_dir('data/assets/' . APP_NAME)) {
                del_dir('data/assets/' . APP_NAME);
            }
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
    private function appid(){
        if (function_exists('com_create_guid')){
            $guid = com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $guid = substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12);
        }
        return strtoupper(hash('ripemd128', $guid));
    }

}
