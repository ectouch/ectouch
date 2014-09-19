<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ShippingBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 配送基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');
 
class ShippingBaseModel extends BaseModel {

    protected $table = 'shipping';

    /**
     * 获得配送区域中指定的配送方式的配送费用的计算参数
     * @access  public
     * @param   int     $area_id        配送区域ID
     * @return array;
     */
    public function get_shipping_config($area_id) {
        $this->table = 'shipping_area';
        /* 获得配置信息 */
        $cfg = array();
        $condition['shipping_area_id'] = $area_id;
        $cfg = $this->field('configure', $condition);
        if (!empty($cfg)) {
            /* 拆分成配置信息的数组 */
            $cfg = unserialize($cfg);
        }
        return $cfg;
    }

}
