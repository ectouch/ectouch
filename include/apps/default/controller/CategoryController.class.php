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
    private $type = ''; //商品类型
    private $price_min = 0; // 最低价格
    private $price_max = 0; // 最大价格
    private $ext = '';
    private $size = 10; // 每页数据
    private $page = 1; // 页数
    private $sort = 'last_update';
    private $order = 'ASC'; // 排序方式
    private $keywords = ''; // 搜索关键词
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
        if (I('get.id', 0) == 0 && CONTROLLER_NAME == 'category') {
            $arg = array(
                'id' => $this->cat_id,
                'brand' => $this->brand,
                'price_max' => $this->price_max,
                'price_min' => $this->price_min,
                'filter_attr' => $this->filter_attr_str,
                'page' => $this->page,
            );

            $url = url('Category/index', $arg);
            $this->redirect($url);
        }
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
        $count = model('Category')->category_get_count($this->children, $this->brand, $this->type, $this->price_min, $this->price_max, $this->ext,$this->keywords);
        $goodslist = $this->category_get_goods();
        $this->assign('goods_list', $goodslist);
        $this->pageLimit(url('index', array('id' => $this->cat_id, 'brand' => $this->brand, 'price_max' => $this->price_max, 'price_min' => $this->price_min, 'filter_attr' => $this->filter_attr_str, 'sort' => $this->sort, 'order' => $this->order,'keywords'=>I('request.keywords'))), $this->size);
        $this->assign('pager', $this->pageShow($count));
        /* 页面标题 */
        $page_info = get_page_title($this->cat_id);
        $this->assign('ur_here', $page_info['ur_here']);
        $this->assign('page_title', $page_info['title']);
        $cat = model('Category')->get_cat_info($this->cat_id);  // 获得分类的相关信息
        if (!empty($cat['keywords'])) {
            $this->assign('meat_keywords',htmlspecialchars($cat['keywords']));
        }
        if (!empty($cat['cat_desc'])) {
            $this->assign('meta_description',htmlspecialchars($cat['cat_desc']));
        }

        $this->assign('categories', model('CategoryBase')->get_categories_tree($this->cat_id));
		$this->assign('show_marketprice', C('show_marketprice'));
        $this->display('category.dwt');
    }

    /**
     * 异步加载商品列表
     */
    public function asynclist() {
        $this->parameter();
		$this->assign('show_marketprice', C('show_marketprice'));
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
            $tag_id = implode(',', $goods_ids);
            if (!empty($tag_id)) {
                $this->keywords .= 'OR g.goods_id ' . db_create_in($tag_id);
            }
            $this->assign('keywords', $keywords);
        }
    }

    /**
     * 处理参数便于搜索商品信息
     */
    private function parameter() {
        // 如果分类ID为0，则返回总分类页
        if (empty($this->cat_id)) {
            $this->cat_id = 0;
        }
        // 获得分类的相关信息
        $cat = model('Category')->get_cat_info($this->cat_id);
        $this->keywords();
        $this->assign('show_asynclist', C('show_asynclist'));
        // 初始化分页信息
        $page_size = C('page_size');
        $brand = I('request.brand');
        $price_max = I('request.price_max');
        $price_min = I('request.price_min');
        $filter_attr = I('request.filter_attr');
        $this->size = intval($page_size) > 0 ? intval($page_size) : 10;
        $this->page = I('request.page') > 0 ? intval(I('request.page')) : 1;
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
        $this->type = (isset($_REQUEST['type']) && in_array(trim(strtolower($_REQUEST['type'])), array('best', 'hot', 'new', 'promotion'))) ? trim(strtolower($_REQUEST['type'])) : '';
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
        $this->assign('display', $display);
        setcookie('ECS[display]', $display, gmtime() + 86400 * 7);
        $this->children = get_children($this->cat_id);
        /* 赋值固定内容 */
        if ($this->brand > 0) {
            $brand_name = model('Base')->model->table('brand')->field('brand_name')->where("brand_id = '$this->brand'")->getOne();
        } else {
            $brand_name = '';
        }

        /* 获取价格分级 */
        if ($cat['grade'] == 0 && $cat['parent_id'] != 0) {
            $cat['grade'] = model('Category')->get_parent_grade($this->cat_id); // 如果当前分类级别为空，取最近的上级分类
        }

        if ($cat['grade'] > 1) {
            /* 需要价格分级 */

            /*
              算法思路：
              1、当分级大于1时，进行价格分级
              2、取出该类下商品价格的最大值、最小值
              3、根据商品价格的最大值来计算商品价格的分级数量级：
              价格范围(不含最大值)    分级数量级
              0-0.1                   0.001
              0.1-1                   0.01
              1-10                    0.1
              10-100                  1
              100-1000                10
              1000-10000              100
              4、计算价格跨度：
              取整((最大值-最小值) / (价格分级数) / 数量级) * 数量级
              5、根据价格跨度计算价格范围区间
              6、查询数据库

              可能存在问题：
              1、
              由于价格跨度是由最大值、最小值计算出来的
              然后再通过价格跨度来确定显示时的价格范围区间
              所以可能会存在价格分级数量不正确的问题
              该问题没有证明
              2、
              当价格=最大值时，分级会多出来，已被证明存在
             */

            $sql = "SELECT min(g.shop_price) AS min, max(g.shop_price) as max " . " FROM " . $this->model->pre . 'goods' . " AS g " . " WHERE ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1  ';
            // 获得当前分类下商品价格的最大值、最小值

            $row = M()->getRow($sql);

            // 取得价格分级最小单位级数，比如，千元商品最小以100为级数
            $price_grade = 0.0001;
            for ($i = - 2; $i <= log10($row['max']); $i++) {
                $price_grade *= 10;
            }

            // 跨度
            $dx = ceil(($row['max'] - $row['min']) / ($cat['grade']) / $price_grade) * $price_grade;
            if ($dx == 0) {
                $dx = $price_grade;
            }

            for ($i = 1; $row['min'] > $dx * $i; $i++)
                ;

            for ($j = 1; $row['min'] > $dx * ($i - 1) + $price_grade * $j; $j++)
                ;
            $row['min'] = $dx * ($i - 1) + $price_grade * ($j - 1);

            for (; $row['max'] >= $dx * $i; $i++)
                ;
            $row['max'] = $dx * ($i) + $price_grade * ($j - 1);

            $sql = "SELECT (FLOOR((g.shop_price - $row[min]) / $dx)) AS sn, COUNT(*) AS goods_num  " . " FROM " . $this->model->pre . 'goods' . " AS g " . " WHERE ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') AND g.is_delete = 0 AND g.is_on_sale = 1 AND g.is_alone_sale = 1 ' . " GROUP BY sn ";

            $price_grade = $this->model->query($sql);

            foreach ($price_grade as $key => $val) {
                $temp_key = $key + 1;
                $price_grade[$temp_key]['goods_num'] = $val['goods_num'];
                $price_grade[$temp_key]['start'] = $row['min'] + round($dx * $val['sn']);
                $price_grade[$temp_key]['end'] = $row['min'] + round($dx * ($val['sn'] + 1));
                $price_grade[$temp_key]['price_range'] = $price_grade[$temp_key]['start'] . '&nbsp;-&nbsp;' . $price_grade[$temp_key]['end'];
                $price_grade[$temp_key]['formated_start'] = price_format($price_grade[$temp_key]['start']);
                $price_grade[$temp_key]['formated_end'] = price_format($price_grade[$temp_key]['end']);
                $price_grade[$temp_key]['url'] = url('category', array(
                    'cid' => $this->cat_id,
                    'bid' => $this->brand,
                    'price_min' => $price_grade[$temp_key]['start'],
                    'price_max' => $price_grade[$temp_key]['end'],
                    'filter_attr' => $filter_attr
                ));

                /* 判断价格区间是否被选中 */
                if (isset($_REQUEST['price_min']) && $price_grade[$temp_key]['start'] == $price_min && $price_grade[$temp_key]['end'] == $price_max) {
                    $price_grade[$temp_key]['selected'] = 1;
                } else {
                    $price_grade[$temp_key]['selected'] = 0;
                }
            }

            $price_grade[0]['start'] = 0;
            $price_grade[0]['end'] = 0;
            $price_grade[0]['price_range'] = L('all_attribute');
            $price_grade[0]['url'] = url('category/index', array(
                'cid' => $this->cat_id,
                'bid' => $brand,
                'price_min' => 0,
                'price_max' => 0,
                'filter_attr' => $filter_attr
            ));
            $price_grade[0]['selected'] = empty($price_max) ? 1 : 0;
            $this->assign('price_grade', $price_grade);
        }

        /* 品牌筛选 */

        $sql = "SELECT b.brand_id, b.brand_name, COUNT(*) AS goods_num " . "FROM " . $this->model->pre . 'brand' . " AS b, " . $this->model->pre . 'goods' . " AS g LEFT JOIN " . $this->model->pre . 'goods_cat' . " AS gc ON g.goods_id = gc.goods_id " . "WHERE g.brand_id = b.brand_id AND ($this->children OR " . 'gc.cat_id ' . db_create_in(array_unique(array_merge(array(
                    $this->cat_id
                                        ), array_keys(cat_list($this->cat_id, 0, false))))) . ") AND b.is_show = 1 " . " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 " . "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY b.sort_order, b.brand_id ASC";

        $brands = $this->model->query($sql);

        foreach ($brands as $key => $val) {
            $temp_key = $key + 1;
            $brands[$temp_key]['brand_id'] = $val['brand_id']; // 同步绑定品牌名称和品牌ID 
            $brands[$temp_key]['brand_name'] = $val['brand_name'];
            $brands[$temp_key]['url'] = url('category/index', array(
                'id' => $this->cat_id,
                'bid' => $val['brand_id'],
                'price_min' => $price_min,
                'price_max' => $price_max,
                'filter_attr' => $filter_attr
            ));

            /* 判断品牌是否被选中 */
            if ($brand == $val['brand_id']) {             // 修正当前品牌的ID
                $brands[$temp_key]['selected'] = 1;
            } else {
                $brands[$temp_key]['selected'] = 0;
            }
        }

        unset($brands[0]); // 清空索引为0的项目 
        $brands[0]['brand_id'] = 0; // 新增默认值
        $brands[0]['brand_name'] = L('all_attribute');
        $brands[0]['url'] = url('category', array(
            'cid' => $this->cat_id,
            'bid' => 0,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'filter_attr' => $filter_attr
        ));
        $brands[0]['selected'] = empty($brand) ? 1 : 0;

        ksort($brands);
        $this->assign('brands', $brands);
        /* 属性筛选 */
        $this->ext = ''; // 商品查询条件扩展
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
                    $all_attr_list[$key]['attr_list'][0]['url'] = url('category/index', array(
                        'id' => $this->cat_id,
                        'bid' => $this->brand,
                        'price_min' => $this->price_min,
                        'price_max' => $this->price_max,
                        'filter_attr' => $temp_arrt_url
                    ));
                    $all_attr_list[$key]['attr_list'][0]['selected'] = empty($filter_attr[$key]) ? 1 : 0;

                    foreach ($attr_list as $k => $v) {
                        $temp_key = $k + 1;
                        // 为url中代表当前筛选属性的位置变量赋值,并生成以‘.’分隔的筛选属性字符串
                        $temp_arrt_url_arr[$key] = $v['goods_id'];
                        $temp_arrt_url = implode('.', $temp_arrt_url_arr);

                        $all_attr_list[$key]['attr_list'][$temp_key]['attr_id'] = $v['goods_id']; // 新增属性参数 
                        $all_attr_list[$key]['attr_list'][$temp_key]['attr_value'] = $v['attr_value'];
                        $all_attr_list[$key]['attr_list'][$temp_key]['url'] = url('category/index', array(
                            'id' => $this->cat_id,
                            'bid' => $this->brand,
                            'price_min' => $this->price_min,
                            'price_max' => $this->price_max,
                            'filter_attr' => $temp_arrt_url
                        ));

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
                $ext_sql = "SELECT DISTINCT(b.goods_id) as dis FROM " . $this->model->pre . "goods_attr AS a, " . $this->model->pre . "goods_attr AS b " . "WHERE ";
                $ext_group_goods = array();
                // 查出符合所有筛选属性条件的商品id
                foreach ($filter_attr as $k => $v) {
                    unset($ext_group_goods);
                    if (is_numeric($v) && $v != 0 && isset($cat_filter_attr[$k])) {
                        $sql = $ext_sql . "b.attr_value = a.attr_value AND b.attr_id = " . $cat_filter_attr[$k] . " AND a.goods_attr_id = " . $v;
                        $res = $this->model->query($sql);
                        foreach ($res as $value) {
                            $ext_group_goods[] = $value['dis'];
                        }
                        $this->ext .= ' AND ' . db_create_in($ext_group_goods, 'g.goods_id');
                    }
                }
            }
        }
    }

    /**
     * 获取分类信息
     * 获取顶级分类
     */
    public function top_all() {
        /* 页面的缓存ID */
        $cache_id = sprintf('%X', crc32($_SERVER['REQUEST_URI'] . C('lang')));
        if (!ECTouch::view()->is_cached('category_all.dwt', $cache_id)) {
            $category = model('CategoryBase')->get_categories_tree();
            $this->assign('title', L('catalog'));
            $this->assign('category', $category);
            /* 页面标题 */
            $page_info = get_page_title();
            $this->assign('ur_here', $page_info['ur_here']);
            $this->assign('page_title', L('catalog') . '_' . $page_info['title']);
        }
        $this->display('category_top_all.dwt', $cache_id);
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
                //顶级分类
                ecs_header("Location: " . url('category/top_all') . "\n");
            }
            $this->assign('title', L('catalog'));
            $this->assign('category', $category);

            /* 页面标题 */
            $page_info = get_page_title($cat_id);
            $this->assign('ur_here', $page_info['ur_here']);
            $this->assign('page_title', ($cat_id > 0) ? $page_info['title'] : L('catalog') . '_' . $page_info['title']);
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
            $where .= " AND (( 1 " . $this->keywords . " ) ) ";
        } else {
            $where.=" AND ($this->children OR " . model('Goods')->get_extension_goods($this->children) . ') ';
        }
        if ($this->type) {
            switch ($this->type) {
                case 'best':
                    $where .= ' AND g.is_best = 1';
                    break;
                case 'new':
                    $where .= ' AND g.is_new = 1';
                    break;
                case 'hot':
                    $where .= ' AND g.is_hot = 1';
                    break;
                case 'promotion':
                    $time = gmtime();
                    $where .= " AND g.promote_price > 0 AND g.promote_start_date <= '$time' AND g.promote_end_date >= '$time'";
                    break;
                default:
                    $where .= '';
            }
        }
        if ($this->brand > 0) {
            $where .= "AND g.brand_id=$this->brand ";
        }
        if ($this->price_min > 0) {
            $where .= " AND g.shop_price >= $this->price_min ";
        }
        if ($this->price_max > 0) {
            $where .= " AND g.shop_price <= $this->price_max ";
        }

        $start = ($this->page - 1) * $this->size;
        $sort = $this->sort == 'sales_volume' ? 'xl.sales_volume' : $this->sort;
        /* 获得商品列表 */
        $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.market_price, g.is_new, g.is_best, g.is_hot, g.shop_price AS org_price, ' . "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, g.promote_price, g.goods_type, " . 'g.promote_start_date, g.promote_end_date, g.goods_brief, g.goods_thumb , g.goods_img, xl.sales_volume ' . 'FROM ' . $this->model->pre . 'goods AS g ' . ' LEFT JOIN ' . $this->model->pre . 'touch_goods AS xl ' . ' ON g.goods_id=xl.goods_id ' . ' LEFT JOIN ' . $this->model->pre . 'member_price AS mp ' . "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' " . "WHERE $where $this->ext ORDER BY $sort $this->order LIMIT $start , $this->size";
        $res = $this->model->query($sql);
        $arr = array();
        foreach ($res as $row) {
            // 销量统计
            $sales_volume = (int) $row['sales_volume'];
            if (mt_rand(0, 3) == 3){
                $sales_volume = model('GoodsBase')->get_sales_count($row['goods_id']);
                $sql = 'REPLACE INTO ' . $this->model->pre . 'touch_goods(`goods_id`, `sales_volume`) VALUES('. $row['goods_id'] .', '.$sales_volume.')';
                $this->model->query($sql);
            }
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
            $arr[$row['goods_id']]['sales_count'] = $sales_volume;
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
            $arr[$row['goods_id']]['comment_count'] = model('Comment')->get_goods_comment($row['goods_id'], 0);  //商品总评论数量 
            $arr[$row['goods_id']]['favorable_count'] = model('Comment')->favorable_comment($row['goods_id'], 0);  //获得商品好评百分比
        }
        return $arr;
    }

}
