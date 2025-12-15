<?php

/**
 * 配置异常类
 * 
 * 用于处理配置相关的错误,包含配置项名称和配置值信息
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

class ConfigException extends ECTouchException
{
    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常(用于异常链)
     * @param string $configKey 相关的配置项名称
     * @param mixed $configValue 配置项的值
     * @param string $configFile 配置文件路径
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly string $configKey = '',
        private readonly mixed $configValue = null,
        private readonly string $configFile = ''
    ) {
        parent::__construct($message, $code, $previous, [
            'config_key' => $configKey,
            'config_value' => $configValue,
            'config_file' => $configFile
        ]);
    }

    /**
     * 获取相关的配置项名称
     * 
     * @return string 配置项名称
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * 获取配置项的值
     * 
     * @return mixed 配置项值
     */
    public function getConfigValue(): mixed
    {
        return $this->configValue;
    }

    /**
     * 获取配置文件路径
     * 
     * @return string 配置文件路径
     */
    public function getConfigFile(): string
    {
        return $this->configFile;
    }
}
