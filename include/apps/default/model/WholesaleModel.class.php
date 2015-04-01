<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：Wholesale.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 批发
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class WholesaleModel extends BaseModel
{

    /**拍卖数量
     * @param $search_category
     * @param $search_keywords
     * @param $where
     * @return mixed
     */
    function wholesale_count($search_category, $search_keywords, $where)
    {
        /* 搜索 */
        /* 搜索类别 */
        if ($search_category) {
            $where .= " AND g.cat_id = '$search_category' ";
            $param['search_category'] = $search_category;
            $this->assign('search_category', $search_category);
        }
        /* 搜索商品名称和关键字 */
        if ($search_keywords) {
            $where .= " AND (g.keywords LIKE '%$search_keywords%'
                    OR g.goods_name LIKE '%$search_keywords%') ";
            $param['search_keywords'] = $search_keywords;
            $this->assign('search_keywords', $search_keywords);
        }

        /* 取得批发商品总数 */
        $sql = "SELECT COUNT(*) as count FROM " . $this->pre . "wholesale AS w, " . $this->model->pre . "goods AS g " . $where;
        $res = $this->row($sql);
        return $res['count'];

    }

    /**
     * 取得某页的批发商品
     * @param   int $size 每页记录数
     * @param   int $page 当前页
     * @param   string $where 查询条件
     * @return  array
     */
    function wholesale_list($size, $page, $where)
    {
        $list = array();

        $sql = "SELECT w.*, g.goods_thumb, g.goods_name as goods_name, g.shop_price, g.market_price " .
            "FROM " . $this->pre . "wholesale AS w, " .
            $this->pre . "goods AS g " . $where .
            " AND w.goods_id = g.goods_id limit " . ($page - 1) * $size . ',' . $size;
        $res = $this->query($sql);
        foreach ($res as $row) {
            $row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $row['goods_url'] = url('wholesale/info', array('id' => $row['goods_id']));
            $properties = model('Goods')->get_goods_properties($row['goods_id']);
            $row['goods_attr'] = $properties['pro'];
            $price_ladder = $this->get_price_ladder($row['goods_id']);
            $row['price_ladder'] = $price_ladder;
            $row['low_price'] = empty($price_ladder) ? price_format($row['shop_price']) : price_format($this->get_low_price($price_ladder));

            $list[] = $row;
        }
        return $list;
    }

    /**
     * 商品价格阶梯
     * @param   int $goods_id 商品ID
     * @return  array
     */
    function get_price_ladder($goods_id)
    {
        /* 显示商品规格 */
        $goods_attr_list = array_values(model('GoodsBase')->get_goods_attr($goods_id));
        $sql = "SELECT prices FROM " . $this->pre .
            "wholesale WHERE goods_id = " . $goods_id;
        $row = $this->row($sql);

        $arr = array();
        $_arr = unserialize($row['prices']);
        if (is_array($_arr)) {
            foreach (unserialize($row['prices']) as $key => $val) {
                // 显示属性
                if (!empty($val['attr'])) {
                    foreach ($val['attr'] as $attr_key => $attr_val) {
                        // 获取当前属性 $attr_key 的信息
                        $goods_attr = array();
                        foreach ($goods_attr_list as $goods_attr_val) {
                            if ($goods_attr_val['attr_id'] == $attr_key) {
                                $goods_attr = $goods_attr_val;
                                break;
                            }
                        }
                        // 重写商品规格的价格阶梯信息
                        if (!empty($goods_attr)) {
                            $arr[$key]['attr'][] = array(
                                'attr_id' => $goods_attr['attr_id'],
                                'attr_name' => $goods_attr['attr_name'],
                                'attr_val' => (isset($goods_attr['goods_attr_list'][$attr_val]) ? $goods_attr['goods_attr_list'][$attr_val] : ''),
                                'attr_val_id' => $attr_val
                            );
                        }
                    }
                }
                $price = !empty($val['qp_list']) ? $val['qp_list'][0]['price'] : '';
                // 显示数量与价格
                foreach ($val['qp_list'] as $index => $qp) {
                    $arr[$key]['qp_list'][$qp['quantity']] = price_format($qp['price']);
                    if ($qp['price'] <= $price) {
                        $arr[$key]['low_price'] = $qp['price'];
                    }
                }
            }
        }
        return $arr;
    }

    /**获取最低价格
     * @param $price_ladder
     * @return mixed
     */
    function  get_low_price($price_ladder)
    {
        $price = $price_ladder[0]['low_price'];
        foreach ($price_ladder as $value) {
            if ($value['low_price'] <= $price) {
                $price = $value['low_price'];
            }
        }
        return $price;
    }

    /**
     * 获取批发商品详情
     * @param $id
     * @return bool
     */
    function wholesale_info($id){
        $sql = "SELECT w.*, g.goods_img, g.goods_name as goods_name, g.shop_price, g.market_price ".
            "FROM " . $this->pre . "wholesale AS w " .
            "LEFT JOIN ".$this->pre."goods as g ON g.goods_id = w.goods_id WHERE w.enabled = 1 AND w.goods_id = ".$id;
        $row = $this->row($sql);

        $res['goods_id'] = $id;
        $res['goods_name'] = $row['goods_name'];
        $res['market_price'] = $row['market_price'];
        $res['goods_img'] = get_image_path($row['goods_id'], $row['goods_img'], true);
        $res['act_id']= $row['act_id'];
        $properties = model('Goods')->get_goods_properties($row['goods_id']);
        $res['goods_attr'] = $properties['pro'];
        $price_ladder = $this->get_price_ladder($row['goods_id']);
        $res['price_ladder'] = $price_ladder;
        $res['low_price'] = empty($price_ladder) ? price_format($row['shop_price']) : price_format($this->get_low_price($price_ladder));
        return $res;

    }

    /**
     * 商品属性是否匹配
     * @param   array   $goods_list     用户选择的商品
     * @param   array   $reference      参照的商品属性
     * @return  bool
     */
    function is_attr_matching(&$goods_list, $reference)
    {
        foreach ($goods_list as $key => $goods)
        {
            // 需要相同的元素个数
            if (count($goods['goods_attr']) != count($reference))
            {
                break;
            }

            // 判断用户提交与批发属性是否相同
            $is_check = true;
            if (is_array($goods['goods_attr']))
            {
                foreach ($goods['goods_attr'] as $attr)
                {
                    if (!(array_key_exists($attr['attr_id'], $reference) && $attr['attr_val_id'] == $reference[$attr['attr_id']]))
                    {
                        $is_check = false;
                        break;
                    }
                }
            }
            if ($is_check)
            {
                return $key;
                break;
            }
        }
        return false;
    }
}
