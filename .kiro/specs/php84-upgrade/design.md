# 设计文档

## 概述

本设计文档描述了将ECTouch电商系统从PHP 5.3+升级到PHP 8.4的技术方案。升级将分阶段进行,首先更新配置和移除已弃用语法,然后添加类型声明,最后优化代码以利用PHP 8.4的新特性。整个升级过程将确保系统的向后兼容性被完全移除,代码完全符合PHP 8.4标准。

## 架构

### 系统架构概述

ECTouch采用经典的MVC架构:
- **Model层**: 位于`include/apps/*/models/`和`include/classes/`
- **View层**: 位于`admin/templates/`和`themes/`
- **Controller层**: 位于`include/apps/*/controllers/`

### 核心组件

1. **数据库抽象层**
   - `include/classes/mysql.php` - 旧版MySQL类
   - `include/libraries/EcsMysql.class.php` - ECSHOP MySQL类
   - 两者都使用mysqli扩展

2. **引导系统**
   - `include/bootstrap.php` - 应用程序入口
   - `index.php` - Web入口点

3. **辅助函数库**
   - `include/base/helpers/function.php` - 核心辅助函数
   - `include/helpers/base_helper.php` - 基础辅助函数

4. **自动加载器**
   - 使用自定义`autoload()`函数
   - Composer PSR-4自动加载

## 组件和接口

### 1. 数据库层重构

#### 现有问题
- 包含PHP 4.x/5.x版本检查
- 使用字符串花括号访问语法
- 缺少类型声明
- 错误处理使用echo而非异常

#### 新设计

```php
<?php

declare(strict_types=1);

class MySQL
{
    private ?mysqli $link_id = null;
    private array $settings = [];
    private int $queryCount = 0;
    private float $queryTime = 0.0;
    private array $queryLog = [];
    private int $max_cache_time = 300;
    private string $cache_data_dir = 'data/caches/query_caches/';
    private string $root_path = '';
    private array $error_message = [];
    private string $platform = '';
    private string $version = '';
    private string $dbhash = '';
    private int $starttime = 0;
    private int $timeline = 0;
    private int $timezone = 0;
    private int $mysql_config_cache_file_time = 0;
    private array $mysql_disable_cache_tables = [];

    public function __construct(
        private readonly string $dbhost,
        private readonly string $dbuser,
        private readonly string $dbpw,
        private readonly string $dbname = '',
        private readonly string $charset = 'utf8',
        private readonly bool $pconnect = false,
        private readonly bool $quiet = false
    ) {
        $this->root_path = ROOT_PATH ?? '';
        
        if (!$quiet) {
            $this->connect($dbhost, $dbuser, $dbpw, $dbname, $charset, $pconnect, $quiet);
        }
    }

    public function connect(
        string $dbhost,
        string $dbuser,
        string $dbpw,
        string $dbname = '',
        string $charset = 'utf8',
        bool $pconnect = false,
        bool $quiet = false
    ): bool {
        $this->link_id = mysqli_connect($dbhost, $dbuser, $dbpw);
        
        if (!$this->link_id) {
            if (!$quiet) {
                throw new DatabaseException("无法连接到MySQL服务器($dbhost)");
            }
            return false;
        }

        $this->dbhash = md5($this->root_path . $dbhost . $dbuser . $dbpw . $dbname);
        $this->version = mysqli_get_server_info($this->link_id);

        if ($charset !== 'latin1') {
            mysqli_query(
                $this->link_id,
                "SET character_set_connection=$charset, character_set_results=$charset, character_set_client=binary"
            );
        }
        
        mysqli_query($this->link_id, "SET sql_mode=''");

        if ($dbname) {
            if (!mysqli_select_db($this->link_id, $dbname)) {
                if (!$quiet) {
                    throw new DatabaseException("无法选择数据库($dbname)");
                }
                return false;
            }
        }

        return true;
    }

    public function query(string $sql, string $type = ''): mysqli_result|bool {
        if ($this->link_id === null) {
            $this->connect(
                $this->dbhost,
                $this->dbuser,
                $this->dbpw,
                $this->dbname,
                $this->charset,
                $this->pconnect
            );
        }

        if ($this->queryCount++ <= 99) {
            $this->queryLog[] = $sql;
        }
        
        if ($this->queryTime === 0.0) {
            $this->queryTime = microtime(true);
        }

        mysqli_ping($this->link_id);

        $query = mysqli_query($this->link_id, $sql);
        
        if (!$query && $type !== 'SILENT') {
            throw new DatabaseException(
                message: 'MySQL查询错误',
                code: mysqli_errno($this->link_id),
                previous: null,
                sql: $sql,
                error: mysqli_error($this->link_id)
            );
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            $logfilename = $this->root_path . 'data/caches/logs/mysql_query_' . 
                          $this->dbhash . '_' . date('Y_m_d') . '.log';
            
            if (!is_dir(dirname($logfilename))) {
                mkdir(dirname($logfilename), 0755, true);
            }

            file_put_contents($logfilename, $sql . "\n\n", FILE_APPEND);
        }

        return $query;
    }

    public function getOne(string $sql, bool $limited = false): mixed {
        if ($limited) {
            $sql = trim($sql) . ' LIMIT 1';
        }

        $res = $this->query($sql);
        
        if ($res !== false) {
            $row = mysqli_fetch_row($res);
            return $row !== false ? $row[0] : '';
        }
        
        return false;
    }

    public function getAll(string $sql): array|false {
        $res = $this->query($sql);
        
        if ($res === false) {
            return false;
        }

        $arr = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $arr[] = $row;
        }

        return $arr;
    }

    public function getRow(string $sql, bool $limited = false): array|false {
        if ($limited) {
            $sql = trim($sql) . ' LIMIT 1';
        }

        $res = $this->query($sql);
        return $res !== false ? mysqli_fetch_assoc($res) : false;
    }

    public function escape_string(string $unescaped_string): string {
        return mysqli_real_escape_string($this->link_id, $unescaped_string);
    }

    public function affected_rows(): int {
        return mysqli_affected_rows($this->link_id);
    }

    public function insert_id(): int {
        return mysqli_insert_id($this->link_id);
    }

    public function close(): bool {
        return mysqli_close($this->link_id);
    }
}
```

