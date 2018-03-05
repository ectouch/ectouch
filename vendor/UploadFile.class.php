<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 上传类
 */
class UploadFile {

    public $maxSize = 10485760; // 上传文件的最大值，默认10M
    public $allowExts = array(); //允许的文件后缀
    public $savePath = ''; // 上传文件保存路径
    public $saveRule = 'md5_file'; //命名规则
    public $autoSave = true; //自动保存，设置为false，自定义存储方式
    public $domain = 'public';
    public $thumb = false; //是否开启缩略图
    public $thumbMaxWidth = 100; //缩略图最大宽度
    public $thumbMaxHeight = 100; //缩略图最大高度   
    public $thumbPrefix = 'thumb_'; //缩略图前缀
    public $thumbPath = '';  //缩略图保存路径，为空则为上传文件保存路径savePath
    protected $saeStorage = NULL;
    protected $uploadFileInfo = array(); //上传成功的文件信息
    protected $errorMsg = ''; //错误信息

    public function __construct($savePath = "data/attached/", $allowExts = array('gif', 'jpg', 'jpeg', 'bmp', 'png'), $maxSize = 10485760) {
        $this->savePath = $savePath;
        $this->allowExts = $allowExts;
        $this->maxSize = $maxSize;
        if (class_exists('SaeStorage')) {
            $this->saeStorage = new SaeStorage();
        }
    }

    /**
     * 上传方法
     * @param type $key
     * @return boolean
     */
    public function upload($key = '') {
        if (empty($_FILES)) {
            $this->errorMsg = '没有文件上传！';
            return false;
        }
        if (empty($key)) {
            $files = $_FILES;
        } else {
            $files[$key] = $_FILES[$key];
        }

        $num = 0;
        foreach ($files as $key => $file) {
            if ($file['error'] == 4)
                continue;
            $saveRuleFunc = $this->saveRule;
            $pathinfo = pathinfo($file['name']);

            $file['key'] = $key;
            $file['extension'] = strtolower($pathinfo['extension']);
            $file['savepath'] = $this->savePath;
            $file['savename'] = $saveRuleFunc($file['tmp_name']) . '.' . $file['extension'];
            //检查文件类型大小和合法性
            if (!$this->check($file)) {
                return false;
            }
            //存储文件
            if ($this->autoSave) {
                if (isset($this->saeStorage)) {
                    $file['savepath'] = str_replace(array('../', './'), '', $file['savepath']);
                    $ret = $this->saeSave($file['tmp_name'], $file['savepath'] . $file['savename']);
                } else {
                    $ret = $this->localSave($file['tmp_name'], $file['savepath'] . $file['savename']);
                }
                if (!$ret) {
                    return false;
                }
                $this->thumb($file); //缩略图片
            }

            $this->uploadFileInfo[$key] = $file;
            $this->uploadFileInfo[$num++] = $file;
        }
        return true;
    }

    /**
     * 检查文件类型大小和合法性
     * @param type $file
     * @return boolean
     */
    protected function check($file) {
        //文件上传失败
        if ($file['error'] !== 0) {
            $this->errorMsg = '文件上传失败！';
            return false;
        }
        //检查文件类型
        $this->allowExts = array_map('strtolower', $this->allowExts);
        if (!in_array($file['extension'], $this->allowExts)) {
            $this->errorMsg = '上传文件类型不允许！';
            return false;
        }
        //检查文件大小
        if ($file['size'] > $this->maxSize) {
            $this->errorMsg = '上传文件大小超出限制！';
            return false;
        }
        //检查是否合法上传
        if (!is_uploaded_file($file['tmp_name'])) {
            $this->errorMsg = '非法上传文件！';
            return false;
        }
        // 如果是图像文件 检测文件格式
        if (in_array($file['extension'], array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf')) && false === getimagesize($file['tmp_name'])) {
            $this->errorMsg = '非法图像文件';
            return false;
        }
        //检查通过，返回true
        return true;
    }

    /**
     * 缩略图片
     * @param type $file
     * @return boolean
     */
    protected function thumb($file) {
        if ($this->thumb && in_array($file['extension'], array('gif', 'jpg', 'jpeg', 'bmp', 'png'))) {
            $this->thumbPath = $this->thumbPath ? $this->thumbPath : $file['savepath'];
            $thumbname = $this->thumbPath . $this->thumbPrefix . basename($file['savename']);
            require_once(dirname(__FILE__) . '/Image.class.php');
            $imagename = isset($this->saeStorage) ? $file['tmp_name'] : $file['savepath'] . $file['savename'];
            return Image::thumb($imagename, $thumbname, $this->domain, $this->thumbMaxWidth, $this->thumbMaxHeight); // 生成图像缩略图
        }
        return false;
    }

    /**
     * sae存储
     * @param type $srcFileName
     * @param type $destFileName
     * @return boolean
     */
    protected function saeSave($srcFileName, $destFileName) {
        if (false != $this->saeStorage->upload($this->domain, $destFileName, $srcFileName)) {
            return true;
        } else {
            $this->errorMsg = $this->saeStorage->errmsg();
            return false;
        }
    }

    /**
     * 本地存储
     * @param type $srcFileName
     * @param type $destFileName
     * @return boolean
     */
    protected function localSave($srcFileName, $destFileName) {
        $dir = dirname($destFileName);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                $this->errorMsg = '上传目录' . $dir . '不存在';
                return false;
            }
        } else {
            if (!is_writeable($dir)) {
                $this->errorMsg = '上传目录' . $dir . '不可写';
                return false;
            }
        }
        if (move_uploaded_file($srcFileName, $destFileName)) {
            return true;
        }
        $this->errorMsg = '文件上传保存错误！';
        return false;
    }

    /**
     * 上传成功获取返回信息
     * @return type
     */
    public function getUploadFileInfo() {
        return $this->uploadFileInfo;
    }

    /**
     * 获取错误信息
     * @return type
     */
    public function getErrorMsg() {
        return $this->errorMsg;
    }

}

?>