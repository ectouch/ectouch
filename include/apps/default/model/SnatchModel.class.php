<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：SnatchModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 夺宝奇兵模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class SnatchModel extends BaseModel {

    /**
     * 获取最近要到期的活动id，没有则返回 0
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function get_last_snatch() {
        $now = gmtime();
        $sql = 'SELECT act_id FROM ' . $this->pre .
                "goods_activity WHERE  start_time < '$now' AND end_time > '$now' AND act_type = " . GAT_SNATCH .
                " ORDER BY end_time ASC LIMIT 1";
        $res = $this->row($sql);
        return $res['act_id'];
    }

    /**
     * 取得当前活动信息
     *
     * @access  public
     *
     * @return 活动名称
     */
    function get_snatch($id) {
        $sql = "SELECT g.goods_id, g.goods_sn, g.is_real, g.goods_name, g.extension_code, g.market_price, g.shop_price AS org_price, product_id, " .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                "g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb,g.goods_img, " .
                "ga.act_name AS snatch_name, ga.start_time, ga.end_time, ga.ext_info, ga.act_desc AS `desc` " .
                "FROM " . $this->pre . "goods_activity AS ga " .
                "LEFT JOIN " . $this->pre . "goods AS g " .
                "ON g.goods_id = ga.goods_id " .
                "LEFT JOIN " . $this->pre . "member_price AS mp " .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                "WHERE ga.act_id = '$id' AND g.is_delete = 0";

        $goods = $this->row($sql);

        if ($goods) {
            $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
            $goods['formated_market_price'] = price_format($goods['market_price']);
            $goods['formated_shop_price'] = price_format($goods['shop_price']);
            $goods['formated_promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods['goods_thumb'] = get_image_path($goods['goods_id'], $goods['goods_thumb'], true);
            $goods['goods_img'] = get_image_path($goods['goods_id'], $goods['goods_img'], true);
            $goods['url'] = url('goods/index', array('id' => $goods['goods_id']));
            $goods['start_time'] = local_date(C('time_format'), $goods['start_time']);

            $info = unserialize($goods['ext_info']);
            if ($info) {
                foreach ($info as $key => $val) {
                    $goods[$key] = $val;
                }
                $goods['is_end'] = gmtime() > $goods['end_time'];
                $goods['formated_start_price'] = price_format($goods['start_price']);
                $goods['formated_end_price'] = price_format($goods['end_price']);
                $goods['formated_max_price'] = price_format($goods['max_price']);
            }
            /* 将结束日期格式化为格林威治标准时间时间戳 */
            $goods['gmt_end_time'] = $goods['end_time'];
            $goods['end_time'] = local_date(C('time_format'), $goods['end_time']);
            $goods['snatch_time'] = sprintf(L('snatch_start_time'), $goods['start_time'], $goods['end_time']);

            return $goods;
        } else {
            return false;
        }
    }

    /**
     * 取得用户对当前活动的所出过的价格
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function get_myprice($id) {
        $my_only_price = array();
        $my_price = array();
        $pay_points = 0;
        $bid_price = array();
        if (!empty($_SESSION['user_id'])) {
            /* 取得用户所有价格 */
            $this->table = 'snatch_log';
            $field = 'bid_price';
            $my_price = $this->gecol(array('snatch_id' => $id, 'user_id' => $_SESSION[user_id]), $field, 'bid_time DESC');
            if ($my_price) {
                /* 取得用户唯一价格 */
                $sql = 'SELECT bid_price , count(*) AS num FROM ' . $this->pre . "snatch_log  WHERE snatch_id ='$id' AND bid_price " . db_create_in(join(',', $my_price)) . ' GROUP BY bid_price HAVING num = 1';
                $res = $this->query($sql);
                foreach ($res as $key => $value) {
                    $my_only_price[$key] = $value['bid_price'];
                }
            }
            for ($i = 0, $count = count($my_price); $i < $count; $i++) {
                $bid_price[] = array('price' => price_format($my_price[$i], false),
                    'is_only' => in_array($my_price[$i], $my_only_price)
                );
            }
            $sql = 'SELECT pay_points FROM ' . $this->pre . "users WHERE user_id = '$_SESSION[user_id]'";
            $res = $this->row($sql);
            $pay_points = $res['pay_points'] . C('integral_name');
        }

        /* 活动结束时间 */
        $sql = 'SELECT end_time FROM ' . $this->pre .
                "goods_activity WHERE act_id = '$id' AND act_type=" . GAT_SNATCH;
        $res = $this->row($sql);
        $my_price = array(
            'pay_points' => $pay_points,
            'bid_price' => $bid_price,
            'is_end' => gmtime() > $res['end_time']
        );

        return $my_price;
    }

    /**
     * 取的最近的几次活动。
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function get_snatch_list($num = 10) {
        $now = gmtime();
        $sql = 'SELECT a.act_id AS snatch_id, a.act_name AS snatch_name, a.end_time ,g.goods_name ,g.goods_id, g.goods_thumb ,g.market_price, g.shop_price,' .
                " g.promote_price, g.promote_start_date, g.promote_end_date, g.goods_brief FROM " . $this->pre .
                "goods_activity a LEFT JOIN " . $this->pre . "goods g ON a.goods_id =g.goods_id  WHERE a.start_time <= '$now' AND a.act_type=" . GAT_SNATCH .
                " ORDER BY a.end_time DESC LIMIT $num";
        $snatch_list = array();
        $overtime = 0;
        $res = $this->query($sql);
        foreach ($res as $row) {
            $overtime = $row['end_time'] > $now ? 0 : 1;
            $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            $snatch_list[] = array(
                'snatch_id' => $row['snatch_id'],
                'snatch_name' => $row['snatch_name'],
                'overtime' => $overtime,
                'name' => $row['goods_name'],
                'market_price' => price_format($row['market_price']),
                'shop_price' => price_format($row['shop_price']),
                'promote_price' => ($promote_price > 0) ? price_format($promote_price) : '',
                'goods_thumb' => get_image_path($row['goods_id'], $row['goods_thumb'], true),
                'url' => url('info', array('id' => $row['snatch_id']))
            );
        }
        return $snatch_list;
    }

    /**
     * 取得当前活动的前n个出价
     *
     * @access  public
     * @param   int $num 列表个数(取前5个)
     *
     * @return void
     */
    function get_price_list($id, $num = 5) {
        $sql = 'SELECT t1.log_id, t1.bid_price, t2.user_name FROM ' . $this->pre . 'snatch_log AS t1, ' . $this->pre . "users AS t2 WHERE snatch_id = '$id' AND t1.user_id = t2.user_id ORDER BY t1.log_id DESC LIMIT $num";
        $res = $this->query($sql);
        $price_list = array();
        foreach ($res as $row) {
            $price_list[] = array('bid_price' => price_format($row['bid_price'], false), 'user_name' => $row['user_name']);
        }
        return $price_list;
    }

}
