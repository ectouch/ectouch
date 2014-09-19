<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：GroupbuyControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：团购活动管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class GroupbuyController extends AdminController {

    /**
     * 团购列表
     */
    public function index() {
        $condition['act_type'] = GAT_GROUP_BUY;
        $res = $this->model->table('goods_activity')->field('act_id , act_name, goods_name, end_time ,ext_info')->where($condition)->order('act_id DESC')->select();
        foreach ($res as $row) {
            $ext_info = unserialize($row['ext_info']);
            $stat = model('GroupBuyBase')->group_buy_stat($row['act_id'], $ext_info['deposit']);
            $arr = array_merge($row, $stat, $ext_info);

            /* 处理价格阶梯 */
            $price_ladder = $arr['price_ladder'];
            if (!is_array($price_ladder) || empty($price_ladder)) {
                $price_ladder = array(array('amount' => 0, 'price' => 0));
            } else {
                foreach ($price_ladder AS $key => $amount_price) {
                    $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
                }
            }

            /* 计算当前价 */
            $cur_price = $price_ladder[0]['price'];    // 初始化
            $cur_amount = $stat['valid_goods'];         // 当前数量
            foreach ($price_ladder AS $amount_price) {
                if ($cur_amount >= $amount_price['amount']) {
                    $cur_price = $amount_price['price'];
                } else {
                    break;
                }
            }

            $arr['cur_price'] = $cur_price;

            $status = model('GroupBuyBase')->group_buy_status($arr);

            $arr['start_time'] = local_date('Y-m-d H:i', $arr['start_time']);
            $arr['end_time'] = local_date('Y-m-d H:i', $arr['end_time']);
            $arr['cur_status'] = L('gbs.' . $status);

            $list[] = $arr;
        }
        /* 模板赋值 */
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('goods_activity')->where($condition)->count();
        $this->assign('page', $this->pageShow($total));
        $this->assign('list', $list);
        $this->assign('ur_here', L('group_buy_list'));
        $this->display();
    }

    /**
     * 编辑团购banner
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $info = I('data');
            if ($_FILES['act_banner']['name']) {
                $result = $this->ectouchUpload('act_banner', 'banner_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成banner链接 */
                $data2['act_banner'] = substr($result['message']['act_banner']['savepath'], 2) . $result['message']['act_banner']['savename'];
                $this->model->table('touch_goods_activity')->data($data2)->where('act_id=' . $id)->update();
            }
            $this->message(sprintf(L('brandedit_succed'), $data['brand_name']), url('index'));
        }

        $info = $this->model->table('goods_activity')->field('act_id,act_name')->where(array('act_id' => $id))->find();
        $touch_info = $this->model->table('touch_goods_activity')->field('act_banner')->where(array('act_id' => $id))->find();
        $info['act_banner'] = $touch_info['act_banner'];
        /* 模板赋值 */
        $this->assign('info', $info);
        $this->assign('ur_here', L('articlecat_edit'));
        $this->display();
    }

}
