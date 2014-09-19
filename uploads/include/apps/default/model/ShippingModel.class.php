<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShippingModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 配送模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ShippingModel extends BaseModel {

    /**
     * 取得配送方式信息
     * @param   int     $shipping_id    配送方式id
     * @return  array   配送方式信息
     */
    function shipping_info($shipping_id) {
        $sql = 'SELECT * FROM ' . $this->pre .
                "shipping WHERE shipping_id = '$shipping_id' " .
                'AND enabled = 1';

        return $this->row($sql);
    }

    /**
     * 取得已安装的配送方式
     * @return  array   已安装的配送方式
     */
    function shipping_list() {
        $sql = 'SELECT shipping_id, shipping_name ' .
                'FROM ' . $this->pre .
                'shipping WHERE enabled = 1';

        return $this->query($sql);
    }

    /**
     * 取得可用的配送方式列表
     * @param   array   $region_id_list     收货人地区id数组（包括国家、省、市、区）
     * @return  array   配送方式数组
     */
    function available_shipping_list($region_id_list) {
        $sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, s.support_cod, a.configure ' .
                'FROM ' . $this->pre . 'shipping AS s, ' .
                $this->pre . 'shipping_area AS a, ' .
                $this->pre . 'area_region AS r ' .
                'WHERE r.region_id ' . db_create_in($region_id_list) .
                ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1 ORDER BY s.shipping_order';

        return $this->query($sql);
    }

    /**
     * 取得某配送方式对应于某收货地址的区域信息
     * @param   int     $shipping_id        配送方式id
     * @param   array   $region_id_list     收货人地区id数组
     * @return  array   配送区域信息（config 对应着反序列化的 configure）
     */
    function shipping_area_info($shipping_id, $region_id_list) {
        $sql = 'SELECT s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, s.support_cod, a.configure ' .
                'FROM ' . $this->pre . 'shipping AS s, ' .
                $this->pre . 'shipping_area AS a, ' .
                $this->pre . 'area_region AS r ' .
                "WHERE s.shipping_id = '$shipping_id' " .
                'AND r.region_id ' . db_create_in($region_id_list) .
                ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1';
        $row = $this->row($sql);

        if (!empty($row)) {
            $shipping_config = unserialize_config($row['configure']);
            if (isset($shipping_config['pay_fee'])) {
                if (strpos($shipping_config['pay_fee'], '%') !== false) {
                    $row['pay_fee'] = floatval($shipping_config['pay_fee']) . '%';
                } else {
                    $row['pay_fee'] = floatval($shipping_config['pay_fee']);
                }
            } else {
                $row['pay_fee'] = 0.00;
            }
        }

        return $row;
    }

}
