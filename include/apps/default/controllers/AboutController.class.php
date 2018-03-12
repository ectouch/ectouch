<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2016 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：AboutControoller.class.php
 * ----------------------------------------------------------------------------
 * 功能描述：关于我们控制器
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class AboutController extends CommonController
{
    public function map()
    {
        $address = I('get.address', '');
        if (empty($address)) {
            $province = model('RegionBase')->get_region_name(C('SHOP_PROVINCE'));
            $city = model('RegionBase')->get_region_name(C('SHOP_CITY'));
            $address = C('CFG.SHOP_ADDRESS');
        }
        $this->assign('city', $city);
        $this->assign('address', $city . $address);
        $this->display('about_map.dwt');
    }
}
