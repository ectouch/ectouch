<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ActivityModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 优惠活动模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ActivityModel extends BaseModel {

    /**
     * 获取优惠活动的信息和活动banner
     * @param unknown $size
     * @param unknown $page
     * @return Ambigous <multitype:, type, string, unknown>
     */
    function get_activity_info($size, $page) {
        $start = ($page - 1) * $size;
        $sql = 'SELECT f.* , a.act_banner' . ' FROM ' . $this->pre . 'favourable_activity f LEFT JOIN ' . $this->pre . 'touch_activity a on a.act_id = f.act_id ' . " ORDER BY f.sort_order ASC, f.end_time DESC LIMIT $start , $size";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            $arr[$row['act_id']]['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
            $arr[$row['act_id']]['end_time'] = local_date('Y-m-d H:i', $row['end_time']);
            $arr[$row['act_id']]['url'] = url('activity/goods_list', array('id' => $row['act_id']));
            $arr[$row['act_id']]['act_name'] = $row['act_name'];
            $arr[$row['act_id']]['act_id'] = $row['act_id'];
            $arr[$row['act_id']]['act_banner'] = get_banner_path($row['act_banner']);
        }
        return $arr;
    }

    function category_get_count($children, $brand, $goods, $min, $max, $ext) {

        $display = $GLOBALS['display'];
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND " . "g.is_delete = 0 ";
        if ($children) {
            $where .= " AND ($children OR " . model('Goods')->get_extension_goods($children) . ')';
        }
        if ($brand) {
            $where .= " AND $brand ";
        }
        if ($goods) {
            $where .= " AND $goods";
        }
        if ($min > 0) {
            $where .= " AND g.shop_price >= $min ";
        }
        if ($max > 0) {
            $where .= " AND g.shop_price <= $max ";
        }
        //echo $where;
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' . ' LEFT JOIN ' . $this->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where $ext ";
        $res = $this->row($sql);
        return $res['count'];
    }

    /**
     * 获得分类下的商品
     * @param unknown $children
     * @param unknown $brand
     * @param unknown $goods
     * @param unknown $size
     * @param unknown $page
     * @param unknown $sort
     * @param unknown $order
     * @return multitype:
     */
    function category_get_goods($children, $brand, $goods, $size, $page, $sort, $order) {
        $display = $GLOBALS['display'];
        $children = $children ? 'AND (' . $children . ' OR ' . Model('Goods')->get_extension_goods($children) . ')' : '';
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 " . $children . " AND g.is_delete = 0 ";
        if ($brand) {
            $where .= " AND $brand ";
        }
        if ($goods) {
            $where .= " AND $goods ";
        }
        /* 获得商品列表 */
        $start = ($page - 1) * $size;
        $sort = $sort == 'sales_volume' ? 'xl.sales_volume' : $sort;
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " . 'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' . 'FROM ' . $this->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' . 'LEFT JOIN ' . $this->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where ORDER BY $sort $order LIMIT $start , $size";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0) {
                $watermark_img = "watermark_promote_small";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new_small";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best_small";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot_small';
            }

            if ($watermark_img != '') {
                $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
            }

            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            if ($display == 'grid') {
                $arr[$row['goods_id']]['goods_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            } else {
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            }
            $arr[$row['goods_id']]['name'] = $row['goods_name'];
            $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
            $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['type'] = $row['goods_type'];
            $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['url'] = url('goods/index', array(
                'id' => $row['goods_id']
            ));
            $arr[$row['goods_id']]['sales_count'] = $this->get_sales_volume($row['goods_id']);
            $arr[$row['goods_id']]['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $arr[$row['goods_id']]['mysc'] = 0;
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION['user_id']) {
                unset($where);
                // 用户自己有没有收藏过
                $where['goods_id'] = $row['goods_id'];
                $where['user_id'] = $_SESSION['user_id'];
                $rs = $this->model->table('collect_goods')
                        ->where($where)
                        ->count();
                $arr[$row['goods_id']]['mysc'] = $rs;
            }
            $arr[$row['goods_id']]['promotion'] = model('GoodsBase')->get_promotion_show($row['goods_id']);
			$arr[$row['goods_id']]['comment_count'] = model('Comment')->get_goods_comment($row['goods_id'], 0);  //商品总评论数量 
            $arr[$row['goods_id']]['favorable_count'] = model('Comment')->favorable_comment($row['goods_id'], 0);  //获得商品好评百分比
        }
        return $arr;
    }

    /**
     * 月销量
     * @param unknown $goods_id
     * @return number
     */
    private function get_sales_volume($goods_id) {
        $last_month = local_strtotime('-1 months'); // 前一个月
        $now_time = gmtime(); // 当前时间
        $sql = "select sum(goods_number) as sum from " . $this->pre . "order_goods AS g ," . $this->pre . "order_info AS o WHERE o.order_id=g.order_id and g.goods_id = " . $goods_id . " and o.pay_status=2 and o.add_time >= " . $last_month . " and o.add_time <= " . $now_time . " group by g.goods_id";
        $res = $this->row($sql);
        return intval($res['sum']);
    }
	
	    /**
     * 获取优惠活动的信息和活动 数量
     */
    function get_activity_count() {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre . 'favourable_activity f LEFT JOIN ' . $this->pre . 'touch_activity a on a.act_id = f.act_id ';
        $res = $this->row($sql);
        $count = $res['count'] ? $res['count'] : 0;
        return $count;
    }

}
