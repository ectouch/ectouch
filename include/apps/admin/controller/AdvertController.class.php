<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AdvertControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：广告管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AdvertController extends AdminController {

    /**
     * 广告位置列表
     */
    public function index() {

        $keywords = I('keywords', '');
        //搜索
        if (!empty($keywords)) {
            $filter['keywords'] = $keywords;
            $condition = 'position_name like "%' . $keywords . '%" ';
            $this->assign('keywords', $keywords);
        }
        //分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('touch_ad_position')->where($condition)->count();
        $this->assign('page', $this->pageShow($total));
        //广告位列表
        $list = $this->get_advert_list($offset, $condition);

        /* 模板赋值 */
        $this->assign('list', $list);
        $this->assign('ur_here', L('ad_position_list'));
        $this->assign('action_link', array('text' => L('ad_position_add'), 'href' => url('add')));
        $this->display();
    }

    /**
     * 新增广告位置
     */
    public function add() {
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['position_name']), L('no_positionname')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $this->model->table('touch_ad_position')->data($data)->insert();

            $this->message(L('positionadd_succed'), url('index'));
        }
        /* 模板赋值 */
        $this->assign('ur_here', L('ad_position_add'));
        $this->assign('posit_arr',   array('position_style' => '<ul>' ."\n". '{foreach from=$ads item=ad}' ."\n". '<li>{$ad}</li>' ."\n". '{/foreach}' ."\n". '</ul>'));
        $this->assign('action_link', array('text' => L('ad_position_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 编辑广告位置
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['position_name']), L('no_positionname')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $this->model->table('touch_ad_position')->data($data)->where('position_id=' . $id)->update();

            $this->message(sprintf(L('positionedit_succed'), $data['position_name']), url('index'));
        }
        /* 查询表信息 */
        $result = $this->model->table('touch_ad_position')->where('position_id=' . $id)->find();

        /* 模板赋值 */
        $this->assign('info', $result);
        $this->assign('ur_here', L('ad_position_edit'));
        $this->assign('action_link', array('text' => L('ad_position_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 删除广告位
     */
    public function del() {
        $id = I('id');
        //检查广告位下是否存在广告
        $count = $this->model->table('touch_ad')->where('position_id = ' . $id)->count();
        if($count > 0){
            $this->message('广告位下存在广告列表，不能删除广告位！',NULL,'error');
        }else{
            //删除广告位
            $condition['position_id'] = $id;
            $this->model->table('touch_ad_position')->where($condition)->delete();
            clear_all_files();
            $this->message(L('drop_succeed'), url('index'));
        }

    }



    /**
     * 查看广告位下的广告列表
     */
    public function ad_list(){
        $position_id = I('id','0');

        $keywords = I('keywords', '');
        $condition = 'position_id = '.$position_id;
        //搜索
        if (!empty($keywords)) {
            $filter['keywords'] = $keywords;
            $condition .= ' and ad_name like "%' . $keywords . '%" ';
            $this->assign('keywords', $keywords);
        }
        //分页
        $filter['page'] = '{page}';
        $filter['id'] = $position_id;
        $offset = $this->pageLimit(url('ad_list', $filter), 12);
        $total = $this->model->table('touch_ad')->where($condition)->count();
        $this->assign('page', $this->pageShow($total));
        //广告位列表
        $list = $this->get_ad_list($offset, $condition);
        $ad_list = array();
        foreach ($list as $key => $value) {
            $ad_list[$key]['ad_name'] = $value['ad_name'];
            $ad_list[$key]['ad_id'] = $value['ad_id'];
            $ad_list[$key]['position_name'] = $this->get_position_name($position_id);
            switch ($value['media_type']) {
                case '0':
                    $ad_list[$key]['media_type'] = '图片';
                    break;
                case '1':
                    $ad_list[$key]['media_type'] = 'FLash';
                    break;
                case '2':
                    $ad_list[$key]['media_type'] = '代码';
                    break;
                case '3':
                    $ad_list[$key]['media_type'] = '文字';
                    break;
            }
            $ad_list[$key]['start_time'] = date('Y-m-d',$value['start_time']);
            $ad_list[$key]['end_time'] = date('Y-m-d',$value['end_time']);
            $ad_list[$key]['click_count'] = $this->get_click_count($value['ad_id']);
            $ad_list[$key]['orders'] = $this->get_orders($value['ad_id']);

        }
        //print_r($ad_list);
        /* 模板赋值 */
        $this->assign('list', $ad_list);
        $this->assign('ur_here', L('ad_list'));
        $this->assign('action_link', array('text' => L('ad_add'), 'href' => url('ad_add', array('id'=>$position_id))));
        $this->display();

    }
    /**
     * 新增广告
     */
    public function ad_add() {
        if (IS_POST) {
            $data = I('data');

            $data2['position_id'] = $data['position_id'];
            $data2['media_type'] = $data['media_type'];
            $data2['ad_name'] = $data['ad_name'];
            $data2['ad_link'] = $data['ad_link'];

            switch ($data['media_type']) {
                /* 添加图片类型的广告 */
                case '0':
                    /* 上传广告图片 */
                    if ($_FILES['ad_img']['name']) {
                        /* ad_img广告图片 */
                        $result = $this->ectouchUpload('ad_img');
                        if ($result['error'] > 0) {
                            $this->message($result['message'], NULL, 'error');
                        }
                        $data2['ad_code'] = substr($result['message']['ad_img']['savepath'], 2) . $result['message']['ad_img']['savename'];
                    }
                    /* 远程图片地址 */
                    if(!empty($data['img_url'])){
                        $data2['ad_code'] = $data['img_url'];
                    }

                    break;
                /* 添加的广告是Flash广告 */
                case '1':
                    /* 上传的FLash文件 */
                    if($_FILES['upfile_flash']['name']) {
                        if($_FILES['upfile_flash']['type'] == 'application/x-shockwave-flash'){
                            /* 生成文件名 */
                            $urlstr = date('Ymd');
                            for ($i = 0; $i < 6; $i++)
                            {
                                $urlstr .= chr(mt_rand(97, 122));
                            }

                            $source_file = $_FILES['upfile_flash']['tmp_name'];
                            //$target      = ROOT_PATH . DATA_DIR . '/afficheimg/'; //生成路径
                            $file_name   = $urlstr .'.swf';
                            //移动到目录
                            //
                            $data2['ad_code'] = $file_name;

                        }
                        else{
                            $this->message('上传文件类型不正确！请重新上传', NULL, 'error');
                        }
                    }
                    /* 远程Flash地址 */
                    if(!empty($data['flash_url'])){
                        $data2['ad_code'] = $data['flash_url'];
                    }
                    break;
                /* 广告类型为代码广告 */
                case '2':
                    $data2['ad_code'] = !empty($data['ad_code']) ? $data['ad_code'] : '';
                    break;
                /* 广告类型为文本广告 */
                case '3':
                    $data2['ad_link'] = !empty($data['ad_link2']) ? $data['ad_link2'] : '';
                    $data2['ad_code'] = !empty($data['ad_text']) ? $data['ad_text'] : '';

                    break;
            }

            /* 处理广告的开始时期与结束日期 */
            $data2['start_time'] = local_strtotime($data['start_time']);
            $data2['end_time']   = local_strtotime($data['end_time']);
            $data2['link_man'] = $data['link_man'];
            $data2['link_email'] = $data['link_email'];
            $data2['link_phone'] = $data['link_phone'];
            $data2['enabled'] = $data['enabled'];
            //print_r($data2);
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data2['ad_name']), L('no_ad_name')),
                        array(Check::must($data2['ad_code']), L('no_ad_code')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }

            $this->model->table('touch_ad')->data($data2)->insert();
            $this->message(L('adadd_succed'), url('ad_list', array('id'=>$data['position_id'])));
        }

        $result['position_id'] = I('id'); //上级广告位id
        $result['start_time'] = local_date('Y-m-d');
        $result['end_time']   = local_date('Y-m-d', gmtime() + 3600 * 24 * 30);  // 默认结束时间为1个月以后

        /* 模板赋值 */
        $this->assign('info', $result);
        $this->assign('posi_arr', $this->get_posit_name_str()); //广告位名称：首页banner[200*100]
        $this->assign('ur_here', L('ad_add'));
        $this->assign('action_link', array('text' => L('ad_list'), 'href' => url('ad_list', array('id'=>$result['position_id']))));
        $this->display();
    }

    /**
     * 编辑广告
     */
    public function ad_edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['ad_name']), L('no_ad_name')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $data2['position_id'] = $data['position_id'];
            $data2['ad_name'] = $data['ad_name'];
            $data2['ad_link'] = $data['ad_link'];

            switch ($data['media_type']) {
                /* 添加图片类型的广告 */
                case '0':
                    /* 上传广告图片 */
                    if ($_FILES['ad_img']['name']) {
                        //得到原先的图片路径 删除
                        $img = $this->get_ad_code($data['ad_id']);
                        //排除远程图片
                        if (strpos($img, 'http://') === false && strpos($img, 'https://') === false){
                            $filename = __URL__.$img;
                            @unlink($filename);
                        }

                        /* ad_img广告图片 */
                        $result = $this->ectouchUpload('ad_img');
                        if ($result['error'] > 0) {
                            $this->message($result['message'], NULL, 'error');
                        }
                        $data2['ad_code'] = substr($result['message']['ad_img']['savepath'], 2) . $result['message']['ad_img']['savename'];
                    }
                    /* 远程图片地址 */
                    if(!empty($data['img_url'])){
                        $data2['ad_code'] = $data['img_url'];
                    }

                    break;
                /* 添加的广告是Flash广告 */
                case '1':
                    /* 上传的FLash文件 */
                    if($_FILES['upfile_flash']['name']) {
                        if($_FILES['upfile_flash']['type'] == 'application/x-shockwave-flash'){
                            /* 生成文件名 */
                            $urlstr = date('Ymd');
                            for ($i = 0; $i < 6; $i++)
                            {
                                $urlstr .= chr(mt_rand(97, 122));
                            }

                            $source_file = $_FILES['upfile_flash']['tmp_name'];
                            //$target      = ROOT_PATH . DATA_DIR . '/afficheimg/'; //生成路径
                            $file_name   = $urlstr .'.swf';
                            //移动到目录
                            //
                            $data2['ad_code'] = $file_name;

                        }
                        else{
                            $this->message('上传文件类型不正确！请重新上传', NULL, 'error');
                        }
                    }
                    /* 远程Flash地址 */
                    if(!empty($data['flash_url'])){
                        $data2['ad_code'] = $data['flash_url'];
                    }
                    break;
                /* 广告类型为代码广告 */
                case '2':
                    $data2['ad_code'] = !empty($data['ad_code']) ? $data['ad_code'] : '';
                    break;
                /* 广告类型为文本广告 */
                case '3':
                    $data2['ad_link'] = !empty($data['ad_link2']) ? $data['ad_link2'] : '';
                    $data2['ad_code'] = !empty($data['ad_text']) ? $data['ad_text'] : '';
                    break;
            }
            /* 处理广告的开始时期与结束日期 */
            $data2['start_time'] = local_strtotime($data['start_time']);
            $data2['end_time']   = local_strtotime($data['end_time']);
            $data2['link_man'] = $data['link_man'];
            $data2['link_email'] = $data['link_email'];
            $data2['link_phone'] = $data['link_phone'];
            $data2['enabled'] = $data['enabled'];

            // print_r($data2);
            $this->model->table('touch_ad')->data($data2)->where('ad_id=' . $data['ad_id'])->update();
            $this->message(sprintf(L('adedit_succed'), $data['ad_name']), url('ad_list', array('id'=>$data['position_id'])));
        }
        /* 查询表信息 */
        $result = $this->model->table('touch_ad')->where('ad_id=' . $id)->find();

        $result['start_time'] = date('Y-m-d',$result['start_time']);
        $result['end_time'] = date('Y-m-d',$result['end_time']);


        if ($result['media_type'] == '0')
        {
            if (strpos($result['ad_code'], 'http://') === false && strpos($result['ad_code'], 'https://') === false)
            {
                $src = __URL__.'/'. $result['ad_code'];
                $this->assign('ad_img', $src);
            }
            else
            {
                $src = $result['ad_code'];
                $this->assign('img_url', $src);
            }
        }
        if ($result['media_type'] == '1')
        {
            if (strpos($result['ad_code'], 'http://') === false && strpos($result['ad_code'], 'https://') === false)
            {
                $src = __URL__.'/'. $result['ad_code'];
                $this->assign('flash_url', $src);
            }
            else
            {
                $src = $result['ad_code'];
                $this->assign('flash_url', $src);
            }
            $this->assign('src', $src);
        }

        //print_r($result);

        /* 模板赋值 */
        $this->assign('info', $result);
        $this->assign('posi_arr', $this->get_posit_name_str()); //广告位名称：首页banner[200*100]
        $this->assign('ur_here', L('ad_edit'));
        $this->assign('action_link', array('text' => L('ad_list'), 'href' => url('ad_list', array('id'=>$result['position_id']))));
        $this->display();
    }

    /**
     * 删除广告
     */
    public function ad_del() {
        $id = I('get.id');
        //删除广告
        $condition['ad_id'] = $id;

        //得到广告的图片路径 删除
        $img = $this->get_ad_code($id);
        //排除远程图片
        if (strpos($img, 'http://') === false && strpos($img, 'https://') === false){
            $filename = __URL__.$img;
            @unlink($filename);
        }

        $this->model->table('touch_ad')->where($condition)->delete();
        clear_all_files();
        $this->message(L('drop_ad_succeed'), url('index'));
    }


