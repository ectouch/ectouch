<?php

// +----------------------------------------------------------------------
// | ECTouch Project [ 商创网络科技 专注移动电商 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/*
 * ******************************************************** 管理中心公用函数库 ********************************************************
 */

/**
 * 获得所有模块的名称以及链接地址
 *
 * @access public
 * @param string $directory
 *            插件存放的目录
 * @return array
 */
function read_modules($directory = '.')
{
    $dir = @opendir($directory);
    $set_modules = true;
    $modules = array();
    
    while (false !== ($file = @readdir($dir))) {
        if (preg_match("/^.*?\.php$/", $file)) {
            include_once ($directory . '/' . $file);
        }
    }
    @closedir($dir);
    unset($set_modules);
    
    foreach ($modules as $key => $value) {
        ksort($modules[$key]);
    }
    ksort($modules);
    
    return $modules;
}

/**
 * 系统提示信息
 *
 * @access public
 * @param
 *            string msg_detail 消息内容
 * @param
 *            int msg_type 消息类型， 0消息，1错误，2询问
 * @param
 *            array links 可选的链接
 * @param boolen $auto_redirect
 *            是否需要自动跳转
 * @return void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true)
{
    if (count($links) == 0) {
        $links[0]['text'] = L('go_back');
        $links[0]['href'] = 'javascript:history.go(-1)';
    }
    
    $this->assign('ur_here', L('system_message'));
    $this->assign('msg_detail', $msg_detail);
    $this->assign('msg_type', $msg_type);
    $this->assign('links', $links);
    $this->assign('default_url', $links[0]['href']);
    $this->assign('auto_redirect', $auto_redirect);
    
    $this->display('message.htm');
    
    exit();
}

/**
 * 将通过表单提交过来的年月日变量合成为"2004-05-10"的格式。
 * 此函数适用于通过smarty函数html_select_date生成的下拉日期。
 *
 * @param string $prefix
 *            年月日变量的共同的前缀。
 * @return date 日期变量。
 */
function sys_joindate($prefix)
{
    /* 返回年-月-日的日期格式 */
    $year = empty($_POST[$prefix . 'Year']) ? '0' : $_POST[$prefix . 'Year'];
    $month = empty($_POST[$prefix . 'Month']) ? '0' : $_POST[$prefix . 'Month'];
    $day = empty($_POST[$prefix . 'Day']) ? '0' : $_POST[$prefix . 'Day'];
    
    return $year . '-' . $month . '-' . $day;
}

/**
 * 设置管理员的session内容
 *
 * @access public
 * @param integer $user_id
 *            管理员编号
 * @param string $username
 *            管理员姓名
 * @param string $action_list
 *            权限列表
 * @param string $last_time
 *            最后登录时间
 * @return void
 */
function set_admin_session($user_id, $username, $action_list, $last_time)
{
    $_SESSION['admin_id'] = $user_id;
    $_SESSION['admin_name'] = $username;
    $_SESSION['action_list'] = $action_list;
    $_SESSION['last_check'] = $last_time; // 用于保存最后一次检查订单的时间
}

/**
 * 判断管理员对某一个操作是否有权限。
 * 根据当前对应的action_code，然后再和用户session里面的action_list做匹配，以此来决定是否可以继续执行。
 *
 * @param string $priv_str
 *            操作对应的priv_str
 * @param string $msg_type
 *            返回的类型
 * @return true/false
 */
function admin_priv($priv_str, $msg_type = '', $msg_output = true)
{
    if ($_SESSION['action_list'] == 'all') {
        return true;
    }
    
    if (strpos(',' . $_SESSION['action_list'] . ',', ',' . $priv_str . ',') === false) {
        $link[] = array(
            'text' => L('go_back'),
            'href' => 'javascript:history.back(-1)'
        );
        if ($msg_output) {
            sys_msg(L('priv_error'), 0, $link);
        }
        return false;
    } else {
        return true;
    }
}

/**
 * 检查管理员权限
 *
 * @access public
 * @param string $authz            
 * @return boolean
 */
function check_authz($authz)
{
    return (preg_match('/,*' . $authz . ',*/', $_SESSION['action_list']) || $_SESSION['action_list'] == 'all');
}

/**
 * 检查管理员权限，返回JSON格式数剧
 *
 * @access public
 * @param string $authz            
 * @return void
 */
function check_authz_json($authz)
{
    if (! check_authz($authz)) {
        make_json_error(L('priv_error'));
    }
}

/**
 * 取得红包类型数组（用于生成下拉列表）
 *
 * @return array 分类数组 bonus_typeid => bonus_type_name
 */
function get_bonus_type()
{
    $bonus = array();
    $sql = 'SELECT type_id, type_name, type_money FROM ' . M()->pre . 'bonus_type ' . ' WHERE send_type = 3';
    $res = M()->query($sql);
    foreach ($res as $key => $row) {
        $bonus[$row['type_id']] = $row['type_name'] . ' [' . sprintf(C('currency_format'), $row['type_money']) . ']';
    }
    
    return $bonus;
}

/**
 * 取得用户等级数组,按用户级别排序
 *
 * @param bool $is_special
 *            是否只显示特殊会员组
 * @return array rank_id=>rank_name
 */
function get_rank_list($is_special = false)
{
    $rank_list = array();
    $sql = 'SELECT rank_id, rank_name, min_points FROM ' . M()->pre . 'user_rank ';
    if ($is_special) {
        $sql .= ' WHERE special_rank = 1';
    }
    $sql .= ' ORDER BY min_points';
    
    $res = M()->query($sql);
    
    foreach ($res as $key => $row) {
        $rank_list[$row['rank_id']] = $row['rank_name'];
    }
    
    return $rank_list;
}

/**
 * 按等级取得用户列表（用于生成下拉列表）
 *
 * @return array 分类数组 user_id => user_name
 */
function get_user_rank($rankid, $where)
{
    $user_list = array();
    $sql = 'SELECT user_id, user_name FROM ' . M()->pre . 'users ' . $where . ' ORDER BY user_id DESC';
    $res = M()->query($sql);
    
    foreach ($res as $key => $row) {
        $user_list[$row['user_id']] = $row['user_name'];
    }
    
    return $user_list;
}

/**
 * 取得广告位置数组（用于生成下拉列表）
 *
 * @return array 分类数组 position_id => position_name
 */
function get_position_list()
{
    $position_list = array();
    $sql = 'SELECT position_id, position_name, ad_width, ad_height ' . 'FROM ' . M()->pre . 'ad_position ';
    $res = M()->query($sql);
    
    foreach ($res as $key => $row) {
        $position_list[$row['position_id']] = addslashes($row['position_name']) . ' [' . $row['ad_width'] . 'x' . $row['ad_height'] . ']';
    }
    
    return $position_list;
}

/**
 * 生成编辑器
 *
 * @param
 *            string input_name 输入框名称
 * @param
 *            string input_value 输入框值
 */
function create_html_editor($input_name, $input_value = '')
{
    global $smarty;
    
    $editor = new FCKeditor($input_name);
    $editor->BasePath = '../includes/fckeditor/';
    $editor->ToolbarSet = 'Normal';
    $editor->Width = '100%';
    $editor->Height = '320';
    $editor->Value = $input_value;
    $FCKeditor = $editor->CreateHtml();
    $smarty->assign('FCKeditor', $FCKeditor);
}

