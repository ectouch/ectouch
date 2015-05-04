<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：TopicControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：专题管理控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class TopicController extends AdminController {

    /**
     * 专题管理列表
     */
    public function index() {
        /* 模板赋值 */
        $filter['page'] = '{page}';
        $offset = $this->pageLimit(url('index', $filter), 12);
        $total = $this->model->table('touch_topic')->where()->count();
        $this->assign('page', $this->pageShow($total));

        $list = $this->get_topic_list($offset);
        $this->assign('topic_list', $list);
        $this->assign('ur_here', L('09_topic'));
        $this->assign('action_link', array('text' => L('topic_add'), 'href' => url('add')));
        $this->display();
    }

    /**
     * 添加专题
     */
    public function add() {
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($_POST['topic_name']), L('topic_name_empty')),
                        array(Check::must($_POST['start_time']), L('start_time_empty')),
                        array(Check::must($_POST['end_time']), L('end_time_empty')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $topic_type = empty($data['topic_type']) ? 0 : intval($data['topic_type']);

            switch ($topic_type) {
                case '0' :
                case '1' :
                    // 主图上传
                    if ($_FILES['topic_img']['name'] && $_FILES['topic_img']['size'] > 0) {
                        $result = $this->ectouchUpload('topic_img', 'topic_image');
                        if ($result['error'] > 0) {
                            $this->message($result['message'], NULL, 'error');
                        }
                        /* 生成logo链接 */
                        $topic_img = substr($result['message']['topic_img']['savepath'], 2) . $result['message']['topic_img']['savename'];
                    } else if (!empty($_POST['url'])) {
                        /* 来自互联网图片 不可以是服务器地址 */
                        if (strstr(I('post.url'), 'http') && !strstr(I('post.url'), $_SERVER['SERVER_NAME'])) {
                            /* 取互联网图片至本地 */
                            $topic_img = get_url_image(I('post.url'));
                        } else {
                            sys_msg(L('web_url_no'));
                        }
                    }
                    $data['topic_img'] = empty($topic_img) ? I('post.img_url') : $topic_img;
                    $htmls = '';
                    break;
                case '2' :
                    $htmls = I('post.content');

                    $data['topic_img'] = '';
                    break;
            }
            // 标题图上传
            if ($_FILES['title_pic']['name'] && $_FILES['title_pic']['size'] > 0) {
                $result = $this->ectouchUpload('title_pic', 'topic_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成logo链接 */
                $data['title_pic'] = substr($result['message']['title_pic']['savepath'], 2) . $result['message']['title_pic']['savename'];
            } else if (!empty($_REQUEST['title_url'])) {
                /* 来自互联网图片 不可以是服务器地址 */
                if (strstr(I('post.title_url'), 'http') && !strstr(I('post.title_url'), $_SERVER['SERVER_NAME'])) {
                    /* 取互联网图片至本地 */
                    $data['title_pic'] = get_url_image(I('post.title_url'));
                } else {
                    sys_msg(L('web_url_no'));
                }
            }
            unset($target);
            $data['title'] = I('request.topic_name');
            $title_pic = empty($data['title_pic']) ? I('post.title_img_url') : $data['title_pic'];

            $data['start_time'] = local_strtotime(I('post.start_time'));
            $data['end_time'] = local_strtotime(I('post.end_time'));
            $json = new EcsJson;
            $tmp_data = $json->decode($_POST['topic_data']);
            $data['data'] = serialize($tmp_data);
            $data['intro'] = I('post.topic_intro');
            $data['template'] = I('post.topic_template_file') ? I('post.topic_template_file') : '';
            $this->model->table('touch_topic')->data($data)->insert();
            $this->message(L('succed'), url('index'));
        }
        $topic = array('title' => '', 'topic_type' => 0, 'url' => 'http://');
        $this->assign('topic', $topic);
        $this->assign('cat_list', cat_list(0, 1));
        $this->assign('brand_list', model('BrandBase')->get_brand_list());
        $this->assign('template_list', $this->get_topic_temp_list());
        $this->assign('ur_here', L('09_topic'));
        $this->display();
    }

    /**
     * 编辑专题
     */
    public function edit() {
        $id = I('id');
        if (!$id) {
            $this->redirect(url('index'));
        }
        if (IS_POST) {
            $data = I('data');
            /* 数据验证 */
            $msg = Check::rule(array(
                        array(Check::must($_POST['topic_name']), L('topic_name_empty')),
                        array(Check::must($_POST['start_time']), L('start_time_empty')),
                        array(Check::must($_POST['end_time']), L('end_time_empty')),
            ));
            /* 提示信息 */
            if ($msg !== true) {
                $this->message($msg, NULL, 'error');
            }
            $topic_type = empty($data['topic_type']) ? 0 : intval($data['topic_type']);

            switch ($topic_type) {
                case '0' :
                case '1' :
                    // 主图上传
                    if ($_FILES['topic_img']['name'] && $_FILES['topic_img']['size'] > 0) {
                        $result = $this->ectouchUpload('topic_img', 'topic_image');
                        if ($result['error'] > 0) {
                            $this->message($result['message'], NULL, 'error');
                        }
                        /* 生成logo链接 */
                        $topic_img = substr($result['message']['topic_img']['savepath'], 2) . $result['message']['topic_img']['savename'];
                    } else if (!empty($_POST['url'])) {
                        /* 来自互联网图片 不可以是服务器地址 */
                        if (strstr(I('post.url'), 'http') && !strstr(I('post.url'), $_SERVER['SERVER_NAME'])) {
                            /* 取互联网图片至本地 */
                            $topic_img = get_url_image(I('post.url'));
                        } else {
                            sys_msg(L('web_url_no'));
                        }
                    }
                    $data['topic_img'] = empty($topic_img) ? I('post.img_url') : $topic_img;
                    $htmls = '';
                    break;
                case '2' :
                    $htmls = I('post.content');
                    $data['topic_img'] = '';
                    break;
            }
            // 标题图上传
            if ($_FILES['title_pic']['name'] && $_FILES['title_pic']['size'] > 0) {
                $result = $this->ectouchUpload('title_pic', 'topic_image');
                if ($result['error'] > 0) {
                    $this->message($result['message'], NULL, 'error');
                }
                /* 生成logo链接 */
                $data['title_pic'] = substr($result['message']['title_pic']['savepath'], 2) . $result['message']['title_pic']['savename'];
            } else if (!empty($_REQUEST['title_url'])) {
                /* 来自互联网图片 不可以是服务器地址 */
                if (strstr(I('post.title_url'), 'http') && !strstr(I('post.title_url'), $_SERVER['SERVER_NAME'])) {
                    /* 取互联网图片至本地 */
                    $data['title_pic'] = get_url_image(I('post.title_url'));
                } else {
                    sys_msg(L('web_url_no'));
                }
            }
            unset($target);
            $data['title'] = I('post.topic_name');
            $title_pic = empty($data['title_pic']) ? I('post.title_img_url') : $data['title_pic'];
            $data['template'] = I('post.topic_template_file') ? I('post.topic_template_file') : '';
            $data['start_time'] = local_strtotime(I('post.start_time'));
            $data['end_time'] = local_strtotime(I('post.end_time'));
            $json = new EcsJson;
            $tmp_data = $json->decode($_POST['topic_data']);
            $data['data'] = serialize($tmp_data);
            $data['intro'] = I('post.topic_intro');
            $this->model->table('touch_topic')->data($data)->where('topic_id =' . $id)->update();
            $this->message(L('succed'), url('index'));
        }
        /* 模板赋值 */
        $topic = $this->model->table('touch_topic')->field('*')->where('topic_id =' . $id)->find();
        $topic['start_time'] = local_date('Y-m-d', $topic['start_time']);
        $topic['end_time'] = local_date('Y-m-d', $topic['end_time']);
        $topic['topic_intro'] = html_out($topic['intro']);
        $topic['intro'] = html_out($topic['intro']);
        $json = new EcsJson;

        if ($topic['data']) {
            $topic['data'] = addcslashes($topic['data'], "'");
            $topic['data'] = $json->encode(@unserialize($topic['data']));
            $topic['data'] = addcslashes($topic['data'], "'");
        }
        if (empty($topic['topic_img']) && empty($topic['htmls'])) {
            $topic['topic_type'] = 0;
        } elseif ($topic['htmls'] != '') {
            $topic['topic_type'] = 2;
        } elseif (preg_match('/.swf$/i', $topic['topic_img'])) {
            $topic['topic_type'] = 1;
        } else {
            $topic['topic_type'] = '';
        }
        $this->assign('topic', $topic);
        $this->assign('cat_list', cat_list(0, 1));
        $this->assign('brand_list', model('BrandBase')->get_brand_list());
        $this->assign('template_list', $this->get_topic_temp_list());
        $this->assign('ur_here', L('09_topic'));
        $this->display();
    }

    /**
     * 删除专题
     */
    public function del() {
        $id = I('id');
        if (!$id) {
            $this->redirect(url('index'));
        }
        /* 删除该品牌的图标 */
        $topic = $this->model->table('touch_topic')->field('*')->where('topic_id = ' . $id)->find();

        $topic_img = $topic['topic_img'];
        $title_pic = $topic['title_pic'];
        $intro = html_out($topic['intro']);
        //删除编辑器中的附件
        $match = array();
        preg_match_all("/(src|href)\=\"\/(.*?)\"/i", $intro, $match);
        if (is_array($match[2])) {
            foreach ($match[2] as $vo) {
                $index = strpos($vo, 'data/');
                @unlink(ROOT_PATH . substr($vo, $index));
            }
        }
        //删除logo
        if (!empty($topic_img)) {
            $index = strpos($topic_img, 'data/');
            @unlink(ROOT_PATH . substr($topic_img, $index));
        }
        //删除分类图标
        if (!empty($title_pic)) {
            $index = strpos($title_pic, 'data/');
            @unlink(ROOT_PATH . substr($title_pic, $index));
        }
        //删除品牌
        $this->model->table('touch_topic')->where(array('topic_id' => $id))->delete();
        clear_all_files();
        $this->message(L('succed'), url('index'));
    }

    /**
     * 获取专题列表
     * @access  public
     * @return void
     */
    function get_topic_list($offset = '0, 12') {
        $result = get_filter();
        if ($result === false) {
            /* 查询条件 */
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'topic_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['record_count'] = $this->model->table('topic')->where()->count();

            /* 分页大小 */
            $filter = page_and_size($filter);

            $sql = "SELECT * FROM " . $this->model->pre . "touch_topic ORDER BY $filter[sort_by] $filter[sort_order] limit $offset";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $query = $this->model->query($sql);

        $res = array();
        foreach ($query as $topic) {
            $topic['start_time'] = local_date('Y-m-d', $topic['start_time']);
            $topic['end_time'] = local_date('Y-m-d', $topic['end_time']);
            $topic['url'] = url('index', array('id' => $topic['topic_id']));
            $res[] = $topic;
        }
        $arr = array('item' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $res;
    }

    /**
     * 异步调用商品列表
     */
    public function get_goods_list() {
        $_POST['filters'] = strip_tags(urldecode($_POST ['filters']));
        $_POST['filters'] = json_str_iconv($_POST['filters']);
        $json = new EcsJson;
        $filters = $json->decode($_POST['filters']);
        $arr = get_goods_list($filters);
        $opt = array();
        foreach ($arr AS $key => $val) {
            $opt[] = array('value' => $val['goods_id'],
                'text' => $val['goods_name']);
        }
        make_json_result($opt);
    }

    /**
     * 查找主题模版
     * @return array
     */
    private function get_topic_temp_list() {
        $tmp_dir = ROOT_PATH . 'themes/' . C('template'); // 模板所在路径
        $dir = @opendir($tmp_dir);
        $tmp[] = 'topic.dwt';
        while (false !== ($file = @readdir($dir))) {
            if (preg_match("/^topic_(.*?)\.dwt/", $file)) {
                $tmp[] = $file;
            }
        }
        return $tmp;
    }

}
