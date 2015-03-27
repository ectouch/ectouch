<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：yto.php
 * ----------------------------------------------------------------------------
 * 功能描述：圆通速递插件的语言文件
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */

/* 访问控制 */
if (! defined('IN_ECTOUCH')) {
    die('Deny Access');
}

$_LANG['yto'] = '圆通速递';
$_LANG['yto_desc'] = '上海圆通物流（速递）有限公司经过多年的网络快速发展，在中国速递行业中一直处于领先地位。为了能更好的发展国际快件市场，加快与国际市场的接轨，强化圆通的整体实力，圆通已在东南亚、欧美、中东、北美洲、非洲等许多城市运作国际快件业务';
$_LANG['item_fee'] = '单件商品费用：';
$_LANG['base_fee'] = '首重费用';
$_LANG['step_fee'] = '续重费用';
$_LANG['shipping_print'] = '<table border="0" cellspacing="0" cellpadding="0" style="width:18.6cm; height:11.3cm;">
  <tr>
    <td valign="top" style="width:7.2cm;">
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:1.5cm;">&nbsp;</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="4" style="height:0.4cm;"></td>
        </tr>
      <tr>
        <td style="width:1cm; height:1cm;">&nbsp;</td>
        <td style="width:2.4cm;">{$shop_name}</td>
        <td style="width:1cm; height:1cm;">&nbsp;</td>
        <td>{$city}</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="6" style="height:0.4cm;">&nbsp;</td>
        </tr>
      <tr>
        <td style="width:1.6cm;">{$province}</td>
        <td style="width:0.8cm; height:0.6cm;"></td>
        <td style="width:1.6cm;">{$city}</td>
        <td style="width:0.8cm;"></td>
        <td style="width:1.6cm;">&nbsp;</td>
        <td style="width:0.8cm;"></td>
      </tr>
      <tr>
        <td colspan="6" style="height:1cm;">{$shop_address}</td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.4cm;"></td>
      </tr>
      <tr>
        <td style="height:1cm;">{$shop_name}</td>
      </tr>
    </table>
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">{$service_phone}</td>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">&nbsp;</td>
      </tr>
    </table>
    </td>
    <td valign="top" style="width:7.2cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td style="height:2.9cm;">&nbsp;</td>
    </tr>
  </table>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:1cm; width:1.2cm;">&nbsp;</td>
    <td style="width:2.4cm;">{$order.consignee}</td>
    <td style="height:1cm; width:1.2cm;">&nbsp;</td>
    <td style="width:2.4cm;">{$order.region}</td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="6" style="height:0.4cm;">&nbsp;</td>
        </tr>
      <tr>
        <td style="width:1.6cm;">{$province}</td>
        <td style="width:0.8cm; height:0.6cm;"></td>
        <td style="width:1.6cm;">{$city}</td>
        <td style="width:0.8cm;"></td>
        <td style="width:1.6cm;"></td>
        <td style="width:0.8cm;"></td>
      </tr>
      <tr>
        <td colspan="6" style="height:1cm;">{$order.address}</td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.4cm;"></td>
      </tr>
      <tr>
        <td style="height:1cm;">{$order.consignee}</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;"></td>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">{$order.mobile}</td>
      </tr>
    </table>

    </td>
    <td valign="top" style="width:4.2cm;">&nbsp;

    </td>
  </tr>
</table>';