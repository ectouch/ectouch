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
        $res['brand_logo'] = get_banner_path($res ['brand_logo']);
        $res['brand_banner'] = get_banner_path($res ['brand_banner']);

        return $res;
    }

    /**
     * 取得品牌列表
     * @return array 品牌列表 id => name
     */
    function get_brand_list() {
        $sql = 'SELECT brand_id, brand_name FROM ' . $this->pre . 'brand ORDER BY sort_order';
        $res = $this->query($sql);

        $brand_list = array();
        foreach ($res AS $row) {
            $brand_list[$row['brand_id']] = addslashes($row['brand_name']);
        }

        return $brand_list;
    }

}
