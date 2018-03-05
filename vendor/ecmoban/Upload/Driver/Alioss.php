<?php
namespace ecmoban\Upload\Driver;

use OSS\OssClient;
use OSS\Core\OssException;

class Alioss
{
    /**
     * 上传文件根目录
     * @var string
     */
    private $rootPath;

    /**
     * 上传错误信息
     * @var string
     */
    private $error = '';

    private $config = array(
        'OSS_ACCESS_ID' => '', //您从OSS获得的AccessKeyId
        'OSS_ACCESS_KEY' => '', //您从OSS获得的AccessKeySecret
        'OSS_ENDPOINT'   => '', //您选定的OSS数据中心访问域名
        'OSS_BUCKET' => '', //空间名称
    );

    /**
     * 构造函数，用于设置上传根路径
     * @param array  $config OSS配置
     */
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
        /* 设置根目录 */
        $this->alioss = new OssClient($this->config['OSS_ACCESS_ID'], $this->config['OSS_ACCESS_KEY'], $this->config['OSS_ENDPOINT']);
    }

    /**
     * 检测上传根目录(阿里云上传时支持自动创建目录，直接返回)
     * @param string $rootpath   根目录
     * @return boolean true-检测通过，false-检测失败
     */
    public function checkRootPath($rootpath)
    {
        $this->rootPath = trim($rootpath, './') . '/';
        return true;
    }

    /**
     * 检测上传目录(阿里云上传时支持自动创建目录，直接返回)
     * @param  string $savepath 上传目录
     * @return boolean          检测结果，true-通过，false-失败
     */
    public function checkSavePath($savepath)
    {
        return true;
    }

    /**
     * 创建文件夹 (阿里云上传时支持自动创建目录，直接返回)
     * @param  string $savepath 目录名称
     * @return boolean          true-创建成功，false-创建失败
     */
    public function mkdir($savepath)
    {
        return true;
    }

    /**
     * 保存指定文件
     * @param  array   $file    保存的文件信息
     * @param  boolean $replace 同名文件是否覆盖
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function save(&$file, $replace = true)
    {
        $filename = $this->rootPath . $file['savepath'] . $file['savename'];
        try{
            $this->alioss->uploadFile($this->config['OSS_BUCKET'], $filename, $file['tmp_name']);
            $file['url'] = 'http://' . $this->config['OSS_BUCKET'] . '.' . $this->config['OSS_ENDPOINT'] . '/' . $filename;
            return true;
        }catch(OssException $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 同步指定文件
     * @param  array   $file    保存的文件信息
     * @return boolean          保存状态，true-成功，false-失败
     */
    public function sync($file)
    {
        $filename = str_replace(ROOT_PATH, '', $file);
        try{
            $this->alioss->uploadFile($this->config['OSS_BUCKET'], $filename, $file);
            return true;
        }catch(OssException $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 获取最后一次上传错误信息
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->error;
    }
}
