<?php

/**
 * ECTouch基础异常类
 * 
 * 提供上下文信息支持的异常基类,用于PHP 8.4升级后的现代异常处理
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

class ECTouchException extends Exception
{
    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常(用于异常链)
     * @param array $context 异常上下文信息(额外的调试信息)
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取异常上下文信息
     * 
     * 返回创建异常时提供的额外上下文信息,用于调试和日志记录
     * 
     * @return array 上下文信息数组
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
