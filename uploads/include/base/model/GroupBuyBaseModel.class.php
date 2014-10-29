<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupbuyBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 团购基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GroupBuyBaseModel extends BaseModel {

    /**
     * 取得团购活动信息
     * @param   int     $group_buy_id   团购活动id
     * @param   int     $current_num    本次购买数量（计算当前价时要加上的数量）
     * @return  array
     *                  status          状态：
     */
    function group_buy_info($group_buy_id, $current_num = 0) {
        /* 取得团购活动信息 */
        $group_buy_id = intval($group_buy_id);
        $sql = "SELECT *, a.act_id AS group_buy_id, a.act_desc AS group_buy_desc, a.start_time AS start_date, a.end_time AS end_date ,
        ta.sales_count ,ta.act_banner , ta.click_num " .
                "FROM " . $this->pre .
                "goods_activity as a LEFT JOIN " . $this->pre . 'touch_goods_activity as ta ON a.act_id = ta.act_id ' .
                "WHERE a.act_id = '$group_buy_id' " .
                "AND a.act_type = '" . GAT_GROUP_BUY . "'";
        $group_buy = $this->row($sql);

        /* 如果为空，返回空数组 */
        if (empty($group_buy)) {
            return array();
        }

        $ext_info = unserialize($group_buy['ext_info']);
        $group_buy = array_merge($group_buy, $ext_info);

        /* 格式化时间 */
        $group_buy['formated_start_date'] = local_date('Y-m-d H:i', $group_buy['start_time']);
        $group_buy['formated_end_date'] = local_date('Y-m-d H:i', $group_buy['end_time']);

        /* 格式化保证金 */
        $group_buy['formated_deposit'] = price_format($group_buy['deposit'], false);

        /* 处理价格阶梯 */
        $price_ladder = $group_buy['price_ladder'];
        if (!is_array($price_ladder) || empty($price_ladder)) {
            $price_ladder = array(array('amount' => 0, 'price' => 0));
        } else {
            foreach ($price_ladder as $key => $amount_price) {
                $price_ladder[$key]['formated_price'] = price_format($amount_price['price'], false);
            }
        }
        $group_buy['price_ladder'] = $price_ladder;

        /* 统计信息 */
        $stat = $this->group_buy_stat($group_buy_id, $group_buy['deposit']);
        $group_buy = array_merge($group_buy, $stat);

        /* 计算当前价 */
        $cur_price = $price_ladder[0]['price']; // 初始化
        $cur_amount = $stat['valid_goods'] + $current_num; // 当前数量
        foreach ($price_ladder as $amount_price) {
            if ($cur_amount >= $amount_price['amount']) {
                $cur_price = $amount_price['price'];
            } else {
                break;
            }
        }
        $group_buy['cur_price'] = $cur_price;
        $group_buy['formated_cur_price'] = price_format($cur_price, false);

        /* 最终价 */
        $group_buy['trans_price'] = $group_buy['cur_price'];
        $group_buy['formated_trans_price'] = $group_buy['formated_cur_price'];
        $group_buy['trans_amount'] = $group_buy['valid_goods'];

        /* 状态 */
        $group_buy['status'] = $this->group_buy_status($group_buy);
        if (L('gbs.' . $group_buy['status']) != '') {
            $group_buy['status_desc'] = L('gbs.' . $group_buy['status']);
        }

        $group_buy['start_time'] = $group_buy['formated_start_date'];
        $group_buy['end_time'] = $group_buy['formated_end_date'];
        $group_buy['act_banner'] = $group_buy['act_banner'];
        $group_buy['click_num'] = $group_buy['click_num'];
        $group_buy['sales_count'] = $group_buy['sales_count'] ? $group_buy['sales_count'] : 0;

        $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
            'as count FROM ' . $this->model->pre . 'order_info AS o LEFT JOIN ' .
            $this->model->pre . 'order_goods AS g  ON o.order_id = g.order_id ' .
            'LEFT JOIN ' . $this->model->pre . 'goods_activity as ga ON ga.goods_id = g.goods_id ' .
            "WHERE o.extension_code = 'group_buy'  AND g.goods_id = '" . $group_buy['goods_id'] . "'";
        
        $nCount = $this->query($sql);
        $group_buy['sales_count']  = $nCount[0]['count'];
        
        return $group_buy;
    }

    /**
     * 获得团购的状态
     *
     * @access  public
     * @param   array
     * @return  integer
     */
    function group_buy_status($group_buy) {
        $now = gmtime();
        if ($group_buy['is_finished'] == 0) {
            /* 未处理 */
            if ($now < $group_buy['start_time']) {
                $status = GBS_PRE_START;
            } elseif ($now > $group_buy['end_time']) {
                $status = GBS_FINISHED;
            } else {
                if ($group_buy['restrict_amount'] == 0 || $group_buy['valid_goods'] < $group_buy['restrict_amount']) {
                    $status = GBS_UNDER_WAY;
                } else {
                    $status = GBS_FINISHED;
                }
            }
        } elseif ($group_buy['is_finished'] == GBS_SUCCEED) {
            /* 已处理，团购成功 */
            $status = GBS_SUCCEED;
        } elseif ($group_buy['is_finished'] == GBS_FAIL) {
            /* 已处理，团购失败 */
            $status = GBS_FAIL;
        }

        return $status;
    }

    /*
     * 取得某团购活动统计信息
     * @param   int     $group_buy_id   团购活动id
     * @param   float   $deposit        保证金
     * @return  array   统计信息
     *                  total_order     总订单数
     *                  total_goods     总商品数
     *                  valid_order     有效订单数
     *                  valid_goods     有效商品数
     */

    function group_buy_stat($group_buy_id, $deposit) {
        $group_buy_id = intval($group_buy_id);

        /* 取得团购活动商品ID */
        $sql = "SELECT goods_id " .
                "FROM " . $this->pre .
                "goods_activity WHERE act_id = '$group_buy_id' " .
                "AND act_type = '" . GAT_GROUP_BUY . "'";
        $result = $this->row($sql);
        $group_buy_goods_id = $result['goods_id'];

        /* 取得总订单数和总商品数 */
        $sql = "SELECT COUNT(*) AS total_order, SUM(g.goods_number) AS total_goods " .
                "FROM " . $this->pre . "order_info AS o, " .
                $this->pre . "order_goods AS g " .
                " WHERE o.order_id = g.order_id " .
                "AND o.extension_code = 'group_buy' " .
                "AND o.extension_id = '$group_buy_id' " .
                "AND g.goods_id = '$group_buy_goods_id' " .
                "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "')";
        $stat = $this->row($sql);
        if ($stat['total_order'] == 0) {
            $stat['total_goods'] = 0;
        }

        /* 取得有效订单数和有效商品数 */
        $deposit = floatval($deposit);
        if ($deposit > 0 && $stat['total_order'] > 0) {
            $sql .= " AND (o.money_paid + o.surplus) >= '$deposit'";
            $row = ECTouch::db()->getRow($sql);
            $stat['valid_order'] = $row['total_order'];
            if ($stat['valid_order'] == 0) {
                $stat['valid_goods'] = 0;
            } else {
                $stat['valid_goods'] = $row['total_goods'];
            }
        } else {
            $stat['valid_order'] = $stat['total_order'];
            $stat['valid_goods'] = $stat['total_goods'];
        }

        return $stat;
    }

}
