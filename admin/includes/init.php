<?php

if (!defined('IN_ECTOUCH')) {
    die('Hacking attempt');
}

define('ECS_ADMIN', true);

error_reporting(E_ALL);

if (__FILE__ == '') {
    die('Fatal error code: 0');
}

/* 初始化设置 */
@ini_set('memory_limit', '512M');
@ini_set('session.cache_expire', 180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies', 1);
@ini_set('session.auto_start', 0);
@ini_set('display_errors', 1);

if (DIRECTORY_SEPARATOR == '\\') {
    @ini_set('include_path', '.;' . ROOT_PATH);
} else {
    @ini_set('include_path', '.:' . ROOT_PATH);
}

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('\\', '/', dirname(getcwd())) . '/');

$db_config = require(ROOT_PATH . 'data/config.php');

defined('IS_ECSHOP') or define('IS_ECSHOP', RUN_ON_ECS);

if (defined('DEBUG_MODE') == false) {
    define('DEBUG_MODE', 0);
}

if (PHP_VERSION >= '5.1') {
    date_default_timezone_set(DEFAULT_TIMEZONE);
}

if (isset($_SERVER['PHP_SELF'])) {
    define('PHP_SELF', $_SERVER['PHP_SELF']);
} else {
    define('PHP_SELF', $_SERVER['SCRIPT_NAME']);
}

// 版本信息
defined('APPNAME') or define('APPNAME', 'ECTouch');
defined('VERSION') or define('VERSION', '2.7.0');
defined('RELEASE') or define('RELEASE', '20180313');
defined('CHARSET') or define('CHARSET', 'utf-8');
defined('APP_DEBUG') or define('APP_DEBUG', true);

defined('BASEPATH') or define('BASEPATH', ROOT_PATH . 'include/');
defined('BASE_PATH') or define('BASE_PATH', ROOT_PATH . 'include/');
defined('DATA_PATH') or define('DATA_PATH', ROOT_PATH . 'data/');
defined('STORAGE_PATH') or define('STORAGE_PATH', DATA_PATH . 'attached/');

defined('__ROOT__') or define('__ROOT__', '../');
defined('__PUBLIC__') or define('__PUBLIC__', '../data/assets');
defined('__TPL__') or define('__TPL__', '../data/assets/admin');

require(ROOT_PATH . 'vendor/autoload.php');
require(BASE_PATH . 'config/constant.php');
spl_autoload_register('autoload');
require(ROOT_PATH . 'include/helpers/time_helper.php');
require(ROOT_PATH . 'include/helpers/base_helper.php');
require(ROOT_PATH . 'include/helpers/common_helper.php');
require(ROOT_PATH . ADMIN_PATH . '/includes/lib_main.php');
require(ROOT_PATH . ADMIN_PATH . '/includes/cls_exchange.php');


/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc()) {
    if (!empty($_GET)) {
        $_GET  = addslashes_deep($_GET);
    }
    if (!empty($_POST)) {
        $_POST = addslashes_deep($_POST);
    }

    $_COOKIE   = addslashes_deep($_COOKIE);
    $_REQUEST  = addslashes_deep($_REQUEST);
}

/* 对路径进行安全处理 */
if (strpos(PHP_SELF, '.php/') !== false) {
    ecs_header("Location:" . substr(PHP_SELF, 0, strpos(PHP_SELF, '.php/') + 4) . "\n");
    exit();
}

/* 创建 ECSHOP 对象 */
$ecs = new ecshop($db_config['DB_NAME'], $db_config['DB_PREFIX']);
define('DATA_DIR', $ecs->data_dir());
define('IMAGE_DIR', $ecs->image_dir());
define('__URL__', rtrim($ecs->url(), '/'));

/* 初始化数据库类 */
// require(ROOT_PATH . 'includes/cls_mysql.php');
$db = new mysql($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PWD'], $db_config['DB_NAME']);

/* 创建错误处理对象 */
$err = new error('message.htm');

/* 初始化session */
// require(ROOT_PATH . 'includes/cls_session.php');
$sess = new session($db, $ecs->table('sessions'), $ecs->table('sessions_data'), 'ECSCP_ID');

/* 初始化 action */
if (!isset($_REQUEST['act'])) {
    $_REQUEST['act'] = '';
} elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') &&
    strpos(PHP_SELF, '/privilege.php') === false) {
    $_REQUEST['act'] = '';
} elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') &&
    strpos(PHP_SELF, '/get_password.php') === false) {
    $_REQUEST['act'] = '';
}

