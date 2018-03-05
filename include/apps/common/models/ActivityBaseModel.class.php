<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ActivityBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 优惠活动基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ActivityBaseModel extends BaseModel {

    /**
     * 获取指定 id snatch 活动的结果
     *
     * @access public
     * @param int $id
     *            snatch_id
     *            
     * @return array array(user_name, bie_price, bid_time, num)
     *         num通常为1，如果为2表示有2个用户取到最小值，但结果只返回最早出价用户。
     */
    function get_snatch_result($id) {
        $sql = 'SELECT u.user_id, u.user_name, u.email, lg.bid_price, lg.bid_time, count(*) as num' . ' FROM ' . $this->pre . 'snatch_log AS lg ' . ' LEFT JOIN ' . $this->pre . 'users AS u ON lg.user_id = u.user_id' . " WHERE lg.snatch_id = '$id'" . ' GROUP BY lg.bid_price' . ' ORDER BY num ASC, lg.bid_price ASC, lg.bid_time ASC LIMIT 1';
        $rec = $this->row($sql);

        if ($rec) {
            $rec['bid_time'] = local_date(C('time_format'), $rec['bid_time']);
            $rec['formated_bid_price'] = price_format($rec['bid_price'], false);

            /* 活动信息 */
            $sql = 'SELECT ext_info " .  " FROM ' . $this->pre . "goods_activity WHERE act_id= '$id' AND act_type=" . GAT_SNATCH . " LIMIT 1";
            $result = $this->row($sql);
            $row = $result['ext_info'];
            $info = unserialize($row);

            if (!empty($info['max_price'])) {
                $rec['buy_price'] = ($rec['bid_price'] > $info['max_price']) ? $info['max_price'] : $rec['bid_price'];
            } else {
                $rec['buy_price'] = $rec['bid_price'];
            }
            /* 检查订单 */
            $sql = "SELECT COUNT(*) as count" . " FROM " . $this->pre . "order_info WHERE extension_code = 'snatch'" . " AND extension_id = '$id'" . " AND order_status " . db_create_in(array(
                        OS_CONFIRMED,
                        OS_UNCONFIRMED
            ));

            $result = $this->row($sql);
            $rec['order_count'] = $result['count'];
        }

        return $rec;
    }

}