### 2. 异常类层次结构

```php
<?php

declare(strict_types=1);

class ECTouchException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array {
        return $this->context;
    }
}

class DatabaseException extends ECTouchException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly string $sql = '',
        private readonly string $error = ''
    ) {
        parent::__construct($message, $code, $previous, [
            'sql' => $sql,
            'error' => $error
        ]);
    }

    public function getSql(): string {
        return $this->sql;
    }

    public function getError(): string {
        return $this->error;
    }
}

class FileException extends ECTouchException {}

class ValidationException extends ECTouchException {}

class ConfigException extends ECTouchException {}
```

### 3. 辅助函数现代化

#### 字符串访问修复
将所有`$str{$index}`替换为`$str[$index]`

#### 类型安全的辅助函数

```php
<?php

declare(strict_types=1);

function msubstr(
    string $str,
    int $length,
    int $start = 0,
    string $charset = "utf-8",
    bool $suffix = true
): string {
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re = match($charset) {
            'utf-8' => "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/",
            'gb2312' => "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/",
            'gbk' => "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/",
            'big5' => "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/",
            default => throw new ValidationException("不支持的字符集: $charset")
        };
        
        preg_match_all($re, $str, $match);
        $slice = implode("", array_slice($match[0], $start, $length));
    }
    
    return $suffix ? $slice . '...' : $slice;
}

function get_client_ip(int $type = 0, bool $adv = false): string|int {
    static $ip = null;
    
    if ($ip !== null) {
        return $type === 1 ? $ip[1] : $ip[0];
    }

    $ipAddress = '0.0.0.0';
    
    if ($adv) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '0.0.0.0';
            
        if (str_contains($ipAddress, ',')) {
            $arr = explode(',', $ipAddress);
            $pos = array_search('unknown', $arr);
            if ($pos !== false) {
                unset($arr[$pos]);
            }
            $ipAddress = trim($arr[0]);
        }
    } else {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    $long = sprintf("%u", ip2long($ipAddress));
    $ip = $long ? [$ipAddress, (int)$long] : ['0.0.0.0', 0];
    
    return $type === 1 ? $ip[1] : $ip[0];
}

function mysql_like_quote(string $str): string {
    return strtr($str, [
        "\\\\" => "\\\\\\\\",
        '_' => '\_',
        '%' => '\%',
        "\'" => "\\\\\'"
    ]);
}
```

## 数据模型

### 配置数据结构

```php
<?php

declare(strict_types=1);

readonly class DatabaseConfig
{
    public function __construct(
        public string $host,
        public string $user,
        public string $password,
        public string $database,
        public string $charset = 'utf8mb4',
        public int $port = 3306
    ) {}
}

readonly class AppConfig
{
    public function __construct(
        public string $name,
        public string $version,
        public bool $debug,
        public string $timezone,
        public DatabaseConfig $database
    ) {}
}
```

## 正确性属性

*属性是一个特征或行为,应该在系统的所有有效执行中保持为真-本质上是关于系统应该做什么的正式陈述。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性 1: 字符串访问语法一致性

*对于任何*使用字符串偏移访问的代码,应该使用方括号语法`$str[$index]`而不是花括号语法`$str{$index}`
**验证: 需求 2.1**

### 属性 2: 类型声明完整性

