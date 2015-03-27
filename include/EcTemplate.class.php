<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcTemplate.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：模板类
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcTemplate {

    public $config = array(); //配置
    protected $vars = array(); //存放变量信息
    protected $_replace = array();

    /**
     * 构造函数
     * @param unknown $config
     */
    public function __construct($config = array()) {
        $this->config = array_merge(C('TPL'), (array) $config); //参数配置	
        $this->assign('EcTemplate', $this);
        $this->_replace = array(
            'str' => array('search' => array(),
                'replace' => array()
            ),
            'reg' => array('search' => array("/__[A-Z]+__/", //替换常量
                    "/{(\\$[a-zA-Z_]\w*(?:\[[\w\.\"\'\[\]\$]+\])*)}/i", //替换变量
                    "/{include\s*file=\"(.*)\"}/i", //递归解析模板包含
                ),
                'replace' => array("<?php echo $0; ?>",
                    "<?php echo $1; ?>",
                    "<?php \$EcTemplate->display(\"$1\"); ?>",
                )
            )
        );
    }

    /**
     * 模板赋值
     * @param unknown $name
     * @param string $value
     */
    public function assign($name, $value = '') {
        if (is_array($name)) {
            foreach ($name as $k => $v) {
                $this->vars[$k] = $v;
            }
        } else {
            $this->vars[$name] = $value;
        }
    }

    /**
     * 执行模板解析输出
     * @param string $tpl
     * @param string $return
     * @param string $is_tpl
     * @throws Exception
     * @return string
     */
    public function display($tpl = '', $return = false, $is_tpl = true) {
        //如果没有设置模板，则调用当前模块的当前操作模板
        if ($is_tpl && ($tpl == "") && (!empty($_GET['_module'])) && (!empty($_GET['_action']))) {
            $tpl = $_GET['_module'] . "/" . $_GET['_action'];
        }
        if ($return) {
            if (ob_get_level()) {
                ob_end_flush();
                flush();
            }
            ob_start();
        }
        extract($this->vars, EXTR_OVERWRITE);
        if ($is_tpl && $this->config['TPL_CACHE_ON']) {
            define('ECTOUCH', true);
            $tplFile = $this->config['TPL_TEMPLATE_PATH'] . $tpl . $this->config['TPL_TEMPLATE_SUFFIX'];
            $cacheFile = $this->config['TPL_CACHE_PATH'] . md5($tplFile) . $this->config['TPL_CACHE_SUFFIX'];

            if (!file_exists($tplFile)) {
                throw new Exception($tplFile . "模板文件不存在");
            }
            //普通的文件缓存
            if (empty($this->config['TPL_CACHE_TYPE'])) {
                if (!is_dir($this->config['TPL_CACHE_PATH'])) {
                    @mkdir($this->config['TPL_CACHE_PATH'], 0777, true);
                }
                if ((!file_exists($cacheFile)) || (filemtime($tplFile) > filemtime($cacheFile))) {
                    file_put_contents($cacheFile, "<?php if (!defined('ECTOUCH')) exit;?>" . $this->compile($tpl)); //写入缓存
                }
                include( $cacheFile ); //加载编译后的模板缓存
            } else {
                //支持memcache等缓存
                $tpl_key = md5(realpath($tplFile));
                $tpl_time_key = $tpl_key . '_time';
                static $cache = NULL;
                $cache = is_object($cache) ? $cache : new EcCache($this->config, $this->config['TPL_CACHE_TYPE']);
                $compile_content = $cache->get($tpl_key);
                if (empty($compile_content) || (filemtime($tplFile) > $cache->get($tpl_time_key))) {
                    $compile_content = $this->compile($tpl);
                    $cache->set($tpl_key, $compile_content, 3600 * 24 * 365); //缓存编译内容
                    $cache->set($tpl_time_key, time(), 3600 * 24 * 365); //缓存编译内容
                }
                eval('?>' . $compile_content);
            }
        } else {
            eval('?>' . $this->compile($tpl, $is_tpl)); //直接执行编译后的模板
        }

        if ($return) {
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }

    /**
     * 自定义添加标签
     * @param unknown $tags
     * @param string $reg
     */
    public function addTags($tags = array(), $reg = false) {
        $flag = $reg ? 'reg' : 'str';
        foreach ($tags as $k => $v) {
            $this->_replace[$flag]['search'][] = $k;
            $this->_replace[$flag]['replace'][] = $v;
        }
    }

    /**
     * 模板编译核心
     * @param unknown $tpl
     * @param string $is_tpl
     * @throws Exception
     * @return mixed
     */
    protected function compile($tpl, $is_tpl = true) {
        if ($is_tpl) {
            $tplFile = $this->config['TPL_TEMPLATE_PATH'] . $tpl . $this->config['TPL_TEMPLATE_SUFFIX'];
            if (!file_exists($tplFile)) {
                throw new Exception($tplFile . "模板文件不存在");
            }
            $template = file_get_contents($tplFile);
        } else {
            extract($this->vars, EXTR_OVERWRITE);
            $template = $tpl;
        }

        //如果自定义模板标签解析函数tpl_parse_ext($template)存在，则执行
        if (function_exists('tpl_parse_ext')) {
            $template = tpl_parse_ext($template);
        }
        $template = str_replace($this->_replace['str']['search'], $this->_replace['str']['replace'], $template);
        $template = preg_replace($this->_replace['reg']['search'], $this->_replace['reg']['replace'], $template);
        return $template;
    }

}