/* 载入系统参数 */
$_CFG = load_config();
C($_CFG);

// TODO : 登录部分准备拿出去做，到时候把以下操作一起挪过去
if ($_REQUEST['act'] == 'captcha') {
    // include(ROOT_PATH . 'includes/cls_captcha.php');

    $img = new captcha('../data/captcha/');
    @ob_end_clean(); //清除之前出现的多余输入
    $img->generate_image();

    exit;
}

require(ROOT_PATH . 'include/languages/' .$_CFG['lang']. '/admin/common.php');
require(ROOT_PATH . 'include/languages/' .$_CFG['lang']. '/admin/log_action.php');

if (file_exists(ROOT_PATH . 'include/languages/' . $_CFG['lang'] . '/admin/' . basename(PHP_SELF))) {
    include(ROOT_PATH . 'include/languages/' . $_CFG['lang'] . '/admin/' . basename(PHP_SELF));
}
L($_LANG);

if (!file_exists('../data/caches')) {
    @mkdir('../data/caches', 0777);
    @chmod('../data/caches', 0777);
}

if (!file_exists('../data/caches/compiled/admin')) {
    @mkdir('../data/caches/compiled/admin', 0777);
    @chmod('../data/caches/compiled/admin', 0777);
}

clearstatcache();

/* 如果有新版本，升级 */
if (!isset($_CFG['ecs_version'])) {
    $_CFG['ecs_version'] = 'v2.7.3';
}

if (preg_replace('/(?:\.|\s+)[a-z]*$/i', '', $_CFG['ecs_version']) != preg_replace('/(?:\.|\s+)[a-z]*$/i', '', VERSION)
        && file_exists('../upgrade/index.php')) {
    // 转到升级文件
    ecs_header("Location: ../upgrade/index.php\n");

    exit;
}

/* 创建 Smarty 对象。*/
// require(ROOT_PATH . 'includes/cls_template.php');
$smarty = new template;

$smarty->template_dir  = ROOT_PATH . ADMIN_PATH . '/templates';
$smarty->compile_dir   = ROOT_PATH . 'data/caches/compiled/admin';
if ((DEBUG_MODE & 2) == 2) {
    $smarty->force_compile = true;
}

$smarty->assign('is_ecshop', IS_ECSHOP ? 1:0);
$smarty->assign('lang', $_LANG);
$smarty->assign('help_open', $_CFG['help_open']);

if (isset($_CFG['enable_order_check'])) {  // 为了从旧版本顺利升级到2.5.0
    $smarty->assign('enable_order_check', $_CFG['enable_order_check']);
} else {
    $smarty->assign('enable_order_check', 0);
}

/* 验证通行证信息 */
if (isset($_GET['ent_id']) && isset($_GET['ent_ac']) &&  isset($_GET['ent_sign']) && isset($_GET['ent_email'])) {
    $ent_id = trim($_GET['ent_id']);
    $ent_ac = trim($_GET['ent_ac']);
    $ent_sign = trim($_GET['ent_sign']);
    $ent_email = trim($_GET['ent_email']);
    $certificate_id = trim($_CFG['certificate_id']);
    $domain_url = $ecs->url();
    $token=$_GET['token'];
    if ($token==md5(md5($_CFG['token']).$domain_url.ADMIN_PATH)) {
        // require(ROOT_PATH . 'includes/cls_transport.php');
        $t = new transport('-1', 5);
        $apiget = "act=ent_sign&ent_id= $ent_id & certificate_id=$certificate_id";

        $t->request('http://cloud.ectouch.cn/api.php', $apiget);
        $db->query('UPDATE '.$ecs->table('shop_config') . ' SET value = "'. $ent_id .'" WHERE code = "ent_id"');
        $db->query('UPDATE '.$ecs->table('shop_config') . ' SET value = "'. $ent_ac .'" WHERE code = "ent_ac"');
        $db->query('UPDATE '.$ecs->table('shop_config') . ' SET value = "'. $ent_sign .'" WHERE code = "ent_sign"');
        $db->query('UPDATE '.$ecs->table('shop_config') . ' SET value = "'. $ent_email .'" WHERE code = "ent_email"');
        clear_cache_files();
        ecs_header("Location: ./index.php\n");
    }
}

