<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CategoryModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 分类模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CategoryModel extends BaseModel {

    protected $table = 'category';

    /**
     * 获得分类的信息
     *
     * @param integer $cat_id 
     *
     * @return void
     */
    function get_cat_info($cat_id) {
        return $this->row('SELECT cat_name, keywords, cat_desc, style, grade, filter_attr, parent_id FROM ' . $this->pre . "category WHERE cat_id = '$cat_id'");
    }

    /**
     * 根据id获取获得分类
     *
     * @param integer $cat_id 
     *
     * @return void
     */
    function get_cat_list($cat_id = 0) {
        return $this->query('SELECT * FROM ' . $this->pre . "category WHERE is_show = '1' and parent_id = '$cat_id'");
    }

    /**
     *
     * @access private
     * @param string $children 
     * @param unknown $brand 
     */
    function category_get_count($children,$brand, $type, $min, $max, $ext, $keyword) {
        
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND " . "g.is_delete = 0 ";
        if ($keyword != '') {
            $where .= " AND (( 1 " . $keyword . " ) ) ";
        } else {
            $where.=" AND ($children OR " . model('Goods')->get_extension_goods($children) . ') ';
        }
        if ($type) {
            switch ($type) {
                case 'best':
                    $where .= ' AND g.is_best = 1';
                    break;
                case 'new':
                    $where .= ' AND g.is_new = 1';
                    break;
                case 'hot':
                    $where .= ' AND g.is_hot = 1';
                    break;
                case 'promotion':
                    $time = gmtime();
                    $where .= " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                    break;
                default:
                    $where .= '';
            }
        }
        if ($this->brand > 0) {
            $where .= "AND g.brand_id=$this->brand ";
        }
        if ($min > 0) {
            $where .= " AND g.shop_price >= $min ";
        }
        if ($max > 0) {
            $where .= " AND g.shop_price <= $max";
        }
        
      
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' . ' LEFT JOIN ' . $this->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where $ext ";
        $res = $this->row($sql);
        return $res['count'];
    }

    /**
     * 获得指定分类下的推荐商品
     *
     * @access  public
     * @param   string      $type       推荐类型，可以是 best, new, hot, promote
     * @param   string      $cats       分类的ID
     * @param   integer     $brand      品牌的ID
     * @param   integer     $min        商品价格下限
     * @param   integer     $max        商品价格上限
     * @param   string      $ext        商品扩展查询
     * @return  array
     */
    function get_category_recommend_goods($type = '', $cats = '', $brand = 0, $min = 0, $max = 0, $ext = '') {
        $brand_where = ($brand > 0) ? " AND g.brand_id = '$brand'" : '';

        $price_where = ($min > 0) ? " AND g.shop_price >= $min " : '';
        $price_where .= ($max > 0) ? " AND g.shop_price <= $max " : '';

        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name ' .
                'FROM ' . $this->pre . 'goods AS g ' .
                'LEFT JOIN ' . $this->pre . 'brand AS b ON b.brand_id = g.brand_id ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' . $brand_where . $price_where . $ext;
        $num = 0;
        $type2lib = array('best' => 'recommend_best', 'new' => 'recommend_new', 'hot' => 'recommend_hot', 'promote' => 'recommend_promotion');
        $num = model('Common')->get_library_number($type2lib[$type]);
        switch ($type) {
            case 'best':
                $sql .= ' AND is_best = 1';
                break;
            case 'new':
                $sql .= ' AND is_new = 1';
                break;
            case 'hot':
                $sql .= ' AND is_hot = 1';
                break;
            case 'promote':
                $time = gmtime();
                $sql .= " AND is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time'";
                break;
        }

        if (!empty($cats)) {
            $sql .= " AND (" . $cats . " OR " . model('goods')->get_extension_goods($cats) . ")";
        }

        $order_type = C('recommend_order');
        $sql .= ($order_type == 0) ? ' ORDER BY g.sort_order, g.last_update DESC' : ' ORDER BY RAND() ' . ' LIMIT ' . $num;
        $res = $this->query($sql);
        $idx = 0;
        $goods = array();
        foreach ($res as $key => $value) {
            if ($value['promote_price'] > 0) {
                $promote_price = bargain_price($value['promote_price'], $value['promote_start_date'], $value['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $value['goods_id'];
            $goods[$idx]['name'] = $value['goods_name'];
            $goods[$idx]['brief'] = $value['goods_brief'];
            $goods[$idx]['brand_name'] = $value['brand_name'];
            $goods[$idx]['short_name'] = C('goods_name_length') > 0 ?
                    sub_str($value['goods_name'], C('goods_name_length')) : $value['goods_name'];
            $goods[$idx]['market_price'] = price_format($value['market_price']);
            $goods[$idx]['shop_price'] = price_format($value['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($value['goods_id'], $value['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($value['goods_id'], $value['goods_img']);
            $goods[$idx]['url'] = url('goods/index', array('id' => $value['goods_id']));

            $goods[$idx]['short_style_name'] = add_style($goods[$idx]['short_name'], $value['goods_name_style']);
            $idx++;
        }
        return $goods;
    }

    /**
     * 获得指定分类的所有上级分类
     *
     * @access  public
     * @param   integer $cat    分类编号
     * @return  array
     */
    function get_parent_cats($cat) {
        if ($cat == 0) {
            return array();
        }
        $sql = 'SELECT cat_id, cat_name, parent_id FROM ' . $this->pre . 'category';
        $arr = $this->query($sql);
        if (empty($arr)) {
            return array();
        }

        $index = 0;
        $cats = array();

        while (1) {
            foreach ($arr AS $row) {
                if ($cat == $row['cat_id']) {
                    $cat = $row['parent_id'];

                    $cats[$index]['cat_id'] = $row['cat_id'];
                    $cats[$index]['cat_name'] = $row['cat_name'];

                    $index++;
                    break;
                }
            }

            if ($index == 0 || $cat == 0) {
                break;
            }
        }
        return $cats;
    }

    /**
     * 取得最近的上级分类的grade值
     *
     * @access  public
     * @param   int     $cat_id    //当前的cat_id
     *
     * @return int
     */
    function get_parent_grade($cat_id) {
        static $res = NULL;

        if ($res === NULL) {
            $data = read_static_cache('cat_parent_grade');
            if ($data === false) {
                $sql = "SELECT parent_id, cat_id, grade " .
                        " FROM " . $this->pre . 'category';
                $res = M()->query($sql);
                write_static_cache('cat_parent_grade', $res);
            } else {
                $res = $data;
            }
        }

        if (!$res) {
            return 0;
        }

        $parent_arr = array();
        $grade_arr = array();

        foreach ($res as $val) {
            $parent_arr[$val['cat_id']] = $val['parent_id'];
            $grade_arr[$val['cat_id']] = $val['grade'];
        }

        while ($parent_arr[$cat_id] > 0 && $grade_arr[$cat_id] == 0) {
            $cat_id = $parent_arr[$cat_id];
        }

        return $grade_arr[$cat_id];
    }

    /* 获得指定商品分类的所有分类
     * by Leah
     */

    function get_parent_id_tree($parent_id) {
        $three_c_arr = array();
        $sql = 'SELECT count(*) as count FROM ' . $this->pre . "category WHERE parent_id = '$parent_id' AND is_show = 1 ";
        $res = $this->row($sql);

        if ($res['count']) {
            $child_sql = 'SELECT cat_id, cat_name, parent_id, is_show ' .
                    'FROM ' . $this->pre .
                    "category WHERE parent_id = '$parent_id' AND is_show = 1 ORDER BY sort_order ASC, cat_id ASC";
            $res = $this->query($child_sql);
            foreach ($res AS $row) {
                if ($row['is_show']) {
                    $three_c_arr[$row['cat_id']]['id'] = $row['cat_id'];
                    $three_c_arr[$row['cat_id']]['name'] = $row['cat_name'];
                    $three_c_arr[$row['cat_id']]['url'] = url('category/index', array('id' => $row['cat_id']));
                }
            }
        }
        return $three_c_arr;
    }

    /**
     * 获得指定分类下的商品
     *
     * @access  public
     * @param   integer     $cat_id     分类ID
     * @param   integer     $num        数量
     * @param   string      $from       来自web/wap的调用
     * @param   string      $order_rule 指定商品排序规则
     * @return  array
     */
    function assign_cat_goods($cat_id, $num = 0, $from = 'web', $order_rule = '') {
        $children = get_children($cat_id);

        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.promote_price, promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
                "FROM " . $this->pre . 'goods AS g ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND ' .
                'g.is_delete = 0 AND (' . $children . 'OR ' . model('Goods')->get_extension_goods($children) . ') ';

        $order_rule = empty($order_rule) ? 'ORDER BY g.sort_order, g.goods_id DESC' : $order_rule;
        $sql .= $order_rule;
        if ($num > 0) {
            $sql .= ' LIMIT ' . $num;
        }
        $res = $this->query($sql);

        $goods = array();
        foreach ($res AS $idx => $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            } else {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['short_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = url('goods/index', array('id' => $row['goods_id']));
        }
        return $goods;
    }
	
	/**
     * 获得分类下的小图标
     * @param  integer $cat_id 
     * @return void       
     */
    function get_cat_image($cat_id){ 
        $cats = $this->row('SELECT cat_image FROM ' . $this->pre . "touch_category WHERE cat_id = '$cat_id'");
        return $cats['cat_image'];
    }

}
