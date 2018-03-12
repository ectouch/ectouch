<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ExchangeModel.php
 * ----------------------------------------------------------------------------
 * 功能描述：积分商城模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ExchangeModel extends BaseModel
{

    /**
     * 获得分类下的商品
     *
     * @access  public
     * @param   string  $children
     * @return  array
     */
    public function exchange_get_goods($children, $min, $max, $ext, $size, $page, $sort, $order)
    {
        $display = $GLOBALS['display'];
        $where = "eg.is_exchange = 1 AND g.is_delete = 0 AND " .
                "($children OR " . model('Goods')->get_extension_goods($children) . ')';

        if ($min > 0) {
            $where .= " AND eg.exchange_integral >= $min ";
        }

        if ($max > 0) {
            $where .= " AND eg.exchange_integral <= $max ";
        }

        /* 获得商品列表 */
        $start = ($page - 1) * $size;
        $sort = $sort == 'sales_volume' ? 'xl.sales_volume' : $sort;
        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.goods_name_style,g.click_count, eg.exchange_integral, ' .
                'g.goods_type, g.goods_brief, g.goods_thumb , g.goods_img, eg.is_hot ' .
                'FROM ' . $this->pre . 'exchange_goods AS eg LEFT JOIN  ' . $this->pre . 'goods AS g ' .
                'ON  eg.goods_id = g.goods_id ' . ' LEFT JOIN ' . $this->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' .
                " WHERE $where $ext ORDER BY $sort $order LIMIT $start ,$size ";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $key => $row) {
            $arr[$key]['goods_id'] = $row['goods_id'];
            if ($display == 'grid') {
                $arr[$key]['goods_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            } else {
                $arr[$key]['goods_name'] = $row['goods_name'];
            }
            $arr[$key]['name'] = $row['goods_name'];
            $arr[$key]['goods_brief'] = $row['goods_brief'];
            $arr[$key]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $arr[$key]['market_price'] = price_format($row ['market_price']);
            $arr[$key]['exchange_integral'] = $row['exchange_integral'];
            $arr[$key]['click_count'] = $row['click_count'];
            $arr[$key]['type'] = $row['goods_type'];
            $arr[$key]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$key]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$key]['url'] = url('exchange_goods', array('gid' => $row['goods_id']));
            $arr[$key]['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $arr[$key]['sales_count'] = model('GoodsBase')->get_sales_count($row['goods_id']); // 销售数量
            $arr[$key]['mysc'] = 0;
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION ['user_id']) {
                unset($where);
                // 用户自己有没有收藏过
                $where['goods_id'] = $row ['goods_id'];
                $where['user_id'] = $_SESSION ['user_id'];
                $rs = $this->model->table('collect_goods')->where($where)->count();
                $arr[$key]['mysc'] = $rs;
            }
        }
        return $arr;
    }

    /**
     * 获得积分兑换商品的详细信息
     *
     * @access  public
     * @param   integer     $goods_id
     * @return  void
     */
    public function get_exchange_goods_info($goods_id)
    {
        $time = gmtime();
        $sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, eg.exchange_integral, eg.is_exchange ' .
                'FROM ' . $this->pre . 'goods AS g ' .
                'LEFT JOIN ' . $this->pre . 'exchange_goods AS eg ON g.goods_id = eg.goods_id ' .
                'LEFT JOIN ' . $this->pre . 'category AS c ON g.cat_id = c.cat_id ' .
                'LEFT JOIN ' . $this->pre . 'brand AS b ON g.brand_id = b.brand_id ' .
                "WHERE g.goods_id = '$goods_id' AND g.is_delete = 0 " .
                'GROUP BY g.goods_id';

        $row = $this->row($sql);

        if ($row !== false) {
            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }

            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                    $row['goods_weight'] . L('kilogram') :
                    ($row['goods_weight'] * 1000) . L('gram');

            /* 修正上架时间显示 */
            $date_format = C('date_format');
            $row['add_time'] = local_date($date_format, $row['add_time']);

            /* 修正商品图片 */
            $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
            $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);
            $row['original_img'] = get_image_path($goods_id, $row['original_img'], true);
            $row['goods_brand_url'] = url('brand/goods_list', array('id' => $row['brand_id']));
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 获得分类下的商品总数
     *
     * @access  public
     * @param   string     $cat_id
     * @return  integer
     */
    public function get_exchange_goods_count($children, $min = 0, $max = 0, $ext = '')
    {
        $where = "eg.is_exchange = 1 AND g.is_delete = 0 AND ($children OR " . model('Goods')->get_extension_goods($children) . ')';


        if ($min > 0) {
            $where .= " AND eg.exchange_integral >= $min ";
        }

        if ($max > 0) {
            $where .= " AND eg.exchange_integral <= $max ";
        }

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre . 'exchange_goods AS eg, ' .
                $this->pre . "goods AS g WHERE eg.goods_id = g.goods_id AND $where $ext";
        
        /* 返回商品总数 */
        $res = $this->row($sql);
        return $res['count'];
    }
}
