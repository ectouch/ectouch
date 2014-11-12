<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ProductsBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 货品基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ProductsBaseModel extends BaseModel {

    /**
     * 取指定规格的货品信息
     *
     * @access      public
     * @param       string      $goods_id
     * @param       array       $spec_goods_attr_id
     * @return      array
     */
    function get_products_info($goods_id, $spec_goods_attr_id) {
        $return_array = array();

        if (empty($spec_goods_attr_id) || !is_array($spec_goods_attr_id) || empty($goods_id)) {
            return $return_array;
        }

        $goods_attr_array = $this->sort_goods_attr_id_array($spec_goods_attr_id);

        if (isset($goods_attr_array['sort'])) {
            $goods_attr = implode('|', $goods_attr_array['sort']);

            $sql = "SELECT * FROM " . $this->pre . "products WHERE goods_id = '$goods_id' AND goods_attr = '$goods_attr' LIMIT 0, 1";
            $return_array = $this->row($sql);
        }
        return $return_array;
    }

    /**
     * 取商品的下拉框Select列表
     *
     * @param       int      $goods_id    商品id
     *
     * @return  array
     */
    function get_good_products_select($goods_id) {
        $return_array = array();
        $products = $this->get_good_products($goods_id);

        if (empty($products)) {
            return $return_array;
        }

        foreach ($products as $value) {
            $return_array[$value['product_id']] = $value['goods_attr_str'];
        }

        return $return_array;
    }

    /**
     * 取商品的货品列表
     *
     * @param       mixed       $goods_id       单个商品id；多个商品id数组；以逗号分隔商品id字符串
     * @param       string      $conditions     sql条件
     *
     * @return  array
     */
    function get_good_products($goods_id, $conditions = '') {
        if (empty($goods_id)) {
            return array();
        }
        switch (gettype($goods_id)) {
            case 'integer':
                $_goods_id = "goods_id = '" . intval($goods_id) . "'";
                break;
            case 'string':
            case 'array':
                $_goods_id = db_create_in($goods_id, 'goods_id');
                break;
        }
        /* 取货品 */
        $sql = "SELECT * FROM " . $this->pre . "products WHERE $_goods_id $conditions";
        $result_products = $this->query($sql);

        /* 取商品属性 */
        $sql = "SELECT goods_attr_id, attr_value FROM " . $this->pre . "goods_attr WHERE $_goods_id";
        $result_goods_attr = $this->query($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value) {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }

        /* 过滤货品 */
        foreach ($result_products as $key => $value) {
            $goods_attr_array = explode('|', $value['goods_attr']);
            if (is_array($goods_attr_array)) {
                $goods_attr = array();
                foreach ($goods_attr_array as $_attr) {
                    $goods_attr[] = $_goods_attr[$_attr];
                }

                $goods_attr_str = implode('，', $goods_attr);
            }

            $result_products[$key]['goods_attr_str'] = $goods_attr_str;
        }

        return $result_products;
    }

    /**
     * 将 goods_attr_id 的序列按照 attr_id 重新排序
     *
     * 注意：非规格属性的id会被排除
     *
     * @access      public
     * @param       array       $goods_attr_id_array        一维数组
     * @param       string      $sort                       序号：asc|desc，默认为：asc
     *
     * @return      string
     */
    function sort_goods_attr_id_array($goods_attr_id_array, $sort = 'asc') {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        //重新排序
        $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " . $this->pre . "attribute AS a
            LEFT JOIN " . $this->pre . "goods_attr AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
        $row = $this->query($sql);

        $return_arr = array();
        foreach ($row as $value) {
            $return_arr['sort'][] = $value['goods_attr_id'];

            $return_arr['row'][$value['goods_attr_id']] = $value;
        }

        return $return_arr;
    }

}