/**
 * 取得商品列表：用于把商品添加到组合、关联类、赠品类
 *
 * @param object $filters
 *            过滤条件
 */
function get_goods_list($filter)
{
    $filter->keyword = json_str_iconv($filter->keyword);
    $where = get_where_sql($filter); // 取得过滤条件
    
    /* 取得数据 */
    $sql = 'SELECT goods_id, goods_name, shop_price ' . 'FROM ' . M()->pre . 'goods ' . ' AS g ' . $where . 'LIMIT 50';
    $row = M()->query($sql);
    
    return $row;
}

/**
 * 取得文章列表：用于商品关联文章
 *
 * @param object $filters
 *            过滤条件
 */
function get_article_list($filter)
{
    /* 创建数据容器对象 */
    $ol = new OptionList();
    
    /* 取得过滤条件 */
    $where = ' WHERE a.cat_id = c.cat_id AND c.cat_type = 1 ';
    $where .= isset($filter->title) ? " AND a.title LIKE '%" . mysql_like_quote($filter->title) . "%'" : '';
    
    /* 取得数据 */
    $sql = 'SELECT a.article_id, a.title ' . 'FROM ' . M()->pre . 'article ' . ' AS a, ' . M()->pre . 'article_cat ' . ' AS c ' . $where;
    $res = M()->query($sql);
    
    foreach ($res as $key => $row) {
        $ol->add_option($row['article_id'], $row['title']);
    }
    
    /* 生成列表 */
    $ol->build_select();
}

/**
 * 返回是否
 *
 * @param int $var
 *            变量 1, 0
 */
function get_yes_no($var)
{
    return empty($var) ? '<img src="images/no.gif" border="0" />' : '<img src="images/yes.gif" border="0" />';
}

/**
 * 生成过滤条件：用于 get_goodslist 和 get_goods_list
 *
 * @param object $filter            
 * @return string
 */
function get_where_sql($filter)
{
    $time = date('Y-m-d');
    
    $where = isset($filter->is_delete) && $filter->is_delete == '1' ? ' WHERE is_delete = 1 ' : ' WHERE is_delete = 0 ';
    $where .= (isset($filter->real_goods) && ($filter->real_goods > - 1)) ? ' AND is_real = ' . intval($filter->real_goods) : '';
    $where .= isset($filter->cat_id) && $filter->cat_id > 0 ? ' AND ' . get_children($filter->cat_id) : '';
    $where .= isset($filter->brand_id) && $filter->brand_id > 0 ? " AND brand_id = '" . $filter->brand_id . "'" : '';
    $where .= isset($filter->intro_type) && $filter->intro_type != '0' ? ' AND ' . $filter->intro_type . " = '1'" : '';
    $where .= isset($filter->intro_type) && $filter->intro_type == 'is_promote' ? " AND promote_start_date <= '$time' AND promote_end_date >= '$time' " : '';
    $where .= isset($filter->keyword) && trim($filter->keyword) != '' ? " AND (goods_name LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_sn LIKE '%" . mysql_like_quote($filter->keyword) . "%' OR goods_id LIKE '%" . mysql_like_quote($filter->keyword) . "%') " : '';
    $where .= isset($filter->suppliers_id) && trim($filter->suppliers_id) != '' ? " AND (suppliers_id = '" . $filter->suppliers_id . "') " : '';
    
    $where .= isset($filter->in_ids) ? ' AND goods_id ' . db_create_in($filter->in_ids) : '';
    $where .= isset($filter->exclude) ? ' AND goods_id NOT ' . db_create_in($filter->exclude) : '';
    $where .= isset($filter->stock_warning) ? ' AND goods_number <= warn_number' : '';
    
    return $where;
}

/**
 * 取得图表颜色
 *
 * @access public
 * @param integer $n
 *            颜色顺序
 * @return void
 */
function chart_color($n)
{
    /* 随机显示颜色代码 */
    $arr = array(
        '33FF66',
        'FF6600',
        '3399FF',
        '009966',
        'CC3399',
        'FFCC33',
        '6699CC',
        'CC3366',
        '33FF66',
        'FF6600',
        '3399FF'
    );
    
    if ($n > 8) {
        $n = $n % 8;
    }
    
    return $arr[$n];
}

/**
 * 获得商品类型的列表
 *
 * @access public
 * @param integer $selected
 *            选定的类型编号
 * @return string
 */
function goods_type_list($selected)
{
    $sql = 'SELECT cat_id, cat_name FROM ' . M()->pre . 'goods_type ' . ' WHERE enabled = 1';
    $res = M()->query($sql);
    
    $lst = '';
    foreach ($res as $key => $row) {
        $lst .= "<option value='$row[cat_id]'";
        $lst .= ($selected == $row['cat_id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['cat_name']) . '</option>';
    }
    
    return $lst;
}

/**
 * 取得货到付款和非货到付款的支付方式
 *
 * @return array('is_cod' => '', 'is_not_cod' => '')
 */
function get_pay_ids()
{
    $ids = array(
        'is_cod' => '0',
        'is_not_cod' => '0'
    );
    $sql = 'SELECT pay_id, is_cod FROM ' . M()->pre . 'payment ' . ' WHERE enabled = 1';
    $res = M()->query($sql);
    
    foreach ($res as $key => $row) {
        if ($row['is_cod']) {
            $ids['is_cod'] .= ',' . $row['pay_id'];
        } else {
            $ids['is_not_cod'] .= ',' . $row['pay_id'];
        }
    }
    
    return $ids;
}

/**
 * 清空表数据
 *
 * @param string $table_name
 *            表名称
 */
function truncate_table($table_name)
{
    $sql = 'TRUNCATE TABLE ' . M()->pre . $table_name;
    
    return M()->query($sql);
}

/**
 * 返回字符集列表数组
 *
 * @access public
 * @param            
 *
 * @return void
 */
function get_charset_list()
{
    return array(
        'UTF8' => 'UTF-8',
        'GB2312' => 'GB2312/GBK',
        'BIG5' => 'BIG5'
    );
}

/**
 * 创建一个JSON格式的数据
 *
 * @access public
 * @param string $content            
 * @param integer $error            
 * @param string $message            
 * @param array $append            
 * @return void
 */
function make_json_response($content = '', $error = "0", $message = '', $append = array())
{
    $res = array(
        'error' => $error,
        'message' => $message,
        'content' => $content
    );
    
    if (! empty($append)) {
        foreach ($append as $key => $val) {
            $res[$key] = $val;
        }
    }
    
    $val = json_encode($res);
    
    exit($val);
}

/**
 *
 * @access public
 * @param            
 *
 * @return void
 */
function make_json_result($content, $message = '', $append = array())
{
    make_json_response($content, 0, $message, $append);
}

/**
 * 创建一个JSON格式的错误信息
 *
 * @access public
 * @param string $msg            
 * @return void
 */
function make_json_error($msg)
{
    make_json_response('', 1, $msg);
}

/**
 * 根据过滤条件获得排序的标记
 *
 * @access public
 * @param array $filter            
 * @return array
 */
function sort_flag($filter)
{
    $flag['tag'] = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['img'] = '<img src="images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';
    
    return $flag;
}

/**
 * 分页的信息加入条件的数组
 *
 * @access public
 * @return array
 */
function page_and_size($filter)
{
    if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
        $filter['page_size'] = intval($_REQUEST['page_size']);
    } elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0) {
        $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
    } else {
        $filter['page_size'] = 15;
    }
    
    /* 每页显示 */
    $filter['page'] = (empty($_REQUEST['page']) || intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);
    
    /* page 总数 */
    $filter['page_count'] = (! empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;
    
    /* 边界处理 */
    if ($filter['page'] > $filter['page_count']) {
        $filter['page'] = $filter['page_count'];
    }
    
    $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];
    
    return $filter;
}

