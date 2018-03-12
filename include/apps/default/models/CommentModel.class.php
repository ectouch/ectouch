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

class CommentModel extends BaseModel
{

    /**
     * 查询评论内容
     *
     * @access  public
     * @params  integer     $id
     * @params  integer     $type
     * @params  integer     $page
     * @return  array
     */
    public function assign_comment($id, $type, $rank = 0, $page = 1)
    {
        $rank_info = '';
        if ($rank == '1') {
            $rank_info = ' AND (comment_rank= 5 OR comment_rank = 4)';
        }
        if ($rank == '2') {
            $rank_info = ' AND (comment_rank= 2 OR comment_rank = 3)';
        }
        if ($rank == '3') {
            $rank_info = ' AND comment_rank= 1 ';
        }
        /* 取得评论列表 */
        $res = $this->row('SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" . $rank_info);
        $count = $res['count'];
        $size = C('comments_number') > 0 ? C('comments_number') : 5;
        $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
        $start = ($page - 1) * $size;
        $sql = 'SELECT * FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" . $rank_info .
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
        $pager['page_first'] = "javascript:gotoPage(1,$id,$type,$rank)";
        $pager['page_prev'] = $page > 1 ? "javascript:gotoPage(" . ($page - 1) . ",$id,$type,$rank)" : 'javascript:;';
        $pager['page_next'] = $page < $page_count ? 'javascript:gotoPage(' . ($page + 1) . ",$id,$type,$rank)" : 'javascript:;';
        $pager['page_last'] = $page < $page_count ? 'javascript:gotoPage(' . $page_count . ",$id,$type,$rank)" : 'javascript:;';
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
    public function add_comment($cmt, $recId, $order_id)
    {
        /* 评论是否需要审核 */
        $status = 1 - C('comment_check');

        $user_id = empty($_SESSION ['user_id']) ? 0 : $_SESSION ['user_id'];
        $email = empty($cmt->email) ? $_SESSION ['email'] : trim($cmt->email);
        $user_name = empty($cmt->username) ? $_SESSION ['user_name'] : '';
        $email = htmlspecialchars($email);
        $user_name = htmlspecialchars($user_name);
        $sql = "SELECT goods_id FROM ".$this->pre ."order_goods where rec_id = '$recId'";
        $row = $this->row($sql);
        $goods_id = $row['goods_id'];

        /* 保存评论内容 */
        $sql = "INSERT INTO " . $this->pre . "comment(comment_type, id_value, email, user_name, content, comment_rank, add_time, ip_address, status, parent_id, user_id) VALUES " . "('" . $cmt->type . "', '" . $cmt->id . "', '$email', '$user_name', '" . $cmt->content . "', '" . $cmt->rank . "', " . gmtime() . ", '" . real_ip() . "', '$status', '0', '$user_id')";
        $result1 = $this->query($sql);
        $comment_id = $this->db->lastId();
        if ($result1) {
            $sql = 'INSERT INTO ' . $this->pre . 'term_relationship ( ' .
                'object_type, object_group, object_id, item_key1, item_value1, item_key2, item_value2'.') VALUES (' .
                "'ecjia.comment', 'comment', '$recId', 'goods_id', '$goods_id', 'comment_id', '$comment_id')";
            $result = $this->query($sql);
            if ($result) {
                clear_cache_files('comments_list.lbi');
                return $result;
            } else {
                $sql = "DELETE FROM " . $this->pre . "comment WHERE commet_id = '" . $commet_id . "'";
                $this->query($sql);
                clear_cache_files('comments_list.lbi');
                return $result;
            }
        } else {
            clear_cache_files('comments_list.lbi');
            return $result1;
        }
    }

    /**
     * 获取商品总的评价详情 by Leah
     * @param type $id
     * @param type $type
     */
    public function get_comment_info($id, $type)
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        $info['count'] = $result['count'];

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND (comment_rank= 5 OR comment_rank = 4) AND status = 1 AND parent_id = 0" .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        $favorable = $result['count'];

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0 AND(comment_rank = 2 OR comment_rank = 3)" .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        $medium = $result['count'];

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$id' AND comment_type = '$type' AND status = 1 AND parent_id = 0 AND comment_rank = 1 " .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        $bad = $result['count'];

        $info['favorable_count'] = $favorable; //好评数量
        $info['medium_count'] = $medium; //中评数量
        $info['bad_count'] = $bad; //差评数量
        if ($info['count'] > 0) {
            $info['favorable'] = 0;
            if ($favorable) {
                $info['favorable'] = round(($favorable / $info['count']) * 100);  //好评率
            }
            $info['medium'] = 0;
            if ($medium) {
                $info['medium'] = round(($medium / $info['count']) * 100); //中评
            }
            $info['bad'] = 0;
            if ($bad) {
                $info['bad'] = round(($bad / $info['count']) * 100); //差评
            }
        } else {
            $info['favorable'] = 100;
            $info['medium'] = 100;
            $info['bad'] = 100;
        }
        return $info;
    }

    /**
     * 获取商品所有评论数量 by Leah
     * @param type $goods_id
     * @param type $type
     * @return type
     */
    public function get_goods_comment($goods_id, $type)
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$goods_id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        return $result['count'];
    }

