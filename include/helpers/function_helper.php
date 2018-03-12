<?php


/**
 * ECTouch 基础函数库
 */
function getDbInstance()
{
    static $obj = array();
    if (empty($obj)) {
        $db_config = require_cache(DATA_PATH . 'config.php', true);
        $obj = new stdClass();
        $obj->ecs = new ecshop($db_config['DB_NAME'], $db_config['DB_PREFIX']);
        $obj->db = new mysql($db_config['DB_HOST'], $db_config['DB_USER'], $db_config['DB_PWD'], $db_config['DB_NAME']);
        $obj->db->set_disable_cache_tables(array($obj->ecs->table('sessions'), $obj->ecs->table('sessions_data'), $obj->ecs->table('cart')));
        $obj->err = new error('message.dwt');
        $obj->model = new Model();
    }
    return $obj;
}

function getInstance()
{
    static $obj = array();
    if (empty($obj)) {
        $obj = getDbInstance();
        //$touch->cfg = null;
        /*$obj->sess = new session($obj->db, $obj->ecs->table('sessions'), $obj->ecs->table('sessions_data'));*/
        $obj->tpl = new template();
        $obj->tpl->cache_lifetime = C('cache_time');
        $obj->tpl->template_dir   = ROOT_PATH . 'themes/' . C('template');
        $obj->tpl->cache_dir      = STORAGE_PATH . 'caches';
        $obj->tpl->compile_dir    = STORAGE_PATH . 'compiled';

        if (APP_DEBUG) {
            $obj->tpl->direct_output = true;
            $obj->tpl->force_compile = true;
        } else {
            $obj->tpl->direct_output = false;
            $obj->tpl->force_compile = false;
        }

        //$touch->lang = null;
        $obj->user =& init_users();
    }
    return $obj;
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
    $controller = CONTROLLER_NAME;
    $action = ACTION_NAME;
    if ($route) {
        $route = explode('/', strtolower($route), 2);
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
    $route = ($controller == 'index') ? 'c=welcome' : 'c='.$controller;
    $route .= ($action != 'index') ? '&a='.$action : '';
    $paramStr = empty($params) ? '' : '&' . http_build_query($params, '', '&');
    $url = $_SERVER["SCRIPT_NAME"] . '?' . $route . $paramStr;
    return $url;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time=0, $msg='')
{
    //多行URL地址支持
    $url        = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg)) {
        $msg    = "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0) {
            $str .= $msg;
        }
        exit($str);
    }
}

/**
 * 重置admin，api模块图片地址
 * @param string $path
 * @return mixed
 */
function get_image_url($path = '')
{
    if (!$path) {
        return false;
    }
    if (preg_match("/http:\/\/|https:\/\//", $path)) {
        return $path;
    }
    $path = base_url($path);
    return str_replace(array('admin/', 'api/'), '', $path);
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
        case 'get':   $input = $obj->input->get();break;
        case 'post':   $input = $obj->input->post();break;
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
        $data       =	$input[$name];
    } else { // 变量默认值
        $data       =	 isset($default)?$default:null;
    }
    return $data;
}

/**
 * 获取输入参数 支持过滤和默认值(API使用)
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
function I_A($name, $default='', $filter = 'htmlspecialchars')
{
    $obj = get_instance();
    if (strpos($name, '.')) { // 指定参数来源
        list($method, $name) =   explode('.', $name, 2);
    } else { // 默认为自动判断
        $method =   'param';
    }

    switch (strtolower($method)) {
        case 'get':   $input =& $obj->input->get();break;
        case 'post':   $input = json_decode(file_get_contents('php://input'), true);break;
        case 'put':   parse_str(file_get_contents('php://input'), $input);break;
        case 'param':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = json_decode(file_get_contents('php://input'), true);
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
        array_walk_recursive($data, 'filter_exp');
        $filters = isset($filter) ? $filter : 'htmlspecialchars';
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) { // 取值操作
        $data       =	$input[$name];
    } else { // 变量默认值
        $data       =	 isset($default)?$default:null;
    }

    return $data;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type=0)
{
    if ($type) {
        return ucfirst(preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 优化的require_once
 * @param string $filename 文件地址
 * @return boolean
 */