/**
 * 将含有单位的数字转成字节
 *
 * @access public
 * @param string $val
 *            带单位的数字
 *            
 * @return int $val
 */
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{strlen($val) - 1});
    switch ($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    
    return $val;
}

/**
 * 获得指定的商品类型下所有的属性分组
 *
 * @param integer $cat_id
 *            商品类型ID
 *            
 * @return array
 */
function get_attr_groups($cat_id)
{
    $grp = str_replace("\r", '', M()->table('goods_type')
        ->field('attr_group')
        ->where("cat_id='$cat_id'")
        ->getOne());
    if ($grp) {
        return explode("\n", $grp);
    } else {
        return array();
    }
}

/**
 * 生成链接后缀
 */
function list_link_postfix()
{
    return 'uselastfilter=1';
}

/**
 * 保存过滤条件
 *
 * @param array $filter
 *            过滤条件
 * @param string $sql
 *            查询语句
 * @param string $param_str
 *            参数字符串，由list函数的参数组成
 */
function set_filter($filter, $sql, $param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }
    setcookie('ECSCP[lastfilterfile]', sprintf('%X', crc32($filterfile)), time() + 600);
    setcookie('ECSCP[lastfilter]', urlencode(serialize($filter)), time() + 600);
    setcookie('ECSCP[lastfiltersql]', base64_encode($sql), time() + 600);
}

/**
 * 取得上次的过滤条件
 *
 * @param string $param_str
 *            参数字符串，由list函数的参数组成
 * @return 如果有，返回array('filter' => $filter, 'sql' => $sql)；否则返回false
 */
function get_filter($param_str = '')
{
    $filterfile = basename(PHP_SELF, '.php');
    if ($param_str) {
        $filterfile .= $param_str;
    }
    if (isset($_GET['uselastfilter']) && isset($_COOKIE['ECSCP']['lastfilterfile']) && $_COOKIE['ECSCP']['lastfilterfile'] == sprintf('%X', crc32($filterfile))) {
        return array(
            'filter' => unserialize(urldecode($_COOKIE['ECSCP']['lastfilter'])),
            'sql' => base64_decode($_COOKIE['ECSCP']['lastfiltersql'])
        );
    } else {
        return false;
    }
}

/**
 * URL过滤
 *
 * @param string $url
 *            参数字符串，一个urld地址,对url地址进行校正
 * @return 返回校正过的url;
 */
function sanitize_url($url, $check = 'http://')
{
    if (strpos($url, $check) === false) {
        $url = $check . $url;
    }
    return $url;
}

/**
 * 检查分类是否已经存在
 *
 * @param string $cat_name
 *            分类名称
 * @param integer $parent_cat
 *            上级分类
 * @param integer $exclude
 *            排除的分类ID
 *            
 * @return boolean
 */
function cat_exists($cat_name, $parent_cat, $exclude = 0)
{
    return (M()->table('category')
        ->field('COUNT(*)')
        ->where("parent_id = '$parent_cat' AND cat_name = '$cat_name' AND cat_id<>'$exclude'")
        ->getOne() > 0) ? true : false;
}

function brand_exists($brand_name)
{
    return (M()->table('brand')
        ->field('COUNT(*)')
        ->where(" brand_name = '" . $brand_name . "'")
        ->getOne() > 0) ? true : false;
}

/**
 * 获取当前管理员信息
 *
 * @access public
 * @param            
 *
 * @return Array
 */
function admin_info()
{
    $sql = "SELECT * FROM " . M()->pre . 'admin_user ' . "
            WHERE user_id = '$_SESSION[admin_id]'
            LIMIT 0, 1";
    $admin_info = M()->getRow($sql);
    
    if (empty($admin_info)) {
        return $admin_info = array();
    }
    
    return $admin_info;
}

/**
 * 供货商列表信息
 *
 * @param string $conditions            
 * @return array
 */
function suppliers_list_info($conditions = '')
{
    $where = '';
    if (! empty($conditions)) {
        $where .= 'WHERE ';
        $where .= $conditions;
    }
    
    /* 查询 */
    $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc
            FROM " . M()->pre . "suppliers " . "
            $where";
    
    return M()->query($sql);
}

/**
 * 供货商名
 *
 * @return array
 */
function suppliers_list_name()
{
    /* 查询 */
    $suppliers_list = suppliers_list_info(' is_check = 1 ');
    
    /* 供货商名字 */
    $suppliers_name = array();
    if (count($suppliers_list) > 0) {
        foreach ($suppliers_list as $suppliers) {
            $suppliers_name[$suppliers['suppliers_id']] = $suppliers['suppliers_name'];
        }
    }
    
    return $suppliers_name;
}

/*
 * ******************************************************** 管理中心商品相关函数 ********************************************************
 */

/**
 * 取得推荐类型列表
 *
 * @return array 推荐类型列表
 */
function get_intro_list()
{
    return array(
        'is_best' => L('is_best'),
        'is_new' => L('is_new'),
        'is_hot' => L('is_hot'),
        'is_promote' => L('is_promote'),
        'all_type' => L('all_type')
    );
}

/**
 * 取得重量单位列表
 *
 * @return array 重量单位列表
 */
function get_unit_list()
{
    return array(
        '1' => L('unit_kg'),
        '0.001' => L('unit_g')
    );
}

/**
 * 取得会员等级列表
 *
 * @return array 会员等级列表
 */
function get_user_rank_list()
{
    $sql = "SELECT * FROM " . M()->pre . 'user_rank ' . " ORDER BY min_points";
    
    return M()->query($sql);
}

/**
 * 取得某商品的会员价格列表
 *
 * @param int $goods_id
 *            商品编号
 * @return array 会员价格列表 user_rank => user_price
 */
function get_member_price_list($goods_id)
{
    /* 取得会员价格 */
    $price_list = array();
    $sql = "SELECT user_rank, user_price FROM " . M()->pre . 'member_price ' . " WHERE goods_id = '$goods_id'";
    $res = M()->query($sql);
    foreach ($res as $key => $row) {
        $price_list[$row['user_rank']] = $row['user_price'];
    }
    
    return $price_list;
}

/**
 * 插入或更新商品属性
 *
 * @param int $goods_id
 *            商品编号
 * @param array $id_list
 *            属性编号数组
 * @param array $is_spec_list
 *            是否规格数组 'true' | 'false'
 * @param array $value_price_list
 *            属性值数组
 * @return array 返回受到影响的goods_attr_id数组
 */
