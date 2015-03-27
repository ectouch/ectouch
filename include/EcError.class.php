<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：EcError.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：错误类
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcError extends Exception {

    private $errorMessage = '';
    private $errorFile = '';
    private $errorLine = 0;
    private $errorCode = '';
    private $errorLevel = '';
    private $trace = '';

    /**
     * 构造函数
     * @param unknown $errorMessage
     * @param number $errorCode
     * @param string $errorFile
     * @param number $errorLine
     */
    public function __construct($errorMessage, $errorCode = 0, $errorFile = '', $errorLine = 0) {
        parent::__construct($errorMessage, $errorCode);
        $this->errorMessage = $errorMessage;
        $this->errorCode = $errorCode == 0 ? $this->getCode() : $errorCode;
        $this->errorFile = $errorFile == '' ? $this->getFile() : $errorFile;
        $this->errorLine = $errorLine == 0 ? $this->getLine() : $errorLine;
        $this->errorLevel = $this->getLevel();
        $this->trace = $this->trace();
        $this->showError();
    }

    /**
     * 获取trace信息
     * @return string
     */
    protected function trace() {
        $trace = $this->getTrace();

        $traceInfo = '';
        $time = date("Y-m-d H:i:s");
        foreach ($trace as $t) {
            $traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
            $traceInfo .= $t['class'] . $t['type'] . $t['function'] . '(';
            $traceInfo .= ")<br />\r\n";
        }
        return $traceInfo;
    }

    /**
     * 错误等级
     * @return string
     */
    protected function getLevel() {
        $Level_array = array(1 => '致命错误(E_ERROR)',
            2 => '警告(E_WARNING)',
            4 => '语法解析错误(E_PARSE)',
            8 => '提示(E_NOTICE)',
            16 => 'E_CORE_ERROR',
            32 => 'E_CORE_WARNING',
            64 => '编译错误(E_COMPILE_ERROR)',
            128 => '编译警告(E_COMPILE_WARNING)',
            256 => '致命错误(E_USER_ERROR)',
            512 => '警告(E_USER_WARNING)',
            1024 => '提示(E_USER_NOTICE)',
            2047 => 'E_ALL',
            2048 => 'E_STRICT'
        );
        return isset($Level_array[$this->errorCode]) ? $Level_array[$this->errorCode] : $this->errorCode;
    }

    /**
     * 抛出错误信息，用于外部调用
     * @param string $message
     * @param number $code
     * @param string $errorFile
     * @param number $errorLine
     */
    static public function show($message = "", $code = 0, $errorFile = '', $errorLine = 0) {
        if (function_exists('ec_error_ext')) {
            ec_error_ext($message, $code, $errorFile, $errorLine);
        } else {
            new EcError($message, $code, $errorFile, $errorLine);
        }
    }

    /**
     * 记录错误信息
     * @param unknown $message
     */
    static public function write($message) {
        $log_path = C('LOG_PATH');
        //检查日志记录目录是否存在
        if (!is_dir($log_path)) {
            //创建日志记录目录
            @mkdir($log_path, 0777, true);
        }
        $time = date('Y-m-d H:i:s');
        $ip = function_exists('get_client_ip') ? get_client_ip() : $_SERVER['REMOTE_ADDR'];
        $destination = $log_path . date("Y-m-d_") . md5($log_path) . ".log";
        //写入文件，记录错误信息
        @error_log("{$time} | {$ip} | {$_SERVER['PHP_SELF']} |{$message}\r\n", 3, $destination);
    }

    /**
     * 输出错误信息
     */
    protected function showError() {
        //如果开启了日志记录，则写入日志
        if (C('LOG_ON')) {
            self::write($this->message);
        }

        $error_url = C('ERROR_URL');
        //错误页面重定向
        if ($error_url != '') {
            echo '<script language="javascript">
                if(self!=top){
                  parent.location.href="' . $error_url . '";
                } else {
                 window.location.href="' . $error_url . '";
                }
                </script>';
            exit;
        }

        if (defined('DEBUG') && false == DEBUG) {
            @header("HTTP/1.1 404 Not Found");
            exit;
        }

        if (!defined('__APP__')) define('__APP__', '/');

        echo '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>系统发生错误</title>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=0">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<style type="text/css">
*{ padding: 0; margin: 0; }
html{ overflow-y: scroll; }
body{ background: #fff; font-family: \'微软雅黑\'; color: #333; font-size: 16px; }
img{ border: 0; }
.error{ margin: 36px 12px; }
.face{ font-size: 36px; font-weight: normal; line-height: 36px; margin-bottom: 36px; }
h1{ font-size: 16px; line-height: 24px; }
.error .content{ padding-top: 10px}
.error .info{ margin-bottom: 12px; }
.error .info .title{ margin-bottom: 3px; }
.error .info .title h3{ color: #000; font-weight: 700; font-size: 16px; }
.error .info .text{ line-height: 24px; word-wrap: break-word;}
.copyright{ padding: 12px; color: #999; }
.copyright a{ color: #000; text-decoration: none; }
</style>
</head>
<body>
<div class="error">
<p class="face">:(</p>
<h1>' . $this->message . '</h1>';
//开启调试模式之后，显示详细信息
if (($this->errorCode > 0) && ($this->errorCode != 404) && C('DEBUG')) {
    echo '
    <div class="content">
	<div class="info">
		<div class="title"><h3>出错信息</h3></div>
		<div class="text">
                    <p>FILE: ' . $this->errorFile . ' &#12288;LINE: ' . $this->errorLine . '</p>
		</div>
		<div class="text">
                    <p>错误级别: ' . $this->errorLevel . '</p>
		</div>
	</div>
    </div>';
}
echo '
</div>
<div class="copyright">
<p><a title="官方网站" href="http://www.ectouch.cn">ECTouch</a><sup>'.VERSION.'_'.RELEASE.'</sup></p>
<p style="text-align:right">[ 商创网络科技 专注移动电商 ]</p>
</div>
</body>
</html>';
        exit;
    }

}
