<?php

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

class Model {

    public $model = NULL;
    protected $db = NULL;
    protected $pre = NULL;
    protected $table = "";
    protected $ignoreTablePrefix = false;

    public function __construct($database = 'DB', $force = false) {
        $this->model = self::connect(C($database), $force);
        $this->db = $this->model->db;
        $this->pre = $this->model->pre;
    }

    static public function connect($config, $force = false) {
        static $model = NULL;
        if ($force == true || empty($model)) {
            $model = new EcModel($config);
        }
        return $model;
    }

    public function query($sql) {
        return $this->model->query($sql);
    }

    public function row($sql) {
        $data = $this->query($sql);
        return isset($data[0]) ? $data[0] : false;
    }

    public function gecol($condition = '', $field = '', $order = '') {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->getCol();
    }

    public function find($condition = '', $field = '', $order = '') {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
    }

    public function field($field = '', $condition = '', $order = '') {
        $result = $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
        return $result[$field];
    }

    public function select($condition = '', $field = '', $order = '', $limit = '') {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->limit($limit)->select();
    }

    public function count($condition = '') {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->count();
    }

    public function insert($data = array()) {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->insert();
    }

    public function update($condition, $data = array()) {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->where($condition)->update();
    }

    public function delete($condition) {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->delete();
    }

    public function getFields() {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->getFields();
    }

    public function getSql() {
        return $this->model->getSql();
    }

    public function escape($value) {
        return $this->model->escape($value);
    }

    public function cache($time = 0) {
        $this->model->cache($time);
        return $this;
    }

}
