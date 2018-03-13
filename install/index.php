<?php
header("Content-type: text/html; charset=utf-8");
define('INSTALL_PATH', str_replace('\\', '/', dirname(__FILE__)) . '/');
define('ROOT_PATH', dirname(INSTALL_PATH) . '/');
define('APPNAME', 'ECTouch');
define('VERSION', '2.7.0');
define('RELEASE', '20180313');
//开启session
session_save_path(ROOT_PATH . 'data/session');
if (!session_id()) {
    session_start();
}
//配置信息
$config = include INSTALL_PATH . 'config.php';
if (empty($config)) {
    exit(get_tip_html('安装配置信息不存在，无法继续安装！'));
}
//本地安装环境验证，获取相应判断信息
require INSTALL_PATH . 'localhost.php';

//限制最大的执行时间
set_time_limit(1000);
//php版本
$phpversion = phpversion();
//php版本过低提示
if ($phpversion < '5.3.0') {
    exit(get_tip_html('您当前php版本('.$phpversion.')，不能安装本软件，请切换到5.3至5.6版本再安装，谢谢！'));
}
//数据库文件
foreach ($config['sqlFileName'] as $sqlFile) {
    if (!file_exists(INSTALL_PATH . 'sqldata/'.$sqlFile)) {
        exit(get_tip_html('数据库文件不存在，无法继续安装！'));
    }
}
//写入数据库完成后处理的文件
if (!file_exists(INSTALL_PATH . $config['handleFile'])) {
    exit(get_tip_html('处理文件不存在，无法继续安装！'));
}
//设置报错级别并返回当前级别。
error_reporting(E_ALL & ~E_NOTICE);
//安装步骤
$steps = array(
    '1' => '安装许可协议',
    '2' => '运行环境检测',
    '3' => '安装参数设置',
    '4' => '安装详细过程',
    '5' => '安装完成',
);
$step = isset($_GET['step']) ? $_GET['step'] : 1;
//当前安装步骤
$step_html = '';
foreach ($steps as $key => $value) {
    $current = $key == $step? 'current':'';
    $step_html .= '<li class="'.$current.'"><em>'.$key.'</em>'.$value.'</li>';
}
//安装页面
switch ($step) {
    //安装许可协议
    case '1':
        $license = file_get_contents(INSTALL_PATH . 'license.txt');
        include(INSTALL_PATH . "templates/1.php");
        break;
    //运行环境检测
    case '2':
        $server = array(
            //操作系统
            'os' => php_uname(),
            //PHP版本
            'php' => $phpversion,
        );
        $error = 0;
        //数据库
        if (function_exists('mysqli_connect')) {
            $server['mysql'] = '<span class="correct_span">&radic;</span> 已安装';
        } else {
            $server['mysql'] = '<span class="correct_span error_span">&radic;</span> 出现错误';
            $error++;
        }
        //上传限制
        if (ini_get('file_uploads')) {
            $server['uploadSize'] = '<span class="correct_span">&radic;</span> ' . ini_get('upload_max_filesize');
        } else {
            $server['uploadSize'] = '<span class="correct_span error_span">&radic;</span>禁止上传';
        }
        //session
        if (function_exists('session_start')) {
            $server['session'] = '<span class="correct_span">&radic;</span> 支持';
        } else {
            $server['session'] = '<span class="correct_span error_span">&radic;</span> 不支持';
            $error++;
        }
        //curl
        if (function_exists('curl_init')) {
            $server['curl'] = '<span class="correct_span">&radic;</span> 支持';
        } else {
            $server['curl'] = '<span class="correct_span error_span">&radic;</span> 不支持';
            $error++;
        }
        //需要读写权限的目录
        $folder = $config['dirAccess'];
        $site_path = ROOT_PATH;
        include(INSTALL_PATH . "templates/2.php");
        $_SESSION['INSTALLSTATUS'] = $error == 0?'SUCCESS':$error;
        break;
    //安装参数设置
    case '3':
        verify(3);
        //测试数据库链接
        if (isset($_GET['testdbpwd'])) {
            empty($_POST['dbhost'])?alert(0, '数据库服务器地址不能为空！', 'dbhost'):'';
            empty($_POST['dbuser'])?alert(0, '数据库用户名不能为空！', 'dbuser'):'';
            empty($_POST['dbname'])?alert(0, '数据库名不能为空！', 'dbname'):'';
            empty($_POST['dbport'])?alert(0, '数据库端口不能为空！', 'dbport'):'';
            $dbHost = $_POST['dbhost'] . ':' . $_POST['dbport'];
            $conn = mysqli_connect($_POST['dbhost'], $_POST['dbuser'], $_POST['dbpw']);
            $conn?alert(1, '数据库链接成功！', 'dbpw'):alert(0, '数据库链接失败！', 'dbpw');
        }
        //域名+路径
        $domain = empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
        if ((int) $_SERVER['SERVER_PORT'] != 80) {
            $domain .= ":" . $_SERVER['SERVER_PORT'];
        }
        $scriptName = !empty($_SERVER["REQUEST_URI"]) ? $scriptName = $_SERVER["REQUEST_URI"] : $scriptName = $_SERVER["PHP_SELF"];
        $rootpath = preg_replace("/\/(I|i)nstall\/index\.php(.*)$/", "", $scriptName);
        $domain = $domain . $rootpath;
        //是否独立安装
        $base_path = str_replace('\\', '/', dirname(dirname(getcwd()))).'/';
        $independent = file_exists($base_path . 'data/config.php') ? 0 : 1;
        // 不是独立安装 则自动读取PC端数据库连接信息
        if ($independent == 0) {
            require_once $base_path . 'data/config.php';
            $db = explode(':', $db_host);
            $config['dbHost'] = empty($db[0]) ? '127.0.0.1': $db[0];
            $config['dbPort'] = empty($db[1]) ? '3306' : $db[1];
            $config['dbName'] = $db_name;
            $config['dbUser'] = $db_user;
            $config['dbPass'] = $db_pass;
            $config['dbPrefix'] = empty($prefix) ? 'ecs_' : $prefix;
        } else {
            $config['dbHost'] = '127.0.0.1';
            $config['dbPort'] = '3306';
            $config['dbName'] = '';
            $config['dbUser'] = '';
            $config['dbPass'] = '';
            $config['dbPrefix'] = 'ecs_';
        }
        include(INSTALL_PATH . "templates/3.php");
        break;
    //安装详细过程
    case '4':
        verify(4);
        if (intval($_GET['install'])) {
            //是否安装基础数据
            $independent = trim($_POST['independent']);
            dataVerify($independent);
            //关闭特殊字符提交处理到数据库
            if ($phpversion <= '5.3.0') {
                set_magic_quotes_runtime(0);
            }
            //设置时区
            date_default_timezone_set('PRC');
            //当前进行的数据库操作
            $n = intval($_GET['n']);
            $arr = array();
            //数据库服务器地址
            $dbHost = trim($_POST['dbhost']);
            //数据库端口
            $dbPort = trim($_POST['dbport']);
            //数据库名
            $dbName = trim($_POST['dbname']);
            //数据库用户名
            $dbUser = trim($_POST['dbuser']);
            //数据库密码
            $dbPwd = trim($_POST['dbpw']);
            //表前缀
            $dbPrefix = empty($_POST['dbprefix']) ? 'db_' : trim($_POST['dbprefix']);
            //链接数据库
            $conn = mysqli_connect($dbHost, $dbUser, $dbPwd);
            if (!$conn) {
                alert(0, '连接数据库失败!');
            }
            //设置数据库编码
            mysqli_query($conn, "SET NAMES 'utf8'"); //,character_set_client=binary,sql_mode='';
            //获取数据库版本信息
            $version = mysqli_get_server_info($conn);
            if ($version < 5.0) {
                alert(0, '数据库版本太低!');
            }
            //选择数据库
            if (!mysqli_select_db($conn, $dbName)) {
                //创建数据时同时设置编码
                if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . $dbName . "` DEFAULT CHARACTER SET utf8;")) {
                    alert(0, '<li><span class="correct_span error_span">&radic;</span>数据库 ' . $dbName . ' 不存在，也没权限创建新的数据库！<span style="float: right;">'.date('Y-m-d H:i:s').'</span></li>');
                } else {
                    alert(1, "<li><span class='correct_span'>&radic;</span>成功创建数据库:{$dbName}<span style='float: right;''>".date('Y-m-d H:i:s')."</span></li>", 0);
                }
            }
            //读取数据文件
            foreach ($config['sqlFileName'] as $sqlFile) {
                $rest = array('structure.sql', 'data.sql');
                if (!$independent && in_array($sqlFile, $rest)) {
                    continue;
                }
                $sqldata .= file_get_contents(INSTALL_PATH . 'sqldata/'.$sqlFile);
            }
            //主题SQL文件
            $themesSql = INSTALL_PATH . 'sqldata/themes.sql';
            if (is_file($themesSql)) {
                $sqldata .= file_get_contents($themesSql);
            }
            if (empty($sqldata)) {
                alert(0, '数据库文件不能为空！');
            }
            if (!$independent) {
                //获得默认主题名称
                $result = mysqli_query($conn, 'select `value` from '.$dbPrefix.'shop_config where `code` = "template"');
                if ($result) {
                    $row = mysqli_fetch_assoc($result);
                    $newThemes = $row['value'];
                    $sqldata = str_replace('/default/', '/'.$newThemes.'/', $sqldata);
                    $oldThemes = ROOT_PATH . 'themes/default';
                    if (is_dir($oldThemes)) {
                        rename($oldThemes, ROOT_PATH . 'themes/'.$newThemes);
                    }
                }
            }
            $sqlFormat = sql_split($sqldata, $dbPrefix, $config['dbPrefix']);

            /**
             * 执行SQL语句
             */
            $counts = count($sqlFormat);
            for ($i = $n; $i < $counts; $i++) {
                $sql = trim($sqlFormat[$i]);
                if (strstr($sql, 'CREATE TABLE')) {
                    //创建表
                    preg_match('/CREATE TABLE `([^ ]*)`/', $sql, $matches);
                    if (empty($matches)) {
                        preg_match('/CREATE TABLE IF NOT EXISTS `([^ ]*)`/', $sql, $matches);
                    }
                    if (!empty($matches[1])) {
                        mysqli_query($conn, "DROP TABLE IF EXISTS `$matches[1]");
                        $ret = mysqli_query($conn, $sql);
                        $i++;
                        if (mysqli_query($conn, $sql)) {
                            $info = '<li><span class="correct_span">&radic;</span>创建数据表' . $matches[1] . '，完成！<span style="float: right;">'.date('Y-m-d H:i:s').'</span></li> ';
                            alert(1, $info, $i);
                        } else {
                            $info = '<li><span class="correct_span error_span">&radic;</span>创建数据表' . $matches[1] . '，失败，安装停止！<span style="float: right;">'.date('Y-m-d H:i:s').'</span></li>';
                            alert(0, $info, $i);
                        }
                    }
                } else {
                    //插入数据
                    if (!empty($sql)) {
                        $ret = mysqli_query($conn, $sql);
                    }
                }
            }

            //处理
            $data = include INSTALL_PATH . $config['handleFile'];
            $_SESSION['INSTALLOK'] = $data['status']?1:0;
            alert($data['status'], $data['info']);
        }
        include(INSTALL_PATH . "templates/4.php");
        break;
    //安装完成
    case '5':
        verify(5);
        include(INSTALL_PATH . "templates/5.php");
        //安装完成,生成.lock文件
        if (isset($_SESSION['INSTALLOK']) && $_SESSION['INSTALLOK'] == 1) {
            filewrite(INSTALL_PATH . $config['installFile']);
        }

        define('IN_ECTOUCH', true);
        $appid = get_appid();
        $appkey_file = ROOT_PATH . 'data/certificate/appkey.php';
        if (!file_exists($appkey_file)) {
            require ROOT_PATH . 'vendor/Http.class.php';
            require ROOT_PATH . 'vendor/Cloud.class.php';
            $contents = "<?php define('EC_APPID', '".$appid."');";
            @file_put_contents($appkey_file, $contents);
            // 推送API
            $cloud = Cloud::getInstance();
            $site_info = get_site_info($appid);
            $cloud->data($site_info)->act('post.install');
        }
        unset($_SESSION);
        break;
}

