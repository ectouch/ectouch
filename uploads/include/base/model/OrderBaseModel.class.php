<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：OrderBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 订单基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class OrderBaseModel extends BaseModel {

    /**
     * 记录订单操作记录
     * @access  public
     * @param   string  $order_sn           订单编号
     * @param   integer $order_status       订单状态
     * @param   integer $shipping_status    配送状态
     * @param   integer $pay_status         付款状态
     * @param   string  $note               备注
     * @param   string  $username           用户名，用户自己的操作则为 buyer
     * @return  void
     */
    function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0) {
        if (is_null($username)) {
            $username = $_SESSION['admin_name'];
        }

        $sql = 'INSERT INTO ' . $this->pre . 'order_action (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
                " SELECT order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" . gmtime() . "' " .
                'FROM ' . $this->pre . "order_info WHERE order_sn = '$order_sn'";
        $this->query($sql);
    }

    /**
     * 返回订单中的虚拟商品
     * @access  public
     * @param   int   $order_id   订单id值
     * @param   bool  $shipping   是否已经发货
     * @return array()
     */
    function get_virtual_goods($order_id, $shipping = false) {
        if ($shipping) {
            $sql = 'SELECT goods_id, goods_name, send_number AS num, extension_code FROM ' . $this->pre .
                    "order_goods WHERE order_id = '$order_id' AND extension_code > ''";
        } else {
            $sql = 'SELECT goods_id, goods_name, (goods_number - send_number) AS num, extension_code FROM ' . $this->pre .
                    "order_goods WHERE order_id = '$order_id' AND is_real = 0 AND (goods_number - send_number) > 0 AND extension_code > '' ";
        }
        $res = $this->query($sql);

        $virtual_goods = array();
        if (is_array($res))
            foreach ($res AS $row) {
                $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num']);
            }

        return $virtual_goods;
    }

    /**
     *  虚拟商品发货
     *
     * @access  public
     * @param   array  $virtual_goods   虚拟商品数组
     * @param   string $msg             错误信息
     * @param   string $order_sn        订单号。
     * @param   string $process         设定当前流程：split，发货分单流程；other，其他，默认。
     *
     * @return bool
     */
    function virtual_goods_ship(&$virtual_goods, &$msg, $order_sn, $return_result = false, $process = 'other') {
        $virtual_card = array();
        foreach ($virtual_goods AS $code => $goods_list) {
            /* 只处理虚拟卡 */
            if ($code == 'virtual_card') {
                foreach ($goods_list as $goods) {
                    if ($this->virtual_card_shipping($goods, $order_sn, $msg, $process)) {
                        if ($return_result) {
                            $virtual_card[] = array('goods_id' => $goods['goods_id'], 'goods_name' => $goods['goods_name'], 'info' => $this->virtual_card_result($order_sn, $goods));
                        }
                    } else {
                        return false;
                    }
                }
                ECTouch::view()->assign('virtual_card', $virtual_card);
            }
        }

        return true;
    }

    /**
     *  虚拟卡发货
     *
     * @access  public
     * @param   string      $goods      商品详情数组
     * @param   string      $order_sn   本次操作的订单
     * @param   string      $msg        返回信息
     * @param   string      $process    设定当前流程：split，发货分单流程；other，其他，默认。
     *
     * @return  boolen
     */
    function virtual_card_shipping($goods, $order_sn, &$msg, $process = 'other') {
        /* 检查有没有缺货 */
        $sql = "SELECT COUNT(*) as count FROM " . $this->pre . "virtual_card WHERE goods_id = '$goods[goods_id]' AND is_saled = 0 ";
        $result = $this->row($sql);
        $num = $result['count'];
        if ($num < $goods['num']) {
            $msg .= sprintf(L('virtual_card_oos'), $goods['goods_name']);
            return false;
        }

        /* 取出卡片信息 */
        $sql = "SELECT card_id, card_sn, card_password, end_date, crc32 FROM " . $this->pre . "virtual_card WHERE goods_id = '$goods[goods_id]' AND is_saled = 0  LIMIT " . $goods['num'];
        $arr = $this->query($sql);

        $card_ids = array();
        $cards = array();

        foreach ($arr as $virtual_card) {
            $card_info = array();

            /* 卡号和密码解密 */
            if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY)) {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
                $card_info['card_password'] = decrypt($virtual_card['card_password']);
            } elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY)) {
                $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
                $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
            } else {
                $msg .= 'error key';

                return false;
            }
            $card_info['end_date'] = date(C('date_format'), $virtual_card['end_date']);
            $card_ids[] = $virtual_card['card_id'];
            $cards[] = $card_info;
        }

        /* 标记已经取出的卡片 */
        $sql = "UPDATE " . $this->pre . "virtual_card SET " .
                "is_saled = 1 ," .
                "order_sn = '$order_sn' " .
                "WHERE " . db_create_in($card_ids, 'card_id');
        if (!$this->query($sql)) {
            $msg .= ECTouch::db()->error();
            return false;
        }

        /* 更新库存 */
        $sql = "UPDATE " . $this->pre . "goods SET goods_number = goods_number - '$goods[num]' WHERE goods_id = '$goods[goods_id]'";
        $this->query($sql);

        if (true) {
            /* 获取订单信息 */
            $sql = "SELECT order_id, order_sn, consignee, email FROM " . $this->pre . "order_info WHERE order_sn = '$order_sn'";
            $order = ECTouch::db()->GetRow($sql);

            /* 更新订单信息 */
            if ($process == 'split') {
                $sql = "UPDATE " . $this->pre .
                        "order_goods SET send_number = send_number + '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
            } else {
                $sql = "UPDATE " . $this->pre .
                        "order_goods SET send_number = '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
            }

            if (!$this->query($sql)) {
                $msg .= ECTouch::db()->error();
                return false;
            }
        }

        /* 发送邮件 */
        ECTouch::view()->assign('virtual_card', $cards);
        ECTouch::view()->assign('order', $order);
        ECTouch::view()->assign('goods', $goods);

        ECTouch::view()->assign('send_time', date('Y-m-d H:i:s'));
        ECTouch::view()->assign('shop_name', C('shop_name'));
        ECTouch::view()->assign('send_date', date('Y-m-d'));
        ECTouch::view()->assign('sent_date', date('Y-m-d'));

        $tpl = model('Base')->get_mail_template('virtual_card');
        $content = ECTouch::view()->fetch('str:' . $tpl['template_content']);
        send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);

        return true;
    }

    /**
     *  返回虚拟卡信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    function virtual_card_result($order_sn, $goods) {
        /* 获取已经发送的卡片数据 */
        $sql = "SELECT card_sn, card_password, end_date, crc32 FROM " . $this->pre . "virtual_card WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";

        $cards = array();
        $array = $this->query($sql);
        foreach ($array as $key=>$row){
            /* 卡号和密码解密 */
            if ($row['crc32'] == 0 || $row['crc32'] == crc32(AUTH_KEY)) {
                $row['card_sn'] = decrypt($row['card_sn']);
                $row['card_password'] = decrypt($row['card_password']);
            } elseif ($row['crc32'] == crc32(OLD_AUTH_KEY)) {
                $row['card_sn'] = decrypt($row['card_sn'], OLD_AUTH_KEY);
                $row['card_password'] = decrypt($row['card_password'], OLD_AUTH_KEY);
            } else {
                $row['card_sn'] = '***';
                $row['card_password'] = '***';
            }
            
            $cards[] = array('card_sn' => $row['card_sn'], 'card_password' => $row['card_password'], 'end_date' => date(C('date_format'), $row['end_date']));
        }

        return $cards;
    }

}
