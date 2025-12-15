<?php

declare(strict_types=1);

/**
 * ECTouch E-Commerce Project
 * ============================================================================
 * Copyright (c) 2014-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * This is NOT a freeware, use is subject to license terms
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/license )
 * ----------------------------------------------------------------------------
 */

/**
 * 数据库配置类
 * 使用PHP 8.1+ readonly属性和构造器属性提升
 */
readonly class DatabaseConfig
{
    /**
     * 构造函数 - 使用构造器属性提升
     * 
     * @param string $host 数据库主机地址
     * @param string $user 数据库用户名
     * @param string $password 数据库密码
     * @param string $database 数据库名称
     * @param string $prefix 表前缀
     * @param string $charset 字符集，默认utf8mb4
     * @param int $port 端口号，默认3306
     * @param string $type 数据库类型，默认mysql
     * 
     * @throws ConfigException 当配置参数无效时
     */
    public function __construct(
        public string $host,
        public string $user,
        public string $password,
        public string $database,
        public string $prefix = '',
        public string $charset = 'utf8mb4',
        public int $port = 3306,
        public string $type = 'mysql'
    ) {
        $this->validate();
    }

    /**
     * 验证配置参数
     * 
     * @throws ConfigException 当配置参数无效时
     */
    private function validate(): void
    {
        if (empty($this->host)) {
            throw new ConfigException('数据库主机地址不能为空');
        }

        if (empty($this->user)) {
            throw new ConfigException('数据库用户名不能为空');
        }

        if (empty($this->database)) {
            throw new ConfigException('数据库名称不能为空');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw new ConfigException('数据库端口号必须在1-65535之间');
        }

        $allowedCharsets = ['utf8', 'utf8mb4', 'latin1', 'gbk', 'gb2312'];
        if (!in_array($this->charset, $allowedCharsets, true)) {
            throw new ConfigException(
                '不支持的字符集: ' . $this->charset . 
                '。支持的字符集: ' . implode(', ', $allowedCharsets)
            );
        }

        if ($this->type !== 'mysql') {
            throw new ConfigException('当前仅支持MySQL数据库类型');
        }
    }

    /**
     * 从数组创建配置对象
     * 
     * @param array $config 配置数组
     * @return self
     * @throws ConfigException 当配置参数无效时
     */
    public static function fromArray(array $config): self
    {
        return new self(
            host: $config['DB_HOST'] ?? $config['host'] ?? '',
            user: $config['DB_USER'] ?? $config['user'] ?? '',
            password: $config['DB_PWD'] ?? $config['password'] ?? '',
            database: $config['DB_NAME'] ?? $config['database'] ?? '',
            prefix: $config['DB_PREFIX'] ?? $config['prefix'] ?? '',
            charset: $config['DB_CHARSET'] ?? $config['charset'] ?? 'utf8mb4',
            port: (int)($config['DB_PORT'] ?? $config['port'] ?? 3306),
            type: $config['DB_TYPE'] ?? $config['type'] ?? 'mysql'
        );
    }

    /**
     * 转换为数组格式
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'DB_TYPE' => $this->type,
            'DB_HOST' => $this->host,
            'DB_USER' => $this->user,
            'DB_PWD' => $this->password,
            'DB_NAME' => $this->database,
            'DB_PREFIX' => $this->prefix,
            'DB_PORT' => $this->port,
            'DB_CHARSET' => $this->charset,
        ];
    }

    /**
     * 获取DSN字符串
     * 
     * @return string
     */
    public function getDsn(): string
    {
        return sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->type,
            $this->host,
            $this->port,
            $this->database,
            $this->charset
        );
    }
}