/**
 * 错误提示html
 */
function get_tip_html($info)
{
    return '<div style="border: 2px solid #69c; background:#f1f1f1; padding:20px;margin:20px;width:800px;font-weight:bold;color: #69c;text-align:center;margin-left: auto;margin-right: auto;border-radius: 5px;"><h1>'.$info.'</h1></div>';
}
//返回提示信息
function alert($status, $info, $type = 0)
{
    exit(json_encode(array('status'=>$status,'info'=>$info,'type'=>$type)));
}
function verify($step = 3)
{
    if ($step >= 3) {
        //未运行环境检测，跳转到安装许可协议页面
        if (!isset($_SESSION['INSTALLSTATUS'])) {
            header('location:./index.php');
            exit();
        }
        //运行环境检测存在错误，返回运行环境检测
        if ($_SESSION['INSTALLSTATUS'] != 'SUCCESS') {
            header('location:./index.php?step=2');
            exit();
        }
    }
    if ($step == 4) {
        //未提交数据
        if (empty($_POST)) {
            header('location:./index.php?step=3');
            exit();
        }
    }
    if ($step >= 5) {
        //数据库未写入完成
        if (!isset($_SESSION['INSTALLOK'])) {
            header('location:./index.php?step=4');
            exit();
        }
    }
}
function dataVerify($independent = 1)
{
    empty($_POST['dbhost'])?alert(0, '数据库服务器不能为空！'):'';
    empty($_POST['dbport'])?alert(0, '数据库端口不能为空！'):'';
    empty($_POST['dbuser'])?alert(0, '数据库用户名不能为空！'):'';
    empty($_POST['dbname'])?alert(0, '数据库名不能为空！'):'';
    empty($_POST['dbprefix'])?alert(0, '数据库表前缀不能为空！'):'';
    if ($independent) {
        empty($_POST['siteurl'])?alert(0, '网站域名不能为空！'):'';
        empty($_POST['manager'])?alert(0, '管理员帐号不能为空！'):'';
        empty($_POST['manager_pwd'])?alert(0, '管理员密码不能为空！'):'';
        empty($_POST['manager_email'])?alert(0, '管理员邮箱不能为空！'):'';
    }
}
/**
 * 判断目录是否可写
 */