function handle_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list)
{
    $goods_attr_id = array();
    
    /* 循环处理每个属性 */
    foreach ($id_list as $key => $id) {
        $is_spec = $is_spec_list[$key];
        if ($is_spec == 'false') {
            $value = $value_price_list[$key];
            $price = '';
        } else {
            $value_list = array();
            $price_list = array();
            if ($value_price_list[$key]) {
                $vp_list = explode(chr(13), $value_price_list[$key]);
                foreach ($vp_list as $v_p) {
                    $arr = explode(chr(9), $v_p);
                    $value_list[] = $arr[0];
                    $price_list[] = $arr[1];
                }
            }
            $value = join(chr(13), $value_list);
            $price = join(chr(13), $price_list);
        }
        
        // 插入或更新记录
        $result_id = M()->table('goods_attr')
            ->field('goods_attr_id')
            ->where(" goods_id = '$goods_id' AND attr_id = '$id' AND attr_value = '$value' ")
            ->limit('1')
            ->getOne();
        if (! empty($result_id)) {
            $sql = "UPDATE " . M()->pre . 'goods_attr ' . "
                    SET attr_value = '$value'
                    WHERE goods_id = '$goods_id'
                    AND attr_id = '$id'
                    AND goods_attr_id = '$result_id'";
            
            $goods_attr_id[$id] = $result_id;
        } else {
            $sql = "INSERT INTO " . M()->pre . 'goods_attr ' . " (goods_id, attr_id, attr_value, attr_price) " . "VALUES ('$goods_id', '$id', '$value', '$price')";
        }
        
        M()->query($sql);
        
        if ($goods_attr_id[$id] == '') {
            $goods_attr_id[$id] = M()->insert_id();
        }
    }
    
    return $goods_attr_id;
}

/**
 * 保存某商品的会员价格
 *
 * @param int $goods_id
 *            商品编号
 * @param array $rank_list
 *            等级列表
 * @param array $price_list
 *            价格列表
 * @return void
 */
function handle_member_price($goods_id, $rank_list, $price_list)
{
    /* 循环处理每个会员等级 */
    foreach ($rank_list as $key => $rank) {
        /* 会员等级对应的价格 */
        $price = $price_list[$key];
        
        // 插入或更新记录
        if (M()->table('member_price')
            ->field('COUNT(*)')
            ->where(" goods_id = '$goods_id' AND user_rank = '$rank'")
            ->getOne() > 0) {
            /* 如果会员价格是小于0则删除原来价格，不是则更新为新的价格 */
            if ($price < 0) {
                $sql = "DELETE FROM " . M()->pre . 'member_price ' . " WHERE goods_id = '$goods_id' AND user_rank = '$rank' LIMIT 1";
            } else {
                $sql = "UPDATE " . M()->pre . 'member_price ' . " SET user_price = '$price' " . "WHERE goods_id = '$goods_id' " . "AND user_rank = '$rank' LIMIT 1";
            }
        } else {
            if ($price == - 1) {
                $sql = '';
            } else {
                $sql = "INSERT INTO " . M()->pre . 'member_price ' . " (goods_id, user_rank, user_price) " . "VALUES ('$goods_id', '$rank', '$price')";
            }
        }
        
        if ($sql) {
            M()->query($sql);
        }
    }
}

/**
 * 保存某商品的扩展分类
 *
 * @param int $goods_id
 *            商品编号
 * @param array $cat_list
 *            分类编号数组
 * @return void
 */
function handle_other_cat($goods_id, $cat_list)
{
    /* 查询现有的扩展分类 */
    $sql = "SELECT cat_id FROM " . M()->pre . 'goods_cat ' . " WHERE goods_id = '$goods_id'";
    $exist_list = M()->getCol($sql);
    
    /* 删除不再有的分类 */
    $delete_list = array_diff($exist_list, $cat_list);
    if ($delete_list) {
        $sql = "DELETE FROM " . M()->pre . 'goods_cat ' . " WHERE goods_id = '$goods_id' " . "AND cat_id " . db_create_in($delete_list);
        M()->query($sql);
    }
    
    /* 添加新加的分类 */
    $add_list = array_diff($cat_list, $exist_list, array(
        0
    ));
    foreach ($add_list as $cat_id) {
        // 插入记录
        $sql = "INSERT INTO " . M()->pre . 'goods_cat ' . " (goods_id, cat_id) " . "VALUES ('$goods_id', '$cat_id')";
        M()->query($sql);
    }
}

/**
 * 保存某商品的关联商品
 *
 * @param int $goods_id            
 * @return void
 */
function handle_link_goods($goods_id)
{
    $sql = "UPDATE " . M()->pre . 'link_goods ' . " SET " . " goods_id = '$goods_id' " . " WHERE goods_id = '0'" . " AND admin_id = '$_SESSION[admin_id]'";
    M()->query($sql);
    
    $sql = "UPDATE " . M()->pre . 'link_goods ' . " SET " . " link_goods_id = '$goods_id' " . " WHERE link_goods_id = '0'" . " AND admin_id = '$_SESSION[admin_id]'";
    M()->query($sql);
}

/**
 * 保存某商品的配件
 *
 * @param int $goods_id            
 * @return void
 */
function handle_group_goods($goods_id)
{
    $sql = "UPDATE " . M()->pre . 'group_goods ' . " SET " . " parent_id = '$goods_id' " . " WHERE parent_id = '0'" . " AND admin_id = '$_SESSION[admin_id]'";
    M()->query($sql);
}

/**
 * 保存某商品的关联文章
 *
 * @param int $goods_id            
 * @return void
 */
function handle_goods_article($goods_id)
{
    $sql = "UPDATE " . M()->pre . 'goods_article ' . " SET " . " goods_id = '$goods_id' " . " WHERE goods_id = '0'" . " AND admin_id = '$_SESSION[admin_id]'";
    M()->query($sql);
}

/**
 * 保存某商品的相册图片
 *
 * @param int $goods_id            
 * @param array $image_files            
 * @param array $image_descs            
 * @return void
 */
function handle_gallery_image($goods_id, $image_files, $image_descs, $image_urls)
{
    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) ? false : true;
    foreach ($image_descs as $key => $img_desc) {
        /* 是否成功上传 */
        $flag = false;
        if (isset($image_files['error'])) {
            if ($image_files['error'][$key] == 0) {
                $flag = true;
            }
        } else {
            if ($image_files['tmp_name'][$key] != 'none') {
                $flag = true;
            }
        }
        
        if ($flag) {
            // 生成缩略图
            if ($proc_thumb) {
                $thumb_url = $GLOBALS['image']->make_thumb($image_files['tmp_name'][$key], C('thumb_width'), C('thumb_height'));
                $thumb_url = is_string($thumb_url) ? $thumb_url : '';
            }
            
            $upload = array(
                'name' => $image_files['name'][$key],
                'type' => $image_files['type'][$key],
                'tmp_name' => $image_files['tmp_name'][$key],
                'size' => $image_files['size'][$key]
            );
            if (isset($image_files['error'])) {
                $upload['error'] = $image_files['error'][$key];
            }
            $img_original = $GLOBALS['image']->upload_image($upload);
            if ($img_original === false) {
                sys_msg($GLOBALS['image']->error_msg(), 1, array(), false);
            }
            $img_url = $img_original;
            
            if (! $proc_thumb) {
                $thumb_url = $img_original;
            }
            // 如果服务器支持GD 则添加水印
            if ($proc_thumb && gd_version() > 0) {
                $pos = strpos(basename($img_original), '.');
                $newname = dirname($img_original) . '/' . $GLOBALS['image']->random_filename() . substr(basename($img_original), $pos);
                copy('../' . $img_original, '../' . $newname);
                $img_url = $newname;
                
                $GLOBALS['image']->add_watermark('../' . $img_url, '', C('watermark'), C('watermark_place'), C('watermark_alpha'));
            }
            
            /* 重新格式化图片名称 */
            $img_original = reformat_image_name('gallery', $goods_id, $img_original, 'source');
            $img_url = reformat_image_name('gallery', $goods_id, $img_url, 'goods');
            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
            $sql = "INSERT INTO " . M()->pre . 'goods_gallery ' . " (goods_id, img_url, img_desc, thumb_url, img_original) " . "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
            M()->query($sql);
            /* 不保留商品原图的时候删除原图 */
            if ($proc_thumb && ! C('retain_original_img') && ! empty($img_original)) {
                M()->query("UPDATE " . M()->pre . 'goods_gallery ' . " SET img_original='' WHERE `goods_id`='{$goods_id}'");
                @unlink('../' . $img_original);
            }
        } elseif (! empty($image_urls[$key]) && ($image_urls[$key] != L('img_file')) && ($image_urls[$key] != 'http://') && copy(trim($image_urls[$key]), ROOT_PATH . 'temp/' . basename($image_urls[$key]))) {
            $image_url = trim($image_urls[$key]);
            
            // 定义原图路径
            $down_img = ROOT_PATH . 'temp/' . basename($image_url);
            
            // 生成缩略图
            if ($proc_thumb) {
                $thumb_url = $GLOBALS['image']->make_thumb($down_img, C('thumb_width'), C('thumb_height'));
                $thumb_url = is_string($thumb_url) ? $thumb_url : '';
                $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
            }
            
            if (! $proc_thumb) {
                $thumb_url = htmlspecialchars($image_url);
            }
            
            /* 重新格式化图片名称 */
            $img_url = $img_original = htmlspecialchars($image_url);
            $sql = "INSERT INTO " . M()->pre . 'goods_gallery ' . " (goods_id, img_url, img_desc, thumb_url, img_original) " . "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
            M()->query($sql);
            
            @unlink($down_img);
        }
    }
}

