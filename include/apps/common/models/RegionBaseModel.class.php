<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：RegionBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 区域基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
 
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class RegionBaseModel extends BaseModel {

    protected $table = 'region';

    /**
     * 获得指定国家的所有省份
     * @param type $type
     * @param type $parent
     * @return type
     */
    public function get_regions($type = 0, $parent = 0) {
        $condition['region_type'] = $type;
        $condition['parent_id'] = $parent;
        return $this->select($condition, 'region_id, region_name');
    }

    /**
     * 获取地区名称
     * @param type $id
     * @return type
     */
    public function get_region_name($id = 0) {
        $condition['region_id'] = $id;
        return $this->field('region_name', $condition);
    }

    /**
     * 获取地区列表的函数。
     * @access  public
     * @param   int     $region_id  上级地区id
     * @return  void
     */
    public function area_list($region_id = 0) {
        $area_arr = array();
        $condition['parent_id'] = $region_id;
        $list = $this->select($condition, '', 'region_id');
        if (is_array($list)) {
            foreach ($list as $vo) {
                $vo['type'] = ($vo['region_type'] == 0) ? L('country') : '';
                $vo['type'] .= ($vo['region_type'] == 1) ? L('province') : '';
                $vo['type'] .= ($vo['region_type'] == 2) ? L('city') : '';
                $vo['type'] .= ($vo['region_type'] == 3) ? L('cantonal') : '';
                $area_arr[] = $vo;
            }
        }
        return $area_arr;
    }

}
