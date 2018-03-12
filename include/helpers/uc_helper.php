<?php

/**
 * 通过判断is_feed 向UCenter提交Feed
 *
 * @access public
 * @param  integer $value_id  $order_id or $comment_id
 * @param  interger $feed_type BUY_GOODS or COMMENT_GOODS
 *
 * @return void
 */
function add_feed($id, $feed_type)
{
    $global = getInstance();
    $feed = array();
    if ($feed_type == BUY_GOODS) {
        if (empty($id)) {
            return;
        }
        $id = intval($id);
        $order_res = $global->db->getAll("SELECT g.goods_id, g.goods_name, g.goods_sn, g.goods_desc, g.goods_thumb, o.goods_price FROM " . $global->ecs->table('order_goods') . " AS o, " . $global->ecs->table('goods') . " AS g WHERE o.order_id='{$id}' AND o.goods_id=g.goods_id");
        foreach ($order_res as $goods_data) {
            if (!empty($goods_data['goods_thumb'])) {
                $url = $global->ecs->url() . $goods_data['goods_thumb'];
            } else {
                $url = $global->ecs->url() . C('no_picture');
            }
            $link = $global->ecs->url() . "goods.php?id=" . $goods_data["goods_id"];

            $feed['icon'] = "goods";
            $feed['title_template'] = '<b>{username} ' . L('feed_user_buy') . ' {goods_name}</b>';
            $feed['title_data'] = array('username'=> $_SESSION['user_name'], 'goods_name'=> $goods_data['goods_name']);
            $feed['body_template'] = '{goods_name}  ' . L('feed_goods_price') . ':{goods_price}  ' . L('feed_goods_desc') . ':{goods_desc}';
            $feed['body_data'] = array('goods_name'=>$goods_data['goods_name'], 'goods_price'=>$goods_data['goods_price'], 'goods_desc'=>sub_str(strip_tags($goods_data['goods_desc']), 150, true));
            $feed['images'][] = array('url'=> $url,
                                      'link'=> $link);
            uc_call("uc_feed_add", array($feed['icon'], $_SESSION['user_id'], $_SESSION['user_name'], $feed['title_template'], $feed['title_data'], $feed ['body_template'], $feed['body_data'], '', '', $feed['images']));
        }
    }
    return;
}

/**
 * 获得商品tag所关联的其他应用的列表
 *
 * @param   array       $attr
 *
 * @return  void
 */
function get_linked_tags($tag_data)
{
    //取所有应用列表
    $app_list = uc_call("uc_app_ls");
    if ($app_list == '') {
        return '';
    }
    foreach ($app_list as $app_key => $app_data) {
        if ($app_data['appid'] == UC_APPID) {
            unset($app_list[$app_key]);
            continue;
        }
        $get_tag_array[$app_data['appid']] = '5';
        $app_array[$app_data['appid']]['name'] = $app_data['name'];
        $app_array[$app_data['appid']]['type'] = $app_data['type'];
        $app_array[$app_data['appid']]['url'] = $app_data['url'];
        $app_array[$app_data['appid']]['tagtemplates'] = $app_data['tagtemplates'];
    }

    $tag_rand_key = array_rand($tag_data);
    $get_tag_data = uc_call("uc_tag_get", array($tag_data[$tag_rand_key], $get_tag_array));
    foreach ($get_tag_data as $appid => $tag_data_array) {
        $templates = $app_array[$appid]['tagtemplates']['template'];
        if (!empty($templates) && !empty($tag_data_array['data'])) {
            foreach ($tag_data_array['data'] as $tag_data) {
                $show_data = $templates;
                foreach ($tag_data as $tag_key => $data) {
                    $show_data = str_replace('{' . $tag_key . '}', $data, $show_data);
                }
                $app_array[$appid]['data'][] = $show_data;
            }
        }
    }

    return $app_array;
}

/**
 * 兑换积分
 *
 * @param  integer $uid 用户ID
 * @param  integer $fromcredits 原积分
 * @param  integer $tocredits 目标积分
 * @param  integer $toappid 目标应用ID
 * @param  integer $netamount 积分数额
 *
 * @return boolean
 */
function exchange_points($uid, $fromcredits, $tocredits, $toappid, $netamount)
{
    $ucresult = uc_call('uc_credit_exchange_request', array($uid, $fromcredits, $tocredits, $toappid, $netamount));
    if (!$ucresult) {
        return false;
    } else {
        return true;
    }
}