/**
 * 修改商品某字段值
 *
 * @param string $goods_id
 *            商品编号，可以为多个，用 ',' 隔开
 * @param string $field
 *            字段名
 * @param string $value
 *            字段值
 * @return bool
 */
function update_goods($goods_id, $field, $value)
{
    if ($goods_id) {
        /* 清除缓存 */
        clear_cache_files();
        
        $sql = "UPDATE " . M()->pre . 'goods ' . " SET $field = '$value' , last_update = '" . gmtime() . "' " . "WHERE goods_id " . db_create_in($goods_id);
        return M()->query($sql);
    } else {
        return false;
    }
}

/**
 * 从回收站删除多个商品
 *
 * @param mix $goods_id
 *            商品id列表：可以逗号格开，也可以是数组
 * @return void
 */
function delete_goods($goods_id)
{
    if (empty($goods_id)) {
        return;
    }
    
    /* 取得有效商品id */
    $sql = "SELECT DISTINCT goods_id FROM " . M()->pre . 'goods ' . " WHERE goods_id " . db_create_in($goods_id) . " AND is_delete = 1";
    $goods_id = M()->getCol($sql);
    if (empty($goods_id)) {
        return;
    }
    
    /* 删除商品图片和轮播图片文件 */
    $sql = "SELECT goods_thumb, goods_img, original_img " . "FROM " . M()->pre . 'goods ' . " WHERE goods_id " . db_create_in($goods_id);
    $res = M()->query($sql);
    while ($goods = M()->fetchRow($res)) {
        if (! empty($goods['goods_thumb'])) {
            @unlink('../' . $goods['goods_thumb']);
        }
        if (! empty($goods['goods_img'])) {
            @unlink('../' . $goods['goods_img']);
        }
        if (! empty($goods['original_img'])) {
            @unlink('../' . $goods['original_img']);
        }
    }
    
    /* 删除商品 */
    $sql = "DELETE FROM " . M()->pre . 'goods ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    
    /* 删除商品的货品记录 */
    $sql = "DELETE FROM " . M()->pre . 'products ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    
    /* 删除商品相册的图片文件 */
    $sql = "SELECT img_url, thumb_url, img_original " . "FROM " . M()->pre . 'goods_gallery ' . " WHERE goods_id " . db_create_in($goods_id);
    $res = M()->query($sql);
    foreach ($res as $key => $row) {
        if (! empty($row['img_url'])) {
            @unlink('../' . $row['img_url']);
        }
        if (! empty($row['thumb_url'])) {
            @unlink('../' . $row['thumb_url']);
        }
        if (! empty($row['img_original'])) {
            @unlink('../' . $row['img_original']);
        }
    }
    
    /* 删除商品相册 */
    $sql = "DELETE FROM " . M()->pre . 'goods_gallery ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    
    /* 删除相关表记录 */
    $sql = "DELETE FROM " . M()->pre . 'collect_goods ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'goods_article ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'goods_attr ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'goods_cat ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'member_price ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'group_goods ' . " WHERE parent_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'group_goods ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'link_goods ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'link_goods ' . " WHERE link_goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'tag ' . " WHERE goods_id " . db_create_in($goods_id);
    M()->query($sql);
    $sql = "DELETE FROM " . M()->pre . 'comment ' . " WHERE comment_type = 0 AND id_value " . db_create_in($goods_id);
    M()->query($sql);
    
    /* 删除相应虚拟商品记录 */
    $sql = "DELETE FROM " . M()->pre . 'virtual_card ' . " WHERE goods_id " . db_create_in($goods_id);
    if (! M()->query($sql, 'SILENT') && M()->errno() != 1146) {
        die(M()->error());
    }
    
    /* 清除缓存 */
    clear_cache_files();
}

/**
 * 为某商品生成唯一的货号
 *
 * @param int $goods_id
 *            商品编号
 * @return string 唯一的货号
 */
function generate_goods_sn($goods_id)
{
    $goods_sn = C('sn_prefix') . str_repeat('0', 6 - strlen($goods_id)) . $goods_id;
    
    $sql = "SELECT goods_sn FROM " . M()->pre . 'goods' . " WHERE goods_sn LIKE '" . mysql_like_quote($goods_sn) . "%' AND goods_id <> '$goods_id' " . " ORDER BY LENGTH(goods_sn) DESC";
    $sn_list = M()->getCol($sql);
    if (in_array($goods_sn, $sn_list)) {
        $max = pow(10, strlen($sn_list[0]) - strlen($goods_sn) + 1) - 1;
        $new_sn = $goods_sn . mt_rand(0, $max);
        while (in_array($new_sn, $sn_list)) {
            $new_sn = $goods_sn . mt_rand(0, $max);
        }
        $goods_sn = $new_sn;
    }
    
    return $goods_sn;
}

/**
 * 商品货号是否重复
 *
 * @param string $goods_sn
 *            商品货号；请在传入本参数前对本参数进行SQl脚本过滤
 * @param int $goods_id
 *            商品id；默认值为：0，没有商品id
 * @return bool true，重复；false，不重复
 */
function check_goods_sn_exist($goods_sn, $goods_id = 0)
{
    $goods_sn = trim($goods_sn);
    $goods_id = intval($goods_id);
    if (strlen($goods_sn) == 0) {
        return true; // 重复
    }
    
    if (empty($goods_id)) {
        $where = "goods_sn = '$goods_sn'";
    } else {
        $where = " goods_sn = '$goods_sn'
                AND goods_id <> '$goods_id'";
    }
    
    $res = M()->table('goods')
        ->field('goods_id')
        ->where($where)
        ->getOne();
    
    if (empty($res)) {
        return false; // 不重复
    } else {
        return true; // 重复
    }
}

