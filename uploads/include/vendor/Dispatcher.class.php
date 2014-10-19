<?php

/**
 * Dispatcher类
 * 完成URL解析、路由和调度
 */
class Dispatcher {

    /**
     * URL映射到控制器
     * @access public
     * @return void
     */
    static public function dispatch() {
	
        $varPath        =   C('VAR_PATHINFO');
        $varModule      =   C('VAR_MODULE');
        $varController  =   C('VAR_CONTROLLER');
        $varAction      =   C('VAR_ACTION');
        $urlCase        =   C('URL_CASE_INSENSITIVE');
        if(isset($_GET[$varPath])) { // 判断URL里面是否有兼容模式参数
            $_SERVER['PATH_INFO'] = $_GET[$varPath];
            unset($_GET[$varPath]);
        }

        // 分析PATHINFO信息
        if(!isset($_SERVER['PATH_INFO'])) {
            $types   =  explode(',',C('URL_PATHINFO_FETCH'));
            foreach ($types as $type){
                if(0===strpos($type,':')) {// 支持函数判断
                    $_SERVER['PATH_INFO'] =   call_user_func(substr($type,1));
                    break;
                }elseif(!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type],$_SERVER['SCRIPT_NAME']))?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME']))   :  $_SERVER[$type];
                    break;
                }
            }
        }

        $depr = C('URL_PATHINFO_DEPR');
        define('MODULE_PATHINFO_DEPR',  $depr);

        if(empty($_SERVER['PATH_INFO'])) {
            $_SERVER['PATH_INFO'] = '';
            define('__INFO__','');
            define('__EXT__','');
        }else{
            define('__INFO__',trim($_SERVER['PATH_INFO'],'/'));
            // URL后缀
            define('__EXT__', strtolower(pathinfo($_SERVER['PATH_INFO'],PATHINFO_EXTENSION)));
            $_SERVER['PATH_INFO'] = __INFO__;
            if (__INFO__ && !defined('BIND_MODULE') && C('MULTI_MODULE')){ // 获取模块名
                $paths      =   explode($depr,__INFO__,2);
                $module     =   preg_replace('/\.' . __EXT__ . '$/i', '',$paths[0]);
				$_GET[$varModule]       =   $module;
				$_SERVER['PATH_INFO']   =   isset($paths[1])?$paths[1]:'';
            }                   
        }

        // URL常量
        define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'],'/'));
        define('__SELF__',strip_tags($_SERVER[C('URL_REQUEST_URI')]));

        // 获取模块名称
        define('APP_NAME', defined('BIND_MODULE')? strtolower(BIND_MODULE) : self::getModule($varModule));
        C('_APP_NAME', APP_NAME);
		
        // 检测模块是否存在
        if( APP_NAME && is_dir(APP_PATH.APP_NAME)){
            // 定义当前模块路径
            define('MODULE_PATH', APP_PATH.APP_NAME.'/');

            // 加载模块配置文件
            if(is_file(MODULE_PATH.'conf/config.php'))
                C(load_config(MODULE_PATH.'conf/config.php'));

			// 加载模块函数文件
            if(is_file(MODULE_PATH.'common/function.php'))
                include MODULE_PATH.'common/function.php';

			// 加载模块的扩展配置文件
            load_ext_file(MODULE_PATH);
        }else{
            E('模块不存在:'.APP_NAME);
        }

        if(!defined('__APP__')){
	        $urlMode = C('URL_MODEL');
	        if($urlMode == 3){// 兼容模式判断
	            define('PHP_FILE',_PHP_FILE_.'?'.$varPath.'=');
	        }elseif($urlMode == 2) {
	            $url = dirname(_PHP_FILE_);
	            if($url == '/' || $url == '\\')
	                $url    =   '';
	            define('PHP_FILE',$url);
	        }else {
	            define('PHP_FILE',_PHP_FILE_);
	        }
	        // 当前应用地址
	        define('__APP__', strip_tags(PHP_FILE));
	    }
        // 模块URL地址
        $moduleName    =   defined('MODULE_ALIAS')? MODULE_ALIAS : APP_NAME;
        define('__MODULE__',(defined('BIND_MODULE') || !C('MULTI_MODULE'))? __APP__ : __APP__.'/'.($urlCase ? strtolower($moduleName) : $moduleName));

        if('' != $_SERVER['PATH_INFO'] && (!C('URL_ROUTER_ON') ||  !Route::check()) ){   // 检测路由规则 如果没有则按默认规则调度URL
                       
            // 去除URL后缀
            $_SERVER['PATH_INFO'] = preg_replace(C('URL_HTML_SUFFIX')? '/\.('.trim(C('URL_HTML_SUFFIX'),'.').')$/i' : '/\.'.__EXT__.'$/i', '', $_SERVER['PATH_INFO']);

            $depr   =   C('URL_PATHINFO_DEPR');
            $paths  =   explode($depr,trim($_SERVER['PATH_INFO'],$depr));

            if(!defined('BIND_CONTROLLER')) {// 获取控制器
                if(C('CONTROLLER_LEVEL')>1){// 控制器层次
                    $_GET[$varController]   =   implode('/',array_slice($paths,0,C('CONTROLLER_LEVEL')));
                    $paths  =   array_slice($paths, C('CONTROLLER_LEVEL'));
                }else{
                    $_GET[$varController]   =   array_shift($paths);
                }
            }
            // 获取操作
            if(!defined('BIND_ACTION')){
                $_GET[$varAction]  =   array_shift($paths);
            }
            // 解析剩余的URL参数
            $var = array();
            if(C('URL_PARAMS_BIND') && 1 == C('URL_PARAMS_BIND_TYPE')){
                $var = $paths; // URL参数按顺序绑定变量
            }else{
                preg_replace('@(\w+)\/([^\/]+)@e', '$var[\'\\1\']=strip_tags(\'\\2\');', implode('/',$paths));
            }
            $_GET = array_merge($var,$_GET);
        }
        // 获取控制器和操作名
        define('CONTROLLER_NAME',   defined('BIND_CONTROLLER')? BIND_CONTROLLER : self::getController($varController,$urlCase));
        define('ACTION_NAME',       defined('BIND_ACTION')? BIND_ACTION : self::getAction($varAction,$urlCase));

        // 当前控制器的UR地址
        $controllerName    =   defined('CONTROLLER_ALIAS')? CONTROLLER_ALIAS : CONTROLLER_NAME;
        define('__CONTROLLER__',__MODULE__.$depr.(defined('BIND_CONTROLLER')? '': ( $urlCase ? parse_name($controllerName) : $controllerName )) );

        // 当前操作的URL地址
        define('__ACTION__',__CONTROLLER__.$depr.(defined('ACTION_ALIAS')?ACTION_ALIAS:ACTION_NAME));

        //保证$_REQUEST正常取值
        $_REQUEST = array_merge($_POST,$_GET);
    }

    /**
     * 获得实际的控制器名称
     */
    static private function getController($var,$urlCase) {
        $controller = (!empty($_GET[$var])? $_GET[$var]:DEFAULT_CONTROLLER);
        unset($_GET[$var]);
        if($maps = C('URL_CONTROLLER_MAP')) {
            if(isset($maps[strtolower($controller)])) {
                // 记录当前别名
                define('CONTROLLER_ALIAS',strtolower($controller));
                // 获取实际的控制器名
                return ucfirst($maps[CONTROLLER_ALIAS]);
            }elseif(array_search(strtolower($controller),$maps)){
                // 禁止访问原始控制器
                return   '';
            }
        }
        if($urlCase) {
            // URL地址不区分大小写
            // 智能识别方式 user_type 识别到 UserTypeController 控制器
            $controller = parse_name($controller,1);
        }
        return strip_tags(ucfirst($controller));
    }

    /**
     * 获得实际的操作名称
     */
    static private function getAction($var,$urlCase) {
        $action = !empty($_POST[$var]) ? $_POST[$var] : (!empty($_GET[$var])?$_GET[$var]:DEFAULT_ACTION);
        unset($_POST[$var],$_GET[$var]);
        if($maps = C('URL_ACTION_MAP')) {
            if(isset($maps[strtolower(CONTROLLER_NAME)])) {
                $maps =   $maps[strtolower(CONTROLLER_NAME)];
                if(isset($maps[strtolower($action)])) {
                    // 记录当前别名
                    define('ACTION_ALIAS',strtolower($action));
                    // 获取实际的操作名
                    if(is_array($maps[ACTION_ALIAS])){
                        parse_str($maps[ACTION_ALIAS][1],$vars);
                        $_GET   =   array_merge($_GET,$vars);
                        return $maps[ACTION_ALIAS][0];
                    }else{
                        return $maps[ACTION_ALIAS];
                    }
                    
                }elseif(array_search(strtolower($action),$maps)){
                    // 禁止访问原始操作
                    return   '';
                }
            }
        }
        return strip_tags( $urlCase ? strtolower($action) : $action );
    }

    /**
     * 获得实际的模块名称
     */
    static private function getModule($var) {
        $module = (!empty($_GET[$var])?$_GET[$var]:DEFAULT_APP);
        unset($_GET[$var]);
        if($maps = C('URL_MODULE_MAP')) {
            if(isset($maps[strtolower($module)])) {
                // 记录当前别名
                define('MODULE_ALIAS',strtolower($module));
                // 获取实际的模块名
                return ucfirst($maps[MODULE_ALIAS]);
            }elseif(array_search(strtolower($module),$maps)){
                // 禁止访问原始模块
                return   '';
            }
        }
        return strip_tags(strtolower($module));
    }

}
