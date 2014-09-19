<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CommentControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：用户评论控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CommentController extends CommonController {

    private $cmt;
    private $act;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();

        /* 只有在没有提交评论内容以及没有act的情况下才跳转 */
        $this->cmt = I('request.cmt');
        $this->act = I('request.act');
        if (!isset($this->cmt) && !isset($this->act)) {
            ecs_header("Location: ./\n");
        }
    }

    public function index() {

        $result = array('error' => 0, 'message' => '', 'content' => '');
        if (empty($this->act)) {

            $this->cmt = I('request.cmt', '', 'json_str_iconv');
            $result = array(
                'error' => 0,
                'message' => '',
                'content' => ''
            );
            if (empty($this->act)) {
                /*
                 * act 参数为空 默认为添加评论内容
                 */
                $json = new EcsJson;
                $cmt = $json->decode($this->cmt);

                $cmt->page = 1;
                $cmt->id = !empty($cmt->id) ? intval($cmt->id) : 0;
                $cmt->type = !empty($cmt->type) ? intval($cmt->type) : 0;

                if (empty($cmt) || !isset($cmt->type) || !isset($cmt->id)) {
                    $result ['error'] = 1;
                    $result ['message'] = L('invalid_comments');
                } elseif (!is_email($cmt->email)) {
                    $result ['error'] = 1;
                    $result ['message'] = L('error_email');
                } else {
                    if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
                        /* 检查验证码 */
                        if ($_SESSION ['ectouch_verify'] !== strtoupper($cmt->captcha)) {
                            $result ['error'] = 1;
                            $result ['message'] = L('invalid_captcha');
                        } else {
                            $factor = intval(C('comment_factor'));
                            if ($cmt->type == 0 && $factor > 0) {
                                /* 只有商品才检查评论条件 */
                                switch ($factor) {
                                    case COMMENT_LOGIN :
                                        if ($_SESSION ['user_id'] == 0) {
                                            $result ['error'] = 1;
                                            $result ['message'] = L('comment_login');
                                        }
                                        break;

                                    case COMMENT_CUSTOM :

                                        if ($_SESSION ['user_id'] > 0) {
                                            $condition = "user_id = '" . $_SESSION ['user_id'] . "'" . " AND (order_status = '" . OS_CONFIRMED . "' or order_status = '" . OS_SPLITED . "') " . " AND (pay_status = '" . PS_PAYED . "' OR pay_status = '" . PS_PAYING . "') " . " AND (shipping_status = '" . SS_SHIPPED . "' OR shipping_status = '" . SS_RECEIVED . "') ";
                                            $tmp = $this->model->table('order_info')->field('order_id')->where($condition)->getOne();
                                            if (empty($tmp)) {
                                                $result ['error'] = 1;
                                                $result ['message'] = L('comment_custom');
                                            }
                                        } else {
                                            $result ['error'] = 1;
                                            $result ['message'] = L('comment_custom');
                                        }
                                        break;
                                    case COMMENT_BOUGHT :
                                        if ($_SESSION ['user_id'] > 0) {
                                            $sql = "SELECT o.order_id" . " FROM " . $this->model->pre . "order_info AS o, " . $this->model->pre . "order_goods AS og " . " WHERE o.order_id = og.order_id" . " AND o.user_id = '" . $_SESSION ['user_id'] . "'" . " AND og.goods_id = '" . $cmt->id . "'" . " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " . " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " . " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " . " LIMIT 1";

                                            $res = $this->model->query($sql);
                                            $tmp = $res[0]['order_id'];
                                            if (empty($tmp)) {
                                                $result ['error'] = 1;
                                                $result ['message'] = L('comment_brought');
                                            }
                                        } else {
                                            $result ['error'] = 1;
                                            $result ['message'] = L('comment_brought');
                                        }
                                }
                            }

                            /* 无错误就保存留言 */
                            if (empty($result ['error'])) {
                                model('Comment')->add_comment($cmt);
                            }
                        }
                    } else {
                        /* 没有验证码时，用时间来限制机器人发帖或恶意发评论 */
                        if (!isset($_SESSION ['send_time'])) {
                            $_SESSION ['send_time'] = 0;
                        }

                        $cur_time = gmtime();
                        if (($cur_time - $_SESSION ['send_time']) < 30) { // 小于30秒禁止发评论
                            $result ['error'] = 1;
                            $result ['message'] = $this->_LANG ['cmt_spam_warning'];
                        } else {
                            $factor = intval($this->_CFG ['comment_factor']);
                            if ($cmt->type == 0 && $factor > 0) {
                                /* 只有商品才检查评论条件 */
                                switch ($factor) {
                                    case COMMENT_LOGIN :
                                        if ($_SESSION ['user_id'] == 0) {
                                            $result ['error'] = 1;
                                            $result ['message'] = $this->_LANG ['comment_login'];
                                        }
                                        break;

                                    case COMMENT_CUSTOM :
                                        if ($_SESSION ['user_id'] > 0) {
                                            $condition = "user_id = '" . $_SESSION ['user_id'] . "'" . " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " . " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " . " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ";
                                            $tmp = $this->model->table('order_info')->field('order_id')->where($condition)->getOne();
                                            if (empty($tmp)) {
                                                $result ['error'] = 1;
                                                $result ['message'] = L('comment_custom');
                                            }
                                        } else {
                                            $result ['error'] = 1;
                                            $result ['message'] = L('comment_custom');
                                        }
                                        break;

                                    case COMMENT_BOUGHT :
                                        if ($_SESSION ['user_id'] > 0) {
                                            $sql = "SELECT o.order_id" . " FROM " . $this->model->pre . "order_info AS o, " . $this->model->pre . "order_goods AS og " . " WHERE o.order_id = og.order_id" . " AND o.user_id = '" . $_SESSION ['user_id'] . "'" . " AND og.goods_id = '" . $cmt->id . "'" . " AND (o.order_status = '" . OS_CONFIRMED . "' or o.order_status = '" . OS_SPLITED . "') " . " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " . " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') " . " LIMIT 1";
                                            $res = $this->model->query($sql);
                                            $tmp = $res[0]['order_id'];
                                            if (empty($tmp)) {
                                                $result ['error'] = 1;
                                                $result ['message'] = L('comment_brought');
                                            }
                                        } else {
                                            $result ['error'] = 1;
                                            $result ['message'] = L('comment_brought');
                                        }
                                }
                            }
                            /* 无错误就保存留言 */
                            if (empty($result ['error'])) {
                                model('Comment')->add_comment($cmt);
                                $_SESSION ['send_time'] = $cur_time;
                            }
                        }
                    }
                }
            }
        } else {
            /*
             * act 参数不为空 默认为评论内容列表 根据 _GET 创建一个静态对象
             */
            $cmt = new stdClass ();
            $id = I('get.id');
            $type = I('get.type');
            $page = I('get.page');
            $cmt->id = !empty($id) ? intval($id) : 0;
            $cmt->type = !empty($type) ? intval($type) : 0;
            $cmt->page = isset($page) && intval($page) > 0 ? intval($page) : 1;
        }

        if ($result ['error'] == 0) {
            $comments = model('Comment')->assign_comment($cmt->id, $cmt->type, $cmt->page);


            $this->assign('comment_type', $cmt->type);
            $this->assign('id', $cmt->id);
            $this->assign('username', $_SESSION['user_name']);
            $this->assign('email', $_SESSION['email']);
            $this->assign('comments', $comments['comments']);
            $this->assign('pager', $comments['pager']);


            /* 验证码相关设置 */
            if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
                $this->assign('enabled_captcha', 1);
                $this->assign('rand', mt_rand());
            }

            $result['message'] = C('comment_check') ? L('cmt_submit_wait') : L('cmt_submit_done');
            $result['content'] = ECTouch::$view->fetch("library/comments_list.lbi");
        }

        echo json_encode($result);
    }

}
