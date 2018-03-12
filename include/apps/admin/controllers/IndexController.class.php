<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：管理中心首页控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class IndexController extends AdminController
{

    // 管理中心
    public function index()
    {
        $this->display('index');
    }

    /**
     * 站点授权
     */
    public function license()
    {
        if (IS_POST) {
            $license = I('license');
            // 数据验证
            $msg = Check::rule(array(
                array(
                    Check::must($license),
                    '授权码不能为空'
                )
            ));
            // 提示信息
            if ($msg !== true) {
                $this->message($msg, null, 'error');
            }
            $data = array('license'=>$license, 'appid' => EC_APPID);
            $result = $this->cloud->data($data)->act('post.dolicense');
            if ($result['error'] > 0) {
                $this->message($result['msg'], null, 'error');
            } else {
                $this->message('授权成功', null, 'success');
            }
        } else {
            // 检测是否授权
            $data = array('appid' => EC_APPID);
            $empower = $this->cloud->data($data)->act('get.licenseInfo');
            if ($empower) {
                $this->assign('empower', $empower);
            }

            $this->assign('ur_here', L('empower'));
            $this->display();
        }
    }

    /**
     * 编辑器上传
     */
    public function uploader()
    {
        $info = $this->ectouchUpload('upload_file');
        if ($info['error']) {
            $data = array(
                'success' => false,
                'msg' => $info['message']
            );
        } else {
            $imageinfo = $info['message']['upload_file'];
            $savepath = substr($imageinfo['savepath'] . $imageinfo['savename'], 1);
            $data = array(
                'success' => true,
                'file_path' => __URL__ . $savepath
            );
        }
        echo json_encode($data);
    }
}
