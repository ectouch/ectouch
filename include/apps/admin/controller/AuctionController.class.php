<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupbuyControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：拍卖活动控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AuctionController extends AdminController {

    /**
     * 拍卖活动列表
     */
    public function index() {
        $condition['act_type'] = GAT_AUCTION;
        
        /* 分页 */
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('goods_activity')->where($condition)->count();
        $this->assign('page', $this->pageShow($total));
        /* 模板赋值 */
        $list = $this->auction_list($offset);
        $this->assign('auction_list', $list['item']);
        $this->assign('ur_here', L('group_buy_list'));
        $this->display();
    }

    /**
     * 编辑团购banner
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            if ($_FILES['act_banner']['name']) {
                $result = $this->ectouchUpload('act_banner', 'banner_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成banner链接 */
                $data2['act_banner'] = substr($result['message']['act_banner']['savepath'], 2) . $result['message']['act_banner']['savename'];
                if ($this->model->table('touch_goods_activity')->where('act_id=' . $id)->count()) {
                    $this->model->table('touch_goods_activity')->data($data2)->where('act_id=' . $id)->update();
                } else {
                    $data2['act_id'] = $id;
                    $this->model->table('touch_goods_activity')->data($data2)->insert();
                }
            }
            $this->message(sprintf(L('edit_auction_ok'), $data2['act_banner']), url('index'));
        }
        $info = $this->model->table('goods_activity')->field('act_id,act_name')->where(array('act_id' => $id))->find();
        $touch_info = $this->model->table('touch_goods_activity')->field('act_banner')->where(array('act_id' => $id))->find();
        $info['act_banner'] = $touch_info['act_banner'];
        /* 模板赋值 */
        $this->assign('info', $info);
        $this->assign('ur_here', L('articlecat_edit'));
        $this->display();
    }

    /*
     * 取得拍卖活动列表
     * @return   array
     */

    public function auction_list($offset = '0, 12') {
        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keyword'] = json_str_iconv($filter['keyword']);
            }
            $filter['is_going'] = empty($_REQUEST['is_going']) ? 0 : 1;
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'act_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $where = "";
            if (!empty($filter['keyword'])) {
                $where .= " AND goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%'";
            }
            if ($filter['is_going']) {
                $now = gmtime();
                $where .= " AND is_finished = 0 AND start_time <= '$now' AND end_time >= '$now' ";
            }
            /* 分页大小 */
//            $filter = page_and_size($filter);

            /* 查询 */
            $sql = "SELECT * " .
                    "FROM " . $this->model->pre .
                    "goods_activity WHERE act_type = '" . GAT_AUCTION . "' $where " .
                    " ORDER BY $filter[sort_by] $filter[sort_order] " .
                    " LIMIT $offset";

            $filter['keyword'] = stripslashes($filter['keyword']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $this->model->query($sql);

        $list = array();
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $arr = array_merge($row, $ext_info);
            $arr['start_time'] = local_date('Y-m-d H:i', $arr['start_time']);
            $arr['end_time'] = local_date('Y-m-d H:i', $arr['end_time']);
            $list[] = $arr;
        }
        $arr = array('item' => $list, 'filter' => $filter);

        return $arr;
    }

}
