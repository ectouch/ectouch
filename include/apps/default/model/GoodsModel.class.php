<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：商品Model.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 商品模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GoodsModel extends BaseModel {

    protected $table = 'goods';

    /**
     * 获得商品的详细信息
     *
     * @access  public
     * @param   integer     $goods_id
     * @return  void
     */
    function get_goods_info($goods_id) {
        $time = gmtime();
        $sql = 'SELECT g.*, c.measure_unit, b.brand_id, b.brand_name AS goods_brand, m.type_money AS bonus_money, ' .
                'IFNULL(AVG(r.comment_rank), 0) AS comment_rank, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price " .
                'FROM ' . $this->pre . 'goods AS g ' .
                'LEFT JOIN ' . $this->pre . 'category AS c ON g.cat_id = c.cat_id ' .
                'LEFT JOIN ' . $this->pre . 'brand AS b ON g.brand_id = b.brand_id ' .
                'LEFT JOIN ' . $this->pre . 'comment AS r ' .
                'ON r.id_value = g.goods_id AND comment_type = 0 AND r.parent_id = 0 AND r.status = 1 ' .
                'LEFT JOIN ' . $this->pre . 'bonus_type AS m ' .
                "ON g.bonus_type_id = m.type_id AND m.send_start_date <= '$time' AND m.send_end_date >= '$time'" .
                " LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE g.goods_id = '$goods_id' AND g.is_delete = 0 " .
                "GROUP BY g.goods_id";
        $row = $this->row($sql);

        if ($row !== false) {
            /* 用户评论级别取整 */
            $row['comment_rank'] = ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);

            /* 获得商品的销售价格 */
            $row['market_price'] = price_format($row['market_price']);
            $row['shop_price_formated'] = price_format($row['shop_price']);

            /* 修正促销价格 */
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0) {
                $watermark_img = "watermark_promote";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot';
            }

            if ($watermark_img != '') {
                $row['watermark_img'] = $watermark_img;
            }

            $row['promote_price_org'] = $promote_price;
            $row['promote_price'] = price_format($promote_price);

            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                    $row['goods_weight'] . L('kilogram') :
                    ($row['goods_weight'] * 1000) . L('gram');

            /* 修正上架时间显示 */
            $row['add_time'] = local_date(C('date_format'), $row['add_time']);

            /* 促销时间倒计时 */
            $time = gmtime();
            if ($time >= $row['promote_start_date'] && $time <= $row['promote_end_date']) {
                $row['gmt_end_time'] = $row['promote_end_date'];
            } else {
                $row['gmt_end_time'] = 0;
            }

            /* 是否显示商品库存数量 */
            $row['goods_number'] = (C('use_storage') == 1) ? $row['goods_number'] : '';

            /* 修正积分：转换为可使用多少积分（原来是可以使用多少钱的积分） */
            $row['integral'] = C('integral_scale') ? round($row['integral'] * 100 / C('integral_scale')) : 0;

            /* 修正优惠券 */
            $row['bonus_money'] = ($row['bonus_money'] == 0) ? 0 : price_format($row['bonus_money'], false);

            /* 修正商品图片 */
            $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
            $row['goods_thumb'] = get_image_path($goods_id, $row['goods_thumb'], true);
            $row['original_img'] = get_image_path($goods_id, $row['original_img'], true);

            return $row;
        } else {
            return false;
        }
    }

    /**
     * 获得商品的属性和规格
     *
     * @access  public
     * @param   integer $goods_id
     * @return  array
     */
    function get_goods_properties($goods_id) {
        /* 对属性进行重新排序和分组 */
        $sql = "SELECT attr_group " .
                "FROM " . $this->pre . "goods_type AS gt, " . $this->pre . "goods AS g " .
                "WHERE g.goods_id='$goods_id' AND gt.cat_id=g.goods_type";
        $result = $this->row($sql);
        $grp = $result['attr_group'];
        if (!empty($grp)) {
            $groups = explode("\n", strtr($grp, "\r", ''));
        }

        /* 获得商品的规格 */
        $sql = "SELECT a.attr_id, a.attr_name, a.attr_group, a.is_linked, a.attr_type, " .
                "g.goods_attr_id, g.attr_value, g.attr_price " .
                'FROM ' . $this->pre . 'goods_attr AS g ' .
                'LEFT JOIN ' . $this->pre . 'attribute AS a ON a.attr_id = g.attr_id ' .
                "WHERE g.goods_id = '$goods_id' " .
                'ORDER BY a.sort_order, g.attr_price, g.goods_attr_id';
        $res = $this->query($sql);

        $arr['pro'] = array();     // 属性
        $arr['spe'] = array();     // 规格
        $arr['lnk'] = array();     // 关联的属性

        foreach ($res AS $row) {
            $row['attr_value'] = str_replace("\n", '<br />', $row['attr_value']);

            if ($row['attr_type'] == 0) {
                $group = (isset($groups[$row['attr_group']])) ? $groups[$row['attr_group']] : L('goods_attr');

                $arr['pro'][$group][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['pro'][$group][$row['attr_id']]['value'] = $row['attr_value'];
            } else {
                $arr['spe'][$row['attr_id']]['attr_type'] = $row['attr_type'];
                $arr['spe'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['spe'][$row['attr_id']]['values'][] = array(
                    'label' => $row['attr_value'],
                    'price' => $row['attr_price'],
                    'format_price' => price_format(abs($row['attr_price']), false),
                    'id' => $row['goods_attr_id']);
            }

            if ($row['is_linked'] == 1) {
                /* 如果该属性需要关联，先保存下来 */
                $arr['lnk'][$row['attr_id']]['name'] = $row['attr_name'];
                $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
            }
        }

        return $arr;
    }

    /**
     * 获得属性相同的商品
     *
     * @access  public
     * @param   array   $attr   // 包含了属性名称,ID的数组
     * @return  array
     */
    function get_same_attribute_goods($attr) {
        $lnk = array();

        if (!empty($attr)) {
            foreach ($attr['lnk'] AS $key => $val) {
                $lnk[$key]['title'] = sprintf(L('same_attrbiute_goods'), $val['name'], $val['value']);

                /* 查找符合条件的商品 */
                $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                        "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                        'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
                        'FROM ' . $this->pre . 'goods AS g ' .
                        'LEFT JOIN ' . $this->pre . 'goods_attr as a ON g.goods_id = a.goods_id ' .
                        "LEFT JOIN " . $this->pre . "member_price AS mp " .
                        "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                        "WHERE a.attr_id = '$key' AND g.is_on_sale=1 AND a.attr_value = '$val[value]' AND g.goods_id <> '$_REQUEST[id]' " .
                        'LIMIT ' . C('attr_related_number');
                $res = $this->query($sql);

                foreach ($res AS $row) {
                    $lnk[$key]['goods'][$row['goods_id']]['goods_id'] = $row['goods_id'];
                    $lnk[$key]['goods'][$row['goods_id']]['goods_name'] = $row['goods_name'];
                    $lnk[$key]['goods'][$row['goods_id']]['short_name'] = C('goods_name_length') > 0 ?
                            sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
                    $lnk[$key]['goods'][$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
                    $lnk[$key]['goods'][$row['goods_id']]['market_price'] = price_format($row['market_price']);
                    $lnk[$key]['goods'][$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
                    $lnk[$key]['goods'][$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $lnk[$key]['goods'][$row['goods_id']]['url'] = url('goods/index', array('id' => $row['goods_id']));
                }
            }
        }

        return $lnk;
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
            $goods[$idx]['short_name'] = C('goods_name_length') > 0 ?
                    sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
            $goods[$idx]['thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $goods[$idx]['url'] = url('goods/index', array('id' => $row['goods_id']));
        }

        if ($from == 'web') {
            ECTouch::view()->assign('cat_goods_' . $cat_id, $goods);
        } elseif ($from == 'wap') {
            $cat['goods'] = $goods;
        }

        /* 分类信息 */
        $sql = 'SELECT cat_name FROM ' . $this->pre . "category WHERE cat_id = '$cat_id'";
        $result = $this->row($sql);
        $cat['name'] = $result['cat_name'];
        $cat['url'] = url('category/index', array('id' => $cat_id));
        $cat['id'] = $cat_id;

        return $cat;
    }

    /**
     * 获得指定的品牌下的商品
     *
     * @access  public
     * @param   integer     $brand_id       品牌的ID
     * @param   integer     $num            数量
     * @param   integer     $cat_id         分类编号
     * @param   string      $order_rule     指定商品排序规则
     * @return  void
     */
    function assign_brand_goods($brand_id, $num = 0, $cat_id = 0, $order_rule = '') {
        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb, g.goods_img ' .
                'FROM ' . $this->pre . 'goods AS g ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.brand_id = '$brand_id'";

        if ($cat_id > 0) {
            $sql .= get_children($cat_id);
        }

        $order_rule = empty($order_rule) ? ' ORDER BY g.sort_order, g.goods_id DESC' : $order_rule;
        $sql .= $order_rule;
        if ($num > 0) {
            $sql .= 'LIMIT ' . $num;
        }
        $res = $this->query($sql);
        $idx = 0;
        $goods = array();
        foreach ($res as $key => $value) {
            if ($value['promote_price'] > 0) {
                $promote_price = bargain_price($value['promote_price'], $value['promote_start_date'], $value['promote_end_date']);
            } else {
                $promote_price = 0;
            }
            $goods[$idx]['id'] = $value['goods_id'];
            $goods[$idx]['name'] = $value['goods_name'];
            $goods[$idx]['short_name'] = C('goods_name_length') > 0 ?
                    sub_str($value['goods_name'], C('goods_name_length')) : $value['goods_name'];
            $goods[$idx]['market_price'] = price_format($value['market_price']);
            $goods[$idx]['shop_price'] = price_format($value['shop_price']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            $goods[$idx]['brief'] = $value['goods_brief'];
            $goods[$idx]['thumb'] = get_image_path($value['goods_id'], $value['goods_thumb'], true);
            $goods[$idx]['goods_img'] = get_image_path($value['goods_id'], $value['goods_img']);
            $goods[$idx]['url'] = url('goods/index', array('id' => $value['goods_id']));

            $idx++;
        }

        /* 分类信息 */
        $sql = 'SELECT brand_name FROM ' . $this->pre . "brand WHERE brand_id = '$brand_id'";

        $brand['id'] = $brand_id;
        $result = $this->row($sql);
        $brand['name'] = $result['brand_name'];
        $brand['url'] = url('brand/index', array('bid' => $brand_id));

        $brand_goods = array('brand' => $brand, 'goods' => $goods);

        return $brand_goods;
    }

    /**
     * 获得所有扩展分类属于指定分类的所有商品ID
     *
     * @access  public
     * @param   string $cat_id     分类查询字符串
     * @return  string
     */
    function get_extension_goods($cats) {
        $extension_goods_array = '';
        $sql = 'SELECT goods_id FROM ' . $this->pre . "goods_cat AS g WHERE $cats";
        $res = $this->query($sql);
        if ($res !== false) {
            $arr = array();
            foreach ($res as $key => $value) {
                $arr[] = $value['goods_id'];
            }
        }
        return db_create_in($arr, 'g.goods_id');
    }

    /**
     * 获得指定的规格的价格
     *
     * @access  public
     * @param   mix     $spec   规格ID的数组或者逗号分隔的字符串
     * @return  void
     */
    function spec_price($spec) {
        if (!empty($spec)) {
            if (is_array($spec)) {
                foreach ($spec as $key => $val) {
                    $spec[$key] = addslashes($val);
                }
            } else {
                $spec = addslashes($spec);
            }

            $where = db_create_in($spec, 'goods_attr_id');

            $sql = 'SELECT SUM(attr_price) AS attr_price FROM ' . $this->pre . "goods_attr WHERE $where";
            $res = $this->row($sql);
            $price = floatval($res['attr_price']);
        } else {
            $price = 0;
        }
        return $price;
    }

    /**
     * 取得商品信息
     * @param   int     $goods_id   商品id
     * @return  array
     */
    function goods_info($goods_id) {
        $sql = "SELECT g.*, b.brand_name " .
                "FROM " . $this->pre . "goods AS g " .
                "LEFT JOIN " . $this->pre . "brand AS b ON g.brand_id = b.brand_id " .
                "WHERE g.goods_id = '$goods_id'";
        $row = $this->row($sql);
        if (!empty($row)) {
            /* 修正重量显示 */
            $row['goods_weight'] = (intval($row['goods_weight']) > 0) ?
                    $row['goods_weight'] . L('kilogram') :
                    ($row['goods_weight'] * 1000) . L('gram');

            /* 修正图片 */
            $row['goods_img'] = get_image_path($goods_id, $row['goods_img']);
        }

        return $row;
    }

    /**
     * 获得购物车中商品的配件
     *
     * @access  public
     * @param   array     $goods_list
     * @return  array
     */
    function get_goods_fittings($goods_list = array()) {
        $temp_index = 0;
        $arr = array();

        $sql = 'SELECT gg.parent_id, ggg.goods_name AS parent_name, gg.goods_id, gg.goods_price, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price " .
                'FROM ' . $this->pre . 'group_goods AS gg ' .
                'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = gg.goods_id ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = gg.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "LEFT JOIN " . $this->pre . "goods AS ggg ON ggg.goods_id = gg.parent_id " .
                "WHERE gg.parent_id " . db_create_in($goods_list) . " AND g.is_delete = 0 AND g.is_on_sale = 1 " .
                "ORDER BY gg.parent_id, gg.goods_id";
        $res = $this->query($sql);
        foreach ($res as $key => $value) {
            $arr[$temp_index]['parent_id'] = $value['parent_id'];
            $arr[$temp_index]['parent_name'] = $value['parent_name']; //配件的基本件的名称
            $arr[$temp_index]['parent_short_name'] = C('goods_name_length') > 0 ?
                    sub_str($value['parent_name'], C('goods_name_length')) : $value['parent_name']; //配件的基本件显示的名称
            $arr[$temp_index]['goods_id'] = $value['goods_id']; //配件的商品ID
            $arr[$temp_index]['goods_name'] = $value['goods_name']; //配件的名称
            $arr[$temp_index]['short_name'] = C('goods_name_length') > 0 ?
                    sub_str($value['goods_name'], C('goods_name_length')) : $value['goods_name']; //配件显示的名称
            $arr[$temp_index]['fittings_price'] = price_format($value['goods_price']); //配件价格
            $arr[$temp_index]['shop_price'] = price_format($value['shop_price']); //配件原价格
            $arr[$temp_index]['goods_thumb'] = get_image_path($value['goods_id'], $value['goods_thumb'], true);
            $arr[$temp_index]['goods_img'] = get_image_path($value['goods_id'], $value['goods_img']);
            $arr[$temp_index]['url'] = url('goods/index', array('id' => $value['goods_id']));
            $temp_index++;
        }
        return $arr;
    }

    /**
     * 获得指定商品的关联商品
     *
     * @access  public
     * @param   integer     $goods_id
     * @return  array
     */
    function get_linked_goods($goods_id) {
        foreach ($goods_id as $gid) {
            $goodsId[] = $gid['goods_id'];
        }
        $related_goods_number = C('related_goods_number') ? C('related_goods_number') : 0;
        $related_goods_number = $related_goods_number * 3;
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' .
                'FROM ' . $this->pre . 'link_goods lg ' .
                'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = lg.link_goods_id ' .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE lg.goods_id  " . db_create_in($goodsId) . " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " .
                "LIMIT " . $related_goods_number;
        $res = $this->query($sql);

        $arr = array();
        foreach ($res as $row) {
            if (!in_array($row['goods_id'], $goodsId)) {
                $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $arr[$row['goods_id']]['short_name'] = C('goods_name_length') > 0 ?
                        sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
                $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
                $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
                $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
                $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
                $arr[$row['goods_id']]['url'] = url('goods/index', array('id' => $row['goods_id']));

                if ($row['promote_price'] > 0) {
                    $arr[$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                    $arr[$row['goods_id']]['formated_promote_price'] = price_format($arr[$row['goods_id']]['promote_price']);
                } else {
                    $arr[$row['goods_id']]['promote_price'] = 0;
                }
            }
        }
        //返回数组
        $related_goods_number = C('related_goods_number');
        if (count($arr) > $related_goods_number) {
            $linked_goods = array_rand($arr, $related_goods_number);
            foreach ($linked_goods as $key) {
                $array[] = $arr[$key];
            }
        } else {
            $array = $arr;
        }
        return $array;
    }

    /**
     * 获得指定商品的关联商品
     *
     * @access public
     * @param integer $goods_id        	
     * @return array
     */
    function get_related_goods($goods_id) {
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " . 'g.market_price, g.promote_price, g.promote_start_date, g.promote_end_date ' . 'FROM ' . $this->pre . 'link_goods AS lg ' . 'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = lg.link_goods_id ' . "LEFT JOIN " . $this->pre . "member_price AS mp " . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE lg.goods_id = '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " . "LIMIT " . C('related_goods_number');
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            $arr [$row ['goods_id']] ['goods_id'] = $row ['goods_id'];
            $arr [$row ['goods_id']] ['goods_name'] = $row ['goods_name'];
            $arr [$row ['goods_id']] ['short_name'] = C('goods_name_length') > 0 ? sub_str($row ['goods_name'], C('goods_name_length')) : $row ['goods_name'];
            $arr [$row ['goods_id']] ['goods_thumb'] = get_image_path($row ['goods_id'], $row ['goods_thumb'], true);
            $arr [$row ['goods_id']] ['goods_img'] = get_image_path($row ['goods_id'], $row ['goods_img']);
            $arr [$row ['goods_id']] ['market_price'] = price_format($row ['market_price']);
            $arr [$row ['goods_id']] ['shop_price'] = price_format($row ['shop_price']);
            $arr [$row ['goods_id']] ['url'] = url('goods/index', array(
                'id' => $row ['goods_id']
            ));

            if ($row ['promote_price'] > 0) {
                $arr [$row ['goods_id']] ['promote_price'] = bargain_price($row ['promote_price'], $row ['promote_start_date'], $row ['promote_end_date']);
                $arr [$row ['goods_id']] ['formated_promote_price'] = price_format($arr [$row ['goods_id']] ['promote_price']);
            } else {
                $arr [$row ['goods_id']] ['promote_price'] = 0;
            }
        }
        return $arr;
    }

    /**
     * 获得指定商品的关联文章
     *
     * @access public
     * @param integer $goods_id        	
     * @return void
     */
    function get_linked_articles($goods_id) {
        $sql = 'SELECT a.article_id, a.title, a.file_url, a.open_type, a.add_time ' . 'FROM ' . $this->pre . 'goods_article AS g, ' . $this->pre . 'article AS a ' . "WHERE g.article_id = a.article_id AND g.goods_id = '$goods_id' AND a.is_open = 1 " . 'ORDER BY a.add_time DESC';
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            $row ['url'] = $row ['open_type'] != 1 ? url('article/index', array('id' => $row ['article_id'])) : trim($row ['file_url']);
            $row ['add_time'] = local_date(C('date_format'), $row ['add_time']);
            $row ['short_title'] = C('article_title_length') > 0 ? sub_str($row ['title'], C('article_title_length')) : $row ['title'];
            $arr [] = $row;
        }
        return $arr;
    }

    /**
     * 获得指定商品的各会员等级对应的价格
     *
     * @access public
     * @param integer $goods_id        	
     * @return array
     */
    function get_user_rank_prices($goods_id, $shop_price) {
        $sql = "SELECT rank_id, IFNULL(mp.user_price, r.discount * $shop_price / 100) AS price, r.rank_name, r.discount " . 'FROM ' . $this->pre . 'user_rank AS r ' . 'LEFT JOIN ' . $this->pre . "member_price AS mp " . "ON mp.goods_id = '$goods_id' AND mp.user_rank = r.rank_id " . "WHERE r.show_price = 1 OR r.rank_id = '$_SESSION[user_rank]'";
        $res = $this->query($sql);
        $arr = array();
        foreach ($res as $row) {
            $arr [$row ['rank_id']] = array(
                'rank_name' => htmlspecialchars($row ['rank_name']),
                'price' => price_format($row ['price'])
            );
        }
        return $arr;
    }

    /**
     * 获得购买过该商品的人还买过的商品
     *
     * @access public
     * @param integer $goods_id        	
     * @return array
     */
    function get_also_bought($goods_id) {
        $sql = 'SELECT COUNT(b.goods_id ) AS num, g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price, g.promote_price, g.promote_start_date, g.promote_end_date ' . 'FROM ' . $this->pre . 'order_goods AS a ' . 'LEFT JOIN ' . $this->pre . 'order_goods AS b ON b.order_id = a.order_id ' . 'LEFT JOIN ' . $this->pre . 'goods AS g ON g.goods_id = b.goods_id ' . "WHERE a.goods_id = '$goods_id' AND b.goods_id <> '$goods_id' AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " . 'GROUP BY b.goods_id ' . 'ORDER BY num DESC ' . 'LIMIT ' . C('bought_goods');
        $res = $this->query($sql);

        $key = 0;
        $arr = array();
        foreach ($res as $row) {
            $arr [$key] ['goods_id'] = $row ['goods_id'];
            $arr [$key] ['goods_name'] = $row ['goods_name'];
            $arr [$key] ['short_name'] = C('goods_name_length') > 0 ? sub_str($row ['goods_name'], C('goods_name_length')) : $row ['goods_name'];
            $arr [$key] ['goods_thumb'] = get_image_path($row ['goods_id'], $row ['goods_thumb'], true);
            $arr [$key] ['goods_img'] = get_image_path($row ['goods_id'], $row ['goods_img']);
            $arr [$key] ['shop_price'] = price_format($row ['shop_price']);
            $arr [$key] ['url'] = url('goods/index', array('id' => $row ['goods_id']));

            if ($row ['promote_price'] > 0) {
                $arr [$key] ['promote_price'] = bargain_price($row ['promote_price'], $row ['promote_start_date'], $row ['promote_end_date']);
                $arr [$key] ['formated_promote_price'] = price_format($arr [$key] ['promote_price']);
            } else {
                $arr [$key] ['promote_price'] = 0;
            }

            $key++;
        }
        return $arr;
    }

    /**
     * 获得商品选定的属性的附加总价格
     *
     * @param integer $goods_id        	
     * @param array $attr        	
     *
     * @return void
     */
    function get_attr_amount($goods_id, $attr) {
        $sql = "SELECT SUM(attr_price) as amount FROM " . $this->pre . "goods_attr WHERE goods_id='$goods_id' AND " . db_create_in($attr, 'goods_attr_id');

        $res = $this->row($sql);
        return $res['amount'];
    }

    /**
     * 取得跟商品关联的礼包列表
     *
     * @param string $goods_id
     *        	商品编号
     *        	
     * @return 礼包列表
     */
    function get_package_goods_list($goods_id) {
        $now = gmtime();
        $sql = "SELECT pg.goods_id, ga.act_id, ga.act_name, ga.act_desc, ga.goods_name, ga.start_time,
					   ga.end_time, ga.is_finished, ga.ext_info
				FROM " . $this->pre . "goods_activity AS ga, " . $this->pre . "package_goods AS pg
				WHERE pg.package_id = ga.act_id
				AND ga.start_time <= '" . $now . "'
				AND ga.end_time >= '" . $now . "'
				AND pg.goods_id = " . $goods_id . "
				GROUP BY ga.act_id
				ORDER BY ga.act_id ";
        $res = $this->query($sql);

        foreach ($res as $tempkey => $value) {
            $subtotal = 0;
            $row = unserialize($value ['ext_info']);
            unset($value ['ext_info']);
            if ($row) {
                foreach ($row as $key => $val) {
                    $res [$tempkey] [$key] = $val;
                }
            }

            $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, p.goods_attr, g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price
					FROM " . $this->pre . "package_goods AS pg
						LEFT JOIN " . $this->pre . "goods AS g
							ON g.goods_id = pg.goods_id
						LEFT JOIN " . $this->pre . "products AS p
							ON p.product_id = pg.product_id
						LEFT JOIN " . $this->pre . "member_price AS mp
							ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]'
					WHERE pg.package_id = " . $value ['act_id'] . "
					ORDER BY pg.package_id, pg.goods_id";

            $goods_res = $this->query($sql);

            foreach ($goods_res as $key => $val) {
                $goods_id_array [] = $val ['goods_id'];
                $goods_res [$key] ['goods_thumb'] = get_image_path($val ['goods_id'], $val ['goods_thumb'], true);
                $goods_res [$key] ['market_price'] = price_format($val ['market_price']);
                $goods_res [$key] ['rank_price'] = price_format($val ['rank_price']);
                $subtotal += $val ['rank_price'] * $val ['goods_number'];
            }

            /* 取商品属性 */
            $sql = "SELECT ga.goods_attr_id, ga.attr_value
					FROM " . $this->pre . "goods_attr AS ga, " . ECTouch::ecs()->table('attribute') . " AS a
					WHERE a.attr_id = ga.attr_id
					AND a.attr_type = 1
					AND " . db_create_in($goods_id_array, 'goods_id');
            $result_goods_attr = $this->query($sql);

            $_goods_attr = array();
            foreach ($result_goods_attr as $value) {
                $_goods_attr [$value ['goods_attr_id']] = $value ['attr_value'];
            }

            /* 处理货品 */
            $format = '[%s]';
            foreach ($goods_res as $key => $val) {
                if ($val ['goods_attr'] != '') {
                    $goods_attr_array = explode('|', $val ['goods_attr']);

                    $goods_attr = array();
                    foreach ($goods_attr_array as $_attr) {
                        $goods_attr [] = $_goods_attr [$_attr];
                    }

                    $goods_res [$key] ['goods_attr_str'] = sprintf($format, implode('，', $goods_attr));
                }
            }

            $res [$tempkey] ['goods_list'] = $goods_res;
            $res [$tempkey] ['subtotal'] = price_format($subtotal);
            $res [$tempkey] ['saving'] = price_format(($subtotal - $res [$tempkey] ['package_price']));
            $res [$tempkey] ['package_price'] = price_format($res [$tempkey] ['package_price']);
        }

        return $res;
    }

}
