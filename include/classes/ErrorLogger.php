<?php

declare(strict_types=1);

/**
 * ErrorLogger - 错误日志记录系统
 * 
 * 用于记录系统中的异常和错误信息到日志文件
 * 支持ECTouchException的上下文信息记录
 */
class ErrorLogger
{
    /**
     * 构造函数
     * 
     * @param string $logPath 日志文件存储路径
     */
    public function __construct(
        private readonly string $logPath
    ) {
        // 确保日志目录存在
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
    }

    /**
     * 记录异常到日志文件
     * 
     * @param Throwable $e 要记录的异常对象
     * @return void
     */
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

        // 如果是ECTouchException,添加上下文信息
        if ($e instanceof ECTouchException) {
            $context = $e->getContext();
            if (!empty($context)) {
                $logEntry .= "Context: " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n";
            }
        }

        // 写入日志文件,按日期分文件
        $logFile = $this->logPath . '/error_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * 记录错误消息到日志文件
     * 
     * @param string $message 错误消息
     * @param string $level 错误级别 (ERROR, WARNING, INFO)
     * @param array $context 附加上下文信息
     * @return void
     */
    public function logMessage(string $message, string $level = 'ERROR', array $context = []): void {
        $logEntry = sprintf(
            "[%s] [%s] %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );

        if (!empty($context)) {
            $logEntry .= "Context: " . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }

        $logEntry .= "\n";

        // 写入日志文件
        $logFile = $this->logPath . '/error_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取日志文件路径
     * 
     * @return string
     */
    public function getLogPath(): string {
        return $this->logPath;
    }

    /**
     * 清理旧日志文件
     * 
     * @param int $days 保留最近多少天的日志
     * @return int 删除的文件数量
     */
    public function cleanOldLogs(int $days = 30): int {
        $deletedCount = 0;
        $cutoffTime = time() - ($days * 86400);

        if (!is_dir($this->logPath)) {
            return 0;
        }

        $files = glob($this->logPath . '/error_*.log');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }
}
