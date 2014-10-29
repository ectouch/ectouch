<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：UserController.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTouch用户中心
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class UserController extends CommonController
{

    protected $user_id;

    protected $action;

    protected $back_act = '';

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct();
        // 属性赋值
        $this->user_id = $_SESSION['user_id'];
        $this->action = ACTION_NAME;
        // 验证登录
        $this->check_login();
        // 用户信息
        $info = model('ClipsBase')->get_user_default($this->user_id);
        // 如果是显示页面，对页面进行相应赋值
        assign_template();
        $this->assign('action', $this->action);
        $this->assign('info', $info);
    }

    /**
     * 会员中心欢迎页
     */
    public function index()
    {
        // 用户等级
        if ($rank = model('ClipsBase')->get_rank_info()) {
            $this->assign('rank_name', sprintf(L('your_level'), $rank['rank_name']));
        }
        // 收藏
        $goods_list = model('ClipsBase')->get_collection_goods($this->user_id, 5, 0);
        // 评论
        $comment_list = model('ClipsBase')->get_comment_list($this->user_id, 5, 0);
        // 浏览记录
        $history = insert_history();
        // 信息中心是否有新回复
        $sql = 'SELECT msg_id FROM ' . $this->model->pre . 'feedback WHERE parent_id IN (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 and f.user_id = ' . $this->user_id . ' and t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
        $rs = $this->model->query($sql);
        if ($rs) {
            $this->assign('new_msg', 1);
        }
        $this->assign('user_notice', C('user_notice'));
        $this->assign('goods_list', $goods_list);
        $this->assign('comment_list', $comment_list);
        $this->assign('history', $history);
        $this->assign('title', L('user_center'));
        $this->display('user.dwt');
    }

    /**
     * 账户中心
     */
    public function profile()
    {
        // 修改个人资料的处理
        if (IS_POST) {
            $email = I('post.email');
            $other['qq'] = $qq = I('post.extend_field2');
            $other['office_phone'] = $office_phone = I('post.extend_field3');
            $other['mobile_phone'] = $mobile_phone = I('post.extend_field5');
            $sel_question = I('post.sel_question');
            $passwd_answer = I('post.passwd_answer');
            
            // 读出所有扩展字段的id
            $where['type'] = 0;
            $where['display'] = 1;
            $fields_arr = $this->model->table('reg_fields')
                ->field('id')
                ->where($where)
                ->order('dis_order, id')
                ->select();
            if (empty($fields_arr)) {
                $fields_arr = array();
            }
            
            // 循环更新扩展用户信息
            foreach ($fields_arr as $val) {
                $extend_field_index = 'extend_field' . $val['id'];
                if (isset($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr(htmlspecialchars($_POST[$extend_field_index]), 0, 99) : htmlspecialchars($_POST[$extend_field_index]);
                    
                    $where_s['reg_field_id'] = $val['id'];
                    $where_s['user_id'] = $this->user_id;
                    $rs_s = $this->model->table('reg_extend_info')
                        ->where($where_s)
                        ->find();
                    
                    if ($rs_s) {
                        // 如果之前没有记录，则插入
                        $where_u['reg_field_id'] = $val['id'];
                        $where_u['user_id'] = $this->user_id;
                        $data_u['content'] = $temp_field_content;
                        $this->model->table('reg_extend_info')
                            ->data($data_u)
                            ->where($where_u)
                            ->update();
                    } else {
                        $data_i['user_id'] = $this->user_id;
                        $data_i['reg_field_id'] = $val['id'];
                        $data_i['content'] = $temp_field_content;
                        $this->model->table('reg_extend_info')
                            ->data($data_i)
                            ->insert();
                    }
                }
            }
            
            if (! empty($office_phone) && ! preg_match('/^[\d|\_|\-|\s]+$/', $office_phone)) {
                show_message(L('passport_js.office_phone_invalid'));
            }
            if (! is_email($email)) {
                show_message(L('msg_email_format'));
            }
            if (! empty($qq) && ! preg_match('/^\d+$/', $qq)) {
                show_message(L('passport_js.qq_invalid'));
            }
            if (! empty($mobile_phone) && ! preg_match('/^[\d-\s]+$/', $mobile_phone)) {
                show_message(L('passport_js.mobile_phone_invalid'));
            }
            
            // 写入密码提示问题和答案
            if (! empty($passwd_answer) && ! empty($sel_question)) {
                $where_up['user_id'] = $this->user_id;
                $data_up['passwd_question'] = $sel_question;
                $data_up['passwd_answer'] = $passwd_answer;
                $this->model->table('users')
                    ->data($data_up)
                    ->where($where_up)
                    ->update();
            }
            
            $profile = array(
                'user_id' => $this->user_id,
                'email' => I('post.email'),
                'sex' => I('post.sex', 0),
                'other' => isset($other) ? $other : array()
            );
            
            if (model('Users')->edit_profile($profile)) {
                show_message(L('edit_profile_success'), L('profile_lnk'), url('profile'), 'info');
            } else {
                if (self::$user->error == ERR_EMAIL_EXISTS) {
                    $msg = sprintf(L('email_exist'), $profile['email']);
                } else {
                    $msg = L('edit_profile_failed');
                }
                show_message($msg, '', '', 'info');
            }
            exit();
        }
        // 用户资料
        $user_info = model('Users')->get_profile($this->user_id);
        // 取出注册扩展字段
        $where = 'type < 2 and display = 1';
        $extend_info_list = $this->model->table('reg_fields')
            ->where($where)
            ->order('dis_order, id')
            ->select();
        
        $condition['user_id'] = $this->user_id;
        $extend_info_arr = $this->model->table('reg_extend_info')
            ->field('reg_field_id, content')
            ->where($condition)
            ->select();
        if (empty($extend_info_arr)) {
            $extend_info_arr = array();
        }
        
        $temp_arr = array();
        foreach ($extend_info_arr as $val) {
            $temp_arr[$val['reg_field_id']] = $val['content'];
        }
        
        foreach ($extend_info_list as $key => $val) {
            switch ($val['id']) {
                case 1:
                    unset($extend_info_list[$key]);
                    break;
                case 2:
                    $extend_info_list[$key]['content'] = $user_info['qq'];
                    break;
                case 3:
                    $extend_info_list[$key]['content'] = $user_info['office_phone'];
                    break;
                case 4:
                    unset($extend_info_list[$key]);
                    break;
                case 5:
                    $extend_info_list[$key]['content'] = $user_info['mobile_phone'];
                    break;
                default:
                    $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
            }
        }
        
        $this->assign('title', L('profile'));
        $this->assign('extend_info_list', $extend_info_list);
        // 密码提示问题
        $this->assign('passwd_questions', L('passwd_questions'));
        $this->assign('profile', $user_info);
        $this->display('user_profile.dwt');
    }

    /**
     * 资金管理
     */
    public function account_detail()
    {
        // 获取剩余余额
        $surplus_amount = model('ClipsBase')->get_user_surplus($this->user_id);
        if (empty($surplus_amount)) {
            $surplus_amount = 0;
        }
        
        // 获取余额记录
        $account_log = array();
        $where = 'user_id = ' . $this->user_id . ' AND user_money <> 0';
        $res = $this->model->table('account_log')
            ->where($where)
            ->order('log_id DESC')
            ->select();
        if (empty($res)) {
            $res = array();
        }
        foreach ($res as $k => $v) {
            $res[$k]['change_time'] = local_date(C('date_format'), $v['change_time']);
            $res[$k]['type'] = $v['user_money'] > 0 ? L('account_inc') : L('account_dec');
            $res[$k]['user_money'] = price_format(abs($v['user_money']), false);
            $res[$k]['frozen_money'] = price_format(abs($v['frozen_money']), false);
            $res[$k]['rank_points'] = abs($v['rank_points']);
            $res[$k]['pay_points'] = abs($v['pay_points']);
            $res[$k]['short_change_desc'] = sub_str($v['change_desc'], 60);
            $res[$k]['amount'] = $v['user_money'];
        }
        $this->assign('title', L('label_user_surplus'));
        $this->assign('surplus_amount', price_format($surplus_amount, false));
        $this->assign('account_log', $res);
        $this->display('user_account_detail.dwt');
    }

    /**
     * 获取未付款订单
     */
    public function not_pay_order_list()
    {
        $this->assign('pay', 0);
        $this->assign('title', L('not_pay_list'));
        $this->display('user_order_list.dwt');
    }

    /**
     * 获取全部订单
     */
    public function order_list()
    {
        $this->assign('pay', 1);
        $this->assign('title', L('order_list_lnk'));
        $this->display('user_order_list.dwt');
    }

    /**
     * ajax获取订单
     */
    public function async_order_list()
    {
        if (IS_AJAX) {
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            $pay = isset($_GET['pay']) ? intval($_GET['pay']) : 0;
            
            $order_list = model('Users')->get_user_orders($this->user_id, $pay, $limit, $start);
            foreach ($order_list as $key => $order) {
                $this->assign('orders', $order);
                $sayList[] = array(
                    'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                );
            }
            die(json_encode($sayList));
        } else {
            $this->redirect(url('index'));
        }
    }

    /**
     * 订单跟踪
     */
    public function order_tracking()
    {
        $order_id = I('get.order_id', 0);
        $ajax = I('get.ajax', 0);
        
        $where['user_id'] = $this->user_id;
        $where['order_id'] = $order_id;
        $orders = $this->model->table('order_info')
            ->field('order_id, order_sn, invoice_no, shipping_name, shipping_id')
            ->where($where)
            ->find();
        // 生成快递100查询接口链接
        $shipping = get_shipping_object($orders['shipping_id']);
        $query_link = $shipping->kuaidi100($orders['invoice_no']);
        
        $get_content = Http::doGet($query_link);
        
        $this->assign('title', L('order_tracking'));
        $this->assign('trackinfo', $get_content);
        $this->display('user_order_tracking.dwt');
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        // 订单详情
        $order = model('Users')->get_order_detail($order_id, $this->user_id);
        if ($order['order_status'] == OS_UNCONFIRMED) {
            $order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"" . url('user/cancel_order', array(
                'order_id' => $order['order_id']
            )) . "\" onclick=\"if (!confirm('" . L('confirm_cancel') . "')) return false;\">" . L('cancel') . "</a>";
        } else 
            if ($order['order_status'] == OS_SPLITED) {
                /* 对配送状态的处理 */
                if ($order['shipping_status'] == SS_SHIPPED) {
                    @$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"" . url('user/affirm_received', array(
                        'order_id' => $order['order_id']
                    )) . "\" onclick=\"if (!confirm('" . L('confirm_received') . "')) return false;\">" . L('received') . "</a>";
                } elseif ($order['shipping_status'] == SS_RECEIVED) {
                    @$order['handler'] = '<a class="btn btn-info ect-colorf" type="button" href="javascript:void(0);">' . L('ss_received') . '</a>';
                } else {
                    if ($order['pay_status'] == PS_UNPAYED) {
                        @$order['handler'] = "<a class=\"btn btn-infoect-colorf\" href=\"" . url('user/cancel_order', array(
                            'order_id' => $order['order_id']
                        )) . "\">" . L('pay_money') . "</a>";
                    } else {
                        //@$order['handler'] = "<a class=\"btn btn-info ect-colorf\" href=\"javascript:void(0);\">" . L('view_order') . "</a>";
                    }
                }
            } else {
                $order['handler'] = '<a class="btn btn-info ect-colorf" type="button" href="javascript:void(0);">' . L('os.' . $order['order_status']) . '</a>';
            }
        if ($order === false) {
            ECTouch::err()->show(L('back_home_lnk'), './');
            exit();
        }
        
        // 订单商品
        $goods_list = model('Order')->order_goods($order_id);
        foreach ($goods_list as $key => $value) {
            $goods_list[$key]['market_price'] = price_format($value['market_price'], false);
            $goods_list[$key]['goods_price'] = price_format($value['goods_price'], false);
            $goods_list[$key]['subtotal'] = price_format($value['subtotal'], false);
            $goods_list[$key]['tags'] = model('ClipsBase')->get_tags($value['goods_id']);
            $goods_list[$key]['goods_thumb'] = get_image_path($order_id, $value['goods_thumb']);
        }
        
        // 设置能否修改使用余额数
        if ($order['order_amount'] > 0) {
            if ($order['order_status'] == OS_UNCONFIRMED || $order['order_status'] == OS_CONFIRMED) {
                $user = model('Order')->user_info($order['user_id']);
                if ($user['user_money'] + $user['credit_line'] > 0) {
                    $this->assign('allow_edit_surplus', 1);
                    $this->assign('max_surplus', sprintf(L('max_surplus'), $user['user_money']));
                }
            }
        }
        
        // 未发货，未付款时允许更换支付方式
        if ($order['order_amount'] > 0 && $order['pay_status'] == PS_UNPAYED && $order['shipping_status'] == SS_UNSHIPPED) {
            $payment_list = model('Order')->available_payment_list(false, 0, true);
            
            // 过滤掉当前支付方式和余额支付方式
            if (is_array($payment_list)) {
                foreach ($payment_list as $key => $payment) {
                    if ($payment['pay_id'] == $order['pay_id'] || $payment['pay_code'] == 'balance') {
                        unset($payment_list[$key]);
                    }
                }
            }
            $this->assign('payment_list', $payment_list);
        }
        $order['pay_desc'] = html_out($order['pay_desc']);
        
        // 订单 支付 配送 状态语言项
        $order['order_status'] = L('os.' . $order['order_status']);
        $order['pay_status'] = L('ps.' . $order['pay_status']);
        $order['shipping_status'] = L('ss.' . $order['shipping_status']);
        
        $this->assign('title', L('order_detail'));
        $this->assign('order', $order);
        $this->assign('goods_list', $goods_list);
        $this->display('user_order_detail.dwt');
    }

    /**
     * 确认收货
     */
    public function affirm_received()
    {
        $order_id = I('get.order_id', 0, 'intval');
        if (model('Users')->affirm_received($order_id, $this->user_id)) {
            ecs_header("Location: " . url('order_list') . "\n");
            exit();
        } else {
            ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
        }
    }

    /**
     * 编辑使用余额支付的处理
     */
    public function edit_surplus()
    {
        
        // 检查订单号
        $order_id = intval($_POST['order_id']);
        if ($order_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查余额
        $surplus = floatval($_POST['surplus']);
        if ($surplus <= 0) {
            ECTouch::err()->add(L('error_surplus_invalid'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 取得订单order_id
        $order = model('Order')->order_info($order_id);
        if (empty($order)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单用户跟当前用户是否一致
        if ($_SESSION['user_id'] != $order['user_id']) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单是否未付款，检查应付款金额是否大于0
        if ($order['pay_status'] != PS_UNPAYED || $order['order_amount'] <= 0) {
            ECTouch::err()->add(L('error_order_is_paid'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 计算应付款金额（减去支付费用）
        $order['order_amount'] -= $order['pay_fee'];
        
        // 余额是否超过了应付款金额，改为应付款金额
        if ($surplus > $order['order_amount']) {
            $surplus = $order['order_amount'];
        }
        
        // 取得用户信息
        $user = model('Order')->user_info($_SESSION['user_id']);
        
        // 用户帐户余额是否足够
        if ($surplus > $user['user_money'] + $user['credit_line']) {
            ECTouch::err()->add(L('error_surplus_not_enough'));
            ECTouch::err()->show(L('order_detail'), url('order_detail', array(
                'order_id' => $order_id
            )));
        }
        
        // 修改订单，重新计算支付费用
        $order['surplus'] += $surplus;
        $order['order_amount'] -= $surplus;
        if ($order['order_amount'] > 0) {
            $cod_fee = 0;
            if ($order['shipping_id'] > 0) {
                $regions = array(
                    $order['country'],
                    $order['province'],
                    $order['city'],
                    $order['district']
                );
                $shipping = model('Shipping')->shipping_area_info($order['shipping_id'], $regions);
                if ($shipping['support_cod'] == '1') {
                    $cod_fee = $shipping['pay_fee'];
                }
            }
            
            $pay_fee = 0;
            if ($order['pay_id'] > 0) {
                $pay_fee = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);
            }
            
            $order['pay_fee'] = $pay_fee;
            $order['order_amount'] += $pay_fee;
        }
        
        // 如果全部支付，设为已确认、已付款
        
        if ($order['order_amount'] == 0) {
            if ($order['order_status'] == OS_UNCONFIRMED) {
                $order['order_status'] = OS_CONFIRMED;
                $order['confirm_time'] = gmtime();
            }
            $order['pay_status'] = PS_PAYED;
            $order['pay_time'] = gmtime();
        }
        $order = addslashes_deep($order);
        model('Users')->update_order($order_id, $order);
        
        // 更新用户余额
        $change_desc = sprintf(L('pay_order_by_surplus'), $order['order_sn']);
        model('ClipsBase')->log_account_change($user['user_id'], (- 1) * $surplus, 0, 0, 0, $change_desc);
        // 销量
        $this->update_touch_goods($order_id);
        // 跳转
        $url = url('order_detail', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 更改支付方式的处理
     */
    public function edit_payment()
    {
        
        // 检查支付方式
        $pay_id = intval($_POST['pay_id']);
        if ($pay_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        $payment_info = model('Order')->payment_info($pay_id);
        if (empty($payment_info)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单号
        $order_id = intval($_POST['order_id']);
        if ($order_id <= 0) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 取得订单
        $order = model('Order')->order_info($order_id);
        if (empty($order)) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单用户跟当前用户是否一致
        if ($_SESSION['user_id'] != $order['user_id']) {
            ecs_header("Location: " . url('index/index') . "\n");
            exit();
        }
        
        // 检查订单是否未付款和未发货 以及订单金额是否为0 和支付id是否为改变
        if ($order['pay_status'] != PS_UNPAYED || $order['shipping_status'] != SS_UNSHIPPED || $order['goods_amount'] <= 0 || $order['pay_id'] == $pay_id) {
            $url = url('order_detail', array(
                'order_id' => $order_id
            ));
            ecs_header("Location: $url\n");
            exit();
        }
        
        $order_amount = $order['order_amount'] - $order['pay_fee'];
        $pay_fee = pay_fee($pay_id, $order_amount);
        $order_amount += $pay_fee;
        
        $data['pay_id'] = $pay_id;
        $data['pay_name'] = $payment_info['pay_name'];
        $data['pay_fee'] = $pay_fee;
        $data['order_amount'] = $order_amount;
        $where['order_id'] = $order_id;
        $this->model->table('order_info')
            ->data($data)
            ->where($where)
            ->update();
        
        // 跳转
        $url = url('order_detail', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 取消订单
     */
    public function cancel_order()
    {
        $order_id = I('get.order_id', 0, 'intval');
        
        if (model('Users')->cancel_order($order_id, $this->user_id)) {
            $url = url('order_list');
            ecs_header("Location: $url\n");
            exit();
        } else {
            ECTouch::err()->show(L('order_list_lnk'), url('order_list'));
        }
    }

    /**
     * 收货地址列表界面
     */
    public function address_list()
    {
        if (IS_AJAX) {
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            // 获得用户所有的收货人信息
            $consignee_list = model('Users')->get_consignee_list($this->user_id, 0, $limit, $start);
            if ($consignee_list) {
                foreach ($consignee_list as $k => $v) {
                    $address = '';
                    if ($v['province']) {
                        $address .= model('RegionBase')->get_region_name($v['province']);
                    }
                    if ($v['city']) {
                        $address .= model('RegionBase')->get_region_name($v['city']);
                    }
                    if ($v['district']) {
                        $address .= model('RegionBase')->get_region_name($v['district']);
                    }
                    $v['address'] = $address . ' ' . $v['address'];
                    $v['url'] = url('user/edit_address', array(
                        'id' => $v['address_id']
                    ));
                    $this->assign('consignee', $v);
                    $sayList[] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                    );
                }
            }
            die(json_encode($sayList));
            exit();
        }
        // 赋值于模板
        $this->assign('title', L('consignee_info'));
        $this->display('user_address_list.dwt');
    }
    
    // 添加收货地址
    public function add_address()
    {
        if (IS_POST) {
            $address = array(
                'user_id' => $this->user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => I('post.country', 0, 'intval'),
                'province' => I('post.province', 0, 'intval'),
                'city' => I('post.city', 0, 'intval'),
                'district' => I('post.district', 0, 'intval'),
                'address' => I('post.address'),
                'consignee' => I('post.consignee'),
                'mobile' => I('post.mobile')
            );
            
            if (model('Users')->update_address($address)) {
                show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
            }
            exit();
        }
        
        $province_list = model('RegionBase')->get_regions(1, 1);
        $city_list = model('RegionBase')->get_regions(2);
        $district_list = model('RegionBase')->get_regions(3);
        
        $this->assign('title', L('add_address'));
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        $this->assign('country_list', model('RegionBase')->get_regions());
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        
        $this->display('user_add_address.dwt');
    }

    /**
     * 编辑收货地址的处理
     */
    public function edit_address()
    {
        // 编辑收货地址
        if (IS_POST) {
            $address = array(
                'user_id' => $this->user_id,
                'address_id' => intval($_POST['address_id']),
                'country' => I('post.country', 0, 'intval'),
                'province' => I('post.province', 0, 'intval'),
                'city' => I('post.city', 0, 'intval'),
                'district' => I('post.district', 0, 'intval'),
                'address' => I('post.address'),
                'consignee' => I('post.consignee'),
                'mobile' => I('post.mobile')
            );
            
            if (model('Users')->update_address($address)) {
                show_message(L('edit_address_success'), L('address_list_lnk'), url('address_list'));
            }
            exit();
        }
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        
        // 获得用户对应收货人信息
        $consignee = model('Users')->get_consignee_list($_SESSION['user_id'], $id);
        
        $province_list = model('RegionBase')->get_regions(1, 1);
        $city_list = model('RegionBase')->get_regions(2, $consignee['province']);
        $district_list = model('RegionBase')->get_regions(3, $consignee['city']);
        
        $this->assign('title', L('edit_address'));
        $this->assign('consignee', $consignee);
        // 取得国家列表、商店所在国家、商店所在国家的省列表
        $this->assign('country_list', model('RegionBase')->get_regions());
        $this->assign('shop_province_list', model('RegionBase')->get_regions(1, C('shop_country')));
        $this->assign('province_list', $province_list);
        $this->assign('city_list', $city_list);
        $this->assign('district_list', $district_list);
        
        $this->display('user_edit_address.dwt');
    }

    /**
     * 删除收货地址
     */
    public function del_address_list()
    {
        $id = intval($_GET['id']);
        
        if (model('Users')->drop_consignee($id)) {
            $url = url('address_list');
            ecs_header("Location: $url\n");
            exit();
        } else {
            show_message(L('del_address_false'));
        }
    }

    /**
     * 信息中心
     */
    public function msg_list()
    {
        if (IS_AJAX) {
            $order_id = I('get.order_id', 0);
            $start = $_POST['last'];
            $limit = $_POST['amount'];
            
            // 获取信息
            $message_list = model('ClipsBase')->get_message_list($this->user_id, $_SESSION['user_name'], $limit, $start, $order_id);
            if (is_array($message_list)) {
                // 修改信息状态
                $sql = 'SELECT parent_id FROM ' . $this->model->pre . 'feedback WHERE parent_id in (SELECT f.msg_id FROM ' . $this->model->pre . 'feedback f LEFT JOIN ' . $this->model->pre . 'touch_feedback t ON f.msg_id = t.msg_id WHERE f.parent_id = 0 AND f.user_id = ' . $this->user_id . ' AND t.msg_read = 0 ORDER BY msg_time DESC) ORDER BY msg_time DESC';
                $rs = $this->model->query($sql);
                if ($rs) {
                    foreach ($rs as $v) {
                        $where['msg_id'] = $v['parent_id'];
                        $data['msg_read'] = 1;
                        $this->model->table('touch_feedback')
                            ->data($data)
                            ->where($where)
                            ->update();
                    }
                }
                foreach ($message_list as $key => $vo) {
                    $this->assign('msg', $vo);
                    $sayList[] = array(
                        'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
                    );
                }
            }
            echo json_encode($sayList);
            exit();
        }
        $this->assign('title', L('user_service_list'));
        $this->display('user_msg_list.dwt');
    }

    /**
     * 删除信息
     */
    public function del_msg()
    {
        $id = I('get.id', 0);
        $order_id = I('get.order_id', 0);
        
        if ($id > 0) {
            $where_s['msg_id'] = $id;
            $row = $this->model->table('feedback')
                ->field('user_id, message_img')
                ->where($where_s)
                ->find();
            
            if ($row && $row['user_id'] == $this->user_id) {
                // 验证通过，删除留言，回复，及相应文件
                if ($row['message_img']) {
                    @unlink(ROOT_PATH . DATA_DIR . '/feedbackimg/' . $row['message_img']);
                }
                
                $where_d = 'msg_id = ' . $id . ' OR parent_id = ' . $id;
                $this->model->table('feedback')
                    ->where($where_d)
                    ->delete();
            }
        }
        $url = url('msg_list', array(
            'order_id' => $order_id
        ));
        ecs_header("Location: $url\n");
        exit();
    }

    /**
     * 客户服务
     */
    public function service()
    {
        if (IS_POST) {
            $message = array(
                'user_id' => $this->user_id,
                'user_name' => $_SESSION['user_name'],
                'user_email' => $_SESSION['email'],
                'msg_type' => I('post.msg_type', 0),
                'msg_title' => I('post.msg_title'),
                'msg_content' => I('post.msg_content'),
                'order_id' => I('post.order_id', 0),
                'upload' => (isset($_FILES['message_img']['error']) && $_FILES['message_img']['error'] == 0) || (! isset($_FILES['message_img']['error']) && isset($_FILES['message_img']['tmp_name']) && $_FILES['message_img']['tmp_name'] != 'none') ? $_FILES['message_img'] : array()
            );
            
            if (model('ClipsBase')->add_message($message)) {
                $data['msg_id'] = mysql_insert_id();
                $this->model->table('touch_feedback')
                    ->data($data)
                    ->insert();
                
                show_message(L('add_message_success'), L('user_service'), url('msg_list'), 'info');
            } else {
                self::err()->show(L('user_service'), url('service'));
            }
            exit();
        }
        
        // 页面显示
        $this->assign('title', L('user_service'));
        $this->display('user_service.dwt');
    }

    /**
     * 分享推荐
     */
    public function share()
    {
        $share = unserialize(C('affiliate'));
        $goodsid = I('request.goodsid', 0);
        if (empty($goodsid)) {
            $page = I('request.page', 1);
            $size = I(C('page_size'), 10);
            empty($share) && $share = array();
            if (empty($share['config']['separate_by'])) {
                // 推荐注册分成
                $affdb = array();
                $num = count($share['item']);
                $up_uid = "'$this->user_id'";
                $all_uid = "'$this->user_id'";
                for ($i = 1; $i <= $num; $i ++) {
                    $count = 0;
                    if ($up_uid) {
                        $where = 'parent_id IN(' . $up_uid . ')';
                        $rs = $this->model->table('users')
                            ->field('user_id')
                            ->where($where)
                            ->select();
                        if (empty($rs)) {
                            $rs = array();
                        }
                        $up_uid = '';
                        foreach ($rs as $k => $v) {
                            $up_uid .= $up_uid ? ",'$v[user_id]'" : "'$v[user_id]'";
                            if ($i < $num) {
                                $all_uid .= ", '$v[user_id]'";
                            }
                            $count ++;
                        }
                    }
                    $affdb[$i]['num'] = $count;
                    $affdb[$i]['point'] = $share['item'][$i - 1]['level_point'];
                    $affdb[$i]['money'] = $share['item'][$i - 1]['level_money'];
                    $this->assign('affdb', $affdb);
                    
                    $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                    
                    $sql = "SELECT o.*, a.log_id, a.user_id as suid,  a.user_name as auser, a.money, a.point, a.separate_type FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (u.parent_id IN ($all_uid) AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
                }
            } else {
                // 推荐订单分成
                $sqlcount = "SELECT count(*) as count FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)";
                
                $sql = "SELECT o.*, a.log_id,a.user_id as suid, a.user_name as auser, a.money, a.point, a.separate_type,u.parent_id as up FROM " . $this->model->pre . "order_info o" . " LEFT JOIN " . $this->model->pre . "users u ON o.user_id = u.user_id" . " LEFT JOIN " . $this->model->pre . "affiliate_log a ON o.order_id = a.order_id" . " WHERE o.user_id > 0 AND (o.parent_id = '$this->user_id' AND o.is_separate = 0 OR a.user_id = '$this->user_id' AND o.is_separate > 0)" . " ORDER BY order_id DESC";
            }
            
            $res = $this->model->query($sqlcount);
            $count = $res[0]['count'];
            $url_format = url('share', array(
                'page' => '{page}'
            ));
            $limit = $this->pageLimit($url_format, 10);
            $sql = $sql . ' LIMIT ' . $limit;
            $rt = $this->model->query($sql);
            if ($rt) {
                foreach ($rt as $k => $v) {
                    if (! empty($v['suid'])) {
                        // 在affiliate_log有记录
                        if ($v['separate_type'] == - 1 || $v['separate_type'] == - 2) {
                            // 已被撤销
                            $v['is_separate'] = 3;
                        }
                    }
                    $rt[$k]['order_sn'] = substr($v['order_sn'], 0, strlen($v['order_sn']) - 5) . "***" . substr($v['order_sn'], - 2, 2);
                }
            } else {
                $rt = array();
            }
            $page = $this->pageShow($count);
            
            $this->assign('page', $page);
            $this->assign('affiliate_type', $share['config']['separate_by']);
            $this->assign('logdb', $rt);
        } else {
            // 单个商品推荐
            $this->assign('userid', $this->user_id);
            $this->assign('goodsid', $goodsid);
            
            $types = array(
                1,
                2,
                3,
                4,
                5
            );
            $this->assign('types', $types);
            
            $goods = model('Goods')->get_goods_info($goodsid);
            $goods['goods_img'] = get_image_path(0, $goods['goods_img']);
            $goods['goods_thumb'] = get_image_path(0, $goods['goods_thumb']);
            $goods['shop_price'] = price_format($goods['shop_price']);
            
            $this->assign('goods', $goods);
        }
        $shoprul = __URL__ . '/?u=' . $this->user_id;
        // 二维码
        // 纠错级别：L、M、Q、H
        $errorCorrectionLevel = 'L';
        // 点的大小：1到10
        $matrixPointSize = 4;
        $filename = 'data/attached/image/' . $errorCorrectionLevel . $matrixPointSize . '.png';
        QRcode::png($shoprul, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        $this->assign('shopurl_qr', __URL__ . '/' . $filename);
        $this->assign('shopurl', $shoprul);
        $this->assign('shopdesc', C('shop_desc'));
        $this->assign('title', L('label_share'));
        $this->assign('share', $share);
        $this->display('user_share.dwt');
    }

    /**
     * 添加收藏商品
     */
    public function add_collection()
    {
        $result = array(
            'error' => 0,
            'message' => ''
        );
        $goods_id = intval($_GET['id']);
        
        if (! isset($this->user_id) || $this->user_id == 0) {
            $result['error'] = 2;
            $result['message'] = L('login_please');
            die(json_encode($result));
        } else {
            // 检查是否已经存在于用户的收藏夹
            $where['user_id'] = $this->user_id;
            $where['goods_id'] = $goods_id;
            $rs = $this->model->table('collect_goods')
                ->where($where)
                ->count();
            if ($rs > 0) {
                $rs = $this->model->table('collect_goods')
                    ->where($where)
                    ->delete();
                if (! $rs) {
                    $result['error'] = 1;
                    $result['message'] = ECTouch::db()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            } else {
                $data['user_id'] = $this->user_id;
                $data['goods_id'] = $goods_id;
                $data['add_time'] = gmtime();
                if ($this->model->table('collect_goods')
                    ->data($data)
                    ->insert() === false) {
                    $result['error'] = 1;
                    $result['message'] = ECTouch::db()->errorMsg();
                    die(json_encode($result));
                } else {
                    $result['error'] = 0;
                    $result['message'] = L('collect_success');
                    die(json_encode($result));
                }
            }
        }
    }

    /**
     * 删除收藏商品
     */
    public function delete_collection()
    {
        // ajax请求
        if (IS_AJAX) {
            $rs = 0;
            $rec_id = I('get.rec_id', 0);
            
            if ($rec_id > 0) {
                $where['user_id'] = $this->user_id;
                $where['rec_id'] = $rec_id;
                $this->model->table('collect_goods')
                    ->where($where)
                    ->delete();
                $rs = 1;
            }
            echo $rs;
        } else {
            $this->redirect(url('index'));
        }
    }

    /**
     * 删除评论
     */
    public function delete_comment()
    {
        // ajax请求
        if (IS_AJAX) {
            $rs = 0;
            $id = I('get.id', 0);
            if ($id > 0) {
                $where['user_id'] = $this->user_id;
                $where['comment_id'] = $id;
                $this->model->table('comment')
                    ->where($where)
                    ->delete();
                $rs = 1;
            }
            echo $rs;
        } else {
            $this->redirect(url('index'));
        }
    }

    /**
     * 登录
     */
    public function login()
    {
        // 登录处理
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password');
            $this->back_act = urldecode(I('post.back_act'));
            
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'referer' => urlencode($this->back_act)
                    )), 'error');
                }
                // 检查验证码
                if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('relogin_lnk'), url('login', array(
                        'referer' => urlencode($this->back_act)
                    )), 'error');
                }
            }
            
            // 用户名是邮箱格式
            if (is_email($username)) {
                $where['email'] = $username;
                $username_try = $this->model->table('users')
                    ->field('user_name')
                    ->where($where)
                    ->getOne();
                $username = $username_try ? $username_try : $username;
            }
            
            // 用户名是手机格式
            if (is_mobile($username)) {
                $where['mobile_phone'] = $username;
                $username_try = $this->model->table('users')
                    ->field('user_name')
                    ->where($where)
                    ->getOne();
                $username = $username_try ? $username_try : $username;
            }
            
            if (self::$user->login($username, $password, isset($_POST['remember']))) {
                model('Users')->update_user_info();
                model('Users')->recalculate_price();
                
                $jump_url = empty($this->back_act) ? url('index') : $this->back_act;
                $this->redirect($jump_url);
            } else {
                $_SESSION['login_fail'] ++;
                show_message(L('login_failure'), L('relogin_lnk'), url('login', array(
                    'referer' => urlencode($this->back_act)
                )), 'error');
            }
            exit();
        }
        
        // 登录页面显示
        if (isset($_GET['referer']) && ! empty($_GET['referer'])) {
            $this->back_act = $_GET['referer'];
        }
        
        if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
            $this->back_act = urlencode($this->back_act);
        }
        
        // 验证码相关设置
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        
        $this->assign('title', L('login'));
        $this->assign('back_act', $this->back_act);
        $this->display('user_login.dwt');
    }

    /**
     * 注册
     */
    public function register()
    {
        // 注册处理
        if (IS_POST) {
            $enabled_sms = isset($_POST['enabled_sms']) ? intval($_POST['enabled_sms']) : 0;
            $this->back_act = isset($_POST['back_act']) ? in($_POST['back_act']) : '';
            
            // 邮箱注册处理
            if (0 == $enabled_sms) {
                // 数据处理
                $username = isset($_POST['username']) ? in($_POST['username']) : '';
                $email = isset($_POST['email']) ? in($_POST['email']) : '';
                $password = isset($_POST['password']) ? in($_POST['password']) : '';
                $other = array();
                
                // 验证码检查
                if (intval(C('captcha')) > 0) {
                    if (empty($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                    // 检查验证码
                    if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                        show_message(L('invalid_captcha'), L('sign_up'), url('register'), 'error');
                    }
                }
                
                if (empty($_POST['agreement'])) {
                    show_message(L('passport_js.agreement'));
                }
                
                if (strlen($username) < 3) {
                    show_message(L('passport_js.username_shorter'));
                }
                if (strlen($username) > 15) {
                    show_message(L('passport_js.username_longer'));
                }
                
                if (strlen($password) < 6) {
                    show_message(L('passport_js.password_shorter'));
                }
                
                if (strpos($password, ' ') > 0) {
                    show_message(L('passwd_balnk'));
                }
            }             // 手机号注册处理
            elseif (1 == $enabled_sms) {
                $username = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
                $password = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
                $sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
                $other['mobile_phone'] = $username;
                
                if (empty($username)) {
                    show_message(L('msg_mobile_blank'), L('register_back'), url('register'), 'error');
                }
                
                if ($sms_code != $_SESSION['sms_code']) {
                    show_message(L('sms_code_error'), L('register_back'), url('register'), 'error');
                }
                
                if ($password != $_SESSION['sms_mobile_code']) {
                    show_message(L('mobile_code_error'), L('register_back'), url('register'), 'error');
                }
                
                // 验证手机号重复
                $where['mobile_phone'] = $username;
                $user_id = $this->model->table('users')
                    ->field('user_id')
                    ->where($where)
                    ->getOne();
                if ($user_id) {
                    show_message(L('msg_mobile_exists'), L('register_back'), url('register'), 'error');
                }
                
                // 设置一个默认的邮箱
                $email = $username . '@qq.com';
            } else {
                ECTouch::err()->show(L('sign_up'), url('register'));
            }
            
            if (model('Users')->register($username, $password, $email, $other) !== false) {
                // 判断是否需要自动发送注册邮件
                if (C('member_email_validate') && C('send_verify_email')) {
                    model('Users')->send_regiter_hash($_SESSION['user_id']);
                }
                $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
                show_message(sprintf(L('register_success'), $username . $ucdata), array(
                    L('back_up_page'),
                    L('profile_lnk')
                ), array(
                    $this->back_act,
                    url('index')
                ), 'info');
            } else {
                ECTouch::err()->show(L('sign_up'), url('register'));
            }
            exit();
        }
        
        // 注册页面显示
        
        if (empty($this->back_act) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index/index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        }
        
        // 验证码相关设置
        if (intval(C('captcha')) > 0) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
            // 随机code
            $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            $this->assign('sms_code', $sms_code);
        }
        
        $this->assign('title', L('register'));
        $this->assign('back_act', $this->back_act);
        $this->display('user_register.dwt');
    }

    /**
     * 第三方登录
     */
    public function third_login()
    {
        $type = I('get.type');
        $file = ROOT_PATH . 'plugins/connect/' . $type . '.php';
        if (file_exists($file)) {
            include_once ($file);
        } else {
            show_message(L('process_false'), L('relogin_lnk'), url('login', array(
                'referer' => urlencode($this->back_act)
            )), 'error');
        }
        $url = __URL__ . '/index.php?m=default&c=user&a=third_login&type=' . $type;
        $info = model('ClipsBase')->get_third_user_info($type);
        // 判断是否安装
        if (! $info) {
            show_message(L('no_register_auth'), L('relogin_lnk'), url('login', array(
                'referer' => urlencode($this->back_act)
            )), 'error');
        }
        $obj = new $type($info);
        if ($_GET['code'] && $_GET['code'] != '') {
            // 授权成功 返回登录
            if ($obj->call_back($info, $url, $_GET['code'])) {
                if ($_SESSION['access_token']) {
                    $res = new $type($info, $_SESSION['access_token']);
                    $openid = $res->get_openid();
                    // 获取用户信息
                    $user = $res->get_user_info($openid);
                    // 处理数据
                    $info['aite_id'] = $type . '_' . $openid; // 添加登录标示
                    $info['user_name'] = str_replace("'", "", empty($user['name']) ? $user['nickname'] : $user['name']);
                    if (model('Users')->get_one_user($info['aite_id'])) {
                        // 已有记录
                        self::$user->set_session($info['user_name']);
                        self::$user->set_cookie($info['user_name']);
                        model('Users')->update_user_info();
                        model('Users')->recalculate_price();
                        $jump_url = empty($this->back_act) ? url('index') : $this->back_act;
                        $this->redirect($jump_url);
                    }
                    // 无记录
                    if (model('Users')->check_user_name($info['user_name'])) { // 重名处理
                        $info['user_name'] = $user['user_name'] . '_' . $type . (rand(10000, 99999));
                    }
                    $info['email'] = empty($user['email']) ? get_pinyin($info['user_name']) . '@' . get_top_domain() : $user['email'];
                    // 插入数据库
                    model('Users')->third_reg($info);
                    self::$user->set_session($info['user_name']);
                    self::$user->set_cookie($info['user_name']);
                    model('Users')->update_user_info();
                    model('Users')->recalculate_price();
                    $jump_url = empty($this->back_act) ? url('index') : $this->back_act;
                    $this->redirect($jump_url);
                }
            } else {
                show_message(L('process_false'), L('relogin_lnk'), url('login', array(
                    'referer' => urlencode($this->back_act)
                )), 'error');
            }
        } else {
            // 开始授权登录
            $url = $obj->act_login($info, $url);
            ecs_header("Location: " . $url . "\n");
            exit();
        }
    }

    /**
     * 手机找回密码
     */
    public function get_password_phone()
    {
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            // 手机找回密码处理
            if (IS_POST) {
                
                $mobile = isset($_POST['mobile']) ? in($_POST['mobile']) : '';
                $mobile_code = isset($_POST['mobile_code']) ? in($_POST['mobile_code']) : '';
                $sms_code = isset($_POST['sms_code']) ? in($_POST['sms_code']) : '';
                
                if ($sms_code != $_SESSION['sms_code']) {
                    show_message(L('sms_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                }
                
                if ($password != $_SESSION['sms_mobile_code']) {
                    show_message(L('mobile_code_error'), L('back_page_up'), url('get_password_phone'), 'error');
                }
                
                $where['mobile_phone'] = $mobile;
                $user_id = $this->model->table('users')
                    ->field('user_id')
                    ->where($where)
                    ->getOne();
                
                $this->assign('uid', $user_id);
                $this->assign('mobile', base64_encode($mobile));
                $this->display('user_reset_password.dwt');
                exit();
            }
            
            // 随机code
            $_SESSION['sms_code'] = $sms_code = md5(mt_rand(1000, 9999));
            
            $this->assign('title', L('get_password'));
            $this->assign('enabled_sms_signin', C('sms_signin'));
            $this->assign('sms_code', $sms_code);
            $this->display('user_get_password.dwt');
        } else {
            $this->redirect(url('get_password_email'));
        }
    }

    /**
     * 邮件找回密码
     */
    public function get_password_email()
    {
        if (isset($_GET['code']) && isset($_GET['uid'])) { // 从邮件处获得的act
            $code = in($_GET['code']);
            $uid = intval($_GET['uid']);
            
            // 判断链接的合法性
            $user_info = self::$user->get_profile_by_id($uid);
            if (empty($user_info) || ($user_info && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) != $code)) {
                show_message(L('parm_error'), L('back_home_lnk'), url('index/index'), 'info');
            }
            
            $this->assign('uid', $uid);
            $this->assign('code', $code);
            $this->assign('title', L('reset_password'));
            $this->display('user_reset_password.dwt');
        } else {
            // 验证码相关设置
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }
            // 短信开启
            if (intval(C('sms_signin')) > 0) {
                $this->assign('enabled_sms_signin', C('sms_signin'));
            }
            $this->assign('title', L('get_password'));
            $this->display('user_get_password.dwt');
        }
    }

    /**
     * 发送密码修改确认邮件
     */
    public function send_pwd_email()
    {
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            if (empty($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }
            
            // 检查验证码
            if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                show_message(L('invalid_captcha'), L('back_page_up'), url('get_password_email'), 'error');
            }
        }
        
        // 初始化会员用户名和邮件地址
        $user_name = ! empty($_POST['user_name']) ? in($_POST['user_name']) : '';
        $email = ! empty($_POST['email']) ? in($_POST['email']) : '';
        
        // 用户信息
        $user_info = self::$user->get_user_info($user_name);
        
        if ($user_info && $user_info['email'] == $email) {
            // 生成code
            $code = md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']);
            // 发送邮件的函数
            if (send_pwd_email($user_info['user_id'], $user_name, $email, $code)) {
                show_message(L('send_success') . $email, L('relogin_lnk'), url('login'), 'info');
            } else {
                // 发送邮件出错
                show_message(L('fail_send_password'), L('back_page_up'), url('get_password_email'), 'info');
            }
        } else {
            // 用户名与邮件地址不匹配
            show_message(L('username_no_email'), L('back_page_up'), url('get_password_email'), 'info');
        }
    }

    /**
     * 安全问题找回密码
     */
    public function get_password_question()
    {
        if (IS_POST) {
            $user_name = isset($_POST['user_name']) ? in($_POST['user_name']) : '';
            $passwd_answer = isset($_POST['passwd_answer']) ? in($_POST['passwd_answer']) : '';
            // 验证码检查
            $captcha = intval(C('captcha'));
            if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
                if (empty($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
                }
                
                // 检查验证码
                if ($_SESSION['ectouch_verify'] !== strtoupper($_POST['captcha'])) {
                    show_message(L('invalid_captcha'), L('back_retry_answer'), url('get_password_question'), 'error');
                }
            }
            
            if (empty($_POST['user_name'])) {
                show_message(L('no_passwd_question'), L('back_home_lnk'), url('index/index'), 'info');
            }
            
            // 取出会员密码问题和答案
            $where['user_name'] = $user_name;
            $user_question_arr = $this->model->table('users')
                ->field('user_id, user_name, passwd_question, passwd_answer')
                ->where($where)
                ->find();
            
            // 如果没有设置密码问题，给出错误提示
            if (empty($user_question_arr['passwd_answer'])) {
                show_message(L('no_passwd_question'), L('back_retry_answer'), url('get_password_question'), 'info');
            }
            
            // 问题答案验证
            if (empty($_POST['passwd_answer']) || in($_POST['passwd_answer']) != $user_question_arr['passwd_answer']) {
                show_message(L('wrong_passwd_answer'), L('back_retry_answer'), url('get_password_question'), 'info');
            }
            
            $this->assign('uid', $user_question_arr['user_id']);
            $this->assign('question', base64_encode($user_question_arr['passwd_question']));
            $this->display('user_reset_password.dwt');
            exit();
        }
        
        // 验证码相关设置
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (! ($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2))) {
            $this->assign('enabled_captcha', 1);
            $this->assign('rand', mt_rand());
        }
        // 短信开启
        if (intval(C('sms_signin')) > 0) {
            $this->assign('enabled_sms_signin', C('sms_signin'));
        }
        $this->assign('title', L('get_password'));
        $this->assign('password_question', L('passwd_questions'));
        $this->display('user_get_password.dwt');
    }

    /**
     * 修改密码
     */
    public function edit_password()
    {
        // 修改密码处理
        if (IS_POST) {
            $old_password = isset($_POST['old_password']) ? in($_POST['old_password']) : null;
            $new_password = isset($_POST['new_password']) ? in($_POST['new_password']) : '';
            $user_id = isset($_POST['uid']) ? intval($_POST['uid']) : $this->user_id;
            $code = isset($_POST['code']) ? in($_POST['code']) : ''; // 邮件code
            $mobile = isset($_POST['mobile']) ? base64_decode(in($_POST['mobile'])) : ''; // 手机号
            $question = isset($_POST['question']) ? base64_decode(in($_POST['question'])) : ''; // 问题
            
            if (strlen($new_password) < 6) {
                show_message(L('passport_js.password_shorter'));
            }
            
            $user_info = self::$user->get_profile_by_id($user_id); // 论坛记录
                                                                   // 短信找回，邮件找回，问题找回，登录修改密码
            if ((! empty($mobile) && $user_info['mobile'] == $mobile) || ($user_info && (! empty($code) && md5($user_info['user_id'] . C('hash_code') . $user_info['reg_time']) == $code)) || (! empty($question) && $user_info['passwd_question'] == $question) || ($_SESSION['user_id'] > 0 && $_SESSION['user_id'] == $user_id && self::$user->check_user($_SESSION['user_name'], $old_password))) {
                
                if (self::$user->edit_user(array(
                    'username' => ((empty($code) && empty($mobile) && empty($question)) ? $_SESSION['user_name'] : $user_info['user_name']),
                    'old_password' => $old_password,
                    'password' => $new_password
                ), empty($code) ? 0 : 1)) {
                    $data['ec_salt'] = 0;
                    $where['user_id'] = $user_id;
                    $this->model->table('users')
                        ->data($data)
                        ->where($where)
                        ->update();
                    
                    self::$user->logout();
                    show_message(L('edit_password_success'), L('relogin_lnk'), url('login'), 'info');
                } else {
                    show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
                }
            } else {
                show_message(L('edit_password_failure'), L('back_page_up'), '', 'info');
            }
        }
        
        // 显示修改密码页面
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            $this->assign('title', L('edit_password'));
            // 判断登录方式
            if (model('Users')->is_third_user($_SESSION['user_id'])) {
                $this->assign('is_third', 1);
            }
            $this->display('user_edit_password.dwt');
        } else {
            $this->redirect(url('login', array(
                'referer' => urlencode(url($this->action))
            )));
        }
    }

    /**
     * 退出
     */
    public function logout()
    {
        if ((! isset($this->back_act) || empty($this->back_act)) && isset($GLOBALS['_SERVER']['HTTP_REFERER'])) {
            $this->back_act = strpos($GLOBALS['_SERVER']['HTTP_REFERER'], 'c=user') ? url('index') : $GLOBALS['_SERVER']['HTTP_REFERER'];
        } else {
            $this->back_act = url('login');
        }
        
        self::$user->logout();
        $ucdata = empty(self::$user->ucdata) ? "" : self::$user->ucdata;
        show_message(L('logout') . $ucdata, array(
            L('back_up_page'),
            L('back_home_lnk')
        ), array(
            $this->back_act,
            url('index/index')
        ), 'info');
    }

    /**
     * 清空浏览历史
     */
    public function clear_history()
    {
        // ajax请求
        if (IS_AJAX) {
            setcookie('ECS[history]', '', 1);
            echo 1;
        } else {
            $this->redirect(url('index'));
        }
    }

    /**
     * 未登录验证
     */
    private function check_login()
    {
        // 不需要登录的操作或自己验证是否登录（如ajax处理）的方法
        $without = array(
            'login',
            'register',
            'get_password_phone',
            'get_password_email',
            'get_password_question',
            'pwd_question_name',
            'send_pwd_email',
            'edit_password',
            'check_answer',
            'logout',
            'clear_histroy',
            'add_collection',
            'third_login'
        );
        // 未登录处理
        if (empty($_SESSION['user_id']) && ! in_array($this->action, $without)) {
            $url = __HOST__ . $_SERVER['REQUEST_URI'];
            $this->redirect(url('login', array(
                'referer' => urlencode($url)
            )));
            exit();
        }
        
        // 已经登录，不能访问的方法
        $deny = array(
            'login',
            'register'
        );
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0 && in_array($this->action, $deny)) {
            $this->redirect(url('index/index'));
            exit();
        }
    }
    
    /**
     * 更新商品销量
     */
    private function update_touch_goods($order){
        $sql = 'select pay_status from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
        $pay_status = $this->model->query($sql);
        $pay_status = $pay_status[0];
        if ($pay_status == 2){
            /* 统计时间段 */
            $period = C('top10_time');
            //近一个月（30天）
            if ($period == 1) { // 一年
                $ext = " AND o.add_time > '" . local_strtotime('-1 years') . "'";
            } elseif ($period == 2) { // 半年
                $ext = " AND o.add_time > '" . local_strtotime('-6 months') . "'";
            } elseif ($period == 3) { // 三个月
                $ext = " AND o.add_time > '" . local_strtotime('-3 months') . "'";
            } elseif ($period == 4) { // 一个月
                $ext = " AND o.add_time > '" . local_strtotime('-1 months') . "'";
            } else {
                $ext = '';
            }
            $sql = 'select goods_id from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
            $arrGoodsid = $this->model->query($sql);
    
            $sql = 'select extension_code from ' . $this->model->pre . 'order_info where  order_id = "' . $order . '"';
            $extension_code = $this->model->query($sql);
    
            if ($extension_code == ''){
                foreach ($arrGoodsid as $key=>$val){
                    /* 查询该商品销量 */
                    $sql = 'SELECT IFNULL(SUM(g.goods_number), 0) ' .
                        'as count FROM ' . $this->pre . 'order_info AS o, ' .
                        $this->pre . 'order_goods AS g ' .
                        "WHERE o.order_id = g.order_id " .
                        "  AND g.goods_id = '" . $val['goods_id'] . "' AND o.pay_status = '2' " . $ext;
                    $res = $this->model->query($sql);
                    $sales_count = $res[0]['count'];
    
                    $nCount = $this->query('select COUNT(*) from ' . $this->model->pre . 'touch_goods where  goods_id = "' . $val['goods_id'] . '"');
                    if ($nCount[0]['COUNT(*)'] == 0) {
                        $this->model->query("INSERT INTO " . $this->model->pre . "touch_goods (`goods_id` ,`sales_volume` ) VALUES ( '" . $val['goods_id'] . "' , '0')");
                    }
                    $sql = 'update ' . $this->model->pre . 'touch_goods AS a set a.sales_volume = ' . $sales_count . " WHERE goods_id=" . $val['goods_id'];
                    $this->model->query($sql);
    
                }
            }
             
        }
    }
}
