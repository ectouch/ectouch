<?php
/**
 * 说明：分布式OSS存储、数据缓存及session配置
 * 作者：模板堂
 * 时间：2016-05
 */

/* 上传文件 */
function ecmoban_move_upload_file($file, $newloc)
{
    if(DEPLOY_MODE){
        $filepath = str_replace(ROOT_PATH, '', $newloc);
        $config = $GLOBALS['DEPLOY_CONF']['UPLOAD_CONF'];
        $upload = new \ecmoban\Upload\Upload(array('rootPath' => './' . dirname($filepath) . '/'), 'Alioss', $config);
        $upload->autoSub = false;
        $files = explode('.', $filepath);
        $upload->saveName = basename($filepath, '.' . end($files));
        if($upload->uploadOne($file)){
            return true;
        }else{
            return false;
        }
    }else{
        return move_upload_file($file['tmp_name'], $newloc);
    }
}

/* 同步文件 */
function ecmoban_upload_file($file)
{
    if(DEPLOY_MODE){
        $filepath = str_replace(ROOT_PATH, '', $file);
        $config = $GLOBALS['DEPLOY_CONF']['UPLOAD_CONF'];
        $upload = new \ecmoban\Upload\Upload(array('rootPath' => './' . dirname($filepath) . '/'), 'Alioss', $config);
        if($upload->sync($file)){
            return true;
        }else{
            return false;
        }
    }
}