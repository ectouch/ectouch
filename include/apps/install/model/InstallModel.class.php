<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：IndexModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 安装模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class InstallModel extends BaseModel {

    /**
     * 导入数据库文件
     * @param type $data
     * @param type $sqlArray
     * @return boolean
     */
    public function runSql($data, $sqlArray = array()) {
        $model = new EcModel($data);
        if (is_array($sqlArray))
            foreach ($sqlArray as $sql) {
                if (!@$model->db->query($sql)) {
                    return false;
                }
            }
        return true;
    }

    /**
     * 获取字段
     * @param unknown $data
     * @param string $_table
     * @param string $_column
     * @return boolean
     */
    public function get_column($data, $_table = '', $_column = ''){
        $model = new EcModel($data);
        $sql = "describe `" . $_table . "` `" . $_column . "`";
        $resource = $model->query($sql);
        $result = mysql_fetch_array($resource);
        if(is_array($result)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 筛选touch_shop_config字段
     * @param type $data
     * @param type $_table
     */
    public function filter_column($data, $_table = '') {
        $model = new EcModel($data);
        $column = array('shop_info', 'display', 'basic', 'goods', 'sms', 'shop_name', 'shop_title', 'shop_desc', 'shop_keywords', 'shop_logo', 'shop_reg_closed', 'shop_url','show_asynclist', 'no_picture', 'stats_code', 'register_points', 'search_keywords', 'top_number', 'history_number', 'comments_number', 'bought_goods', 'article_number', 'goods_name_length', 'goods_name_length', 'page_size', 'sort_order_type', 'sort_order_method', 'show_order_type', 'attr_related_number', 'related_goods_number', 'article_page_size', 'show_goodssn', 'show_brand', 'show_goodsweight', 'show_goodsnumber', 'show_addtime', 'goodsattr_style', 'show_marketprice', 'sms_ecmoban_user', 'sms_ecmoban_password', 'sms_shop_mobile', 'sms_order_placed', 'sms_order_payed', 'sms_signin','user_notice','template','stylename');
        $result = $model->table($_table)->field('code')->select();
        //删除touch_shop_config表
        foreach ($result as $key => $value) {
            if (!in_array($value['code'], $column)) {
                $model->table($_table)->where(array('code' => $value['code']))->delete();
            }
        }
        //设置模板主题
        $data2['value'] = 'default';
        $condition['code'] = 'template';
        $model->table($_table)->data($data2)->where($condition)->update();
		//设置logo目录
        $data3['store_dir'] = './themes/{$template}/images/';
        $condition3['code'] = 'shop_logo';
        $model->table($_table)->data($data3)->where($condition3)->update();
        //设置默认图片目录
        $data3['store_dir'] = './data/common/images/';
        $condition3['code'] = 'no_picture';
        $model->table($_table)->data($data3)->where($condition3)->update();
        return true;
    }

}
