<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GoodsBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 商品基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GoodsBaseModel extends BaseModel {

    /**
     *  所有的促销活动信息
     * @access  public
     * @return  array
     */
    function get_promotion_info($goods_id = '') {
        $snatch = array();
        $group = array();
        $auction = array();
        $package = array();
        $favourable = array();

        $gmtime = gmtime();
        $sql = 'SELECT act_id, act_name, act_type, start_time, end_time FROM ' . $this->pre . "goods_activity WHERE is_finished=0 AND start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND goods_id = '$goods_id'";
        }
        $res = $this->query($sql);
        if (is_array($res))
            foreach ($res as $data) {
                switch ($data['act_type']) {
                    case GAT_SNATCH: //夺宝奇兵
                        $snatch[$data['act_id']]['act_name'] = $data['act_name'];
                        $snatch[$data['act_id']]['url'] = url('snatch/index', array('sid' => $data['act_id']));
                        $snatch[$data['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                        $snatch[$data['act_id']]['sort'] = $data['start_time'];
                        $snatch[$data['act_id']]['type'] = 'snatch';
                        break;

                    case GAT_GROUP_BUY: //团购
                        $group[$data['act_id']]['act_name'] = $data['act_name'];
                        $group[$data['act_id']]['url'] = url('groupbuy/info', array('id' => $data['act_id']));
                        $group[$data['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                        $group[$data['act_id']]['sort'] = $data['start_time'];
                        $group[$data['act_id']]['type'] = 'group_buy';
                        break;

                    case GAT_AUCTION: //拍卖
                        $auction[$data['act_id']]['act_name'] = $data['act_name'];
                        $auction[$data['act_id']]['url'] = url('auction/info', array('id' => $data['act_id']));
                        $auction[$data['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                        $auction[$data['act_id']]['sort'] = $data['start_time'];
                        $auction[$data['act_id']]['type'] = 'auction';
                        break;

                    case GAT_PACKAGE: //礼包
                        $package[$data['act_id']]['act_name'] = $data['act_name'];
                        $package[$data['act_id']]['url'] = 'package.php#' . $data['act_id'];
                        $package[$data['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                        $package[$data['act_id']]['sort'] = $data['start_time'];
                        $package[$data['act_id']]['type'] = 'package';
                        break;
                }
            }

        $user_rank = ',' . $_SESSION['user_rank'] . ',';
        $favourable = array();
        $sql = 'SELECT act_id, act_range, act_type,act_range_ext, act_name, start_time, end_time FROM ' . $this->pre . "favourable_activity WHERE start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'";
        }
        $res = $this->query($sql);

        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = url('activity/index');
                $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
            }
        } else {
            $sql = "SELECT cat_id, brand_id FROM " . $this->pre . "goods WHERE goods_id = '$goods_id'";
            $row = $this->row($sql);
            $category_id = $row['cat_id'];
            $brand_id = $row['brand_id'];

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = url('activity/index');
                    $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = array();
                    $raw_id_list = explode(',', $rows['act_range_ext']);
                    foreach ($raw_id_list as $id) {
                        $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                    }
                    $ids = join(',', array_unique($id_list));

                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = url('activity/index');
                        $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = url('activity/index');
                        $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                        $favourable[$rows['act_id']]['url'] = url('activity/index');
                        $favourable[$rows['act_id']]['time'] = sprintf(L('promotion_time'), local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                        $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
                        $favourable[$rows['act_id']]['type'] = 'favourable';
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                }
            }
        }

        $sort_time = array();
        $arr = array_merge($snatch, $group, $auction, $package, $favourable);
        foreach ($arr as $key => $value) {
            $sort_time[] = $value['sort'];
        }
        array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

        return $arr;
    }

    /**
     *  所有的促销活动信息
     * @access  public
     * @return  array
     */
    function get_promotion_show($goods_id = '') {
        $group = array();
        $package = array();
        $favourable = array();
        $gmtime = gmtime();
        $sql = 'SELECT act_id, act_name, act_type, start_time, end_time FROM ' . $this->pre . "goods_activity WHERE is_finished=0 AND start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND goods_id = '$goods_id'";
        }
        $res = $this->query($sql);
        if (is_array($res))
            foreach ($res as $data) {
                switch ($data['act_type']) {
                    case GAT_GROUP_BUY: //团购 
                        $group[$data['act_id']]['type'] = 'group_buy';
                        break;
                    case GAT_PACKAGE: //礼包
                        $package[$data['act_id']]['type'] = 'package';
                        break;
                }
            }

        $user_rank = ',' . $_SESSION['user_rank'] . ',';
        $favourable = array();
        $sql = 'SELECT act_id, act_range, act_type,act_range_ext, act_name, start_time, end_time FROM ' . $this->pre . "favourable_activity WHERE start_time <= '$gmtime' AND end_time >= '$gmtime'";
        if (!empty($goods_id)) {
            $sql .= " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'";
        }
        $res = $this->query($sql);

        if (empty($goods_id)) {
            foreach ($res as $rows) {
                $favourable[$rows['act_id']]['type'] = 'favourable';
            }
        } else {
            $sql = "SELECT cat_id, brand_id FROM " . $this->pre . "goods WHERE goods_id = '$goods_id'";
            $row = $this->row($sql);
            $category_id = $row['cat_id'];
            $brand_id = $row['brand_id'];

            foreach ($res as $rows) {
                if ($rows['act_range'] == FAR_ALL) {
                    $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                } elseif ($rows['act_range'] == FAR_CATEGORY) {
                    /* 找出分类id的子分类id */
                    $id_list = array();
                    $raw_id_list = explode(',', $rows['act_range_ext']);
                    foreach ($raw_id_list as $id) {
                        $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                    }
                    $ids = join(',', array_unique($id_list));
                    if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_BRAND) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                } elseif ($rows['act_range'] == FAR_GOODS) {
                    if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false) {
                        $favourable[$rows['act_id']]['act_type'] = $rows['act_type'];
                    }
                }
            }
        }
        $sort_time = array();
        $arr = array_merge($group, $package, $favourable);
        foreach ($arr as $key => $value) {
            $sort_time[] = $value['sort'];
        }
        array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

        return array_unique($arr);
    }

    /**
     * 调用某商品的累积收藏
     * @param type $arr
     * @return int
     */
    function get_goods_collect($goods_id = 0) {
        $sql = "SELECT count(*) as count FROM " . $this->pre .
                "collect_goods WHERE goods_id = '" . $goods_id . "'";
        $count = $this->row($sql);
        return $count['count'];
    }

    /**
     * 获得指定商品的相册
     *
     * @access  public
     * @param   integer     $goods_id
     * @return  array
     */
    function get_goods_gallery($goods_id) {
        $sql = 'SELECT img_id, img_url, thumb_url, img_desc' .
                ' FROM ' . $this->pre .
                "goods_gallery WHERE goods_id = '$goods_id' LIMIT " . C('goods_gallery_number');
        $row = $this->query($sql);
        /* 格式化相册图片路径 */
        foreach ($row as $key => $gallery_img) {
            $row[$key]['img_url'] = get_image_path($goods_id, $gallery_img['img_url'], false, 'gallery');
            $row[$key]['thumb_url'] = get_image_path($goods_id, $gallery_img['thumb_url'], true, 'gallery');
            $row[$key]['img_desc'] = $gallery_img['img_desc'];
        }
        return $row;
    }

    /**
     * 取得商品优惠价格列表
     *
     * @param   string  $goods_id    商品编号
     * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
     *
     * @return  优惠价格列表
     */
    function get_volume_price_list($goods_id, $price_type = '1') {
        $volume_price = array();
        $temp_index = '0';

        $sql = "SELECT `volume_number` , `volume_price`" .
                " FROM " . $this->pre . "" .
                "volume_price WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'" .
                " ORDER BY `volume_number`";

        $res = $this->query($sql);
        foreach ($res as $k => $v) {
            $volume_price[$temp_index] = array();
            $volume_price[$temp_index]['number'] = $v['volume_number'];
            $volume_price[$temp_index]['price'] = $v['volume_price'];
            $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
            $temp_index++;
        }
        return $volume_price;
    }

    /**
     * 取得商品最终使用价格
     *
     * @param   string  $goods_id      商品编号
     * @param   string  $goods_num     购买数量
     * @param   boolean $is_spec_price 是否加入规格价格
     * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
     *
     * @return  商品最终购买价格
     */
    function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array()) {
        $final_price = '0'; //商品最终购买价格
        $volume_price = '0'; //商品优惠价格
        $promote_price = '0'; //商品促销价格
        $user_price = '0'; //商品会员价格
        //取得商品优惠价格列表
        $price_list = $this->get_volume_price_list($goods_id, '1');

        if (!empty($price_list)) {
            foreach ($price_list as $value) {
                if ($goods_num >= $value['number']) {
                    $volume_price = $value['price'];
                }
            }
        }

        //取得商品促销价格列表
        /* 取得商品信息 */
        $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, " .
                "IFNULL(mp.user_price, g.shop_price * '" . $_SESSION['discount'] . "') AS shop_price " .
                " FROM " . $this->pre . "goods AS g " .
                " LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $_SESSION['user_rank'] . "' " .
                " WHERE g.goods_id = '" . $goods_id . "'" .
                " AND g.is_delete = 0";
        $goods = $this->row($sql);

        /* 计算商品的促销价格 */
        if ($goods['promote_price'] > 0) {
            $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
        } else {
            $promote_price = 0;
        }

        //取得商品会员价格列表
        $user_price = $goods['shop_price'];

        //比较商品的促销价格，会员价格，优惠价格
        if (empty($volume_price) && empty($promote_price)) {
            //如果优惠价格，促销价格都为空则取会员价格
            $final_price = $user_price;
        } elseif (!empty($volume_price) && empty($promote_price)) {
            //如果优惠价格为空时不参加这个比较。
            $final_price = min($volume_price, $user_price);
        } elseif (empty($volume_price) && !empty($promote_price)) {
            //如果促销价格为空时不参加这个比较。
            $final_price = min($promote_price, $user_price);
        } elseif (!empty($volume_price) && !empty($promote_price)) {
            //取促销价格，会员价格，优惠价格最小值
            $final_price = min($volume_price, $promote_price, $user_price);
        } else {
            $final_price = $user_price;
        }

        //如果需要加入规格价格
        if ($is_spec_price) {
            if (!empty($spec)) {
                $spec_price = model('Goods')->spec_price($spec);
                $final_price += $spec_price;
            }
        }

        //返回商品最终购买价格
        return $final_price;
    }

    /**
     *
     * 是否存在规格
     *
     * @access      public
     * @param       array       $goods_attr_id_array        一维数组
     *
     * @return      string
     */
    function is_spec($goods_attr_id_array, $sort = 'asc') {
        if (empty($goods_attr_id_array)) {
            return $goods_attr_id_array;
        }

        //重新排序
        $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " . $this->pre . "attribute AS a
            LEFT JOIN " . $this->pre . "goods_attr AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
        $row = $this->query($sql);
        $return_arr = array();
        foreach ($row as $value) {
            $return_arr['sort'][] = $value['goods_attr_id'];
            $return_arr['row'][$value['goods_attr_id']] = $value;
        }
        if (!empty($return_arr)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取商品的规格列表
     *
     * @param       int      $goods_id    商品id
     * @param       string   $conditions  sql条件
     *
     * @return  array
     */
    function get_specifications_list($goods_id, $conditions = '') {
        /* 取商品属性 */
        $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value, a.attr_name
            FROM " . $this->pre . "goods_attr AS ga, " . $this->pre . "attribute AS a
            WHERE ga.attr_id = a.attr_id
            AND ga.goods_id = '$goods_id'
            $conditions";
        $result = $this->query($sql);
        $return_array = array();
        foreach ($result as $value) {
            $return_array[$value['goods_attr_id']] = $value;
        }
        return $return_array;
    }

    /**
     * 取得拍卖活动信息
     * @param   int     $act_id     活动id
     * @return  array
     */
    function auction_info($act_id, $config = false) {
        $sql = "SELECT * FROM " . $this->pre . "goods_activity WHERE act_id = '$act_id'";
        $auction = $this->row($sql);
        if ($auction['act_type'] != GAT_AUCTION) {
            return array();
        }
        $auction['status_no'] = auction_status($auction);
        if ($config == true) {

            $auction['start_time'] = local_date('Y-m-d H:i', $auction['start_time']);
            $auction['end_time'] = local_date('Y-m-d H:i', $auction['end_time']);
        } else {
            $auction['start_time'] = local_date(C('time_format'), $auction['start_time']);
            $auction['end_time'] = local_date(C('time_format'), $auction['end_time']);
        }
        $ext_info = unserialize($auction['ext_info']);
        $auction = array_merge($auction, $ext_info);
        $auction['formated_start_price'] = price_format($auction['start_price']);
        $auction['formated_end_price'] = price_format($auction['end_price']);
        $auction['formated_amplitude'] = price_format($auction['amplitude']);
        $auction['formated_deposit'] = price_format($auction['deposit']);

        /* 查询出价用户数和最后出价 */
        $sql = "SELECT COUNT(DISTINCT bid_user) as count FROM " . $this->pre .
                "auction_log WHERE act_id = '$act_id'";
        $res = $this->row($sql);
        $auction['bid_user_count'] = $res['count'];
        if ($auction['bid_user_count'] > 0) {
            $sql = "SELECT a.*, u.user_name " .
                    "FROM " . $this->pre . "auction_log AS a, " .
                    $this->pre . "users AS u " .
                    "WHERE a.bid_user = u.user_id " .
                    "AND act_id = '$act_id' " .
                    "ORDER BY a.log_id DESC";
            $row = $this->row($sql);
            $row['formated_bid_price'] = price_format($row['bid_price'], false);
            $row['bid_time'] = local_date(C('time_format'), $row['bid_time']);
            $auction['last_bid'] = $row;
        }

        /* 查询已确认订单数 */
        if ($auction['status_no'] > 1) {
            $sql = "SELECT COUNT(*) as count" .
                    " FROM " . $this->pre .
                    "order_info WHERE extension_code = 'auction'" .
                    " AND extension_id = '$act_id'" .
                    " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_UNCONFIRMED));
            $res = $this->row($sql);
            $auction['order_count'] = $res['count'];
        } else {
            $auction['order_count'] = 0;
        }

        /* 当前价 */
        $auction['current_price'] = isset($auction['last_bid']) ? $auction['last_bid']['bid_price'] : $auction['start_price'];
        $auction['formated_current_price'] = price_format($auction['current_price'], false);

        return $auction;
    }

    /**
     * 取得拍卖活动出价记录
     * @param   int     $act_id     活动id
     * @return  array
     */
    function auction_log($act_id) {
        $log = array();
        $sql = "SELECT a.*, u.user_name " .
                "FROM " . $this->pre . "auction_log AS a," .
                $this->pre . "users AS u " .
                "WHERE a.bid_user = u.user_id " .
                "AND act_id = '$act_id' " .
                "ORDER BY a.log_id DESC";
        $res = $this->query($sql);
        $idx = 0;
        foreach ($res as $key => $value) {

            $res[$idx][bid_time] = local_date(C('time_format'), $value['bid_time']);
            $res[$idx][formated_bid_price] = price_format($value['bid_price'], false);
            $idx++;
        }
        return $res;
    }

    /**
     * 取得优惠活动信息
     * @param   int     $act_id     活动id
     * @return  array
     */
    function favourable_info($act_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "favourable_activity WHERE act_id = '$act_id'";
        $row = $this->row($sql);
        if (!empty($row)) {
            $row['start_time'] = local_date(C('time_format'), $row['start_time']);
            $row['end_time'] = local_date(C('time_format'), $row['end_time']);
            $row['formated_min_amount'] = price_format($row['min_amount']);
            $row['formated_max_amount'] = price_format($row['max_amount']);
            $row['gift'] = unserialize($row['gift']);
            if ($row['act_type'] == FAT_GOODS) {
                $row['act_type_ext'] = round($row['act_type_ext']);
            }
        }

        return $row;
    }

    /**
     * 批发信息
     * @param   int     $act_id     活动id
     * @return  array
     */
    function wholesale_info($act_id) {
        $sql = "SELECT * FROM " . $this->pre .
                "wholesale WHERE act_id = '$act_id'";
        $row = $this->row($sql);
        if (!empty($row)) {
            $row['price_list'] = unserialize($row['prices']);
        }
        return $row;
    }

    /**
     * 取得商品属性
     * @param   int     $goods_id   商品id
     * @return  array
     */
    function get_goods_attr($goods_id) {
        $attr_list = array();
        $sql = "SELECT a.attr_id, a.attr_name " .
                "FROM " . $this->pre . "goods AS g, " . $this->pre . "attribute AS a " .
                "WHERE g.goods_id = '$goods_id' " .
                "AND g.goods_type = a.cat_id " .
                "AND a.attr_type = 1";
        $attr_id_list = $this->query($sql);
        $return_array = array();
        foreach ($attr_id_list as $value) {
            $return_array[] = $value['attr_id'];
        }
        foreach ($attr_id_list as $key => $value) {
            if (defined('ECS_ADMIN')) {
                $value['goods_attr_list'] = array(0 => L('select_please'));
            } else {
                $value['goods_attr_list'] = array();
            }
            $attr_list[$value['attr_id']] = $value;
        }
        $sql = "SELECT attr_id, goods_attr_id, attr_value " .
                "FROM " . $this->pre .
                "goods_attr WHERE goods_id = '$goods_id' " .
                "AND attr_id " . db_create_in($return_array);
        $res = $this->query($sql);
        foreach ($res as $key => $value) {

            $attr_list[$value['attr_id']]['goods_attr_list'][$value['goods_attr_id']] = $value['attr_value'];
        }
        return $attr_list;
    }

    /**
     * 销量
     * @param unknown $goods_id
     * @return Ambigous <string, boolean>
     */
    function get_sales_count($goods_id) {
        return get_goods_count($goods_id);
    }

}