/**
 * 取得通用属性和某分类的属性，以及某商品的属性值
 *
 * @param int $cat_id
 *            分类编号
 * @param int $goods_id
 *            商品编号
 * @return array 规格与属性列表
 */
function get_attr_list($cat_id, $goods_id = 0)
{
    if (empty($cat_id)) {
        return array();
    }
    
    // 查询属性值及商品的属性值
    $sql = "SELECT a.attr_id, a.attr_name, a.attr_input_type, a.attr_type, a.attr_values, v.attr_value, v.attr_price " . "FROM " . M()->pre . 'attribute' . " AS a " . "LEFT JOIN " . M()->pre . 'goods_attr' . " AS v " . "ON v.attr_id = a.attr_id AND v.goods_id = '$goods_id' " . "WHERE a.cat_id = " . intval($cat_id) . " OR a.cat_id = 0 " . "ORDER BY a.sort_order, a.attr_type, a.attr_id, v.attr_price, v.goods_attr_id";
    
    $row = M()->query($sql);
    
    return $row;
}

/**
 * 获取商品类型中包含规格的类型列表
 *
 * @access public
 * @return array
 */
function get_goods_type_specifications()
{
    // 查询
    $sql = "SELECT DISTINCT cat_id
            FROM " . M()->pre . 'attribute ' . "
            WHERE attr_type = 1";
    $row = M()->query($sql);
    
    $return_arr = array();
    if (! empty($row)) {
        foreach ($row as $value) {
            $return_arr[$value['cat_id']] = $value['cat_id'];
        }
    }
    return $return_arr;
}

/**
 * 根据属性数组创建属性的表单
 *
 * @access public
 * @param int $cat_id
 *            分类编号
 * @param int $goods_id
 *            商品编号
 * @return string
 */
function build_attr_html($cat_id, $goods_id = 0)
{
    $attr = get_attr_list($cat_id, $goods_id);
    $html = '<table width="100%" id="attrTable">';
    $spec = 0;
    
    foreach ($attr as $key => $val) {
        $html .= "<tr><td class='label'>";
        if ($val['attr_type'] == 1 || $val['attr_type'] == 2) {
            $html .= ($spec != $val['attr_id']) ? "<a href='javascript:;' onclick='addSpec(this)'>[+]</a>" : "<a href='javascript:;' onclick='removeSpec(this)'>[-]</a>";
            $spec = $val['attr_id'];
        }
        
        $html .= "$val[attr_name]</td><td><input type='hidden' name='attr_id_list[]' value='$val[attr_id]' />";
        
        if ($val['attr_input_type'] == 0) {
            $html .= '<input name="attr_value_list[]" type="text" value="' . htmlspecialchars($val['attr_value']) . '" size="40" /> ';
        } elseif ($val['attr_input_type'] == 2) {
            $html .= '<textarea name="attr_value_list[]" rows="3" cols="40">' . htmlspecialchars($val['attr_value']) . '</textarea>';
        } else {
            $html .= '<select name="attr_value_list[]">';
            $html .= '<option value="">' . L('select_please') . '</option>';
            
            $attr_values = explode("\n", $val['attr_values']);
            
            foreach ($attr_values as $opt) {
                $opt = trim(htmlspecialchars($opt));
                
                $html .= ($val['attr_value'] != $opt) ? '<option value="' . $opt . '">' . $opt . '</option>' : '<option value="' . $opt . '" selected="selected">' . $opt . '</option>';
            }
            $html .= '</select> ';
        }
        
        $html .= ($val['attr_type'] == 1 || $val['attr_type'] == 2) ? L('spec_price') . ' <input type="text" name="attr_price_list[]" value="' . $val['attr_price'] . '" size="5" maxlength="10" />' : ' <input type="hidden" name="attr_price_list[]" value="0" />';
        
        $html .= '</td></tr>';
    }
    
    $html .= '</table>';
    
    return $html;
}

/**
 * 获得指定商品相关的商品
 *
 * @access public
 * @param integer $goods_id            
 * @return array
 */
function get_linked_goods($goods_id)
{
    $sql = "SELECT lg.link_goods_id AS goods_id, g.goods_name, lg.is_double " . "FROM " . M()->pre . 'link_goods ' . " AS lg, " . M()->pre . 'goods ' . " AS g " . "WHERE lg.goods_id = '$goods_id' " . "AND lg.link_goods_id = g.goods_id ";
    if ($goods_id == 0) {
        $sql .= " AND lg.admin_id = '$_SESSION[admin_id]'";
    }
    $row = M()->query($sql);
    
    foreach ($row as $key => $val) {
        $linked_type = $val['is_double'] == 0 ? L('single') : L('double');
        
        $row[$key]['goods_name'] = $val['goods_name'] . " -- [$linked_type]";
        
        unset($row[$key]['is_double']);
    }
    
    return $row;
}

/**
 * 获得指定商品的配件
 *
 * @access public
 * @param integer $goods_id            
 * @return array
 */
function get_group_goods($goods_id)
{
    $sql = "SELECT gg.goods_id, CONCAT(g.goods_name, ' -- [', gg.goods_price, ']') AS goods_name " . "FROM " . M()->pre . 'group_goods ' . " AS gg, " . M()->pre . 'goods ' . " AS g " . "WHERE gg.parent_id = '$goods_id' " . "AND gg.goods_id = g.goods_id ";
    if ($goods_id == 0) {
        $sql .= " AND gg.admin_id = '$_SESSION[admin_id]'";
    }
    $row = M()->query($sql);
    
    return $row;
}

/**
 * 获得商品的关联文章
 *
 * @access public
 * @param integer $goods_id            
 * @return array
 */
function get_goods_articles($goods_id)
{
    $sql = "SELECT g.article_id, a.title " . "FROM " . M()->pre . 'goods_article ' . " AS g, " . M()->pre . 'article ' . " AS a " . "WHERE g.goods_id = '$goods_id' " . "AND g.article_id = a.article_id ";
    if ($goods_id == 0) {
        $sql .= " AND g.admin_id = '$_SESSION[admin_id]'";
    }
    $row = M()->query($sql);
    
    return $row;
}

/**
 * 获得商品列表
 *
 * @access public
 * @param
 *            s integer $isdelete
 * @param
 *            s integer $real_goods
 * @param
 *            s integer $conditions
 * @return array
 */
