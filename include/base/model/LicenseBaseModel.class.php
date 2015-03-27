<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：LicenseBaseModel.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：ECTOUCH 许可证基础模型
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class LicenseBaseModel extends Model {

    /**
     * 获得网店 license 信息
     *
     * @access  public
     * @param   integer     $size
     *
     * @return  array
     */
    function get_shop_license() {
        // 取出网店 license
        $sql = "SELECT code, value
            FROM " . $this->pre .
                "touch_shop_config WHERE code IN ('certificate_id', 'token', 'certi')
            LIMIT 0,3";
        $license_info = $this->query($sql);
        $license_info = is_array($license_info) ? $license_info : array();
        $license = array();
        foreach ($license_info as $value) {
            $license[$value['code']] = $value['value'];
        }
        return $license;
    }

    /**
     * 功能：license 注册
     *
     * @param   array     $certi_added    配置信息补充数组 array_key 登录信息的key；array_key => array_value；
     * @return  array     $return_array['flag'] = reg_succ、reg_fail、reg_ping_fail；
     *                    $return_array['request']；
     */
    function license_reg($certi_added = '') {
        // 登录信息配置
        $certi['certi_app'] = ''; // 证书方法
        $certi['app_id'] = 'ectouch_free'; // 说明客户端来源
        $certi['app_instance_id'] = ''; // 应用服务ID
        $certi['version'] = LICENSE_VERSION; // license接口版本号
        $certi['shop_version'] = VERSION . '#' . RELEASE; // 网店软件版本号
        $certi['certi_url'] = sprintf(__URL__); // 网店URL
        $certi['certi_session'] = ECTouch::sess()->get_session_id(); // 网店SESSION标识
        $certi['certi_validate_url'] = sprintf(__URL__ . url('api/certi')); // 网店提供于官方反查接口
        $certi['format'] = 'json'; // 官方返回数据格式
        $certi['certificate_id'] = ''; // 网店证书ID
        // 标识
        $certi_back['succ'] = 'succ';
        $certi_back['fail'] = 'fail';
        // return 返回数组
        $return_array = array();

        if (is_array($certi_added)) {
            foreach ($certi_added as $key => $value) {
                $certi[$key] = $value;
            }
        }

        // 取出网店 license
        $license = model('LicenseBase')->get_shop_license();

        // 注册
        $certi['certi_app'] = 'certi.reg'; // 证书方法
        $certi['certi_ac'] = make_shopex_ac($certi, ''); // 网店验证字符串
        unset($certi['certificate_id']);

        $request_arr = exchange_shop_license($certi, $license);
        if (is_array($request_arr) && $request_arr['res'] == $certi_back['succ']) {
            // 注册信息入库
            $data['value'] = $request_arr['info']['certificate_id'];
            $condition['code'] = 'certificate_id';
            model('Base')->table('touch_shop_config')->data($data)->where($condition)->update();

            $data['value'] = $request_arr['info']['token'];
            $condition['code'] = 'certificate_id';
            model('Base')->table('touch_shop_config')->data($data)->where($condition)->update();
            $return_array['flag'] = 'reg_succ';
            $return_array['request'] = $request_arr;
            clear_cache_files();
        } elseif (is_array($request_arr) && $request_arr['res'] == $certi_back['fail']) {
            $return_array['flag'] = 'reg_fail';
            $return_array['request'] = $request_arr;
        } else {
            $return_array['flag'] = 'reg_ping_fail';
            $return_array['request'] = array('res' => 'fail');
        }

        return $return_array;
    }

}
