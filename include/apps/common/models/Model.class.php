<?php

declare(strict_types=1);

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：Model.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class Model
{
    public ?EcModel $model = null;
    protected mixed $db = null;
    protected ?string $pre = null;
    protected string $table = "";
    protected bool $ignoreTablePrefix = false;

    public function __construct(string $database = 'DB', bool $force = false)
    {
        $this->model = self::connect(C($database), $force);
        $this->db = $this->model->db;
        $this->pre = $this->model->pre;
    }

    public static function connect(array|string $config, bool $force = false): EcModel
    {
        static $model = null;
        if ($force == true || empty($model)) {
            $model = new EcModel($config);
        }
        return $model;
    }

    public function query(string $sql): array|false
    {
        return $this->model->query($sql);
    }

    public function row(string $sql): array|false
    {
        $data = $this->query($sql);
        return isset($data[0]) ? $data[0] : false;
    }

    public function gecol(string|array $condition = '', string $field = '', string $order = ''): array|false
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->getCol();
    }

    public function find(string|array $condition = '', string $field = '', string $order = ''): array|false
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
    }

    public function field(string $field = '', string|array $condition = '', string $order = ''): mixed
    {
        $result = $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
        return $result[$field];
    }

    public function select(string|array $condition = '', string $field = '', string $order = '', string|int $limit = ''): array|false
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->limit($limit)->select();
    }

    public function count(string|array $condition = ''): int
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->count();
    }

    public function insert(array $data = []): int|bool
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->insert();
    }

    public function update(string|array $condition, array $data = []): int|bool
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->where($condition)->update();
    }

    public function delete(string|array $condition): int|bool
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->delete();
    }

    public function getFields(): array|false
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->getFields();
    }

    public function getSql(): string
    {
        return $this->model->getSql();
    }

    public function escape(string $value): string
    {
        return $this->model->escape($value);
    }

    public function cache(int $time = 0): self
    {
        $this->model->cache($time);
        return $this;
    }
}