function goods_list($is_delete, $real_goods = 1, $conditions = '')
{
    /* 过滤条件 */
    $param_str = '-' . $is_delete . '-' . $real_goods;
    $result = get_filter($param_str);
    if ($result === false) {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        
        $filter['cat_id'] = empty($_REQUEST['cat_id']) ? 0 : intval($_REQUEST['cat_id']);
        $filter['intro_type'] = empty($_REQUEST['intro_type']) ? '' : trim($_REQUEST['intro_type']);
        $filter['is_promote'] = empty($_REQUEST['is_promote']) ? 0 : intval($_REQUEST['is_promote']);
        $filter['stock_warning'] = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);
        $filter['brand_id'] = empty($_REQUEST['brand_id']) ? 0 : intval($_REQUEST['brand_id']);
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['suppliers_id'] = isset($_REQUEST['suppliers_id']) ? (empty($_REQUEST['suppliers_id']) ? '' : trim($_REQUEST['suppliers_id'])) : '';
        $filter['is_on_sale'] = isset($_REQUEST['is_on_sale']) ? ((empty($_REQUEST['is_on_sale']) && $_REQUEST['is_on_sale'] === 0) ? '' : trim($_REQUEST['is_on_sale'])) : '';
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'goods_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['is_delete'] = $is_delete;
        $filter['real_goods'] = $real_goods;
        
        $where = $filter['cat_id'] > 0 ? " AND " . get_children($filter['cat_id']) : '';
        
        /* 推荐类型 */
        switch ($filter['intro_type']) {
            case 'is_best':
                $where .= " AND is_best=1";
                break;
            case 'is_hot':
                $where .= ' AND is_hot=1';
                break;
            case 'is_new':
                $where .= ' AND is_new=1';
                break;
            case 'is_promote':
                $where .= " AND is_promote = 1 AND promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today'";
                break;
            case 'all_type':
                $where .= " AND (is_best=1 OR is_hot=1 OR is_new=1 OR (is_promote = 1 AND promote_price > 0 AND promote_start_date <= '" . $today . "' AND promote_end_date >= '" . $today . "'))";
        }
        
        /* 库存警告 */
        if ($filter['stock_warning']) {
            $where .= ' AND goods_number <= warn_number ';
        }
        
        /* 品牌 */
        if ($filter['brand_id']) {
            $where .= " AND brand_id='$filter[brand_id]'";
        }
        
        /* 扩展 */
        if ($filter['extension_code']) {
            $where .= " AND extension_code='$filter[extension_code]'";
        }
        
        /* 关键字 */
        if (! empty($filter['keyword'])) {
            $where .= " AND (goods_sn LIKE '%" . mysql_like_quote($filter['keyword']) . "%' OR goods_name LIKE '%" . mysql_like_quote($filter['keyword']) . "%')";
        }
        
        if ($real_goods > - 1) {
            $where .= " AND is_real='$real_goods'";
        }
        
        /* 上架 */
        if ($filter['is_on_sale'] !== '') {
            $where .= " AND (is_on_sale = '" . $filter['is_on_sale'] . "')";
        }
        
        /* 供货商 */
        if (! empty($filter['suppliers_id'])) {
            $where .= " AND (suppliers_id = '" . $filter['suppliers_id'] . "')";
        }
        
        $where .= $conditions;
        
        /* 记录总数 */
        $filter['record_count'] = M()->table('goods as g')
            ->field('COUNT(*)')
            ->where(" is_delete='$is_delete' $where")
            ->getOne();
        
        /* 分页大小 */
        $filter = page_and_size($filter);
        
        $sql = "SELECT goods_id, goods_name, goods_type, goods_sn, shop_price, is_on_sale, is_best, is_new, is_hot, sort_order, goods_number, integral, " . " (promote_price > 0 AND promote_start_date <= '$today' AND promote_end_date >= '$today') AS is_promote " . " FROM " . M()->pre . 'goods ' . " AS g WHERE is_delete='$is_delete' $where" . " ORDER BY $filter[sort_by] $filter[sort_order] " . " LIMIT " . $filter['start'] . ",$filter[page_size]";
        
        $filter['keyword'] = stripslashes($filter['keyword']);
        set_filter($filter, $sql, $param_str);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $row = M()->query($sql);
    
    return array(
        'goods' => $row,
        'filter' => $filter,
        'page_count' => $filter['page_count'],
        'record_count' => $filter['record_count']
    );
}

/**
 * 检测商品是否有货品
 *
 * @access public
 * @param
 *            s integer $goods_id 商品id
 * @param
 *            s string $conditions sql条件，AND语句开头
 * @return string number -1，错误；1，存在；0，不存在
 */
function check_goods_product_exist($goods_id, $conditions = '')
{
    if (empty($goods_id)) {
        return - 1; // $goods_id不能为空
    }
    
    $sql = "SELECT goods_id
            FROM " . M()->pre . 'products ' . "
            WHERE goods_id = '$goods_id'
            " . $conditions . "
            LIMIT 0, 1";
    $result = M()->getRow($sql);
    
    if (empty($result)) {
        return 0;
    }
    
    return 1;
}

/**
 * 获得商品的货品总库存
 *
 * @access public
 * @param
 *            s integer $goods_id 商品id
 * @param
 *            s string $conditions sql条件，AND语句开头
 * @return string number
 */
function product_number_count($goods_id, $conditions = '')
{
    if (empty($goods_id)) {
        return - 1; // $goods_id不能为空
    }
    
    $sql = "SELECT SUM(product_number)
            FROM " . M()->pre . 'products ' . "
            WHERE goods_id = '$goods_id'
            " . $conditions;
    $nums = M()->table('products')
        ->field('SUM(product_number)')
        ->where("goods_id = '$goods_id'" . $conditions)
        ->getOne();
    $nums = empty($nums) ? 0 : $nums;
    return $nums;
}

/**
 * 获得商品的规格属性值列表
 *
 * @access public
 * @param
 *            s integer $goods_id
 * @return array
 */
function product_goods_attr_list($goods_id)
{
    if (empty($goods_id)) {
        return array(); // $goods_id不能为空
    }
    
    $sql = "SELECT goods_attr_id, attr_value FROM " . M()->pre . 'goods_attr ' . " WHERE goods_id = '$goods_id'";
    $results = M()->query($sql);
    
    $return_arr = array();
    foreach ($results as $value) {
        $return_arr[$value['goods_attr_id']] = $value['attr_value'];
    }
    
    return $return_arr;
}

/**
 * 获得商品已添加的规格列表
 *
 * @access public
 * @param
 *            s integer $goods_id
 * @return array
 */
function get_goods_specifications_list($goods_id)
{
    if (empty($goods_id)) {
        return array(); // $goods_id不能为空
    }
    
    $sql = "SELECT g.goods_attr_id, g.attr_value, g.attr_id, a.attr_name
            FROM " . M()->pre . 'goods_attr ' . " AS g
                LEFT JOIN " . M()->pre . 'attribute ' . " AS a
                    ON a.attr_id = g.attr_id
            WHERE goods_id = '$goods_id'
            AND a.attr_type = 1
            ORDER BY g.attr_id ASC";
    $results = M()->query($sql);
    
    return $results;
}

/**
 * 获得商品的货品列表
 *
 * @access public
 * @param
 *            s integer $goods_id
 * @param
 *            s string $conditions
 * @return array
 */
