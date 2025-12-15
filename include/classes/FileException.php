<?php

/**
 * 文件操作异常类
 * 
 * 用于处理文件操作相关的错误,包含文件路径和操作类型信息
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

class FileException extends ECTouchException
{
    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常(用于异常链)
     * @param string $filePath 相关的文件路径
     * @param string $operation 执行的操作类型(如: read, write, delete, copy等)
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly string $filePath = '',
        private readonly string $operation = ''
    ) {
        parent::__construct($message, $code, $previous, [
            'file_path' => $filePath,
            'operation' => $operation
        ]);
    }

    /**
     * 获取相关的文件路径
     * 
     * @return string 文件路径
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * 获取执行的操作类型
     * 
     * @return string 操作类型
     */
    public function getOperation(): string
    {
        return $this->operation;
    }
}
