<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：CategoryControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：商品分类控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class CategoryController extends CommonController {

    private $cat_id = 0; // 分类id
    private $children = '';
    private $brand = 0; // 品牌
    private $price_min = 0; // 最低价格
    private $price_max = 0; // 最大价格
    private $ext = '';
    private $size = 10; // 每页数据
    private $page = 1; // 页数
    private $sort = 'last_update';
    private $order = 'ASC'; // 排序方式
    private $keywords = ''; // 搜索关键词
    private $tag = ''; // tag搜索id
    private $filter_attr_str = 0;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->cat_id = I('request.id');
    }

    /**
     * 分类产品信息列表
     */
    public function index() {
        $this->parameter();
        $this->assign('brand_id', $this->brand);
        $this->assign('price_max', $this->price_max);
        $this->assign('price_min', $this->price_min);
        $this->assign('filter_attr', $this->filter_attr_str);
        $this->assign('page', $this->page);
        $this->assign('size', $this->size);
        $this->assign('sort', $this->sort);
        $this->assign('order', $this->order);
        $this->assign('id', $this->cat_id);
        // 获取分类
        $this->assign('category', model('CategoryBase')->get_top_category());
        $this->assign('nCount', model('Category')->category_get_count($this->children, $this->brand, $this->ext, $this->keywords));

        /* 页面标题 */
        $page_info = get_page_title($this->cat_id);
        $this->assign('ur_here', $page_info['ur_here']);
        $this->assign('page_title', $page_info['title']);

        $this->display('category.dwt');
    }

    /**
     * 异步加载商品列表
     */
    public function asynclist() {
        $this->parameter();
        $asyn_last = intval(I('post.last')) + 1;
        $this->size = I('post.amount');
        $this->page = ($asyn_last > 0) ? ceil($asyn_last / $this->size) : 1;
        $goodslist = $this->category_get_goods();
        foreach ($goodslist as $key => $goods) {
            $this->assign('goods', $goods);
            $sayList[] = array(
                'single_item' => ECTouch::view()->fetch('library/asynclist_info.lbi')
            );
        }
        die(json_encode($sayList));
        exit();
    }

    /**
     * 处理关键词
     */
    public function keywords() {
        $keywords = I('request.keywords');
        if ($keywords != '') {
            $this->keywords = 'AND (';
            $goods_ids = array();
            $val = mysql_like_quote(trim($keywords));
            $this->keywords .= "(goods_name LIKE '%$val%' OR goods_sn LIKE '%$val%' OR keywords LIKE '%$val%' )";

            $sql = 'SELECT DISTINCT goods_id FROM ' . $this->model->pre . "tag WHERE tag_words LIKE '%$val%' ";
            $row = $this->model->query($sql);
            foreach ($row as $vo) {
                $goods_ids[] = $vo['goods_id'];
            }
            /**
             * 处理关键字查询次数
             */
            $sql = 'INSERT INTO ' . $this->model->pre . "keywords (date , searchengine,keyword ,count) VALUES ('" . local_date('Y-m-d') . "', '" . ECTouch . "', '" . addslashes(str_replace('%', '', $val)) . "', '1')";
            $condition = 'keyword = "' . addslashes(str_replace('%', '', $val)) . '"';
            $set = $this->model->table('keywords')
                    ->where($condition)
                    ->find();

            if (!empty($set)) {
                $sql .= ' ON DUPLICATE KEY UPDATE count = count+1';
            }
            $this->model->query($sql);
            $this->keywords .= ')';
            $goods_ids = array_unique($goods_ids);
            // 拼接商品id
            $this->tag = implode(',', $goods_ids);
            if (!empty($this->tag)) {
                $this->tag = 'OR g.goods_id ' . db_create_in($this->tag);
            }
            $this->assign('keywords', $keywords);
        } elseif ($this->cat_id == 0) {
            ecs_header("Location: " . url('category/all') . "\n");
        }
    }

    /**
     * 处理参数便于搜索商品信息
     */
    public function parameter() {
        // 如果分类ID为0，则返回总分类页
        if (empty($this->cat_id)) {
            $this->cat_id = 0;
        }
        // 获得分类的相关信息
        $cat = model('Category')->get_cat_info($this->cat_id);
        $this->keywords();
        // 初始化分页信息
        $page_size = C('page_size');
        $brand = I('request.brand');
        $price_max = I('request.price_max');
        $price_min = I('request.price_min');
        $filter_attr = I('request.filter_attr');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->brand = $brand > 0 ? $brand : 0;
        $this->price_max = $price_max > 0 ? $price_max : 0;
        $this->price_min = $price_min > 0 ? $price_min : 0;
        $this->filter_attr_str = $filter_attr > 0 ? $filter_attr : '0';

        $this->filter_attr_str = trim(urldecode($this->filter_attr_str));
        $this->filter_attr_str = preg_match('/^[\d\.]+$/', $this->filter_attr_str) ? $this->filter_attr_str : '';
        $filter_attr = empty($this->filter_attr_str) ? '' : explode('.', $this->filter_attr_str);

        /* 排序、显示方式以及类型 */
        $default_display_type = C('show_order_type') == '0' ? 'list' : (C('show_order_type') == '1' ? 'grid' : 'album');
        $default_sort_order_method = C('sort_order_method') == '0' ? 'DESC' : 'ASC';
        $default_sort_order_type = C('sort_order_type') == '0' ? 'goods_id' : (C('sort_order_type') == '1' ? 'shop_price' : 'last_update');

        $this->sort = (isset($_REQUEST['sort']) && in_array(trim(strtolower($_REQUEST['sort'])), array(
                    'goods_id',
                    'shop_price',
                    'last_update',
                    'click_count',
                    'sales_volume'
                ))) ? trim($_REQUEST['sort']) : $default_sort_order_type; // 增加按人气、按销量排序 by wang
        $this->order = (isset($_REQUEST['order']) && in_array(trim(strtoupper($_REQUEST['order'])), array(
                    'ASC',
                    'DESC'
                ))) ? trim($_REQUEST['order']) : $default_sort_order_method;
        $display = (isset($_REQUEST['display']) && in_array(trim(strtolower($_REQUEST['display'])), array(
                    'list',
                    'grid',
                    'album'
                ))) ? trim($_REQUEST['display']) : (isset($_COOKIE['ECS']['display']) ? $_COOKIE['ECS']['display'] : $default_display_type);
        $display = in_array($display, array(
                    'list',
                    'grid',
                    'album'
                )) ? $display : 'album';
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);

        $this->children = get_children($this->cat_id);
        /* 属性筛选 */
        $ext = ''; // 商品查询条件扩展
        if ($cat['filter_attr'] > 0) {
            $cat_filter_attr = explode(',', $cat['filter_attr']); // 提取出此分类的筛选属性
            $all_attr_list = array();

            foreach ($cat_filter_attr as $key => $value) {
                $sql = "SELECT a.attr_name FROM " . $this->model->pre . "attribute AS a, " . $this->model->pre . "goods_attr AS ga, " . $this->model->pre . "goods AS g WHERE ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ") AND a.attr_id = ga.attr_id AND g.goods_id = ga.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND a.attr_id='$value'";
                $res = $this->model->query($sql);

                if ($temp_name = $res[0]['attr_name']) {
                    $all_attr_list[$key]['filter_attr_id'] = $value; // 新增属性标识 by wang
                    $all_attr_list[$key]['filter_attr_name'] = $temp_name;

                    $sql = "SELECT a.attr_id, MIN(a.goods_attr_id ) AS goods_id, a.attr_value AS attr_value FROM " . $this->model->pre . "goods_attr AS a, " . $this->model->pre . "goods AS g" . " WHERE ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') AND g.goods_id = a.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 ' . " AND a.attr_id='$value' " . " GROUP BY a.attr_value";

                    $attr_list = $this->model->query($sql);

                    $temp_arrt_url_arr = array();

                    for ($i = 0; $i < count($cat_filter_attr); $i++) { // 获取当前url中已选择属性的值，并保留在数组中
                        $temp_arrt_url_arr[$i] = !empty($filter_attr[$i]) ? $filter_attr[$i] : 0;
                    }
                    // “全部”的信息生成
                    $temp_arrt_url_arr[$key] = 0;
                    $temp_arrt_url = implode('.', $temp_arrt_url_arr);
                    // 默认数值
                    $all_attr_list[$key]['attr_list'][0]['attr_id'] = 0;
                    $all_attr_list[$key]['attr_list'][0]['attr_value'] = L('all_attribute');
                    $all_attr_list[$key]['attr_list'][0]['url'] = build_uri('category/index', array(
                        'id' => $this->cat_id,
                        'bid' => $this->brand,
                        'price_min' => $this->price_min,
                        'price_max' => $this->price_max,
                        'filter_attr' => $temp_arrt_url
                            ), $cat['cat_name']);
                    $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                    foreach ($attr_list as $k => $v) {
                        $temp_key = $k + 1;
                        // 为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                        $temp_arrt_url_arr[$key] = $v['goods_id'];
                        $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                        $all_attr_list[$key]['attr_list'][$temp_key]['attr_id'] = $v['goods_id']; // 新增属性参数 by wang
                        $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                        $all_attr_list[$key]['attr_list'][$temp_key]['url'] = build_uri('category/index', array(
                            'id' => $this->cat_id,
                            'bid' => $this->brand,
                            'price_min' => $this->price_min,
                            'price_max' => $this->price_max,
                            'filter_attr' => $temp_arrt_url
                                ), $cat['cat_name']);

                        if (!empty($filter_attr[$key]) and $filter_attr[$key] == $v['goods_id']) {
                            $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 1;
                        } else {
                            $all_attr_list[$key]['attr_list'][$temp_key]['selected'] = 0;
                        }
                    }
                }
            }

            $this->assign('filter_attr_list', $all_attr_list);
            // 扩展商品查询条件
            if (!empty($filter_attr)) {
                $ext_sql = "SELECT DISTINCT(b.goods_id) as distinct FROM " . $this->model->pre . "goods_attr AS a, " . $this->model->pre . "goods_attr AS b " . "WHERE ";
                $ext_group_goods = array();
                // 查出符合所有筛选属性条件的商品id
                foreach ($filter_attr as $k => $v) {
                    if (is_numeric($v) && $v != 0 && isset($cat_filter_attr[$k])) {
                        $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] . " AND a.goods_attr_id = " . $v;
                        $res = $this->model->query($sql);
                        foreach ($res as $value) {
                            $ext_group_goods[] = $value['distinct'];
                        }
                        $this->ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                    }
                }
            }
        }
    }

    /**
     * 获取分类信息
     * 只获取二级分类当没有参数时获取最高的二级分类
     */
    public function all() {
        $cat_id = I('get.id');
        /* 页面的缓存ID */
        $cache_id = sprintf('%X', crc32($_SERVER['REQUEST_URI'] . C('lang')));
        if (!ECTouch::view()->is_cached('category_all.dwt', $cache_id)) {
            // 获得请求的分类 ID
            if ($cat_id > 0) {
                $category = model('CategoryBase')->get_child_tree($cat_id);
            } else {
                $category = model('CategoryBase')->get_categories_tree();
            }
            $this->assign('title', L('catalog'));
            $this->assign('category', $category);

            /* 页面标题 */
            $page_info = get_page_title($cat_id);
            $this->assign('ur_here', $page_info['ur_here']);
            $this->assign('page_title', ($cat_id > 0)? $page_info['title']:L('catalog').'_'.$page_info['title']);
        }
        $this->display('category_all.dwt', $cache_id);
    }

    /**
     * 获得分类下的商品
     *
     * @access public
     * @param string $children            
     * @return array
     */
    private function category_get_goods() {
        $display = $GLOBALS['display'];
        $where = "g.is_on_sale = 1 AND g.is_alone_sale = 1 AND " . "g.is_delete = 0 ";
        if ($this->keywords != '') {
            $where .= " AND (( 1 " . $this->keywords . " ) " . $this->tag_where . " ) ";
        }else{
            $where.=" AND ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') ';
        }
        if ($this->brand > 0) {
            $where .= "AND g.brand_id=$this->brand ";
        }
        if ($this->min > 0) {
            $where .= " AND g.shop_price >= $this->min ";
        }
        if ($this->max > 0) {
            $where .= " AND g.shop_price <= $this->max ";
        }
        $start = ($this->page - 1) * $this->size;
        /* 获得商品列表 */
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " . 'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img ' . 'FROM ' . $this->model->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->model->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' . ' LEFT JOIN ' . $this->model->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where $this->ext ORDER BY $this->sort $this->order LIMIT $start , $this->size";
        $res = $this->model->query($sql);
        $arr = array();
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }
            /* 处理商品水印图片 */
            $watermark_img = '';

            if ($promote_price != 0) {
                $watermark_img = "watermark_promote_small";
            } elseif ($row['is_new'] != 0) {
                $watermark_img = "watermark_new_small";
            } elseif ($row['is_best'] != 0) {
                $watermark_img = "watermark_best_small";
            } elseif ($row['is_hot'] != 0) {
                $watermark_img = 'watermark_hot_small';
            }

            if ($watermark_img != '') {
                $arr[$row['goods_id']]['watermark_img'] = $watermark_img;
            }

            $arr[$row['goods_id']]['goods_id'] = $row['goods_id'];
            if ($display == 'grid') {
                $arr[$row['goods_id']]['goods_name'] = C('goods_name_length') > 0 ? sub_str($row['goods_name'], C('goods_name_length')) : $row['goods_name'];
            } else {
                $arr[$row['goods_id']]['goods_name'] = $row['goods_name'];
            }
            $arr[$row['goods_id']]['name'] = $row['goods_name'];
            $arr[$row['goods_id']]['goods_brief'] = $row['goods_brief'];
            $arr[$row['goods_id']]['goods_style_name'] = add_style($row['goods_name'], $row['goods_name_style']);
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);
            $arr[$row['goods_id']]['shop_price'] = price_format($row['shop_price']);
            $arr[$row['goods_id']]['type'] = $row['goods_type'];
            $arr[$row['goods_id']]['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $arr[$row['goods_id']]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img'] = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['url'] = url('goods/index', array(
                'id' => $row['goods_id']
            ));
            $arr[$row['goods_id']]['sales_count'] = model('GoodsBase')->get_sales_count($row['goods_id']);
            $arr[$row['goods_id']]['sc'] = model('GoodsBase')->get_goods_collect($row['goods_id']);
            $arr[$row['goods_id']]['mysc'] = 0;
            // 检查是否已经存在于用户的收藏夹
            if ($_SESSION['user_id']) {
                unset($where);
                // 用户自己有没有收藏过
                $where['goods_id'] = $row['goods_id'];
                $where['user_id'] = $_SESSION['user_id'];
                $rs = $this->model->table('collect_goods')
                        ->where($where)
                        ->count();
                $arr[$row['goods_id']]['mysc'] = $rs;
            }
            $arr[$row['goods_id']]['promotion'] = model('GoodsBase')->get_promotion_show($row['goods_id']);
        }
        return $arr;
    }

}