    /**
     * 获取商品好评百分比 by Leah
     * @param type $goods_id
     * @param type $type
     * @return type
     */
    public function favorable_comment($goods_id, $type)
    {
        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$goods_id' AND comment_type = '$type' AND status = 1 AND parent_id = 0" .
                ' ORDER BY comment_id DESC';
        $result = $this->row($sql);
        $count = $result['count'];

        $sql = 'SELECT COUNT(*) as count FROM ' . $this->pre .
                "comment WHERE id_value = '$goods_id' AND comment_type = '$type' AND (comment_rank= 5 OR comment_rank = 4) AND status = 1 AND parent_id = 0" .
                ' ORDER BY comment_id DESC';
        $goods_result = $this->row($sql);
        $good_count = $goods_result['count'];
        $round = 100;
        if ($count > 0) {
            $round = round(($good_count / $count) * 100);
        }
        return $round;
    }


    /**
     * 获取商品的评论列表
     */
    public function get_comment($goods_id, $type, $pay = 1, $num = 10, $start = 0)
    {
        if (empty($goods_id)) {
            return false;
        }
        switch ($type) {
            case 1:
            $where = " AND comment_rank > 0";
            break;
            case 2:
            $where = " AND comment_rank IN (4,5)";
            break;
            case 3:
            $where = " AND comment_rank IN (2,3)";
            break;
            case 4:
            $where = " AND comment_rank IN (0,1)";
            break;
        }
        if (!empty($limit)) {
            $limit = " LIMIT $limit";
        }
        $sql = "SELECT comment_id,email,user_name,content,comment_rank,add_time FROM ". $this->pre ."comment WHERE ".
               "comment_type = 0 AND id_value = '".$goods_id."' AND status = 1 $where ORDER BY comment_id DESC LIMIT $start , $num";
        $comment_list = $this->query($sql);
        foreach ($comment_list as $k => $v) {
            $comment_list[$k]['add_time'] = local_date(C('time_format'), $v['add_time']);
            $comment_list[$k]['reply'] =  $this->reply_content($v['comment_id']);
        }
        return $comment_list;
    }
    
    /**
     * 获取评轮回复信息
     */
    public function reply_content($parent_id)
    {
        $sql = "SELECT comment_id,email,user_name,content,add_time FROM ". $this->pre ."comment WHERE ".
               " parent_id = '".$parent_id."'";
        $replyt_list = $this->query($sql);
        foreach ($replyt_list as $k => $v) {
            $replyt_list[$k]['add_time'] = local_date(C('time_format'), $v['add_time']);
        }
        return $replyt_list;
    }
}