*对于任何*公共方法,应该同时具有参数类型声明和返回类型声明
**验证: 需求 3.1, 3.2**

### 属性 3: 异常处理一致性

*对于任何*错误情况,系统应该抛出类型化的异常而不是使用echo输出错误信息
**验证: 需求 5.1**

### 属性 4: 数据库查询安全性

*对于任何*包含用户输入的SQL查询,应该使用参数化查询或正确转义输入
**验证: 需求 4.1**

### 属性 5: 版本检查移除完整性

*对于任何*PHP版本检查代码,不应该包含对PHP 5.x或7.x版本的条件判断
**验证: 需求 8.1**

### 属性 6: 构造器属性提升正确性

*对于任何*在构造函数中声明并立即赋值给属性的参数,应该使用构造器属性提升语法
**验证: 需求 3.5**

### 属性 7: Null合并运算符使用

*对于任何*形如`isset($var) ? $var : $default`的表达式,应该使用null合并运算符`$var ?? $default`
**验证: 需求 6.2**

### 属性 8: 现代字符串函数使用

*对于任何*字符串包含检查,应该优先使用`str_contains()`而不是`strpos() !== false`
**验证: 需求 6.3**

## 错误处理

### 异常处理策略

1. **数据库错误**: 抛出`DatabaseException`,包含SQL语句和错误信息
2. **文件操作错误**: 抛出`FileException`,包含文件路径和操作类型
3. **验证错误**: 抛出`ValidationException`,包含验证失败的字段和原因
4. **配置错误**: 抛出`ConfigException`,包含配置项名称

### 错误日志

```php
<?php

declare(strict_types=1);

class ErrorLogger
{
    public function __construct(
        private readonly string $logPath
    ) {}

    public function log(Throwable $e): void {
        $logEntry = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        if ($e instanceof ECTouchException) {
            $logEntry .= "Context: " . json_encode($e->getContext()) . "\n\n";
        }

        file_put_contents(
            $this->logPath . '/error_' . date('Y-m-d') . '.log',
            $logEntry,
            FILE_APPEND
        );
    }
}
```

## 测试策略

### 单元测试

使用PHPUnit 11.x(支持PHP 8.4)进行单元测试:

1. **数据库类测试**
   - 测试连接建立
   - 测试查询执行
   - 测试事务处理
   - 测试错误处理

2. **辅助函数测试**
   - 测试字符串处理函数
   - 测试数据过滤函数
   - 测试加密解密函数

3. **异常处理测试**
   - 测试异常抛出
   - 测试异常上下文信息
   - 测试异常日志记录

### 属性基测试

使用Pest PHP(支持PHP 8.4)进行属性基测试:

1. **字符串访问属性测试**
   - 生成随机PHP文件
   - 验证不包含`{}`字符串访问语法

2. **类型声明属性测试**
   - 生成随机类定义
   - 验证所有公共方法都有类型声明

3. **异常处理属性测试**
   - 生成随机错误场景
   - 验证所有错误都抛出异常

### 集成测试

1. **前台功能测试**
   - 商品列表显示
   - 商品详情查看
   - 购物车操作
   - 订单提交

2. **后台功能测试**
   - 管理员登录
   - 商品管理
   - 订单管理
   - 系统配置

### 性能测试

1. **数据库查询性能**
   - 对比升级前后查询速度
   - 测试缓存机制

2. **页面加载性能**
   - 测试首页加载时间
   - 测试商品列表加载时间

## 实施计划

### 阶段1: 准备和配置更新
- 更新composer.json
- 更新README.md
- 备份现有代码

### 阶段2: 语法更新
- 替换字符串花括号访问
- 移除已弃用函数调用
- 移除版本检查代码

### 阶段3: 类型系统
- 添加类型声明
- 实现构造器属性提升
- 添加联合类型和返回类型

### 阶段4: 异常处理
- 创建异常类层次结构
- 替换错误输出为异常
- 实现错误日志系统

### 阶段5: 现代化优化
- 使用null合并运算符
- 使用现代字符串函数
- 优化数组操作

### 阶段6: 测试和验证
- 运行单元测试
- 运行属性基测试
- 执行集成测试
- 性能基准测试

## 风险和缓解

### 风险1: 第三方库不兼容
**缓解**: 检查vendor目录中的所有库,更新到支持PHP 8.4的版本

### 风险2: 隐藏的版本依赖
**缓解**: 全面搜索代码库,识别所有版本检查和条件编译

### 风险3: 性能回退
**缓解**: 在升级前后进行性能基准测试,优化关键路径

### 风险4: 数据丢失
**缓解**: 在升级前完整备份数据库和文件系统

### 风险5: 用户会话中断
**缓解**: 计划在低流量时段进行升级,提前通知用户
