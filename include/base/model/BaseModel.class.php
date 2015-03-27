<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：BaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class BaseModel extends Model {

    /**
     * 查询全部分类列表
     * @param type $area_id
     */
    public function get_all_cat_list() {
        $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children " .
                'FROM ' . $this->pre . "category AS c " .
                "LEFT JOIN " . $this->pre . "category AS s ON s.parent_id=c.cat_id " .
                "GROUP BY c.cat_id " .
                'ORDER BY c.parent_id, c.sort_order ASC';
        $res = $this->query($sql);

        $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                " FROM " . $this->pre . "goods WHERE is_delete = 0 AND is_on_sale = 1 " .
                " GROUP BY cat_id";
        $res2 = $this->query($sql);

        $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                " FROM " . $this->pre . "goods_cat AS gc , " . $this->pre . "goods AS g " .
                " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 " .
                " GROUP BY gc.cat_id";
        $res3 = $this->query($sql);

        $newres = array();
        if (is_array($res2))
            foreach ($res2 as $k => $v) {
                $newres[$v['cat_id']] = $v['goods_num'];
                if (is_array($res3))
                    foreach ($res3 as $ks => $vs) {
                        if ($v['cat_id'] == $vs['cat_id']) {
                            $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                        }
                    }
            }

        foreach ($res as $k => $v) {
            $res[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
        }

        return $res;
    }

    /**
     * 载入配置信息
     * @access  public
     * @return  array
     */
    public function load_config() {
        $data = read_static_cache('touch_shop_config');
        if ($data === false) {
            $sql = 'SELECT code, value FROM ' . $this->pre . 'touch_shop_config WHERE parent_id > 0';
            $res = $this->query($sql);
            $arr1 = array();
            foreach ($res AS $row) {
                $arr1[$row['code']] = $row['value'];
            }

            $sql = 'SELECT code, value FROM ' . $this->pre . 'shop_config WHERE parent_id > 0';
            $res = $this->query($sql);
            $arr2 = array();
            foreach ($res AS $row) {
                $arr2[$row['code']] = $row['value'];
            }
            $arr = array_merge($arr2, $arr1);

            /* 对数值型设置处理 */
            $arr['watermark_alpha'] = intval($arr['watermark_alpha']);
            $arr['market_price_rate'] = floatval($arr['market_price_rate']);
            $arr['integral_scale'] = floatval($arr['integral_scale']);
            //$arr['integral_percent']     = floatval($arr['integral_percent']);
            $arr['cache_time'] = intval($arr['cache_time']);
            $arr['thumb_width'] = intval($arr['thumb_width']);
            $arr['thumb_height'] = intval($arr['thumb_height']);
            $arr['image_width'] = intval($arr['image_width']);
            $arr['image_height'] = intval($arr['image_height']);
            $arr['best_number'] = !empty($arr['best_number']) && intval($arr['best_number']) > 0 ? intval($arr['best_number']) : 3;
            $arr['new_number'] = !empty($arr['new_number']) && intval($arr['new_number']) > 0 ? intval($arr['new_number']) : 3;
            $arr['hot_number'] = !empty($arr['hot_number']) && intval($arr['hot_number']) > 0 ? intval($arr['hot_number']) : 3;
            $arr['promote_number'] = !empty($arr['promote_number']) && intval($arr['promote_number']) > 0 ? intval($arr['promote_number']) : 3;
            $arr['top_number'] = intval($arr['top_number']) > 0 ? intval($arr['top_number']) : 10;
            $arr['history_number'] = intval($arr['history_number']) > 0 ? intval($arr['history_number']) : 5;
            $arr['comments_number'] = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
            $arr['article_number'] = intval($arr['article_number']) > 0 ? intval($arr['article_number']) : 5;
            $arr['page_size'] = intval($arr['page_size']) > 0 ? intval($arr['page_size']) : 10;
            $arr['bought_goods'] = intval($arr['bought_goods']);
            $arr['goods_name_length'] = intval($arr['goods_name_length']);
            $arr['top10_time'] = intval($arr['top10_time']);
            $arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
            $arr['no_picture'] = !empty($arr['no_picture']) ? str_replace('./', '/', $arr['no_picture']) : __ROOT__.'/data/common/images/no_picture.gif'; // 修改默认商品图片的路径
            $arr['qq'] = !empty($arr['qq']) ? $arr['qq'] : '';
            $arr['ww'] = !empty($arr['ww']) ? $arr['ww'] : '';
            $arr['default_storage'] = isset($arr['default_storage']) ? intval($arr['default_storage']) : 1;
            $arr['min_goods_amount'] = isset($arr['min_goods_amount']) ? floatval($arr['min_goods_amount']) : 0;
            $arr['one_step_buy'] = empty($arr['one_step_buy']) ? 0 : 1;
            $arr['invoice_type'] = empty($arr['invoice_type']) ? array('type' => array(), 'rate' => array()) : unserialize($arr['invoice_type']);
            $arr['show_order_type'] = isset($arr['show_order_type']) ? $arr['show_order_type'] : 0;    // 显示方式默认为列表方式
            $arr['help_open'] = isset($arr['help_open']) ? $arr['help_open'] : 1;    // 显示方式默认为列表方式

            $ecs_version = C('ecs_version');
            if (!isset($ecs_version)) {
                /* 如果没有版本号则默认为2.0.5 */
                C('ecs_version', 'v2.7.3');
            }

            //限定语言项
            $lang_array = array('zh_cn', 'zh_tw', 'en_us');
            if (empty($arr['lang']) || !in_array($arr['lang'], $lang_array)) {
                $arr['lang'] = 'zh_cn'; // 默认语言为简体中文
            }

            if (empty($arr['integrate_code'])) {
                $arr['integrate_code'] = 'ecshop'; // 默认的会员整合插件为 ecshop
            }
			
            write_static_cache('touch_shop_config', $arr);
        } else {
            $arr = $data;
        }
        $config = array();
        foreach ($arr AS $key=>$vo) {
            $config[strtoupper($key)] = $vo;
        }
        
        return $config;
    }

    /**
     * 获取邮件模板
     * @access  public
     * @param:  $tpl_name[string]       模板代码
     * @return array
     */
    function get_mail_template($tpl_name) {
        $sql = 'SELECT template_subject, is_html, template_content FROM ' . $this->pre . "mail_templates WHERE template_code = '$tpl_name'";
        return $this->row($sql);
    }

}
