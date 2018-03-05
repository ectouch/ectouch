<?php

/**
 * 帮助信息接口
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

$get_keyword = trim($_GET['al']); // 获取关键字
header("location:http://help.ectouch.cn/do.php?k=".$get_keyword."&v=".$_CFG['ecs_version']."&l=".$_CFG['lang']."&c=".CHARSET);
