<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UpgradeControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：在线更新控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class UpgradeController extends AdminController
{

    private $md5_arr = array();
    private $_filearr = array('admin', 'api', 'include', 'plugins', '');
    // md5验证地址
    private $_upgrademd5 = 'http://www.ectouch.cn/upgrademd5/';
    // 补丁地址
    private $_patchurl = 'http://download.ectouch.cn/upgrade/1.0/patch/';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        $this->patch_charset = str_replace('-', '', EC_CHARSET);
        $this->upgrade_path_base = $this->_patchurl . $this->patch_charset . '/';
        defined('CACHE_PATH') or define('CACHE_PATH', ROOT_PATH.'data/cache/');
    }

    /**
     * 升级列表
     */
    public function index()
    {
        $pathlist = $this->pathlist();
        $this->assign('pathlist', $pathlist);
        $this->display();
    }

    /**
     * 在线升级
     */
    public function init()
    {
        // 开始升级
        $do = I('get.do');
        $cover = I('cover', 0);
        if (empty($do)) {
            $this->message(L('upgradeing'), url('init', array('do'=>1, 'cover'=> $cover)));
        }
        // 获取补丁列表
        $pathlist = $this->pathlist();
        if (empty($pathlist)) {
            $this->message(L('upgrade_success'), url('checkfile'));
        }
        // 创建缓存文件夹
        if (! file_exists(CACHE_PATH . 'upgrade')) {
            @mkdir(CACHE_PATH . 'upgrade');
        }

        foreach ($pathlist as $k => $v) {
            $release = str_replace('patch_R', '', basename($v, ".zip"));
            // 远程压缩包地址
            $upgradezip_url = $this->upgrade_path_base . $v;
            // 保存到本地地址
            $upgradezip_path = CACHE_PATH . 'upgrade' . DIRECTORY_SEPARATOR . $v;
            // 解压路径
            $upgradezip_source_path = CACHE_PATH . 'upgrade' . DIRECTORY_SEPARATOR . basename($v, ".zip");
            // 下载压缩包
            @file_put_contents($upgradezip_path, Http::doGet($upgradezip_url));
            // 解压缩
            $zip = new Zip();
            if ($zip->decompress($upgradezip_path, $upgradezip_source_path) == 0) {
                die("Error : unpack the failure.");
            }
            // 拷贝utf8/upload文件夹到根目录
            $copy_from = $upgradezip_source_path . DIRECTORY_SEPARATOR . $this->patch_charset . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR;
            $copy_to = ROOT_PATH;
            
            $this->copyfailnum = 0;
            $this->copydir($copy_from, $copy_to, $cover);
            // 检查文件操作权限，是否复制成功
            if ($this->copyfailnum > 0) {
                // 如果失败，终止操作
                $this->message(L('please_check_filepri'), url('index'));
            }

            // 执行sql
            // sql目录地址
            $sql_path = CACHE_PATH . 'upgrade' . DIRECTORY_SEPARATOR . basename($v, ".zip") . DIRECTORY_SEPARATOR . $this->patch_charset . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR;
            $file_list = glob($sql_path . '*');
            if (! empty($file_list)) {
                foreach ($file_list as $fk => $fv) {
                    $file_path = strtolower($fv);
                    if (in_array(substr($file_path, - 3, 3), array('php', 'sql')) && substr($file_path, - 10, 10) != 'config.php') {
                        if (substr($file_path, - 3, 3) == 'sql') {
                            // 更新sql文件
                            $sqlData = Install::mysql($file_path, 'ecs_', C('DB_PREFIX'));
                            // 执行sql文件
                            $model = new EcModel();
                            if (is_array($sqlData)){
                                foreach ($sqlData as $sql) {
                                    @$model->db->query($sql);
                                }
                            }
                        } elseif (strtolower(substr($file_list[$fk], - 3, 3)) == 'php' && file_exists($file_path)) {
                            include $file_path;
                        }
                    }
                }
            }
            
            // 读取版本号写入version.php文件
            // 配置文件地址
            $configpath = CACHE_PATH . 'upgrade' . DIRECTORY_SEPARATOR . basename($v, ".zip") . DIRECTORY_SEPARATOR . $this->patch_charset . DIRECTORY_SEPARATOR . 'upgrade' . DIRECTORY_SEPARATOR . 'version.php';
            if (file_exists($configpath)) {
                $config = include $configpath;
                // 版本文件地址
                $content = "<?php\ndefine('APPNAME', '".$config['APPNAME']."');\ndefine('VERSION', '".$config['VERSION']."');\ndefine('RELEASE', '".$release."');\ndefine('ECTOUCH_AUTH_KEY', '".ECTOUCH_AUTH_KEY."');";
                @file_put_contents(ROOT_PATH . 'data/version.php', $content);
            }
            
            // 删除文件
            @unlink($upgradezip_path);
            // 删除文件夹
            del_dir($upgradezip_source_path);
            
            // 提示语
            $tmp_k = $k + 1;
            if (! empty($pathlist[$tmp_k])) {
                $next_update = '<br />' . L('upgradeing') . basename($pathlist[$tmp_k], ".zip");
            } else {
                $next_update;
            }
            // 是否升级成功
            $this->message(basename($v, ".zip") . L('upgrade_success') . $next_update, url('init', array('do'=>1, 'cover'=> $cover)));
        }
        
    }
    
    // 检查文件md5值
    public function checkfile()
    {
        $do = I('get.do', 0);
        if (! empty($do)) {
            $this->ec_readdir('.');
            // 读取接口
            $ectouch_md5 = Http::doGet($this->_upgrademd5 . RELEASE . '_' . $this->patch_charset . ".php");
            $ectouch_md5_arr = json_decode($ectouch_md5, 1);
            $ectouch_md5_arr = empty($ectouch_md5_arr) ? array():$ectouch_md5_arr;
            // 计算数组差集
            $diff = array_diff($ectouch_md5_arr, $this->md5_arr);
            // 丢失文件列表
            $lostfile = array();
            foreach ($ectouch_md5_arr as $k => $v) {
                if (! in_array($k, array_keys($this->md5_arr))) {
                    $lostfile[] = $k;
                    unset($diff[$k]);
                }
            }
            // 未知文件列表
            $unknowfile = array_diff(array_keys($this->md5_arr), array_keys($ectouch_md5_arr));
            // 赋值
            $this->assign('diff', $diff);
            $this->assign('lostfile', $lostfile);
            $this->assign('unknowfile', $unknowfile);
            $this->display();
        } else {
            $this->message(L('begin_checkfile'), url('checkfile', array('do'=> 1)));
        }
    }
    
    /**
     * 生成核心文件md5
     */
    public function buildhash(){
        $this->ec_readdir('.');
        file_put_contents(CACHE_PATH . RELEASE . '_' . $this->patch_charset.'.php', json_encode($this->md5_arr));
        $this->message(L('build_success'), url('index'));
    }
    
    /**
     * 获取补丁列表
     */
    private function pathlist(){
        $pathlist_str = Http::doGet($this->upgrade_path_base);
        $pathlist = $allpathlist = array();
        $key = - 1;
        // 获取压缩包列表
        preg_match_all("/\"(patch_R[\w_]+\.zip)\"/", $pathlist_str, $allpathlist);
        $allpathlist = $allpathlist[1];
        // 获取可供当前版本升级的压缩包
        foreach ($allpathlist as $k => $v) {
            if (strstr($v, 'patch_R' . RELEASE)) {
                $key = $k;
                break;
            }
        }
        $key = $key < 0 ? 9999 : $key;
        foreach ($allpathlist as $k => $v) {
            if ($k > $key) {
                $pathlist[$k] = $v;
            }
        }
        return $pathlist;
    }

    private function ec_readdir($path = '')
    {
        $dir_arr = explode('/', dirname($path));
        if (is_dir($path)) {
            $handler = opendir($path);
            while (($filename = @readdir($handler)) !== false) {
                if (substr($filename, 0, 1) != ".") {
                    $this->ec_readdir($path . '/' . $filename);
                }
            }
            closedir($handler);
        } else {
            if (dirname($path) == '.' || (isset($dir_arr[1]) && in_array($dir_arr[1], $this->_filearr))) {
                $this->md5_arr[base64_encode($path)] = md5_file($path);
            }
        }
    }

    private function copydir($dirfrom, $dirto, $cover = '')
    {
        // 如果遇到同名文件无法复制，则直接退出
        if (is_file($dirto)) {
            die(L('have_no_pri') . $dirto);
        }
        // 如果目录不存在，则建立之
        if (! file_exists($dirto)) {
            mkdir($dirto);
        }
        
        $handle = opendir($dirfrom); // 打开当前目录
                                     
        // 循环读取文件
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') { // 排除"."和"."
                                                 // 生成源文件名
                $filefrom = $dirfrom . DIRECTORY_SEPARATOR . $file;
                // 生成目标文件名
                $fileto = $dirto . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filefrom)) { // 如果是子目录，则进行递归操作
                    $this->copydir($filefrom, $fileto, $cover);
                } else { // 如果是文件，则直接用copy函数复制
                    if (! empty($cover)) {
                        if (! copy($filefrom, $fileto)) {
                            $this->copyfailnum ++;
                            echo L('copy') . $filefrom . L('to') . $fileto . L('failed') . "<br />";
                        }
                    } else {
                        if ((get_extension($fileto) == 'dwt' || get_extension($fileto) == 'lbi') && file_exists($fileto)) {} else {
                            if (! copy($filefrom, $fileto)) {
                                $this->copyfailnum ++;
                                echo L('copy') . $filefrom . L('to') . $fileto . L('failed') . "<br />";
                            }
                        }
                    }
                }
            }
        }
    }
    
}