<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BrandBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 品牌基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BrandBaseModel extends BaseModel {

    /**
     * 获得指定品牌的详细信息
     *
     * @access  private 
     * @param   integer $id
     * @return  void
     */
    function get_brand_info($id) {
        $sql = 'SELECT b.* , tb.brand_banner FROM ' . $this->pre . "brand as b LEFT JOIN " . $this->pre . "touch_brand as tb ON b.brand_id = tb.brand_id WHERE b.brand_id = '$id'";
        $res = $this->row($sql);
        $brand_logo = strtolower(substr($res ['brand_logo'], 0, '4')) == 'http' ? $res ['brand_logo'] : $base_url . 'data/brandlogo/' . $res ['brand_logo'];
        $brand_banner = strtolower(substr($res ['brand_banner'], 0, '4')) == 'http' ? $res ['brand_banner'] : $base_url . 'data/brand_banner/' . $res ['brand_banner'];

        $res['brand_logo'] = get_banner_path($res ['brand_logo']);
        $res['brand_banner'] = get_banner_path($res ['brand_banner']);

        return $res;
    }

}
