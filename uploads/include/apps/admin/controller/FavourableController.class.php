<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：FavourableControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：优惠活动控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class FavourableController extends AdminController {

    /**
     * 活动列表
     */
    public function index() {
        $list = $this->favourable_list();
        /* 模板赋值 */
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('favourable_activity')->where()->count();
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('ur_here', L('favourable_list'));
        $this->display();
    }

    /**
     * 编辑活动
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            if ($_FILES['act_banner']['name']) {
                $result = $this->ectouchUpload('act_banner', 'banner_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成banner链接 */
                $data2['act_banner'] = substr($result['message']['act_banner']['savepath'], 2) . $result['message']['act_banner']['savename'];
                $this->model->table('touch_activity')->data($data2)->where('act_id=' . $id)->update();
            }
            $this->message(sprintf(L('brandedit_succed'), $data['brand_name']), url('index'));
        }
        /* 查询附表信息 */
        $touch_result = $this->model->table('touch_activity')->where('act_id=' . $id)->find();
        $favourable = model('GoodsBase')->favourable_info($id);
        /* 附表信息不存在则生成 */
        if (empty($touch_result)) {
            $data['act_id'] = $id;
            $this->model->table('touch_activity')->data($data)->insert();
        } else {
            $favourable['act_banner'] = $touch_result['act_banner'];
            $favourable['act_content'] = html_out($touch_result['act_content']);
        }
        $this->assign('act_range_ext', $act_range_ext);
        /* 模板赋值 */
        $this->assign('favourable', $favourable);
        $this->assign('ur_here', L('edit_favourable'));
        $this->assign('action_link', array('text' => L('06_goods_brand_list'), 'href' => url('index')));
        $this->display();
    }

    /*
     * 取得优惠活动列表
     * @return   array
     */

    private function favourable_list() {
        /* 查询 */
        $sql = "SELECT * " .
                "FROM " . $this->model->pre .
                "favourable_activity WHERE 1" .
                " ORDER BY act_id  DESC";
        $res = $this->model->query($sql);
        $list = array();
        foreach ($res as $row) {
            $row['start_time'] = local_date('Y-m-d H:i', $row['start_time']);
            $row['end_time'] = local_date('Y-m-d H:i', $row['end_time']);
            $list[] = $row;
        }
        return $list;
    }

}
