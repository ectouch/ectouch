<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CommentModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 评论模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CommentModel extends BaseModel {

    /**
     * 查询评论内容
     *
     * @access  public
     * @params  integer     $id
     * @params  integer     $type
     * @params  integer     $page
     * @return  array
     */
    function assign_comment($id, $type, $page = 1) {
        /* 取得评论列表 */
        $count = $this->row('SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0");
        $count = $res['count'];
        $size = C('comments_number') > 0 ? C('comments_number') : 5;

        $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
        $start = ($page - 1) * $size;
        $sql = 'SELECT * FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" .
                " ORDER BY comment_id DESC LIMIT $start , $size";
        $res = $this->query($sql);
        $arr = array();
        $ids = '';
        foreach ($res as $key => $row) {
            $ids .= $ids ? ",$row[comment_id]" : $row['comment_id'];
            $arr[$row['comment_id']]['id'] = $row['comment_id'];
            $arr[$row['comment_id']]['email'] = $row['email'];
            $arr[$row['comment_id']]['username'] = $row['user_name'];
            $arr[$row['comment_id']]['content'] = str_replace('\r\n', '<br />', htmlspecialchars($row['content']));
            $arr[$row['comment_id']]['content'] = nl2br(str_replace('\n', '<br />', $arr[$row['comment_id']]['content']));
            $arr[$row['comment_id']]['rank'] = $row['comment_rank'];
            $arr[$row['comment_id']]['add_time'] = local_date(C('time_format'), $row['add_time']);
        }
        /* 取得已有回复的评论 */
        if ($ids) {
            $sql = 'SELECT * FROM ' . $this->pre .
                    "comment WHERE parent_id IN( $ids )";
            $res = $this->query($sql);
            foreach ($res as $row) {
                $arr[$row['parent_id']]['re_content'] = nl2br(str_replace('\n', '<br />', htmlspecialchars($row['content'])));
                $arr[$row['parent_id']]['re_add_time'] = local_date(C('time_format'), $row['add_time']);
                $arr[$row['parent_id']]['re_email'] = $row['email'];
                $arr[$row['parent_id']]['re_username'] = $row['user_name'];
            }
        }
        /* 分页样式 */
        //$pager['styleid'] = isset(C('page_style'))? intval(C('page_style')) : 0;
        $pager['page'] = $page;
        $pager['size'] = $size;
        $pager['record_count'] = $count;
        $pager['page_count'] = $page_count;
        $pager['page_first'] = "javascript:gotoPage(1,$id,$type)";
        $pager['page_prev'] = $page > 1 ? "javascript:gotoPage(" . ($page - 1) . ",$id,$type)" : 'javascript:;';
        $pager['page_next'] = $page < $page_count ? 'javascript:gotoPage(' . ($page + 1) . ",$id,$type)" : 'javascript:;';
        $pager['page_last'] = $page < $page_count ? 'javascript:gotoPage(' . $page_count . ",$id,$type)" : 'javascript:;';

        $cmt = array('comments' => $arr, 'pager' => $pager);

        return $cmt;
    }

    /**
     * 添加评论内容
     *
     * @access public
     * @param object $cmt        	
     * @return void
     */
    function add_comment($cmt) {
        /* 评论是否需要审核 */
        $status = 1 - C('comment_check');

        $user_id = empty($_SESSION ['user_id']) ? 0 : $_SESSION ['user_id'];
        $email = empty($cmt->email) ? $_SESSION ['email'] : trim($cmt->email);
        $user_name = empty($cmt->username) ? $_SESSION ['user_name'] : '';
        $email = htmlspecialchars($email);
        $user_name = htmlspecialchars($user_name);

        /* 保存评论内容 */
        $sql = "INSERT INTO " . $this->pre . "comment(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " . "('" . $cmt->type . "', '" . $cmt->id . "', '$email', '$user_name', '" . $cmt->content . "', '" . $cmt->rank . "', " . gmtime() . ", '" . real_ip() . "', '$status', '0', '$user_id')";


        $result = $this->query($sql);
        clear_cache_files('comments_list.lbi');
        return $result;
    }

}
