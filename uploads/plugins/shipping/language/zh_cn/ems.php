<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：ems.php
 * ----------------------------------------------------------------------------
 * 功能描述：EMS插件的语言文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

$_LANG['ems'] = 'EMS 国内邮政特快专递';
$_LANG['ems_express_desc'] = 'EMS 国内邮政特快专递描述内容';
$_LANG['item_fee'] = '单件商品费用：';
$_LANG['base_fee'] = '500克以内费用：';
$_LANG['step_fee'] = '续重每500克或其零数的费用：';
$_LANG['shipping_print'] = '<table style="width:18.8cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:3.2cm;">&nbsp;</td>
  </tr>
</table>
<table style="width:18.8cm;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="width:8.9cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:1.6cm; height:0.7cm;">&nbsp;</td>
    <td style="width:2.3cm;">{$shop_name}</td>
    <td style="width:2cm;">&nbsp;</td>
    <td>{$service_phone}</td>
    </tr>
    <tr>
    <td colspan="4" style="height:0.7cm; padding-left:1.6cm;">{$shop_name}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.7cm;">{$shop_address}</td>
    </tr>
    <tr>
    <td colspan="3" style="width:6.3cm; height:0.5cm;"></td>
    <td>&nbsp;</td>
    </tr>
    </table>
    </td>
    <td style="width:0.4cm;"></td>
    <td style="width:9.5cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:1.6cm; height:0.7cm;">&nbsp;</td>
    <td style="width:2.3cm;">{$order.consignee}</td>
    <td style="width:2cm;">&nbsp;</td>
    <td>{$order.mobile}</td>
    </tr>
    <tr>
    <td colspan="4" style="height:0.7cm;">&nbsp;</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.7cm;">{$order.address}</td>
    </tr>
    <tr>
    <td colspan="3" style="width:6.3cm; height:0.5cm;"></td>
    <td>{$order.zipcode}</td>
    </tr>
    </table>
    </td>
  </tr>
</table>
<table style="width:18.8cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:5.1cm;">&nbsp;</td>
  </tr>
</table>';