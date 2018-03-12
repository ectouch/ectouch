<?php

/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function insert_query_info()
{
    $global = getInstance();
    if ($global->db->queryTime == '') {
        $query_time = 0;
    } else {
        if (PHP_VERSION >= '5.0.0') {
            $query_time = number_format(microtime(true) - $global->db->queryTime, 6);
        } else {
            list($now_usec, $now_sec)     = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $global->db->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }

    /* 内存占用情况 */
    if (L('memory_info') && function_exists('memory_get_usage')) {
        $memory_usage = sprintf(L('memory_info'), memory_get_usage() / 1048576);
    } else {
        $memory_usage = '';
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? L('gzip_enabled') : L('gzip_disabled');

    $online_count = $global->db->getOne("SELECT COUNT(*) FROM " . $global->ecs->table('sessions'));

    /* 加入触发cron代码 */
    $cron_method = C('cron_method');
    $cron_method = empty($cron_method) ? '<img src="api/cron.php?t=' . gmtime() . '" alt="" style="width:0px;height:0px;" />' : '';

    return sprintf(L('query_info'), $global->db->queryCount, $query_time, $online_count) . $gzip_enabled . $memory_usage . $cron_method;
}

/**
 * 调用浏览历史
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
    $global = getInstance();
    $str = '';
    if (!empty($_COOKIE['ECS']['history'])) {
        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT goods_id, goods_name, goods_thumb, shop_price FROM ' . $global->ecs->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
        $query = $global->db->query($sql);
        $res = array();
        while ($row = $global->db->fetch_array($query)) {
            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
            $str.='<ul class="clearfix"><li class="goodsimg"><a href="'.$goods['url'].'" target="_blank"><img src="'.$goods['goods_thumb'].'" alt="'.$goods['goods_name'].'" class="B_blue" /></a></li><li><a href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'">'.$goods['short_name'].'</a><br />'.L('shop_price').'<font class="f1">'.$goods['shop_price'].'</font><br /></li></ul>';
        }
        $str .= '<ul id="clear_history"><a onclick="clear_history()">' . L('clear_history') . '</a></ul>';
    }
    return $str;
}

/**
 * 调用购物车信息
 *
 * @access  public
 * @return  string
 */
function insert_cart_info()
{
    $global = getInstance();
    $sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
           ' FROM ' . $global->ecs->table('cart') .
           " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $row = $global->db->GetRow($sql);

    if ($row) {
        $number = intval($row['number']);
        $amount = floatval($row['amount']);
    } else {
        $number = 0;
        $amount = 0;
    }

    $str = sprintf(L('cart_info'), $number, price_format($amount, false));

    return '<a href="flow.php" title="' . L('view_cart') . '">' . $str . '</a>';
}

/**
 * 调用指定的广告位的广告
 *
 * @access  public
 * @param   integer $id     广告位ID
 * @param   integer $num    广告数量
 * @return  string
 */
function insert_ads($arr)
{
    $global = getInstance();
    static $static_res = null;

    $time = gmtime();
    if (!empty($arr['num']) && $arr['num'] != 1) {
        $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, ' .
                    'p.ad_height, p.position_style, RAND() AS rnd ' .
                'FROM ' . $global->ecs->table('ad') . ' AS a '.
                'LEFT JOIN ' . $global->ecs->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
                "WHERE enabled = 1 AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' ".
                    "AND a.position_id = '" . $arr['id'] . "' " .
                'ORDER BY rnd LIMIT ' . $arr['num'];
        $res = $global->db->GetAll($sql);
    } else {
        if ($static_res[$arr['id']] === null) {
            $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, '.
                        'p.ad_height, p.position_style, RAND() AS rnd ' .
                    'FROM ' . $global->ecs->table('ad') . ' AS a '.
                    'LEFT JOIN ' . $global->ecs->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
                    "WHERE enabled = 1 AND a.position_id = '" . $arr['id'] .
                        "' AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' " .
                    'ORDER BY rnd LIMIT 1';
            $static_res[$arr['id']] = $global->db->GetAll($sql);
        }
        $res = $static_res[$arr['id']];
    }
    $ads = array();
    $position_style = '';

    foreach ($res as $row) {
        if ($row['position_id'] != $arr['id']) {
            continue;
        }
        $position_style = $row['position_style'];
        switch ($row['media_type']) {
            case 0: // 图片广告
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                        "data/attached/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'><img src='$src' width='" .$row['ad_width']. "' height='$row[ad_height]'
                border='0' /></a>";
                break;
            case 1: // Flash
                $src = (strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false) ?
                        "data/attached/afficheimg/$row[ad_code]" : $row['ad_code'];
                $ads[] = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" " .
                         "codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\"  " .
                           "width='$row[ad_width]' height='$row[ad_height]'>
                           <param name='movie' value='$src'>
                           <param name='quality' value='high'>
                           <embed src='$src' quality='high'
                           pluginspage='http://www.macromedia.com/go/getflashplayer'
                           type='application/x-shockwave-flash' width='$row[ad_width]'
                           height='$row[ad_height]'></embed>
                         </object>";
                break;
            case 2: // CODE
                $ads[] = $row['ad_code'];
                break;
            case 3: // TEXT
                $ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                target='_blank'>" .htmlspecialchars($row['ad_code']). '</a>';
                break;
        }
    }
    $position_style = 'str:' . $position_style;

    $need_cache = $global->tpl->caching;
    $global->tpl->caching = false;

    $global->tpl->assign('ads', $ads);
    $val = $global->tpl->fetch($position_style);

    $global->tpl->caching = $need_cache;

    return $val;
}

/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info()
{
    $global = getInstance();
    $need_cache = $global->tpl->caching;
    $global->tpl->caching = false;

    if ($_SESSION['user_id'] > 0) {
        $global->tpl->assign('user_info', get_user_info());
    } else {
        if (!empty($_COOKIE['ECS']['username'])) {
            $global->tpl->assign('ecs_username', stripslashes($_COOKIE['ECS']['username']));
        }
        $captcha = intval(C('captcha'));
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0) {
            $global->tpl->assign('enabled_captcha', 1);
            $global->tpl->assign('rand', mt_rand());
        }
    }
    $output = $global->tpl->fetch('library/member_info.lbi');

    $global->tpl->caching = $need_cache;

    return $output;
}

/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments($arr)
{
    $global = getInstance();
    $need_cache = $global->tpl->caching;
    $need_compile = $global->tpl->force_compile;

    $global->tpl->caching = false;
    $global->tpl->force_compile = true;

    /* 验证码相关设置 */
    if ((intval(C('captcha')) & CAPTCHA_COMMENT) && gd_version() > 0) {
        $global->tpl->assign('enabled_captcha', 1);
        $global->tpl->assign('rand', mt_rand());
    }
    $global->tpl->assign('username', stripslashes($_SESSION['user_name']));
    $global->tpl->assign('email', $_SESSION['email']);
    $global->tpl->assign('comment_type', $arr['type']);
    $global->tpl->assign('id', $arr['id']);
    $cmt = assign_comment($arr['id'], $arr['type']);
    $global->tpl->assign('comments', $cmt['comments']);
    $global->tpl->assign('pager', $cmt['pager']);


    $val = $global->tpl->fetch('library/comments_list.lbi');

    $global->tpl->caching = $need_cache;
    $global->tpl->force_compile = $need_compile;

    return $val;
}


/**
 * 调用商品购买记录
 *
 * @access  public
 * @return  string
 */
function insert_bought_notes($arr)
{
    $global = getInstance();
    $need_cache = $global->tpl->caching;
    $need_compile = $global->tpl->force_compile;

    $global->tpl->caching = false;
    $global->tpl->force_compile = true;

    /* 商品购买记录 */
    $sql = 'SELECT u.user_name, og.goods_number, oi.add_time, IF(oi.order_status IN (2, 3, 4), 0, 1) AS order_status ' .
           'FROM ' . $global->ecs->table('order_info') . ' AS oi LEFT JOIN ' . $global->ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $global->ecs->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'] . ' ORDER BY oi.add_time DESC LIMIT 5';
    $bought_notes = $global->db->getAll($sql);

    foreach ($bought_notes as $key => $val) {
        $bought_notes[$key]['add_time'] = local_date("Y-m-d G:i:s", $val['add_time']);
    }

    $sql = 'SELECT count(*) ' .
           'FROM ' . $global->ecs->table('order_info') . ' AS oi LEFT JOIN ' . $global->ecs->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $global->ecs->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'];
    $count = $global->db->getOne($sql);


    /* 商品购买记录分页样式 */
    $pager = array();
    $pager['page']         = $page = 1;
    $pager['size']         = $size = 5;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;
    ;
    $pager['page_first']   = "javascript:gotoBuyPage(1,$arr[id])";
    $pager['page_prev']    = $page > 1 ? "javascript:gotoBuyPage(" .($page-1). ",$arr[id])" : 'javascript:;';
    $pager['page_next']    = $page < $page_count ? 'javascript:gotoBuyPage(' .($page + 1) . ",$arr[id])" : 'javascript:;';
    $pager['page_last']    = $page < $page_count ? 'javascript:gotoBuyPage(' .$page_count. ",$arr[id])"  : 'javascript:;';

    $global->tpl->assign('notes', $bought_notes);
    $global->tpl->assign('pager', $pager);


    $val= $global->tpl->fetch('library/bought_notes.lbi');

    $global->tpl->caching = $need_cache;
    $global->tpl->force_compile = $need_compile;

    return $val;
}


/**
 * 调用在线调查信息
 *
 * @access  public
 * @return  string
 */
function insert_vote()
{
    $global = getInstance();
    $vote = get_vote();
    if (!empty($vote)) {
        $global->tpl->assign('vote_id', $vote['id']);
        $global->tpl->assign('vote', $vote['content']);
    }
    $val = $global->tpl->fetch('library/vote.lbi');

    return $val;
}