/* 验证管理员身份 */
if ((!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <= 0) &&
    $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
    $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order') {
    /* session 不存在，检查cookie */
    if (!empty($_COOKIE['ECSCP']['admin_id']) && !empty($_COOKIE['ECSCP']['admin_pass'])) {
        // 找到了cookie, 验证cookie信息
        $sql = 'SELECT user_id, user_name, password, action_list, last_login ' .
                ' FROM ' .$ecs->table('admin_user') .
                " WHERE user_id = '" . intval($_COOKIE['ECSCP']['admin_id']) . "'";
        $row = $db->GetRow($sql);

        if (!$row) {
            // 没有找到这个记录
            setcookie($_COOKIE['ECSCP']['admin_id'], '', 1);
            setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1);

            if (!empty($_REQUEST['is_ajax'])) {
                make_json_error($_LANG['priv_error']);
            } else {
                ecs_header("Location: privilege.php?act=login\n");
            }

            exit;
        } else {
            // 检查密码是否正确
            if (md5($row['password'] . $_CFG['hash_code']) == $_COOKIE['ECSCP']['admin_pass']) {
                !isset($row['last_time']) && $row['last_time'] = '';
                set_admin_session($row['user_id'], $row['user_name'], $row['action_list'], $row['last_time']);

                // 更新最后登录时间和IP
                $db->query('UPDATE ' . $ecs->table('admin_user') .
                            " SET last_login = '" . gmtime() . "', last_ip = '" . real_ip() . "'" .
                            " WHERE user_id = '" . $_SESSION['admin_id'] . "'");
            } else {
                setcookie($_COOKIE['ECSCP']['admin_id'], '', 1);
                setcookie($_COOKIE['ECSCP']['admin_pass'], '', 1);

                if (!empty($_REQUEST['is_ajax'])) {
                    make_json_error($_LANG['priv_error']);
                } else {
                    ecs_header("Location: privilege.php?act=login\n");
                }

                exit;
            }
        }
    } else {
        if (!empty($_REQUEST['is_ajax'])) {
            make_json_error($_LANG['priv_error']);
        } else {
            ecs_header("Location: privilege.php?act=login\n");
        }

        exit;
    }
}

$smarty->assign('token', $_CFG['token']);

if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
    $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order' && (isset($_GET['item']) && $_GET['item'] != 'goods_desc')) {
    $admin_path = preg_replace('/:\d+/', '', $ecs->url()) . ADMIN_PATH;
    if (!empty($_SERVER['HTTP_REFERER']) &&
        strpos(preg_replace('/:\d+/', '', $_SERVER['HTTP_REFERER']), $admin_path) === false) {
        if (!empty($_REQUEST['is_ajax'])) {
            make_json_error($_LANG['priv_error']);
        } else {
            ecs_header("Location: privilege.php?act=login\n");
        }

        exit;
    }
}

/* 管理员登录后可在任何页面使用 act=phpinfo 显示 phpinfo() 信息 */
if ($_REQUEST['act'] == 'phpinfo' && function_exists('phpinfo')) {
    phpinfo();

    exit;
}

//header('Cache-control: private');
header('content-type: text/html; charset=' . CHARSET);
header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

if ((DEBUG_MODE & 1) == 1) {
    error_reporting(E_ALL);
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}

/* 判断是否支持gzip模式 */
if (gzip_enabled()) {
    ob_start('ob_gzhandler');
} else {
    ob_start();
}

function getDbInstance()
{
    static $obj = array();
    if (empty($obj)) {
        $obj = new stdClass();
        $obj->ecs = $GLOBALS['ecs'];
        $obj->db = $GLOBALS['db'];
        $obj->err = $GLOBALS['err'];
        $obj->sess = $GLOBALS['sess'];
        $obj->tpl = new template;
    }
    return $obj;
}

