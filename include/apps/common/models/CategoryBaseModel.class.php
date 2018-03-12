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

class CategoryBaseModel extends BaseModel
{

    /**
     * 获得指定分类同级的所有分类以及该分类下的子分类
     *
     * @access  public
     * @param   integer     $cat_id     分类编号
     * @return  array
     */
    public function get_categories_tree($cat_id = 0)
    {
        $data = read_static_cache('categories_tree');
        if ($data === false) {
            if ($cat_id > 0) {
                $sql = 'SELECT parent_id FROM ' . $this->pre . "category  WHERE cat_id = '$cat_id'";
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

                $sql = 'SELECT c.cat_id,c.cat_name,c.parent_id,c.is_show ' .
                        'FROM ' . $this->pre . 'category as c ' .
                        "WHERE c.parent_id = 0 AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC";

                $res = $this->query($sql);
                foreach ($res as $row) {
                    if ($row['is_show']) {
                        $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                        $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                        $cat_arr[$row['cat_id']]['img'] = empty($row['cat_img']) ? '':$row['cat_img'];
                        $cat_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));
                        if (isset($row['cat_id']) == isset($row['parent_id'])) {
                            $cat_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
                        }
                    }
                }
            }
            if (isset($cat_arr)) {
                write_static_cache('categories_tree', $cat_arr);
                return $cat_arr;
            }
        }
        return $data;
    }

    public function get_child_tree($tree_id = 0)
    {
        $three_arr = array();
        $sql = 'SELECT count(*) FROM ' . $this->pre . "category WHERE parent_id = '$tree_id' AND is_show = 1 ";
        if ($this->row($sql) || $tree_id == 0) {
            $child_sql = 'SELECT c.cat_id, c.cat_name, c.parent_id, c.is_show ' .
                    'FROM ' . $this->pre . 'category as c ' .
                    " WHERE c.parent_id = '$tree_id' AND c.is_show = 1 GROUP BY c.cat_id ORDER BY c.sort_order ASC, c.cat_id ASC";
            $res = $this->query($child_sql);
            foreach ($res as $row) {
                if ($row['is_show']) {
                    $three_arr[$row['cat_id']]['id'] = $row['cat_id'];
                    $three_arr[$row['cat_id']]['name'] = $row['cat_name'];
                    //如果上传分类图片则使用上传的图片，没有上传分类图片使用默认的分类图片
                    if ($row['cat_id']) {
                        $sql = 'SELECT t.cat_image' .' FROM ' . $this->pre . 'touch_category as t ' ." WHERE t.cat_id = ".$row['cat_id'] . " group by id desc limit 0 , 1";
                        $imgres = $this->model->getRow($sql);
                        if (!empty($imgres['cat_image'])) {
                            $row['cat_img'] =$imgres['cat_image'];
                        } else {
                            $row['cat_img'] =  $this->get_cat_goods_img($row['cat_id']);
                        }
                    }
                    $three_arr[$row['cat_id']]['img'] = get_image_path(0, $row['cat_img'], false);
                    $three_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));
                }
                if (isset($row['cat_id']) != null) {
                    $three_arr[$row['cat_id']]['cat_id'] = $this->get_child_tree($row['cat_id']);
                }
            }
        }
        return $three_arr;
    }

    /**
     * 调用指定分类下子分类的商品的缩略图，条件:精品，排序规则:按推荐排序，默认按最大商品ID
     * @param  [type] $cat_id 　add by 20160204
     * @return [type]
     */
    public function get_cat_goods_img($cat_id)
    {
        $extension_goods_array = '';
        $sql = 'SELECT goods_id FROM ' . $this->model->pre. "goods_cat AS g WHERE g.cat_id  IN ('".$cat_id."')";
        $res = $this->model->query($sql);
        if ($res !== false) {
            $arr = array();
            foreach ($res as $key => $value) {
                $arr[] = $value['goods_id'];
            }
        }
        $extension_goods_array =  db_create_in($arr, 'g.goods_id');
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND " . "g.is_delete = 0 ";
        if ($cat_id !== 0) {
            $where .= "AND(g.cat_id = $cat_id OR " .$extension_goods_array .")";
            //$where .= "AND(g.cat_id = $cat_id OR " .model('Goods')->get_extension_goods($cat_id) .")";
        }
        /* 获得商品列表 */
        $sql = 'SELECT g.goods_id,  g.goods_thumb as cat_img , g.goods_img ' . 'FROM ' . $this->model->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->model->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' .
            ' LEFT JOIN ' . $this->model->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id " . "WHERE $where GROUP BY g.goods_id ORDER BY g.sort_order DESC";
        $res = $this->model->query($sql);
        return $res[0]['cat_img'];
    }
    /**
     * 获取一级分类信息
     */
    public function get_top_category()
    {
        $sql = 'SELECT c.cat_id,c.cat_name,c.parent_id,c.is_show,t.cat_image ' .
                'FROM ' . $this->pre . 'category as c ' .
                'left join ' . $this->pre . 'touch_category as t on t.cat_id = c.cat_id ' .
                "WHERE c.parent_id = 0 AND c.is_show = 1 ORDER BY c.sort_order ASC, c.cat_id ASC";

        $res = $this->query($sql);

        foreach ($res as $row) {
            if ($row['is_show']) {
                $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
                $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
                $cat_arr[$row['cat_id']]['cat_image'] = get_image_path(0, $row['cat_image'], false);
                $cat_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));
            }
        }
        return $cat_arr;
    }
    
    /**
     * 获取品牌二级分类
     */
    public function get_cagtegory_goods($cat_id)
    {
        $sql = "SELECT a.*,b.cat_image FROM  ".$this->pre."category AS a LEFT JOIN ".$this->pre."touch_category AS b ON a.cat_id = b.cat_id WHERE a.is_show = 1 AND a.parent_id = ".$cat_id;
        $cate = $this->query($sql);
        if ($cate) {
            foreach ($cate as $key=>$val) {
                $cate[$key]['cat_image'] = get_image_path($val['goods_id'], $val['goods_img']); //设置默认图片
            }
        }
        return $cate;
    }
    
    /**
     * 调用当前分类的销售排行榜
     *
     * @access  public
     * @param   string  $cats   查询的分类
     * @return  array
     */
    public function get_top10($cats = '')
    {
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
