<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UploadControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：文件上传控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class UploadController extends AdminController {

    private $conf = array();

    public function __construct() {
        parent::__construct();
        $this->content = file_get_contents(ROOT_PATH . "data/common/ueditor/config.json");
        $this->conf = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", str_replace('__ROOT__', __ROOT__, $this->content)), true);
    }

    // 上传方法
    public function index() {
        $action = I('get.action');

        switch ($action) {
            case 'config' :
                $result = json_encode($this->conf);
                break;

            /* 上传图片 */
            case 'uploadimage' :
            /* 上传涂鸦 */
            case 'uploadscrawl' :
            /* 上传视频 */
            case 'uploadvideo' :
            /* 上传文件 */
            case 'uploadfile' :
                $result = $this->uploads();
                break;

            /* 列出图片 */
            case 'listimage' :
                $result = $this->lists();
                break;
            /* 列出文件 */
            case 'listfile' :
                $result = $this->lists();
                break;

            /* 抓取远程文件 */
            case 'catchimage' :
                $result = $this->crawler();
                break;

            default :
                $result = json_encode(array(
                    'state' => '请求地址出错'
                ));
                break;
        }

        /* 输出结果 */
        if (isset($_GET["callback"])) {
            if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
                echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
            } else {
                echo json_encode(array(
                    'state' => 'callback参数不合法'
                ));
            }
        } else {
            echo $result;
        }
    }

    // 上传附件和上传视频
    private function uploads() {
        /* 上传配置 */
        $base64 = "upload";
        switch (htmlspecialchars($_GET['action'])) {
            case 'uploadimage' :
                $config = array(
                    "pathFormat" => $this->conf['imagePathFormat'],
                    "maxSize" => $this->conf['imageMaxSize'],
                    "allowFiles" => $this->conf['imageAllowFiles']
                );
                $fieldName = $this->conf['imageFieldName'];
                break;
            case 'uploadscrawl' :
                $config = array(
                    "pathFormat" => $this->conf['scrawlPathFormat'],
                    "maxSize" => $this->conf['scrawlMaxSize'],
                    "allowFiles" => $this->conf['scrawlAllowFiles'],
                    "oriName" => "scrawl.png"
                );
                $fieldName = $this->conf['scrawlFieldName'];
                $base64 = "base64";
                break;
            case 'uploadvideo' :
                $config = array(
                    "pathFormat" => $this->conf['videoPathFormat'],
                    "maxSize" => $this->conf['videoMaxSize'],
                    "allowFiles" => $this->conf['videoAllowFiles']
                );
                $fieldName = $this->conf['videoFieldName'];
                break;
            case 'uploadfile' :
            default :
                $config = array(
                    "pathFormat" => $this->conf['filePathFormat'],
                    "maxSize" => $this->conf['fileMaxSize'],
                    "allowFiles" => $this->conf['fileAllowFiles']
                );
                $fieldName = $this->conf['fileFieldName'];
                break;
        }

        /* 生成上传实例对象并完成上传 */
        $up = new Uploader($fieldName, $config, $base64);

        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         * "state" => "", //上传状态，上传成功时必须返回"SUCCESS"
         * "url" => "", //返回的地址
         * "title" => "", //新文件名
         * "original" => "", //原始文件名
         * "type" => "" //文件类型
         * "size" => "", //文件大小
         * )
         */
        /* 返回数据 */
        return json_encode($up->getFileInfo());
    }

    // 显示文件列表
    private function lists() {
        /* 判断类型 */
        switch ($_GET['action']) {
            /* 列出文件 */
            case 'listfile' :
                $allowFiles = $this->conf['fileManagerAllowFiles'];
                $listSize = $this->conf['fileManagerListSize'];
                $path = $this->conf['fileManagerListPath'];
                break;
            /* 列出图片 */
            case 'listimage' :
            default :
                $allowFiles = $this->conf['imageManagerAllowFiles'];
                $listSize = $this->conf['imageManagerListSize'];
                $path = $this->conf['imageManagerListPath'];
        }
        $allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);

        /* 获取参数 */
        $size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
        $start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
        $end = $start + $size;

        /* 获取文件列表 */
        $path = $_SERVER['DOCUMENT_ROOT'] . (substr($path, 0, 1) == "/" ? "" : "/") . $path;
        $files = $this->getfiles($path, $allowFiles);
        if (!count($files)) {
            return json_encode(array(
                "state" => "no match file",
                "list" => array(),
                "start" => $start,
                "total" => count($files)
            ));
        }

        /* 获取指定范围的列表 */
        $len = count($files);
        for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i --) {
            $list[] = $files [$i];
        }
        // 倒序
        // for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
        // $list[] = $files[$i];
        // }

        /* 返回数据 */
        $result = json_encode(array(
            "state" => "SUCCESS",
            "list" => $list,
            "start" => $start,
            "total" => count($files)
        ));

        return $result;
    }

    // 抓取远程文件
    private function crawler() {
        set_time_limit(0);

        /* 上传配置 */
        $config = array(
            "pathFormat" => $this->conf['catcherPathFormat'],
            "maxSize" => $this->conf['catcherMaxSize'],
            "allowFiles" => $this->conf['catcherAllowFiles'],
            "oriName" => "remote.png"
        );
        $fieldName = $this->conf['catcherFieldName'];

        /* 抓取远程图片 */
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $item = new Uploader($imgUrl, $config, "remote");
            $info = $item->getFileInfo();
            array_push($list, array(
                "state" => $info["state"],
                "url" => $info["url"],
                "size" => $info["size"],
                "title" => htmlspecialchars($info["title"]),
                "original" => htmlspecialchars($info["original"]),
                "source" => htmlspecialchars($imgUrl)
            ));
        }

        /* 返回抓取数据 */
        return json_encode(array(
            'state' => count($list) ? 'SUCCESS' : 'ERROR',
            'list' => $list
        ));
    }

    /**
     * 遍历获取目录下的指定类型的文件
     *
     * @param
     *        	$path
     * @param array $files        	
     * @return array
     */
    private function getfiles($path, $allowFiles, &$files = array()) {
        if (!is_dir($path))
            return null;
        if (substr($path, strlen($path) - 1) != '/')
            $path .= '/';
        $handle = opendir($path);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..') {
                $path2 = $path . $file;
                if (is_dir($path2)) {
                    $this->getfiles($path2, $allowFiles, $files);
                } else {
                    if (preg_match("/\.(" . $allowFiles . ")$/i", $file)) {
                        $files [] = array(
                            'url' => substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
                            'mtime' => filemtime($path2)
                        );
                    }
                }
            }
        }
        return $files;
    }

}