function getInstance()
{
    return getDbInstance();
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else {
        return $output;
    }
}

/**
 * 生成URL
 * @param  string $route  URL路径
 * @param  array  $params URL参数
 * @return string
 */
function url($route=null, $params=array())
{
    $controller = defined('CONTROLLER_NAME') ? CONTROLLER_NAME : '';
    $action = defined('ACTION_NAME') ? ACTION_NAME : '';
    if ($route) {
        $route = explode('/', $route, 2);
        $routeNum = count($route);
        switch ($routeNum) {
            case 1:
                $action = $route[0];
                break;
            case 2:
                $controller = $route[0];
                $action = $route[1];
                break;
        }
    }
    $route = 'c='.$controller;
    $route .= ($action != 'index') ? '&a='.$action : '';
    $paramStr = empty($params) ? '' : '&' . http_build_query($params);
    $url = $_SERVER["SCRIPT_NAME"] . '?' . $route . $paramStr;
    return $url;
}

/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @return mixed
 */
function I($name, $default='')
{
    $obj = get_instance();
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) =   explode('.', $name, 2);
    } else { // 默认为自动判断
        $method =   'param';
    }

    switch (strtolower($method)) {
        case 'get':   $input =& $obj->input->get();break;
        case 'post':   $input =& $obj->input->post();break;
        case 'put':   parse_str(file_get_contents('php://input'), $input);break;
        case 'param':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input  =  $obj->input->post();
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                default:
                    $input  =  $obj->input->get();
            }
            break;
        default:
            return null;
    }
    if (empty($name)) { // 获取全部变量
        $data       =   $input;
    } elseif (isset($input[$name])) { // 取值操作
        $data       =   $input[$name];
    } else { // 变量默认值
        $data       =    isset($default)?$default:null;
    }
    return $data;
}

/**
 * 获取和设置语言定义(不区分大小写)
 * @param string|array $name 语言变量
 * @param string $value 语言值
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // 空参数返回所有定义
    if (empty($name)) {
        return $_lang;
    }
    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name

    if (is_string($name)) {
        if (!strpos($name, '.')) {
            //$name = strtoupper($name);
            if (is_null($value)) {
                return isset($_lang[$name]) ? $_lang[$name] : '';
            } elseif (is_array($value)) {
                // 支持变量
                $replace = array_keys($value);
                foreach ($replace as &$v) {
                    $v = '{$' . $v . '}';
                }
                return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : '');
            }
            $_lang[$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        //$name[0] = strtoupper($name[0]);
        if (is_null($value)) {
            return isset($_lang[$name[0]][$name[1]]) ? $_lang[$name[0]][$name[1]] : '';
        } elseif (is_array($value)) {
            // 支持变量
            $replace = array_keys($value);
            foreach ($replace as &$v) {
                $v = '{$' . $v . '}';
            }
            return str_replace($replace, $value, isset($_lang[$name[0]][$name[1]]) ? $_lang[$name[0]][$name[1]] : '');
        }
        $_lang[$name[0]][$name[1]] = $value;
        return;
    }

    // 批量定义
    if (is_array($name)) {
        $_lang = array_merge($_lang, $name);
    }
    return;
}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @return mixed
 */
function C($name=null, $value=null)
{
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        if (!empty($value) && $array = S('c_'.$value)) {
            $_config = array_merge($_config, array_change_key_case($array));
        }
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value)) {
                return isset($_config[$name]) ? $_config[$name] : null;
            }
            $_config[$name] = $value;
            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  strtolower($name[0]);
        if (is_null($value)) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : null;
        }
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // 批量设置
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name));
        if (!empty($value)) {// 保存配置值
            S('c_'.$value, $_config);
        }
        return;
    }
    return null; // 避免非法参数
}

function autoload($class)
{
    static $_map = array();
    // 检查是否存在映射
    if (!isset($_map[$class])) {
        //$class = ucfirst($class);
        $array = array(
            ROOT_PATH . 'include/classes/' . $class . '.php',
            ROOT_PATH . 'vendor/libraries/' . $class . '.php'
        );
        foreach ($array as $file) {
            if (is_file($file)) {
                $_map[$class] = $file;
            }
        }
    }
    include $_map[$class];
}
