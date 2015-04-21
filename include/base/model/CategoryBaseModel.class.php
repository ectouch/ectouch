<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CategoryBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 分类基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CategoryBaseModel extends BaseModel {

    /**
     * 获得指定分类同级的所有分类以及该分类下的子分类
     *
     * @access  public
     * @param   integer     $cat_id     分类编号
     * @return  array
     */
    function get_categories_tree($cat_id = 0) {
        if ($cat_id > 0) {
            $sql = 'SELECT parent_id FROM ' . $this->pre . "category WHERE cat_id = '$cat_id'";
            $result = $this->row($sql);
            $parent_id = $result['parent_id'];
        } else {
            $parent_id = 0;
        }

        /*
          判断当前分类中全是是否是底级分类，
          如果是取出底级分类上级分类，
          如果不是取当前分类及其下的子分类
         */
        $sql = 'SELECT count(*) FROM ' . $this->pre . "category WHERE parent_id = '$parent_id' AND is_show = 1 ";
        if ($this->row($sql) || $parent_id == 0) {
            /* 获取当前分类及其子分类 */
            $sql = 'SELECT c.cat_id,c.cat_name,c.parent_id,c.is_show,t.cat_image ' .
                    'FROM ' . $this->pre . 'category as c ' .
                    'left join ' . $this->pre . 'touch_category as t on t.cat_id = c.cat_id ' .
                    "WHERE c.parent_id = '$parent_id' AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC";

            $res = $this->query($sql);

            foreach ($res AS $row) {
                if ($row['is_show']) {
                    $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                    $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                    $cat_arr[$row['cat_id']]['cat_image'] = get_image_path(0, $row['cat_image'],false);
                    $cat_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));

                    if (isset($row['cat_id']) != NULL) {
                        $cat_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
                    }
                }
            }
        }
        if (isset($cat_arr)) {
            return $cat_arr;
        }
    }

    function get_child_tree($tree_id = 0) {
        $three_arr = array();
        $sql = 'SELECT count(*) FROM ' . $this->pre . "category WHERE parent_id = '$tree_id' AND is_show = 1 ";
        if ($this->row($sql) || $tree_id == 0) {
            $child_sql = 'SELECT c.cat_id, c.cat_name, c.parent_id, c.is_show, t.cat_image ' .
                    'FROM ' . $this->pre . 'category as c ' .
                    'left join ' . $this->pre . 'touch_category as t on t.cat_id = c.cat_id ' .
                    "WHERE c.parent_id = '$tree_id' AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC";
            $res = $this->query($child_sql);
            foreach ($res AS $row) {
                if ($row['is_show'])
                    $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $three_arr[$row['cat_id']]['cat_image'] = get_image_path(0,$row['cat_image'],false);
                $three_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));

                if (isset($row['cat_id']) != NULL) {
                    $three_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
                }
            }
        }
        return $three_arr;
    }

    /**
     * 获取一级分类信息
     */
    function get_top_category() {
        $sql = 'SELECT c.cat_id,c.cat_name,c.parent_id,c.is_show,t.cat_image ' .
                'FROM ' . $this->pre . 'category as c ' .
                'left join ' . $this->pre . 'touch_category as t on t.cat_id = c.cat_id ' .
                "WHERE c.parent_id = 0 AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC";

        $res = $this->query($sql);

        foreach ($res AS $row) {
            if ($row['is_show']) {
                $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $cat_arr[$row['cat_id']]['cat_image'] = get_image_path(0,$row['cat_image'],false);
                $cat_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));
            }
        }
        return $cat_arr;
    }

    /**
     * 调用当前分类的销售排行榜
     *
     * @access  public
     * @param   string  $cats   查询的分类
     * @return  array
     */
    function get_top10($cats = '') {
        $cats = get_children($cats);
        $where = !empty($cats) ? "AND ($cats OR " . model('Goods')->get_extension_goods($cats) . ") " : '';

        /* 排行统计的时间 */
        switch (C('top10_time')) {
            case 1: // 一年
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 365 * 86400) . "'";
                break;
            case 2: // 半年
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 180 * 86400) . "'";
                break;
            case 3: // 三个月
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 90 * 86400) . "'";
                break;
            case 4: // 一个月
                $top10_time = "AND o.order_sn >= '" . date('Ymd', gmtime() - 30 * 86400) . "'";
                break;
            default:
                $top10_time = '';
        }

        $sql = 'SELECT g.goods_id, g.goods_name, g.shop_price, g.goods_thumb, SUM(og.goods_number) as goods_number ' .
                'FROM ' . $this->pre . 'goods AS g, ' .
                $this->pre . 'order_info AS o, ' .
                $this->pre . 'order_goods AS og ' .
                "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 $where $top10_time ";
        //判断是否启用库存，库存数量是否大于0
        if (C('use_storage') == 1) {
            $sql .= " AND g.goods_number > 0 ";
        }
        $sql .= ' AND og.order_id = o.order_id AND og.goods_id = g.goods_id ' .
                "AND (o.order_status = '" . OS_CONFIRMED . "' OR o.order_status = '" . OS_SPLITED . "') " .
                "AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
                "AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " .
                'GROUP BY g.goods_id ORDER BY goods_number DESC, g.goods_id DESC LIMIT ' . C('top_number');

        $arr = $this->query($sql);

        for ($i = 0, $count = count($arr); $i < $count; $i++) {
            $arr[$i]['short_name'] = C('goods_name_length') > 0 ?
                    sub_str($arr[$i]['goods_name'], C('goods_name_length')) : $arr[$i]['goods_name'];
            $arr[$i]['url'] = url('goods/index', array('id' => $arr[$i]['goods_id']));
            $arr[$i]['thumb'] = get_image_path($arr[$i]['goods_id'], $arr[$i]['goods_thumb'], true);
            $arr[$i]['price'] = price_format($arr[$i]['shop_price']);
        }

        return $arr;
    }

}
