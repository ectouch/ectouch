<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ArticleModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 文章模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class ArticleModel extends BaseModel {

    /**
     * 分配文章列表给smarty
     *
     * @access  public
     * @param   integer     $id     文章分类的编号
     * @param   integer     $num    文章数量
     * @return  array
     */
    function assign_articles($id, $num) {
        $sql = 'SELECT cat_name FROM ' . $this->pre . "article_cat WHERE cat_id = '" . $id . "'";

        $cat['id'] = $id;
        $res = $this->row($sql);
        $cat['name'] = $res['cat_name'];
        $cat['url'] = build_uri('article_cat', array('acid' => $id), $cat['name']);

        $articles['cat'] = $cat;
        $articles['arr'] = model('ArticleBase')->get_cat_articles($id, 1, $num);

        return $articles;
    }

    /**
     * 分配帮助信息
     *
     * @access  public
     * @return  array
     */
    function get_shop_help() {
        $sql = 'SELECT c.cat_id, c.cat_name, c.sort_order, a.article_id, a.title, a.file_url, a.open_type ' .
                'FROM ' . $this->pre . 'article AS a ' .
                'LEFT JOIN ' . $this->pre . 'article_cat AS c ' .
                'ON a.cat_id = c.cat_id WHERE c.cat_type = 5 AND a.is_open = 1 ' .
                'ORDER BY c.sort_order ASC, a.article_id';
        $res = $this->query($sql);

        $arr = array();
        foreach ($res AS $key => $row) {
            $arr[$row['cat_id']]['cat_id'] = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
            $arr[$row['cat_id']]['cat_name'] = $row['cat_name'];
            $arr[$row['cat_id']]['article'][$key]['article_id'] = $row['article_id'];
            $arr[$row['cat_id']]['article'][$key]['title'] = $row['title'];
            $arr[$row['cat_id']]['article'][$key]['short_title'] = C('article_title_length') > 0 ?
                    sub_str($row['title'], C('article_title_length')) : $row['title'];
            $arr[$row['cat_id']]['article'][$key]['url'] = $row['open_type'] != 1 ?
                    build_uri('article', array('aid' => $row['article_id']), $row['title']) : trim($row['file_url']);
        }

        return $arr;
    }

    /**
     * 获得指定文章分类的所有上级分类
     *
     * @access  public
     * @param   integer $cat    分类编号
     * @return  array
     */
    function get_article_parent_cats($cat) {
        if ($cat == 0) {
            return array();
        }

        $arr = $this->query('SELECT cat_id, cat_name, parent_id FROM ' . $this->pre . 'article_cat');

        if (empty($arr)) {
            return array();
        }

        $index = 0;
        $cats = array();

        while (1) {
            foreach ($arr AS $row) {
                if ($cat == $row['cat_id']) {
                    $cat = $row['parent_id'];

                    $cats[$index]['cat_id'] = $row['cat_id'];
                    $cats[$index]['cat_name'] = $row['cat_name'];

                    $index++;
                    break;
                }
            }

            if ($index == 0 || $cat == 0) {
                break;
            }
        }

        return $cats;
    }

    /**
     * 获得指定分类同级的所有分类以及该分类下的子分类
     *
     * @access  public
     * @param   integer     $cat_id     分类编号
     * @return  array
     */
    function article_categories_tree($cat_id = 0) {
        if ($cat_id > 0) {
            $sql = 'SELECT parent_id FROM ' . $this->pre .
                    "touch_article_cat  WHERE cat_id = '$cat_id'";
            $res = $this->row($sql);          
            $parent_id = $res['parent_id'];
        } else {
            $parent_id = 0;
        }
        /*
          判断当前分类中全是是否是底级分类，
          如果是取出底级分类上级分类，
          如果不是取当前分类及其下的子分类
         */
        $sql = 'SELECT count(*) as count FROM ' . $this->pre .
                "touch_article_cat  WHERE parent_id = '$parent_id'";
        $res = $this->row($sql);
        if ($res['count']) {
            /* 获取当前分类及其子分类 */
            $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, ' .
                    'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order ' .
                    'FROM ' . $this->pre . 'touch_article_cat AS a ' .
                    'LEFT JOIN ' . $this->pre . 'touch_article_cat AS b ON b.parent_id = a.cat_id ' . 
                    "WHERE a.parent_id = '$parent_id' ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";
        } else {
            /* 获取当前分类及其父分类 */
            $sql = 'SELECT a.cat_id, a.cat_name, b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order ' .
                    'FROM ' . $this->pre . 'touch_article_cat AS a ' .
                    'LEFT JOIN ' . $this->pre . 'touch_article_cat AS b ON b.parent_id = a.cat_id ' .
                    "WHERE b.parent_id = '$parent_id' ORDER BY sort_order ASC";
        }
        $res = $this->query($sql);
        $cat_arr = array();
        foreach ($res AS $row) {
            $cat_arr[$row['cat_id']]['id'] = $row['cat_id'];
            $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];

            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url'] = url('article/art_list', array('acid' => $row ['cat_id']));
            //$cat_arr[$row['cat_id']]['url'] = build_uri('article_cat', array('acid' => $row['cat_id']), $row['cat_name']);
            if ($row['child_id'] != NULL) {

                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id'] = $row['child_id'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
                $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url'] = url('article/art_list', array('acid' => $row ['child_id']));
                // $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url'] = build_uri('article_cat', array('acid' => $row['child_id']), $row['child_name']);
            }
        }

        return $cat_arr;
    }

    /**
     * 获得指定的文章的详细信息
     *
     * @access  private
     * @param   integer     $article_id
     * @return  array
     */
    function get_article_info($article_id) {
        /* 获得文章的信息 */
        $sql = "SELECT a.*, IFNULL(AVG(r.comment_rank), 0) AS comment_rank " .
                "FROM " . $this->pre . "touch_article AS a " .
                "LEFT JOIN " . $this->pre . "comment AS r ON r.id_value = a.article_id AND comment_type = 1 " .
                "WHERE a.is_open = 1 AND a.article_id = '$article_id' GROUP BY a.article_id";
        $row = $this->row($sql);

        if ($row !== false) {
            $row['comment_rank'] = ceil($row['comment_rank']);                              // 用户评论级别取整
            $row['add_time'] = local_date(L('date_format'), $row['add_time']); // 修正添加时间显示
            $row['content'] = html_out($row['content']);;
            
            /* 作者信息如果为空，则用网站名称替换 */
            if (empty($row['author']) || $row['author'] == '_SHOPHELP') {
                $row['author'] = L('shop_name');
            }
        }

        return $row;
    }

}