function testwrite($d)
{
    $tfile = "_test.txt";
    $fp = fopen($d . "/" . $tfile, "w");
    if (!$fp) {
        return false;
    }
    fclose($fp);
    $rs = unlink($d . "/" . $tfile);
    if ($rs) {
        return true;
    }
    return false;
}
/**
 * 创建目录
 */
function dir_create($path, $mode = 0777)
{
    if (is_dir($path)) {
        return true;
    }
    mkdir($path, $mode, true);
    chmod($path, $mode);
}
/**
 * 数据库语句解析
 * @param $sql 数据库
 * @param $newTablePre 新的前缀
 * @param $oldTablePre 旧的前缀
 */
function sql_split($sql, $newTablePre, $oldTablePre)
{
    //前缀替换
    if ($newTablePre != $oldTablePre) {
        $sql = str_replace($oldTablePre, $newTablePre, $sql);
    }
    $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);

    $sql = str_replace("\r", "\n", $sql);
    $ret = array();
    $queriesarray = explode(";\n", trim($sql));
    unset($sql);
    foreach ($queriesarray as $k=>$query) {
        $ret[$k] = '';
        $queries = explode("\n", trim($query));
        $queries = array_filter($queries);
        foreach ($queries as $query) {
            $str1 = substr($query, 0, 1);
            if ($str1 != '#' && $str1 != '-') {
                $ret[$k] .= $query;
            }
        }
    }
    return $ret;
}
/**
 * 产生随机字符串
* 产生一个指定长度的随机字符串,并返回给用户
* @access public
* @param int $len 产生字符串的位数
* @return string
*/
function genRandomString($len = 6)
{
    $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9", '!', '@', '#', '$',
            '%', '^', '&', '*', '(', ')'
    );
    $charsLen = count($chars) - 1;
    shuffle($chars);	// 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}
