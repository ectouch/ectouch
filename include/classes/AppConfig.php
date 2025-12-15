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
 * 应用配置类
 * 使用PHP 8.1+ readonly属性和构造器属性提升
 */
readonly class AppConfig
{
    /**
     * 构造函数 - 使用构造器属性提升
     * 
     * @param string $name 应用名称
     * @param string $version 应用版本
     * @param bool $debug 调试模式
     * @param string $timezone 时区
     * @param DatabaseConfig $database 数据库配置
     * @param string $charset 字符集
     * @param string $authKey 认证密钥
     * @param string $adminPath 后台路径
     * @param int $deployMode 部署模式
     * 
     * @throws ConfigException 当配置参数无效时
     */
    public function __construct(
        public string $name,
        public string $version,
        public bool $debug,
        public string $timezone,
        public DatabaseConfig $database,
        public string $charset = 'utf-8',
        public string $authKey = '',
        public string $adminPath = 'admin',
        public int $deployMode = 0
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
        if (empty($this->name)) {
            throw new ConfigException('应用名称不能为空');
        }

        if (empty($this->version)) {
            throw new ConfigException('应用版本不能为空');
        }

        if (empty($this->timezone)) {
            throw new ConfigException('时区不能为空');
        }

        // 验证时区是否有效
        $validTimezones = timezone_identifiers_list();
        if (!in_array($this->timezone, $validTimezones, true) && $this->timezone !== 'PRC') {
            throw new ConfigException('无效的时区: ' . $this->timezone);
        }

        if (empty($this->charset)) {
            throw new ConfigException('字符集不能为空');
        }

        if ($this->deployMode < 0 || $this->deployMode > 2) {
            throw new ConfigException('部署模式必须在0-2之间');
        }
    }

    /**
     * 从配置文件加载配置
     * 
     * @param string $configPath 配置文件路径
     * @return self
     * @throws ConfigException 当配置文件不存在或无效时
     */
    public static function loadFromFile(string $configPath): self
    {
        if (!file_exists($configPath)) {
            throw new ConfigException('配置文件不存在: ' . $configPath);
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new ConfigException('配置文件格式无效: ' . $configPath);
        }

        return self::fromArray($config);
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
        // 创建数据库配置对象
        $databaseConfig = DatabaseConfig::fromArray($config);

        return new self(
            name: $config['name'] ?? 'ECTouch',
            version: $config['version'] ?? '1.0.0',
            debug: (bool)($config['debug'] ?? false),
            timezone: $config['timezone'] ?? 'PRC',
            database: $databaseConfig,
            charset: $config['charset'] ?? 'utf-8',
            authKey: $config['auth_key'] ?? '',
            adminPath: $config['admin_path'] ?? 'admin',
            deployMode: (int)($config['deploy_mode'] ?? 0)
        );
    }

    /**
     * 从全局常量加载配置
     * 
     * @param string $databaseConfigPath 数据库配置文件路径
     * @return self
     * @throws ConfigException 当配置无效时
     */
    public static function loadFromGlobals(string $databaseConfigPath): self
    {
        if (!file_exists($databaseConfigPath)) {
            throw new ConfigException('数据库配置文件不存在: ' . $databaseConfigPath);
        }

        $dbConfig = require $databaseConfigPath;

        if (!is_array($dbConfig)) {
            throw new ConfigException('数据库配置文件格式无效: ' . $databaseConfigPath);
        }

        $databaseConfig = DatabaseConfig::fromArray($dbConfig);

        return new self(
            name: defined('SHOP_NAME') ? SHOP_NAME : 'ECTouch',
            version: defined('VERSION') ? VERSION : '1.0.0',
            debug: defined('APP_DEBUG') ? APP_DEBUG : false,
            timezone: defined('DEFAULT_TIMEZONE') ? DEFAULT_TIMEZONE : 'PRC',
            database: $databaseConfig,
            charset: defined('EC_CHARSET') ? EC_CHARSET : 'utf-8',
            authKey: defined('AUTH_KEY') ? AUTH_KEY : '',
            adminPath: defined('ADMIN_PATH') ? ADMIN_PATH : 'admin',
            deployMode: defined('DEPLOY_MODE') ? DEPLOY_MODE : 0
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
            'name' => $this->name,
            'version' => $this->version,
            'debug' => $this->debug,
            'timezone' => $this->timezone,
            'charset' => $this->charset,
            'auth_key' => $this->authKey,
            'admin_path' => $this->adminPath,
            'deploy_mode' => $this->deployMode,
            'database' => $this->database->toArray(),
        ];
    }

    /**
     * 获取数据库配置
     * 
     * @return DatabaseConfig
     */
    public function getDatabaseConfig(): DatabaseConfig
    {
        return $this->database;
    }
}
