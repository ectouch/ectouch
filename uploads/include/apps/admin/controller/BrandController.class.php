<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BrandControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：品牌管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BrandController extends AdminController {

    /**
     * 品牌列表
     */
    public function index() {
        $keywords = I('keywords', '');
        //搜索
        if (!empty($keywords)) {
            $filter['keywords'] = $keywords;
            $condition = 'brand_name like "%' . $keywords . '%" or brand_desc like "%' . $keywords . '%"';
            $this->assign('keywords', $keywords);
        }
        //分页
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('brand')->where($condition)->count();
        $this->assign('page', $this->pageShow($total));
        //品牌列表
        $list = $this->get_list($offset, $condition);
        /* 模板赋值 */
        $this->assign('list', $list);
        $this->assign('ur_here', L('06_goods_brand_list'));
        $this->assign('action_link', array('text' => L('07_brand_add'), 'href' => url('add')));
        $this->display();
    }

    /**
     * 新增品牌
     */
    public function add() {
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['brand_name']), L('no_brandname')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            /* 上传图标 */
            if ($_FILES['brand_logo']['name']) {
                $result = $this->ectouchUpload('brand_logo', 'brand_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成logo链接 */
                $data['brand_logo'] = substr($result['message']['brand_logo']['savepath'], 2) . $result['message']['brand_logo']['savename'];
            }
            $brand_id = $this->model->table('brand')->data($data)->insert();
            /* 更新附表 */
            if ($_FILES['brand_banner']['name']) {
                $result = $this->ectouchUpload('brand_banner', 'brand_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成banner链接 */
                $data2['brand_banner'] = substr($result['message']['brand_banner']['savepath'], 2) . $result['message']['brand_banner']['savename'];
            }
            /* 品牌详情 */
            $data2['brand_content'] = I('post.content');
            $data2['brand_id'] = $brand_id;
            $this->model->table('touch_brand')->data($data2)->insert();

            $this->message(L('brandadd_succed'), url('index'));
        }
        /* 模板赋值 */
        $this->assign('ur_here', L('brand_edit'));
        $this->assign('action_link', array('text' => L('06_goods_brand_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 编辑品牌
     */
    public function edit() {
        $id = I('id');
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($data['brand_name']), L('no_brandname')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            /* 更新图标 */
            if ($_FILES['brand_logo']['name']) {
                $result = $this->ectouchUpload('brand_logo', 'brand_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成logo链接 */
                $data['brand_logo'] = substr($result['message']['brand_logo']['savepath'], 2) . $result['message']['brand_logo']['savename'];
            }
            $this->model->table('brand')->data($data)->where('brand_id=' . $id)->update();
            /* 更新附表 */
            if ($_FILES['brand_banner']['name']) {
                $result = $this->ectouchUpload('brand_banner', 'brand_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成banner链接 */
                $data2['brand_banner'] = substr($result['message']['brand_banner']['savepath'], 2) . $result['message']['brand_banner']['savename'];
            }
            /* 品牌详情 */
            $data2['brand_content'] = I('post.content');
            $this->model->table('touch_brand')->data($data2)->where('brand_id=' . $id)->update();

            $this->message(sprintf(L('brandedit_succed'), $data['brand_name']), url('index'));
        }
        /* 查询附表信息 */
        $result = $this->model->table('brand')->where('brand_id=' . $id)->find();
        $touch_result = $this->model->table('touch_brand')->where('brand_id=' . $id)->find();
        /* 附表信息不存在则生成 */
        if (empty($touch_result)) {
            $data['brand_id'] = $id;
            $this->model->table('touch_brand')->data($data)->insert();
        } else {
            $result['brand_banner'] = $touch_result['brand_banner'];
            $result['brand_content'] = html_out($touch_result['brand_content']);
        }
        /* 模板赋值 */
        $this->assign('info', $result);
        $this->assign('ur_here', L('brand_edit'));
        $this->assign('action_link', array('text' => L('06_goods_brand_list'), 'href' => url('index')));
        $this->display();
    }

    /**
     * 删除品牌
     */
    public function del() {
        $id = I('id');
        /* 删除该品牌的图标 */
        $sql = "SELECT a.brand_logo,b.brand_banner,b.brand_content FROM " . $this->model->pre . "brand as a left join " . $this->model->pre . "touch_brand as b on a.brand_id = b.brand_id WHERE a.brand_id = '$id'";
        $result = $this->model->query($sql);
        $brand_logo = $result[0]['brand_logo'];
        $brand_banner = $result[0]['brand_banner'];
        $brand_content = html_out($result[0]['brand_content']);
        //删除编辑器中的附件
        preg_match_all("/(src|href)\=\"\/(.*?)\"/i", $brand_content, $match);
        if (is_array($match[2])) {
            foreach ($match[2] as $vo) {
                $index = strpos($vo, 'data/');
                @unlink(ROOT_PATH . substr($vo, $index));
            }
        }
        //删除logo
        if (!empty($brand_logo)) {
            $index = strpos($brand_logo, 'data/');
            @unlink(ROOT_PATH . substr($brand_logo, $index));
        }
        //删除广告位
        if (!empty($brand_banner)) {
            $index = strpos($brand_banner, 'data/');
            @unlink(ROOT_PATH . substr($brand_banner, $index));
        }
        //更新商品的品牌编号
        $this->model->table('goods')->data('brand_id=0')->where('brand_id=' . $id)->update();
        //删除品牌
        $condition['brand_id'] = $id;
        $this->model->table('brand')->where($condition)->delete();
        $this->model->table('touch_brand')->where($condition)->delete();
        clear_all_files();
        $this->message(L('drop_succeed'), url('index'));
    }

    /**
     * 返回品牌列表
     * @return array
     */
    private function get_list($offset = '0, 12', $condition = '') {
        /* 查询 */
        return $this->model->table('brand')->where($condition)->order('sort_order asc')->limit($offset)->select();
    }

}
