# ErrorLogger - 错误日志系统

## 概述

ErrorLogger 是 ECTouch 系统的现代化错误日志记录组件,专为 PHP 8.4+ 设计。它提供了类型安全的异常日志记录功能,支持上下文信息和自动日志管理。

## 特性

- ✅ **类型安全**: 使用 PHP 8.4 的严格类型声明
- ✅ **构造器属性提升**: 使用现代 PHP 语法
- ✅ **上下文支持**: 自动记录 ECTouchException 的上下文信息
- ✅ **按日期分文件**: 每天自动创建新的日志文件
- ✅ **线程安全**: 使用 LOCK_EX 防止并发写入冲突
- ✅ **自动清理**: 提供旧日志清理功能
- ✅ **多级别日志**: 支持 ERROR、WARNING、INFO 等级别

## 安装

ErrorLogger 类位于 `include/classes/ErrorLogger.php`,无需额外安装。

## 基本使用

### 1. 创建 ErrorLogger 实例

```php
<?php

declare(strict_types=1);

// 创建日志记录器,指定日志存储路径
$logger = new ErrorLogger(ROOT_PATH . 'data/logs');
```

### 2. 记录异常

```php
try {
    // 可能抛出异常的代码
    throw new DatabaseException(
        message: '数据库连接失败',
        code: 2002,
        sql: 'SELECT * FROM users',
        error: 'Connection refused'
    );
} catch (Throwable $e) {
    // 记录异常到日志
    $logger->log($e);
}
```

### 3. 记录自定义消息

```php
// 记录错误消息
$logger->logMessage('用户登录失败', 'ERROR', [
    'user_id' => 123,
    'ip' => '192.168.1.1'
]);

// 记录警告消息
$logger->logMessage('缓存未命中', 'WARNING');

// 记录信息消息
$logger->logMessage('订单创建成功', 'INFO', ['order_id' => 456]);
```

## 日志格式

### 异常日志格式

```
[2025-12-15 10:30:45] DatabaseException: 数据库连接失败 in /path/to/file.php:123
Stack trace:
#0 /path/to/file.php(123): method()
#1 {main}

Context: {
    "sql": "SELECT * FROM users",
    "error": "Connection refused"
}
```

### 消息日志格式

```
[2025-12-15 10:30:45] [ERROR] 用户登录失败
Context: {
    "user_id": 123,
    "ip": "192.168.1.1"
}
```

## API 参考

### 构造函数

```php
public function __construct(string $logPath)
```

**参数:**
- `$logPath`: 日志文件存储目录路径

**说明:**
- 如果目录不存在,会自动创建
- 目录权限设置为 0755

### log() 方法

```php
public function log(Throwable $e): void
```

**参数:**
- `$e`: 要记录的异常对象

**说明:**
- 记录异常的完整信息,包括消息、文件、行号、堆栈跟踪
- 如果是 ECTouchException,会自动记录上下文信息
- 日志文件名格式: `error_YYYY-MM-DD.log`

### logMessage() 方法

```php
public function logMessage(string $message, string $level = 'ERROR', array $context = []): void
```

**参数:**
- `$message`: 日志消息
- `$level`: 日志级别 (ERROR, WARNING, INFO)
- `$context`: 附加上下文信息数组

**说明:**
- 用于记录自定义消息
- 支持附加上下文信息

### getLogPath() 方法

```php
public function getLogPath(): string
```

**返回:**
- 日志文件存储路径

### cleanOldLogs() 方法

```php
public function cleanOldLogs(int $days = 30): int
```

**参数:**
- `$days`: 保留最近多少天的日志 (默认 30 天)

**返回:**
- 删除的日志文件数量

**说明:**
- 删除指定天数之前的日志文件
- 建议在定时任务中定期执行

## 集成示例

### 在应用启动时初始化

```php
// include/bootstrap.php

// 创建全局错误日志记录器
$GLOBALS['error_logger'] = new ErrorLogger(ROOT_PATH . 'data/logs');

// 设置全局异常处理器
set_exception_handler(function(Throwable $e) {
    $GLOBALS['error_logger']->log($e);
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        // 开发环境显示详细错误
        echo "<pre>" . $e . "</pre>";
    } else {
        // 生产环境显示友好错误
        echo "系统错误,请稍后再试";
    }
});
```

### 在数据库类中使用

```php
class MySQL
{
    public function __construct(
        private readonly ErrorLogger $logger
    ) {}
    
    public function query(string $sql): mysqli_result|bool
    {
        try {
            $result = mysqli_query($this->link_id, $sql);
            
            if (!$result) {
                throw new DatabaseException(
                    message: 'MySQL查询错误',
                    code: mysqli_errno($this->link_id),
                    sql: $sql,
                    error: mysqli_error($this->link_id)
                );
            }
            
            return $result;
        } catch (DatabaseException $e) {
            $this->logger->log($e);
            throw $e;
        }
    }
}
```

### 定期清理日志

```php
// 在定时任务中执行
$logger = new ErrorLogger(ROOT_PATH . 'data/logs');
$deletedCount = $logger->cleanOldLogs(30); // 保留30天
echo "已删除 {$deletedCount} 个旧日志文件\n";
```

## 配置建议

### 日志路径

推荐使用以下路径存储日志:
- 开发环境: `data/logs/`
- 生产环境: `/var/log/ectouch/` 或 `data/logs/`

### 日志保留策略

- 开发环境: 保留 7-14 天
- 生产环境: 保留 30-90 天
- 根据磁盘空间和合规要求调整

### 权限设置

确保日志目录有正确的权限:
```bash
chmod 755 data/logs
chown www-data:www-data data/logs
```

## 性能考虑

- 日志写入使用 `FILE_APPEND | LOCK_EX` 确保线程安全
- 每天一个日志文件,避免单个文件过大
- 建议定期清理旧日志,释放磁盘空间
- 在高并发场景下,考虑使用异步日志队列

## 故障排查

### 日志文件未创建

1. 检查日志目录权限
2. 检查磁盘空间
3. 检查 PHP 错误日志

### 日志写入失败

1. 检查文件权限
2. 检查 SELinux 设置
3. 检查磁盘配额

## 相关类

- `ECTouchException`: 基础异常类
- `DatabaseException`: 数据库异常
- `FileException`: 文件操作异常
- `ValidationException`: 验证异常
- `ConfigException`: 配置异常

## 版本要求

- PHP >= 8.4.0
- 需要文件系统写入权限

## 许可证

与 ECTouch 系统相同的许可证
