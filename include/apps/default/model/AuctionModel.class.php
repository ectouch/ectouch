<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ActivityModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 拍卖活动模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AuctionModel extends BaseModel {

    /**
     * 取得拍卖活动数量
     * @return  int
     */
    function auction_count() {
        $now = gmtime();
        $sql = "SELECT COUNT(*) as count " .
                "FROM " . $this->pre .
                "goods_activity WHERE act_type = '" . GAT_AUCTION . "' " .
                "AND start_time <= '$now' AND end_time >= '$now' AND is_finished < 2";
        $res = $this->row($sql);
        return $res['count'];
    }

    /**
     * 取得某页的拍卖活动
     * @param   int     $size   每页记录数
     * @param   int     $page   当前页
     * @param   str     $sort   分类
     * @param   str     $order  排序
     * @return  array
     */
    function auction_list($size, $page, $sort, $order) {
        $auction_list = array();
        $auction_list['finished'] = $auction_list['finished'] = array();

        $now = gmtime();
        $start = ($page - 1) * $size;
        $sort = $sort != 'goods_id' ? 't.' . $sort : $sort;
        $sql = "SELECT a.*,t.act_banner ,t.sales_count ,t.click_num ,  IFNULL(g.goods_thumb, '') AS goods_thumb " .
                "FROM " . $this->pre . "goods_activity AS a " .
                "LEFT JOIN " . $this->pre . "goods AS g ON a.goods_id = g.goods_id " .
                "LEFT JOIN " . $this->pre . "touch_goods_activity AS t ON a.act_id = t.act_id " .
                "LEFT JOIN " . $this->pre . "touch_goods as tg ON g.goods_id = tg.goods_id " .
                "WHERE a.act_type = '" . GAT_AUCTION . "' " .
                "AND a.start_time <= '$now' AND a.end_time >= '$now' AND a.is_finished < 2 ORDER BY $sort $order LIMIT $start ,$size ";
        $res = $this->query($sql);
        
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $auction = array_merge($row, $ext_info);
            $auction['status_no'] = auction_status($auction);

            $auction['start_time'] = local_date(C('time_format'), $auction['start_time']);
            $auction['end_time'] = local_date(C('time_format'), $auction['end_time']);
            $auction['formated_start_price'] = price_format($auction['start_price']);
            $auction['formated_end_price'] = price_format($auction['end_price']);
            $auction['formated_deposit'] = price_format($auction['deposit']);
            $auction['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $auction['act_banner'] = $row['act_banner'] ? $row['act_banner'] : $auction['goods_thumb'];
            $auction['url'] = url('auction/info', array('id' => $auction['act_id']));

            if ($auction['status_no'] < 2) {
                $auction_list['under_way'][] = $auction;
            } else {
                $auction_list['finished'][] = $auction;
            }
            //增加扩展表判断
            $sql = 'SELECT count(*) as count FROM ' . $this->pre . "touch_goods_activity WHERE  `act_id` = '" . $auction['act_id'] . "'";
            $res = $this->row($sql);
            if ($res['count']) {
                $this->table = 'touch_goods_activity';
                $data['cur_price'] = $auction['start_price'];
                $condition['act_id'] = $auction['act_id'];
                $this->update($condition, $data);
            } else {
                $this->table = 'touch_goods_activity';
                $data1['act_id'] = $auction['act_id'];
                $data1['cur_price'] = $auction['start_price'];
                $this->insert($data1);
            }
        }
        $auction_list = @array_merge($auction_list['under_way'], $auction_list['finished']);
        return $auction_list;
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
        $auction['status_no'] = $this->auction_status($auction);
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
     * 计算拍卖活动状态（注意参数一定是原始信息）
     * @param   array   $auction    拍卖活动原始信息
     * @return  int
     */
    function auction_status($auction) {
        $now = gmtime();
        if ($auction['is_finished'] == 0) {
            if ($now < $auction['start_time']) {
                return PRE_START; // 未开始
            } elseif ($now > $auction['end_time']) {
                return FINISHED; // 已结束，未处理
            } else {
                return UNDER_WAY; // 进行中
            }
        } elseif ($auction['is_finished'] == 1) {
            return FINISHED; // 已结束，未处理
        } else {
            return SETTLED; // 已结束，已处理
        }
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
        foreach ($res as $row) {
            $row['bid_time'] = local_date(C('time_format'), $row['bid_time']);
            $row['formated_bid_price'] = price_format($row['bid_price'], false);
            $log[] = $row;
        }
        return $log;
    }

}
