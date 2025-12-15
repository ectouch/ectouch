<?php

/**
 * ErrorLogger 使用示例
 * 
 * 本文件展示如何在ECTouch系统中使用ErrorLogger类
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

// ============================================================================
// 示例1: 基本使用 - 在应用程序启动时初始化ErrorLogger
// ============================================================================

// 在 include/bootstrap.php 或应用入口文件中初始化
$errorLogger = new ErrorLogger(ROOT_PATH . 'data/logs');

// 设置全局异常处理器
set_exception_handler(function(Throwable $e) use ($errorLogger) {
    // 记录异常到日志
    $errorLogger->log($e);
    
    // 根据环境显示不同的错误信息
    if (defined('APP_DEBUG') && APP_DEBUG) {
        // 开发环境:显示详细错误
        echo "<h1>异常: " . get_class($e) . "</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        // 生产环境:显示友好错误页面
        echo "<h1>系统错误</h1>";
        echo "<p>抱歉,系统遇到了一个错误。请稍后再试。</p>";
    }
});

// ============================================================================
// 示例2: 在数据库类中使用
// ============================================================================

class MySQL
{
    private ErrorLogger $logger;
    
    public function __construct(
        string $dbhost,
        string $dbuser,
        string $dbpw,
        string $dbname = '',
        ErrorLogger $logger = null
    ) {
        $this->logger = $logger ?? new ErrorLogger(ROOT_PATH . 'data/logs');
        // ... 其他初始化代码
    }
    
    public function query(string $sql): mysqli_result|bool
    {
        try {
            // 执行查询
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
            // 记录异常
            $this->logger->log($e);
            
            // 重新抛出或返回false,根据需要
            throw $e;
        }
    }
}

// ============================================================================
// 示例3: 在控制器中使用
// ============================================================================

class UserController
{
    private ErrorLogger $logger;
    
    public function __construct(ErrorLogger $logger)
    {
        $this->logger = $logger;
    }
    
    public function register(array $userData): bool
    {
        try {
            // 验证用户数据
            if (empty($userData['email'])) {
                throw new ValidationException(
                    message: '邮箱地址不能为空',
                    context: ['field' => 'email', 'value' => $userData['email'] ?? null]
                );
            }
            
            // 保存用户...
            return true;
            
        } catch (ValidationException $e) {
            // 记录验证错误
            $this->logger->log($e);
            
            // 返回错误给用户
            return false;
        }
    }
}

// ============================================================================
// 示例4: 记录自定义消息
// ============================================================================

function processOrder(int $orderId, ErrorLogger $logger): void
{
    // 记录信息级别的消息
    $logger->logMessage(
        "开始处理订单",
        "INFO",
        ['order_id' => $orderId, 'timestamp' => time()]
    );
    
    try {
        // 处理订单逻辑...
        
        // 记录成功
        $logger->logMessage(
            "订单处理成功",
            "INFO",
            ['order_id' => $orderId]
        );
        
    } catch (Exception $e) {
        // 记录错误
        $logger->logMessage(
            "订单处理失败: " . $e->getMessage(),
            "ERROR",
            ['order_id' => $orderId, 'error' => $e->getMessage()]
        );
        
        throw $e;
    }
}

// ============================================================================
// 示例5: 定期清理旧日志
// ============================================================================

// 在定时任务或维护脚本中
function cleanupLogs(): void
{
    $logger = new ErrorLogger(ROOT_PATH . 'data/logs');
    
    // 删除30天前的日志
    $deletedCount = $logger->cleanOldLogs(30);
    
    echo "已删除 {$deletedCount} 个旧日志文件\n";
}

// ============================================================================
// 示例6: 在API接口中使用
// ============================================================================

class ApiController
{
    private ErrorLogger $logger;
    
    public function __construct(ErrorLogger $logger)
    {
        $this->logger = $logger;
    }
    
    public function handleRequest(): array
    {
        try {
            // 处理API请求...
            return ['success' => true, 'data' => []];
            
        } catch (ECTouchException $e) {
            // 记录异常
            $this->logger->log($e);
            
            // 返回JSON错误响应
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }
}