/*================================================*/
//  FUNCTION
/*================================================*/

    /**
     * 返回广告位列表
     * @return array
     */
    private function get_advert_list($offset = '0, 12', $condition = '') {
        /* 查询 */
        return $this->model->table('touch_ad_position')->where($condition)->order('position_id asc')->limit($offset)->select();
    }
    /**
     * 获取广告位名称
     * @param   $id [广告位id]
     * @return  str
     */
    private function get_position_name($id){

        $arr = $this->model->table('touch_ad_position')->where('position_id =' .$id)->field('position_name')->find();
        return $arr['position_name'];
    }
    /**
     * 获取组合式广告位名称
     * @return [type]   首页banner [ 200x100 ]
     */
    private function get_posit_name_str(){

        $list = $this->model->table('touch_ad_position')->order('position_id asc')->field('position_id,position_name,ad_width,ad_height')->select();
        $posit_name_arr = array();
        foreach ($list as $key => $value) {
            $posit_name_arr[$key]['position_id'] = $value['position_id'];
            $posit_name_arr[$key]['position_name_str'] = $value['position_name'] . ' [ ' . $value['ad_width'] . ' x ' . $value['ad_height'] . ' ] ';
        }
        return $posit_name_arr;
    }

    /**
     * 返回广告列表
     *
     */
    private function get_ad_list($offset = '0, 12',$condition){
        /* 查询 */
        return $this->model->table('touch_ad')->where($condition)->order('ad_id asc')->limit($offset)->select();

    }
    /**
     * 获取广告点击次数
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    private function get_click_count($id){
        $arr = $this->model->table('touch_ad')->where('ad_id =' .$id)->field('click_count')->find();
        return $arr['click_count'];
    }
    /**
     * 获取广告生成订单
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    private function get_orders($id){

        $orders = $this->model->table('touch_ad as t, ' . $this->model->pre . 'order_info as o')->where('o.from_ad = '.$id)->count();
        return $orders;
    }
    /**
     * 广告图片ad_code
     */
    private function get_ad_code($id){
        $arr = $this->model->table('touch_ad')->where('ad_id = '.$id)->field('ad_code')->find();
        return $arr['ad_code'];
    }

}