function require_cache($filename, $return = false)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists($filename)) {
            if ($return) {
                $_importFiles[$filename] = require $filename;
            } else {
                require $filename;
                $_importFiles[$filename] = true;
            }
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * 批量导入文件 成功则返回
 * @param array $array 文件数组
 * @param boolean $return 加载成功后是否返回
 * @return boolean
 */
function require_array($array, $return=false)
{
    foreach ($array as $file) {
        if (require_cache($file) && $return) {
            return true;
        }
    }
    if ($return) {
        return false;
    }
}

/**
 * M函数用于实例化一个没有模型文件的Model
 * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
 * @return Model
 */
function M($name='')
{
    $touch = get_Instance();
    $name = strtolower($name);
    $touch->load->model(ucfirst($name) . '_model', $name);
    return $touch->$name;
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

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr   = '';
    //分析php源码
    $tokens     = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr  .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr  .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i+1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } elseif ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr  .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

//[RUNTIME]
// 编译文件
function compile($filename)
{
    $content        = file_get_contents($filename);
    // 替换预编译指令
    $content        = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    $content        = substr(trim($content), 5);
    if ('?>' == substr($content, -2)) {
        $content    = substr($content, 0, -2);
    }
    return $content;
}

// 根据数组生成常量定义
function array_define($array, $check=true)
{
    $content = "\n";
    foreach ($array as $key => $val) {
        $key = strtoupper($key);
        if ($check) {
            $content .= 'defined(\'' . $key . '\') or ';
        }
        if (is_int($val) || is_float($val)) {
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_bool($val)) {
            $val = ($val) ? 'true' : 'false';
            $content .= "define('" . $key . "'," . $val . ');';
        } elseif (is_string($val)) {
            $content .= "define('" . $key . "','" . addslashes($val) . "');";
        }
        $content    .= "\n";
    }
    return $content;
}
//[/RUNTIME]

/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @return string
 */
function friendlyDate($sTime, $type = 'normal')
{
    if (!$sTime) {
        return '';
    }
    //sTime=源时间，cTime=当前时间，dTime=时间差
    $cTime      =   time();
    $dTime      =   $cTime - $sTime;
    $dDay       =   intval(date("z", $cTime)) - intval(date("z", $sTime));
    //$dDay     =   intval($dTime/3600/24);
    $dYear      =   intval(date("Y", $cTime)) - intval(date("Y", $sTime));
    //normal：n秒前，n分钟前，n小时前，日期
    if ($type=='normal') {
        if ($dTime < 60) {
            if ($dTime < 10) {
                return '刚刚';
            } else {
                return intval(floor($dTime / 10) * 10)."秒前";
            }
        } elseif ($dTime < 3600) {
            return intval($dTime/60)."分钟前";
        //今天的数据.年份相同.日期相同.
        } elseif ($dYear==0 && $dDay == 0) {
            //return intval($dTime/3600)."小时前";
            return '今天'.date('H:i', $sTime);
        } elseif ($dYear==0) {
            return date("m月d日 H:i", $sTime);
        } else {
            return date("Y-m-d H:i", $sTime);
        }
    } elseif ($type=='mohu') {
        if ($dTime < 60) {
            return $dTime."秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime/60)."分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime/3600)."小时前";
        } elseif ($dDay > 0 && $dDay<=7) {
            return intval($dDay)."天前";
        } elseif ($dDay > 7 &&  $dDay <= 30) {
            return intval($dDay/7) . '周前';
        } elseif ($dDay > 30) {
            return intval($dDay/30) . '个月前';
        }
        //full: Y-m-d , H:i:s
    } elseif ($type=='full') {
        return date("Y-m-d , H:i:s", $sTime);
    } elseif ($type=='ymd') {
        return date("Y-m-d", $sTime);
    } else {
        if ($dTime < 60) {
            return $dTime."秒前";
        } elseif ($dTime < 3600) {
            return intval($dTime/60)."分钟前";
        } elseif ($dTime >= 3600 && $dDay == 0) {
            return intval($dTime/3600)."小时前";
        } elseif ($dYear==0) {
            return date("Y-m-d H:i:s", $sTime);
        } else {
            return date("Y-m-d H:i:s", $sTime);
        }
    }
}
