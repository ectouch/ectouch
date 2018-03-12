<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AdminController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 后台控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AdminController extends BaseController
{
    protected $sess = null;

    public function __construct()
    {
        parent::__construct();

        require BASE_PATH .'classes/session.php';
        $this->sess = new session(self::$db, self::$ecs->table('sessions'), self::$ecs->table('sessions_data'), 'ECSCP_ID');

        $this->checkLogin();
        $this->assign('lang', L());
    }

    protected function checkLogin()
    {
        $access = array(
            'crowd' => '*',
            'wechat' => '*',
            'extend' => '*',
            'upload' => '*',
            'authorization' => '*',
            'navigator' => '*',
            'upgrade' => '*',
            'index' => array('license', 'uploader')
        );
        $controller = strtolower(CONTROLLER_NAME);
        $action = strtolower(ACTION_NAME);

        if (intval($_SESSION['admin_id']) <= 0) {
            $this->redirect('./admin');
        }

        if (isset($access[$controller])) {
            if ($access[$controller] != '*') {
                if (!in_array($action, $access[$controller])) {
                    $this->redirect('./admin');
                }
            }
        } else {
            $this->redirect('./admin');
        }
    }

    //$upload_dir上传的目录名
    protected function ectouchUpload($key = '', $upload_dir = 'images', $thumb = false, $width = 220, $height = 220)
    {
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 1024 * 1024 * 5; //最大10M,但最佳5M以内。
        //设置上传文件类型
        $upload->allowExts = explode(',', 'jpg,jpeg,gif,png,bmp,mp3,amr,mp4');
        //生成缩略图
        $upload->thumb = $thumb;
        //缩略图大小
        $upload->thumbMaxWidth = $width;
        $upload->thumbMaxHeight = $height;

        //设置附件上传目录
        $upload->savePath = './data/attached/' . $upload_dir . "/";

        if (!$upload->upload($key)) {
            //捕获上传异常
            return array('error' => 1, 'message' => $upload->getErrorMsg());
        } else {
            //取得成功上传的文件信息
            return array('error' => 0, 'message' => $upload->getUploadFileInfo());
        }
    }
}

class_alias('AdminController', 'ECTouch');
