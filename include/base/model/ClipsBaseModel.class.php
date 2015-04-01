<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ClipsBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 用户基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ClipsBaseModel extends BaseModel {

    protected $table = '';

    /**
     *  获取指定用户的收藏商品列表
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $arr
     */
    public function get_collection_goods($user_id, $num = 10, $start = 0) {
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.market_price, g.shop_price AS org_price, ' .
                "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, " .
                'g.promote_price, g.promote_start_date,g.promote_end_date, c.rec_id, c.is_attention' .
                ' FROM ' . $this->pre . 'collect_goods AS c' .
                ' LEFT JOIN ' . $this->pre . 'goods AS g ' .
                'ON g.goods_id = c.goods_id ' .
                ' LEFT JOIN ' . $this->pre . 'member_price AS mp ' .
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " .
                " WHERE c.user_id = '$user_id' ORDER BY c.rec_id DESC limit $start, $num";
        $res = $this->query($sql);

        $goods_list = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                if ($row['promote_price'] > 0) {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }
                $goods_list[$row['goods_id']]['rec_id'] = $row['rec_id'];
                $goods_list[$row['goods_id']]['is_attention'] = $row['is_attention'];
                $goods_list[$row['goods_id']]['goods_id'] = $row['goods_id'];
                $goods_list[$row['goods_id']]['goods_name'] = $row['goods_name'];
                $goods_list[$row['goods_id']]['goods_thumb'] = get_image_path(0, $row['goods_thumb']);
                $goods_list[$row['goods_id']]['market_price'] = price_format($row['market_price']);
                $goods_list[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
                $goods_list[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
                $goods_list[$row['goods_id']]['url'] = url('goods/index', array('id' => $row['goods_id']));
            }
        }

        return $goods_list;
    }

    /**
     *  查看此商品是否已进行过缺货登记
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $goods_id       商品ID
     * @return  int
     */
    public function get_booking_rec($user_id, $goods_id) {
        $this->table = 'booking_goods';
        $condition['user_id'] = $user_id;
        $condition['goods_id'] = $goods_id;
        $condition['is_dispose'] = 0;
        return $this->count($condition);
    }

    /**
     *  获取指定用户的留言
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $user_name      用户名
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $msg            留言及回复列表
     * @return  string  $order_id       订单ID
     */
    public function get_message_list($user_id, $user_name, $num, $start, $order_id = 0) {
        $this->table = 'feedback';
        /* 获取留言数据 */
        $condition['parent_id'] = 0;
        $condition['user_id'] = $user_id;
        if ($order_id) {
            $condition['order_id'] = $order_id;
        } else {
            $condition['order_id'] = 0;
            $condition['user_name'] = $_SESSION['user_name'];
        }
        $list = $this->select($condition, '*', 'msg_time DESC', $start . ',' . $num);

        $msg = array();
        if (is_array($list)) {
            foreach ($list as $vo) {
                $reply = array();

                $condition2['parent_id'] = $vo['msg_id'];
                $reply = $this->find($condition2, 'user_name, user_email, msg_time, msg_content');

                if ($reply) {
                    $msg[$vo['msg_id']]['re_user_name'] = $reply['user_name'];
                    $msg[$vo['msg_id']]['re_user_email'] = $reply['user_email'];
                    $msg[$vo['msg_id']]['re_msg_time'] = local_date(C('time_format'), $reply['msg_time']);
                    $msg[$vo['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
                }
                $msg[$vo['msg_id']]['url'] = url('user/del_msg', array('id' => $vo['msg_id'], 'order_id' => $vo['order_id']));
                $msg[$vo['msg_id']]['msg_content'] = nl2br(htmlspecialchars($vo['msg_content']));
                $msg[$vo['msg_id']]['msg_time'] = local_date(C('time_format'), $vo['msg_time']);
                $msg[$vo['msg_id']]['msg_type'] = $order_id ? $vo['user_name'] : L('type.' . $vo['msg_type']);
                $msg[$vo['msg_id']]['msg_title'] = nl2br(htmlspecialchars($vo['msg_title']));
                $msg[$vo['msg_id']]['message_img'] = $vo['message_img'];
                $msg[$vo['msg_id']]['order_id'] = $vo['order_id'];
            }
        }

        return $msg;
    }

    /**
     *  添加留言函数
     * @access  public
     * @param   array       $message
     * @return  boolen      $bool
     */
    public function add_message($message) {
        $upload_size_limit = C('upload_size_limit') == '-1' ? ini_get('upload_max_filesize') : C('upload_size_limit');
        $status = 1 - C('message_check');

        $last_char = strtolower($upload_size_limit{strlen($upload_size_limit) - 1});

        switch ($last_char) {
            case 'm':
                $upload_size_limit *= 1024 * 1024;
                break;
            case 'k':
                $upload_size_limit *= 1024;
                break;
        }

        if ($message['upload']) {
            if ($_FILES['message_img']['size'] / 1024 > $upload_size_limit) {
                ECTouch::err()->add(sprintf(L('upload_file_limit'), $upload_size_limit));
                return false;
            }
            $img_name = upload_file($_FILES['message_img'], 'feedbackimg');

            if ($img_name === false) {
                return false;
            }
        } else {
            $img_name = '';
        }

        if (empty($message['msg_title'])) {
            ECTouch::err()->add(L('msg_title_empty'));
            return false;
        }

        $message['msg_area'] = isset($message['msg_area']) ? intval($message['msg_area']) : 0;

        $data['msg_id'] = NULL;
        $data['parent_id'] = 0;
        $data['user_id'] = $message['user_id'];
        $data['user_name'] = $message['user_name'];
        $data['user_email'] = $message['user_email'];
        $data['msg_title'] = $message['msg_title'];
        $data['msg_type'] = $message['msg_type'];
        $data['msg_status'] = $status;
        $data['msg_content'] = $message['msg_content'];
        $data['msg_time'] = gmtime();
        $data['message_img'] = $img_name;
        $data['order_id'] = $message['order_id'];
        $data['msg_area'] = $message['msg_area'];
        $this->table = 'feedback';
        $this->insert($data);

        return true;
    }

    /**
     *  验证性的删除某个tag
     * @access  public
     * @param   int         $tag_words      tag的ID
     * @param   int         $user_id        用户的ID
     * @return  boolen      bool
     */
    public function delete_tag($tag_words, $user_id) {
        $this->table = 'tag';
        $condition['tag_words'] = $tag_words;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     *  获取某用户的缺货登记列表
     * @access  public
     * @param   int     $user_id        用户ID
     * @param   int     $num            列表最大数量
     * @param   int     $start          列表其实位置
     * @return  array   $booking
     */
    public function get_booking_list($user_id, $num, $start) {
        $booking = array();
        $sql = "SELECT bg.rec_id, bg.goods_id, bg.goods_number, bg.booking_time, bg.dispose_note, g.goods_name " .
                "FROM " . $this->pre . "booking_goods AS bg , " . $this->pre . "goods AS g" . " WHERE bg.goods_id = g.goods_id AND bg.user_id = '$user_id' ORDER BY bg.booking_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                if (empty($vo['dispose_note'])) {
                    $vo['dispose_note'] = 'N/A';
                }
                $booking[] = array('rec_id' => $vo['rec_id'],
                    'goods_name' => $vo['goods_name'],
                    'goods_number' => $vo['goods_number'],
                    'booking_time' => local_date(C('date_format'), $vo['booking_time']),
                    'dispose_note' => $vo['dispose_note'],
                    'url' => url('goods/index', array('id' => $vo['goods_id'])));
            }
        }

        return $booking;
    }

    /**
     *  获取某用户的缺货登记列表
     * @access  public
     * @param   int     $goods_id    商品ID
     * @return  array   $info
     */
    public function get_goodsinfo($goods_id) {
        $info = array();
        $this->table = 'goods';
        $condition['goods_id'] = $goods_id;
        $info['goods_name'] = $this->field('goods_name', $condition);
        $info['goods_number'] = 1;
        $info['id'] = $goods_id;

        if (!empty($_SESSION['user_id'])) {
            $row = array();
            $sql = "SELECT ua.consignee, ua.email, ua.tel, ua.mobile " .
                    "FROM " . $this->pre . "user_address AS ua, " . $this->pre . "users AS u" .
                    " WHERE u.address_id = ua.address_id AND u.user_id = '$_SESSION[user_id]'";
            $row = $this->row($sql);
            $info['consignee'] = empty($row['consignee']) ? '' : $row['consignee'];
            $info['email'] = empty($row['email']) ? '' : $row['email'];
            $info['tel'] = empty($row['mobile']) ? (empty($row['tel']) ? '' : $row['tel']) : $row['mobile'];
        }

        return $info;
    }

    /**
     *  验证删除某个收藏商品
     * @access  public
     * @param   int         $booking_id     缺货登记的ID
     * @param   int         $user_id        会员的ID
     * @return  boolen      $bool
     */
    public function delete_booking($booking_id, $user_id) {
        $this->table = 'booking_goods';
        $condition['rec_id'] = $booking_id;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     * 添加缺货登记记录到数据表
     * @access  public
     * @param   array $booking
     * @return void
     */
    public function add_booking($booking) {
        $this->table = 'booking_goods';
        $data['user_id'] = $_SESSION['user_id'];
        $data['email'] = $booking['email'];
        $data['link_man'] = $booking['linkman'];
        $data['tel'] = $booking['tel'];
        $data['goods_id'] = $booking['goods_id'];
        $data['goods_desc'] = $booking['desc'];
        $data['goods_number'] = $booking['goods_amount'];
        $data['booking_time'] = gmtime();
        return $this->insert($data);
    }

    /**
     * 插入会员账目明细
     * @access  public
     * @param   array     $surplus  会员余额信息
     * @param   string    $amount   余额
     * @return  int
     */
    public function insert_user_account($surplus, $amount) {
        $this->table = 'user_account';
        $data['user_id'] = $surplus['user_id'];
        $data['admin_user'] = '';
        $data['amount'] = $amount;
        $data['add_time'] = gmtime();
        $data['paid_time'] = 0;
        $data['admin_note'] = '';
        $data['user_note'] = $surplus['user_note'];
        $data['process_type'] = $surplus['process_type'];
        $data['payment'] = $surplus['payment'];
        $data['is_paid'] = 0;
        return $this->insert($data);
    }

    /**
     * 更新会员账目明细
     * @access  public
     * @param   array     $surplus  会员余额信息
     * @return  int
     */
    public function update_user_account($surplus) {
        $this->table = 'user_account';
        $data['amount'] = $surplus['amount'];
        $data['user_note'] = $surplus['user_note'];
        $data['payment'] = $surplus['payment'];
        $condition['id'] = $surplus['rec_id'];
        $this->update($condition, $data);

        return $surplus['rec_id'];
    }

    /**
     * 将支付LOG插入数据表
     * @access  public
     * @param   integer     $id         订单编号
     * @param   float       $amount     订单金额
     * @param   integer     $type       支付类型
     * @param   integer     $is_paid    是否已支付
     * @return  int
     */
    public function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0) {
        $this->table = 'pay_log';
        $data['order_id'] = $id;
        $data['order_amount'] = $amount;
        $data['order_type'] = $type;
        $data['is_paid'] = $is_paid;
        return $this->insert($data);
    }

    /**
     * 取得上次未支付的pay_lig_id
     * @access  public
     * @param   array     $surplus_id  余额记录的ID
     * @param   array     $pay_type    支付的类型：预付款/订单支付
     * @return  int
     */
    public function get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS) {
        $this->table = 'pay_log';
        $condition['order_id'] = $surplus_id;
        $condition['order_type'] = $pay_type;
        $condition['is_paid'] = 0;
        return $this->field('log_id', $condition);
    }

    /**
     * 根据ID获取当前余额操作信息
     * @access  public
     * @param   int     $surplus_id  会员余额的ID
     * @return  int
     */
    public function get_surplus_info($surplus_id) {
        $this->table = 'user_account';
        $condition['id'] = $surplus_id;
        return $this->find($condition);
    }

    /**
     * 取得已安装的支付方式(其中不包括线下支付的)
     * @param   bool    $include_balance    是否包含余额支付（冲值时不应包括）
     * @return  array   已安装的配送方式列表
     */
    public function get_online_payment_list($include_balance = true) {
        $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc ' .
                'FROM ' . $this->pre . "touch_payment WHERE enabled = 1 AND is_cod <> 1";
        if (!$include_balance) {
            $sql .= " AND pay_code <> 'balance' ";
        }
        $modules = M()->query($sql);
        //支付插件排序
        if (isset($modules)) {
            /* 将财付通提升至第二个显示 */
            foreach ($modules as $k => $v) {
                if ($v['pay_code'] == 'tenpay') {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }
            /* 将快钱直连银行显示在快钱之后 */
            foreach ($modules as $k => $v) {
                if (strpos($v['pay_code'], 'kuaiqian') !== false) {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }

            /* 将快钱提升至第一个显示 */
            foreach ($modules as $k => $v) {
                if ($v['pay_code'] == 'kuaiqian') {
                    $tenpay = $modules[$k];
                    unset($modules[$k]);
                    array_unshift($modules, $tenpay);
                }
            }
        }

        return $modules;
    }

    /**
     * 查询会员余额的操作记录
     * @access  public
     * @param   int     $user_id    会员ID
     * @param   int     $num        每页显示数量
     * @param   int     $start      开始显示的条数
     * @return  array
     */
    public function get_account_log($user_id, $num, $start) {
        $account_log = array();
        $sql = 'SELECT * FROM ' . $this->pre . "user_account WHERE user_id = '$user_id'" .
                " AND process_type " . db_create_in(array(SURPLUS_SAVE, SURPLUS_RETURN)) .
                " ORDER BY add_time DESC limit " . $start . ',' . $num;
        $list = $this->query($sql);

        if (is_array($list)) {
            foreach ($list as $vo) {
                $vo['add_time'] = local_date(C('date_format'), $vo['add_time']);
                $vo['admin_note'] = nl2br(htmlspecialchars($vo['admin_note']));
                $vo['short_admin_note'] = ($vo['admin_note'] > '') ? sub_str($vo['admin_note'], 30) : 'N/A';
                $vo['user_note'] = nl2br(htmlspecialchars($vo['user_note']));
                $vo['short_user_note'] = ($vo['user_note'] > '') ? sub_str($vo['user_note'], 30) : 'N/A';
                $vo['pay_status'] = ($vo['is_paid'] == 0) ? L('un_confirm') : L('is_confirm');
                $vo['amount'] = price_format(abs($vo['amount']), false);

                /* 会员的操作类型： 冲值，提现 */
                if ($vo['process_type'] == 0) {
                    $vo['type'] = L('surplus_type_0');
                } else {
                    $vo['type'] = L('surplus_type_1');
                }

                /* 支付方式的ID */
                $this->table = 'touch_payment';
                $condition['pay_name'] = $vo['payment'];
                $condition['enabled'] = 1;
                $pid = $this->field('pay_id', $condition);

                /* 如果是预付款而且还没有付款, 允许付款 */
                if (($vo['is_paid'] == 0) && ($vo['process_type'] == 0)) {
                    $vo['handle'] = '<a href="' . url('user/pay') . '&id=' . $vo['id'] . '&pid=' . $pid . '" class="btn btn-default">' . L('pay') . '</a>';
                }

                $account_log[] = $vo;
            }

            return $account_log;
        } else {
            return false;
        }
    }

    /**
     *  删除未确认的会员帐目信息
     * @access  public
     * @param   int         $rec_id     会员余额记录的ID
     * @param   int         $user_id    会员的ID
     * @return  boolen
     */
    public function del_user_account($rec_id, $user_id) {
        $this->table = 'user_account';
        $condition['is_paid'] = 0;
        $condition['id'] = $rec_id;
        $condition['user_id'] = $user_id;
        return $this->delete($condition);
    }

    /**
     * 查询会员余额的数量
     * @access  public
     * @param   int     $user_id        会员ID
     * @return  int
     */
    public function get_user_surplus($user_id) {
        $this->table = 'account_log';
        $condition['user_id'] = $user_id;
        return $this->field('SUM(user_money)', $condition);
    }

    /**
     * 查询会员的红包金额
     * @access  public
     * @param   integer     $user_id
     * @return  void
     */
    public function get_user_bonus($user_id = 0) {
        if ($user_id == 0) {
            $user_id = $_SESSION['user_id'];
        }

        $sql = "SELECT SUM(bt.type_money) AS bonus_value, COUNT(*) AS bonus_count " .
                "FROM " . $this->pre . "user_bonus AS ub, " . $this->pre . "bonus_type AS bt " .
                "WHERE ub.user_id = '$user_id' AND ub.bonus_type_id = bt.type_id AND ub.order_id = 0";
        $row = $this->row($sql);

        return $row;
    }

    /**
     * 获取用户中心默认页面所需的数据
     * @access  public
     * @param   int         $user_id            用户ID
     * @return  array       $info               默认页面所需资料数组
     */
    public function get_user_default($user_id) {
        $user_bonus = $this->get_user_bonus();

        $sql = "SELECT pay_points, user_money, credit_line, last_login, is_validated FROM " . $this->pre . "users WHERE user_id = '$user_id'";
        $row = $this->row($sql);
        $info = array();
        $info['username'] = stripslashes($_SESSION['user_name']);
        $info['shop_name'] = C('shop_name');
        $info['integral'] = $row['pay_points'] . C('integral_name');
        /* 增加是否开启会员邮件验证开关 */
        $info['is_validate'] = (C('member_email_validate') && !$row['is_validated']) ? 0 : 1;
        $info['credit_line'] = $row['credit_line'];
        $info['formated_credit_line'] = price_format($info['credit_line'], false);

        //新增获取用户头像，昵称
        $u_row = '';
        if(class_exists('WechatController')){
            if (method_exists('WechatController', 'get_avatar')) {
                $u_row = call_user_func(array('WechatController', 'get_avatar'), $user_id);
            }
        }
        if ($u_row) {
            $info['nickname'] = $u_row['nickname'];
            $info['headimgurl'] = $u_row['headimgurl'];
        } else {
            $info['nickname'] = $info['username'];
            $info['headimgurl'] = __PUBLIC__ . '/images/get_avatar.png';
        }

        //如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
        $last_time = !isset($_SESSION['last_time']) ? $row['last_login'] : $_SESSION['last_time'];

        if ($last_time == 0) {
            $_SESSION['last_time'] = $last_time = gmtime();
        }

        $info['last_time'] = local_date(C('time_format'), $last_time);
        $info['surplus'] = price_format($row['user_money'], false);
        $info['bonus'] = sprintf(L('user_bonus_info'), $user_bonus['bonus_count'], price_format($user_bonus['bonus_value'], false));

        $this->table = 'order_info';
        $condition = "user_id = '" . $user_id . "' AND add_time > '" . local_strtotime('-1 months') . "'";
        $info['order_count'] = $this->count($condition);

        $condition = "user_id = '" . $user_id . "' AND shipping_time > '" . $last_time . "'" . order_query_sql('shipped');
        $info['shipped_order'] = $this->select($condition, 'order_id, order_sn');

        return $info;
    }

    /**
     * 获得指定用户、商品的所有标记
     * @access  public
     * @param   integer $goods_id
     * @param   integer $user_id
     * @return  array
     */
    public function get_tags($goods_id = 0, $user_id = 0) {
        $where = '';
        if ($goods_id > 0) {
            $where .= " goods_id = '$goods_id'";
        }

        if ($user_id > 0) {
            if ($goods_id > 0) {
                $where .= " AND";
            }
            $where .= " user_id = '$user_id'";
        }

        if ($where > '') {
            $where = ' WHERE' . $where;
        }

        $sql = 'SELECT tag_id, user_id, tag_words, COUNT(tag_id) AS tag_count' .
                ' FROM ' . $this->pre . "tag$where GROUP BY tag_words";
        $arr = $this->query($sql);

        return $arr;
    }

    /**
     * 添加商品标签
     * @access  public
     * @param   integer     $id
     * @param   string      $tag
     * @return  void
     */
    public function add_tag($id, $tag) {
        $this->table = 'tag';
        if (empty($tag)) {
            return;
        }

        $arr = explode(',', $tag);

        foreach ($arr AS $val) {
            /* 检查是否重复 */
            $condition['user_id'] = $_SESSION['user_id'];
            $condition['goods_id'] = $id;
            $condition['tag_words'] = $val;
            $total = $this->count($condition);

            if ($total == 0) {
                $data['user_id'] = $_SESSION['user_id'];
                $data['goods_id'] = $id;
                $data['tag_words'] = $val;
                $this->insert($data);
            }
        }
    }

    /**
     * 取得用户等级信息
     * @access   public
     * @author   Xuan Yan
     * @return array
     */
    public function get_rank_info() {
        if (!empty($_SESSION['user_rank'])) {
            $sql = "SELECT rank_name, special_rank FROM " . $this->pre . "user_rank WHERE rank_id = '$_SESSION[user_rank]'";
            $row = $this->row($sql);
            if (empty($row)) {
                return array();
            }
            $rank_name = $row['rank_name'];
            if ($row['special_rank']) {
                return array('rank_name' => $rank_name);
            } else {
                $this->table = 'users';
                $condition['user_id'] = $_SESSION['user_id'];
                $user_rank = $this->field('rank_points', $condition);
                $this->table = 'user_rank';
                $sql = "SELECT rank_name,min_points FROM " . $this->pre . "user_rank WHERE min_points > '$user_rank' ORDER BY min_points ASC LIMIT 1";
                $rt = $this->row($sql);
                $next_rank_name = $rt['rank_name'];
                $next_rank = $rt['min_points'] - $user_rank;
                return array('rank_name' => $rank_name, 'next_rank_name' => $next_rank_name, 'next_rank' => $next_rank);
            }
        } else {
            return array();
        }
    }

    /**
     *  获取用户参与活动信息
     * @access  public
     * @param   int     $user_id        用户id
     * @return  array
     */
    public function get_user_prompt($user_id) {
        $prompt = array();
        $now = gmtime();
        /* 夺宝奇兵 */
        $sql = "SELECT act_id, goods_name, end_time " .
                "FROM " . $this->pre . "goods_activity WHERE act_type = '" . GAT_SNATCH . "'" .
                " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $this->query($sql);

        if (is_array($res)) {
            foreach ($res as $row) {
                $act_id = $row['act_id'];
                $result = model('ActivityBase')->get_snatch_result($act_id);
                if (isset($result['order_count']) && $result['order_count'] == 0 && $result['user_id'] == $user_id) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_snatch'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
                if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_auction'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
            }
        }

        /* 竞拍 */
        $sql = "SELECT act_id, goods_name, end_time " .
                "FROM " . $this->pre . "goods_activity WHERE act_type = '" . GAT_AUCTION . "'" .
                " AND (is_finished = 1 OR (is_finished = 0 AND end_time <= '$now'))";
        $res = $this->query($sql);
        if (is_array($res)) {
            foreach ($res as $row) {
                $act_id = $row['act_id'];
                $auction = model('GoodsBase')->auction_info($act_id);
                if (isset($auction['last_bid']) && $auction['last_bid']['bid_user'] == $user_id && $auction['order_count'] == 0) {
                    $prompt[] = array(
                        'text' => sprintf(L('your_auction'), $row['goods_name'], $row['act_id']),
                        'add_time' => $row['end_time']
                    );
                }
            }
        }

        /* 排序 */
        $cmp = create_function('$a, $b', 'if($a["add_time"] == $b["add_time"]){return 0;};return $a["add_time"] < $b["add_time"] ? 1 : -1;');
        usort($prompt, $cmp);

        /* 格式化时间 */
        foreach ($prompt as $key => $val) {
            $prompt[$key]['formated_time'] = local_date(C('time_format'), $val['add_time']);
        }

        return $prompt;
    }

    /**
     *  获取用户评论
     *
     * @access  public
     * @param   int     $user_id        用户id
     * @param   int     $page_size      列表最大数量
     * @param   int     $start          列表起始页
     * @return  array
     */
    public function get_comment_list($user_id, $page_size, $start) {
        $sql = "SELECT c.*, g.goods_name AS cmt_name, r.content AS reply_content, r.add_time AS reply_time " .
                " FROM " . $this->pre . "comment AS c " .
                " LEFT JOIN " . $this->pre . "comment AS r " .
                " ON r.parent_id = c.comment_id AND r.parent_id > 0 " .
                " LEFT JOIN " . $this->pre . "goods AS g " .
                " ON c.comment_type=0 AND c.id_value = g.goods_id " .
                " WHERE c.user_id='$user_id' limit " . $start . ',' . $page_size;
        $res = $this->query($sql);

        $comments = array();
        $to_article = array();
        if (is_array($res)) {
            foreach ($res as $row) {
                $row['formated_add_time'] = local_date(C('time_format'), $row['add_time']);
                if ($row['reply_time']) {
                    $row['formated_reply_time'] = local_date(C('time_format'), $row['reply_time']);
                }
                if ($row['comment_type'] == 1) {
                    $to_article[] = $row["id_value"];
                }
                $comments[] = $row;
            }
        }

        if ($to_article) {
            $sql = "SELECT article_id , title FROM " . $this->pre . "article WHERE " . db_create_in($to_article, 'article_id');
            $arr = $this->query($sql);
            $to_cmt_name = array();
            foreach ($arr as $row) {
                $to_cmt_name[$row['article_id']] = $row['title'];
            }

            foreach ($comments as $key => $row) {
                if ($row['comment_type'] == 1) {
                    $comments[$key]['cmt_name'] = isset($to_cmt_name[$row['id_value']]) ? $to_cmt_name[$row['id_value']] : '';
                }
            }
        }

        return $comments;
    }

    /**
     * 记录帐户变动
     * @param   int     $user_id        用户id
     * @param   float   $user_money     可用余额变动
     * @param   float   $frozen_money   冻结余额变动
     * @param   int     $rank_points    等级积分变动
     * @param   int     $pay_points     消费积分变动
     * @param   string  $change_desc    变动说明
     * @param   int     $change_type    变动类型：参见常量文件
     * @return  void
     */
    function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER) {
        /* 插入帐户变动记录 */
        $account_log = array(
            'user_id' => $user_id,
            'user_money' => $user_money,
            'frozen_money' => $frozen_money,
            'rank_points' => $rank_points,
            'pay_points' => $pay_points,
            'change_time' => gmtime(),
            'change_desc' => $change_desc,
            'change_type' => $change_type
        );
        $this->table = 'account_log';
        $this->insert($account_log);
        /* 更新用户信息 */
        $sql = "UPDATE " . $this->pre .
                "users SET user_money = user_money + ('$user_money')," .
                " frozen_money = frozen_money + ('$frozen_money')," .
                " rank_points = rank_points + ('$rank_points')," .
                " pay_points = pay_points + ('$pay_points')" .
                " WHERE user_id = '$user_id' LIMIT 1";
        $this->query($sql);
    }

    /**
     * 获取第三方登录配置信息 
     * @param type $type
     * @return type
     */
    function get_third_user_info($type) {
        $sql = "SELECT auth_config FROM " . $this->pre . "touch_auth WHERE `from` = '$type'";
        $info = $this->row($sql);
        if ($info) {
            $user = unserialize($info['auth_config']);
            $config = array();
            foreach ($user as $key => $value) {
                $config[$value['name']] = $value['value'];
            }
            return $config;
        }
    }

}
