<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BrandModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 品牌模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BrandModel extends BaseModel {

    /**
     * 获得品牌下的商品
     *
     * @access private
     * @param integer $brand_id 
     * @return array
     */
    function brand_get_goods($brand_id, $cate, $sort, $order, $size, $page) {
        $cate_where = ($cate > 0) ? 'AND ' . get_children($cate) : '';

        $start = ($page - 1) * $size;
        /* 获得商品列表 */
        $sort = $sort =='sales_volume'? 'xl.sales_volume': $sort;
        $sql = 'SELECT g.goods_id, g.goods_name,g.goods_number, g.market_price, g.shop_price AS org_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, " . 'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' . 'FROM ' . $this->pre . 'goods AS g ' . 'LEFT JOIN ' . $this->pre . 'touch_goods AS xl ' . "ON g.goods_id=xl.goods_id " . 'LEFT JOIN ' . $this->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.brand_id = '$brand_id' $cate_where" . "ORDER BY $sort $order LIMIT $start , $size";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            if ($GLOBALS['display'] == 'grid') {
                $arr[$row['goods_id']]['goods_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            } else {
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            }
            $arr[$row['goods_id']]['discount'] = $row['market_price'] > 0 ? (round((($promote_price > 0 ? $promote_price : $row['shop_price']) / $row['market_price']) * 10)) : 0;
            $arr[$row['goods_id']]['goods_number'] = $row['goods_number'];
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
            $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['url'] = build_uri('goods/index', array(
                'id' => $row['goods_id']
                    ), $row['goods_name']);
            $arr[$row['goods_id']]['sales_count'] = model('GoodsBase')->get_sales_count($row['goods_id']);
            $arr[$row['goods_id']]['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $arr[$row['goods_id']]['promotion'] = model('GoodsBase')->get_promotion_show($row['goods_id']);
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
        }

        return $arr;
    }

    /**
     * 获得品牌列表
     *
     * @global type $page_libs
     * @staticvar null $static_page_libs
     * @param type $cat 
     * @param type $app 
     * @param type $size 
     * @param type $page 
     * @return type
     */
    function get_brands($app = 'brand', $size, $page) {
        $start = ($page - 1) * $size;
        $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, b.brand_desc, t.brand_banner FROM " . $this->pre . "brand b LEFT JOIN  " . $this->pre . "touch_brand t ON t.brand_id = b.brand_id " . "WHERE is_show = 1 " . "GROUP BY b.brand_id , b.sort_order ASC LIMIT $start , $size";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            $arr[$row['brand_id']]['brand_name'] = $row['brand_name'];
            $arr[$row['brand_id']]['url'] = build_uri('brand/goods_list', array( 'id' => $row['brand_id']));
            $arr[$row['brand_id']]['brand_logo'] = get_banner_path($row['brand_logo']);
            $arr[$row['brand_id']]['brand_banner'] = get_banner_path($row['brand_banner']);
            $arr[$row['brand_id']]['brand_desc'] = htmlspecialchars($val['brand_desc'], ENT_QUOTES);
        }
        return $arr;
    }

}