function product_list($goods_id, $conditions = '')
{
    /* 过滤条件 */
    $param_str = '-' . $goods_id;
    $result = get_filter($param_str);
    if ($result === false) {
        $day = getdate();
        $today = local_mktime(23, 59, 59, $day['mon'], $day['mday'], $day['year']);
        
        $filter['goods_id'] = $goods_id;
        $filter['keyword'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
        $filter['stock_warning'] = empty($_REQUEST['stock_warning']) ? 0 : intval($_REQUEST['stock_warning']);
        
        if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
            $filter['keyword'] = json_str_iconv($filter['keyword']);
        }
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'product_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $filter['extension_code'] = empty($_REQUEST['extension_code']) ? '' : trim($_REQUEST['extension_code']);
        $filter['page_count'] = isset($filter['page_count']) ? $filter['page_count'] : 1;
        
        $where = '';
        
        /* 库存警告 */
        if ($filter['stock_warning']) {
            $where .= ' AND goods_number <= warn_number ';
        }
        
        /* 关键字 */
        if (! empty($filter['keyword'])) {
            $where .= " AND (product_sn LIKE '%" . $filter['keyword'] . "%')";
        }
        
        $where .= $conditions;
        
        /* 记录总数 */
        $filter['record_count'] = M()->table('products as p')
            ->field('COUNT(*)')
            ->where("goods_id = $goods_id $where")
            ->getOne();
        
        $sql = "SELECT product_id, goods_id, goods_attr, product_sn, product_number
                FROM " . M()->pre . 'products ' . " AS g
                WHERE goods_id = $goods_id $where
                ORDER BY $filter[sort_by] $filter[sort_order]";
        
        $filter['keyword'] = stripslashes($filter['keyword']);
        // set_filter($filter, $sql, $param_str);
    } else {
        $sql = $result['sql'];
        $filter = $result['filter'];
    }
    $row = M()->query($sql);
    
    /* 处理规格属性 */
    $goods_attr = product_goods_attr_list($goods_id);
    foreach ($row as $key => $value) {
        $_goods_attr_array = explode('|', $value['goods_attr']);
        if (is_array($_goods_attr_array)) {
            $_temp = '';
            foreach ($_goods_attr_array as $_goods_attr_value) {
                $_temp[] = $goods_attr[$_goods_attr_value];
            }
            $row[$key]['goods_attr'] = $_temp;
        }
    }
    
    return array(
        'product' => $row,
        'filter' => $filter,
        'page_count' => $filter['page_count'],
        'record_count' => $filter['record_count']
    );
}

/**
 * 取货品信息
 *
 * @access public
 * @param int $product_id
 *            货品id
 * @param int $filed
 *            字段
 * @return array
 */
function get_product_info($product_id, $filed = '')
{
    $return_array = array();
    
    if (empty($product_id)) {
        return $return_array;
    }
    
    $filed = trim($filed);
    if (empty($filed)) {
        $filed = '*';
    }
    
    $sql = "SELECT $filed FROM  " . M()->pre . 'products ' . " WHERE product_id = '$product_id'";
    $return_array = M()->getRow($sql);
    
    return $return_array;
}

/**
 * 检查单个商品是否存在规格
 *
 * @param int $goods_id
 *            商品id
 * @return bool true，存在；false，不存在
 */
function check_goods_specifications_exist($goods_id)
{
    $goods_id = intval($goods_id);
    $count = M()->table('attribute as a')
        ->field('COUNT(a.attr_id)')
        ->where("a.cat_id = g.goods_type AND g.goods_id = '$goods_id'")
        ->getOne();
    if ($count > 0) {
        return true; // 存在
    } else {
        return false; // 不存在
    }
}

/**
 * 商品的货品规格是否存在
 *
 * @param string $goods_attr
 *            商品的货品规格
 * @param string $goods_id
 *            商品id
 * @param int $product_id
 *            商品的货品id；默认值为：0，没有货品id
 * @return bool true，重复；false，不重复
 */
function check_goods_attr_exist($goods_attr, $goods_id, $product_id = 0)
{
    $goods_id = intval($goods_id);
    if (strlen($goods_attr) == 0 || empty($goods_id)) {
        return true; // 重复
    }
    
    if (empty($product_id)) {
        $where = " goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'";
    } else {
        $where = " goods_attr = '$goods_attr'
                AND goods_id = '$goods_id'
                AND product_id <> '$product_id'";
    }
    
    $res = M()->table('products')
        ->field('product_id')
        ->where($where)
        ->getOne();
    
    if (empty($res)) {
        return false; // 不重复
    } else {
        return true; // 重复
    }
}

/**
 * 商品的货品货号是否重复
 *
 * @param string $product_sn
 *            商品的货品货号；请在传入本参数前对本参数进行SQl脚本过滤
 * @param int $product_id
 *            商品的货品id；默认值为：0，没有货品id
 * @return bool true，重复；false，不重复
 */
function check_product_sn_exist($product_sn, $product_id = 0)
{
    $product_sn = trim($product_sn);
    $product_id = intval($product_id);
    if (strlen($product_sn) == 0) {
        return true; // 重复
    }
    $sql = "SELECT goods_id FROM " . M()->pre . 'goods ' . "WHERE goods_sn='$product_sn'";
    if (M()->table('goods')
        ->field('goods_id')
        ->where("goods_sn='$product_sn'")
        ->getOne()) {
        return true; // 重复
    }
    
    if (empty($product_id)) {
        $where = " product_sn = '$product_sn'";
    } else {
        $where = " product_sn = '$product_sn'
                AND product_id <> '$product_id'";
    }
    $res = M()->table('products')
        ->field('product_id')
        ->where($where)
        ->getOne();
    
    if (empty($res)) {
        return false; // 不重复
    } else {
        return true; // 重复
    }
}

/**
 * 格式化商品图片名称（按目录存储）
 *
 * @param type $type            
 * @param type $goods_id            
 * @param type $source_img            
 * @param type $position            
 * @return boolean
 */
function reformat_image_name($type, $goods_id, $source_img, $position = '')
{
    $rand_name = gmtime() . sprintf("%03d", mt_rand(1, 999));
    $img_ext = substr($source_img, strrpos($source_img, '.'));
    $dir = 'images';
    if (defined('IMAGE_DIR')) {
        $dir = IMAGE_DIR;
    }
    $sub_dir = date('Ym', gmtime());
    if (! make_dir(ROOT_PATH . $dir . '/' . $sub_dir)) {
        return false;
    }
    if (! make_dir(ROOT_PATH . $dir . '/' . $sub_dir . '/source_img')) {
        return false;
    }
    if (! make_dir(ROOT_PATH . $dir . '/' . $sub_dir . '/goods_img')) {
        return false;
    }
    if (! make_dir(ROOT_PATH . $dir . '/' . $sub_dir . '/thumb_img')) {
        return false;
    }
    switch ($type) {
        case 'goods':
            $img_name = $goods_id . '_G_' . $rand_name;
            break;
        case 'goods_thumb':
            $img_name = $goods_id . '_thumb_G_' . $rand_name;
            break;
        case 'gallery':
            $img_name = $goods_id . '_P_' . $rand_name;
            break;
        case 'gallery_thumb':
            $img_name = $goods_id . '_thumb_P_' . $rand_name;
            break;
    }
    if ($position == 'source') {
        if (move_image_file(ROOT_PATH . $source_img, ROOT_PATH . $dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext)) {
            return $dir . '/' . $sub_dir . '/source_img/' . $img_name . $img_ext;
        }
    } elseif ($position == 'thumb') {
        if (move_image_file(ROOT_PATH . $source_img, ROOT_PATH . $dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext)) {
            return $dir . '/' . $sub_dir . '/thumb_img/' . $img_name . $img_ext;
        }
    } else {
        if (move_image_file(ROOT_PATH . $source_img, ROOT_PATH . $dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext)) {
            return $dir . '/' . $sub_dir . '/goods_img/' . $img_name . $img_ext;
        }
    }
    return false;
}

/**
 * 移动文件
 *
 * @param type $source            
 * @param type $dest            
 * @return boolean
 */
function move_image_file($source, $dest)
{
    if (@copy($source, $dest)) {
        @unlink($source);
        return true;
    }
    return false;
}