/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @return mixed
 */
 function get_client_ip($type = 0)
 {
     $type	   =  $type ? 1 : 0;
     static $ip  =   null;
     if ($ip !== null) {
         return $ip[$type];
     }
     if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
         $arr	=   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
         $pos	=   array_search('unknown', $arr);
         if (false !== $pos) {
             unset($arr[$pos]);
         }
         $ip	 =   trim($arr[0]);
     } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
         $ip	 =   $_SERVER['HTTP_CLIENT_IP'];
     } elseif (isset($_SERVER['REMOTE_ADDR'])) {
         $ip	 =   $_SERVER['REMOTE_ADDR'];
     }
     // IP地址合法验证
     $long = sprintf("%u", ip2long($ip));
     $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
     return $ip[$type];
 }

/**
 * 生成为一的appid
 */
function get_appid()
{
    if (function_exists('com_create_guid')) {
        $guid = com_create_guid();
    } else {
        mt_srand((double)microtime()*10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $guid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid, 12, 4).$hyphen
                .substr($charid, 16, 4).$hyphen
                .substr($charid, 20, 12);
    }
    return strtoupper(hash('ripemd128', $guid));
}

/**
 * 站点数据
 * @param string $appid
 */
function get_site_info($appid = EC_APPID)
{
    $db_config = require ROOT_PATH . 'data/config.php';
    $conn = mysqli_connect($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PWD']);
    mysqli_query($conn, "SET NAMES 'utf8'");
    mysqli_select_db($conn, $db_config['DB_NAME']);
    $sql = 'SELECT `code`, `value` FROM ' . $db_config['DB_PREFIX'] . 'shop_config';
    $result = mysqli_query($conn, $sql);

    $config = array();
    while ($row = mysqli_fetch_array($result)) {
        $config[$row['code']] = $row['value'];
    }

    $shop_country = get_region_name($config['shop_country'], $conn, $db_config);
    $shop_province = get_region_name($config['shop_province'], $conn, $db_config);
    $shop_city = get_region_name($config['shop_city'], $conn, $db_config);

    $mysql_ver = (!$conn) ? '未知':mysqli_get_server_info($conn);
    $data = array(
        'appid'    => $appid,
        'domain'   =>  empty($_SERVER['HTTP_HOST']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'],
        'url'      =>  dirname('http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"]),
        'shop_name'=>  $config['shop_name'],
        'shop_title'=> $config['shop_title'],
        'shop_desc'=>  $config['shop_desc'],
        'shop_keywords'=> $config['shop_keywords'],
        'country'  =>  $shop_country,
        'province' =>  $shop_province,
        'city'     =>  $shop_city,
        'address'  =>  $config['shop_address'],
        'qq'       =>  $config['qq'],
        'ww'       =>  $config['ww'],
        'ym'       =>  $config['ym'],
        'msn'      =>  $config['msn'],
        'email'    =>  $config['service_email'],
        'phone'    =>  $config['service_phone'],
        'icp'      =>  $config['icp_number'],
        'version'  =>  VERSION.'('.RELEASE.')',
        'language' =>  $config['lang'],
        'php_ver'  =>  PHP_VERSION,
        'mysql_ver'=>  $mysql_ver,
        'charset'  =>  'utf-8'
    );
    return $data;
}

function get_region_name($region_id, $conn, $db_config)
{
    $sql = 'SELECT `region_name` FROM ' . $db_config['DB_PREFIX'] . 'region WHERE region_id = '. intval($region_id);
    $result = mysqli_query($conn, $sql);

    if ($result !== false) {
        $row = mysqli_fetch_row($result);
        return $row[0];
    } else {
        return '';
    }
}
