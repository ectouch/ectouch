<?php

/**
 * 数据库异常类
 * 
 * 用于处理数据库操作相关的错误,包含SQL语句和数据库错误信息
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

class DatabaseException extends ECTouchException
{
    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常(用于异常链)
     * @param string $sql 导致错误的SQL语句
     * @param string $error 数据库返回的错误信息
     */
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

    /**
     * 获取导致错误的SQL语句
     * 
     * @return string SQL语句
     */
    public function getSql(): string
    {
        return $this->sql;
    }

    /**
     * 获取数据库返回的错误信息
     * 
     * @return string 数据库错误信息
     */
    public function getError(): string
    {
        return $this->error;
    }
}
