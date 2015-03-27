<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupbuyModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 团购模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GroupbuyModel extends BaseModel {

    /**
     * 取得某页的所有团购活动
     * @param   int     $size   每页记录数
     * @param   int     $page   当前页
     * @return  array
     */
    function group_buy_list($size, $page, $sort, $order) {
        /* 取得团购活动 */
        $gb_list = array();
        $now = gmtime();
        $sql = "SELECT b.*, IFNULL(g.goods_thumb, '') AS goods_thumb, t.act_banner ,t.sales_count ,t.click_num,  g.market_price , b.act_id AS group_buy_id, " .
                "b.start_time AS start_date, b.end_time AS end_date " .
                "FROM " . $this->pre . "goods_activity AS b " .
                "LEFT JOIN " . $this->pre . "goods AS g ON b.goods_id = g.goods_id " .
                "LEFT JOIN " . $this->pre . "touch_goods_activity as t on t.act_id=b.act_id " .
                "LEFT JOIN " . $this->pre . "touch_goods as s on s.goods_id=g.goods_id " .
                "WHERE b.act_type = '" . GAT_GROUP_BUY . "' " .
                "AND b.start_time <= '$now' AND b.is_finished < 3 ORDER BY " . $sort . ' ' . $order . ' LIMIT ' . ($page - 1) * $size . ',' . $size;
        $result = $this->query($sql);
        foreach ($result as $group_buy) {
            $ext_info = unserialize($group_buy['ext_info']);
            $group_buy = array_merge($group_buy, $ext_info);

            /* 格式化时间 */
            $group_buy['formated_start_date'] = local_date(C('time_format'), $group_buy['start_date']);
            $group_buy['formated_end_date'] = local_date(C('time_format'), $group_buy['end_date']);

            /* 格式化保证金 */
            $group_buy['formated_deposit'] = price_format($group_buy['deposit'], false);

            /* 处理价格阶梯 */
            $price_ladder = $group_buy['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder)) {
                $price_ladder = array(array('amount' => 0, 'price' => 0));
            } else {
                foreach ($price_ladder as $key => $amount_price) {
                    $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
                }
            }
            $group_buy['price_ladder'] = $price_ladder;

            /* 计算当前价 */
            $cur_price = $price_ladder[0]['price']; // 初始化

            $cur_amount = 1; // 当前数量
            foreach ($price_ladder as $amount_price) {
                if ($cur_amount >= $amount_price['amount']) {
                    $cur_price = $amount_price['price'];
                } else {
                    break;
                }
            }
            //添加当前价格字段为列表排序
            $sql = 'SELECT count(*) as count FROM ' . $this->pre . "touch_goods_activity WHERE  `act_id` = '" . $group_buy['act_id'] . "'";
            $res = $this->row($sql);
            if ($res['count']) {
                $this->table = 'touch_goods_activity';
                $data['cur_price'] = $cur_price;
                $condition['act_id'] = $group_buy['act_id'];
                $this->update($condition, $data);
            } else {
                $this->table = 'touch_goods_activity';
                $data1['act_id'] = $group_buy['act_id'];
                $data1['cur_price'] = $cur_price;
                $this->insert($data1);
            }

            $group_buy['cur_price'] = price_format($cur_price);
            $group_buy['spare_discount'] = $group_buy['market_price'] != 0 ? round($cur_price / $group_buy['market_price'] * 10, 2):0;
            $group_buy['spare_price'] = price_format($group_buy['market_price'] - $cur_price); //增加优惠金额 by carson add 20140606
            $group_buy['market_price'] = price_format($group_buy['market_price']); //增加市场价 by carson add 20140606
            //$stat = group_buy_stat($group_buy['act_id'], $ext_info['deposit']);
            //$group_buy['cur_amount'] = $stat['valid_goods']; // 当前数量
            $group_buy['sales_count'] = $group_buy['sales_count'] ? $group_buy['sales_count'] : 0; // 销售数量
            $group_buy['click_num'] = $group_buy['click_num'] ? $group_buy['click_num'] : 0; // 点击数量
            /* 处理图片 */
            if (!empty($group_buy['goods_thumb'])) {
                $group_buy['goods_thumb'] = get_image_path($group_buy['goods_id'], $group_buy['goods_thumb'], true);
            }
            $group_buy['act_banner'] = $group_buy['act_banner'] ? $group_buy['act_banner'] : $group_buy['goods_thumb'];
            /* 处理链接 */
            $group_buy['url'] = url('groupbuy/info', array('id' => $group_buy ['group_buy_id']));
            /* 加入数组 */
            $gb_list[] = $group_buy;
        }
        return $gb_list;
    }

    /**
     * 取得团购活动总数
     * @return type
     */
    function group_buy_count() {
        $now = gmtime();
        $sql = "SELECT COUNT(*) as count " .
                "FROM " . $this->pre .
                "goods_activity WHERE act_type = '" . GAT_GROUP_BUY . "' " .
                "AND start_time <= '$now' AND is_finished < 3";
        $res = $this->row($sql);
        return $res['count'];
    }

}
