<?php

/**
 * 验证异常类
 * 
 * 用于处理数据验证相关的错误,包含验证失败的字段和详细错误信息
 * 
 * @package ECTouch
 * @version 8.4+
 */

declare(strict_types=1);

class ValidationException extends ECTouchException
{
    /**
     * 构造函数
     * 
     * @param string $message 异常消息
     * @param int $code 异常代码
     * @param Throwable|null $previous 前一个异常(用于异常链)
     * @param string $field 验证失败的字段名
     * @param array $errors 详细的验证错误信息数组
     * @param mixed $value 导致验证失败的值
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        private readonly string $field = '',
        private readonly array $errors = [],
        private readonly mixed $value = null
    ) {
        parent::__construct($message, $code, $previous, [
            'field' => $field,
            'errors' => $errors,
            'value' => $value
        ]);
    }

    /**
     * 获取验证失败的字段名
     * 
     * @return string 字段名
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * 获取详细的验证错误信息
     * 
     * @return array 验证错误数组
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取导致验证失败的值
     * 
     * @return mixed 验证失败的值
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
