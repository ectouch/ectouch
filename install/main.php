<?php
//网站域名
$site_url = trim($_POST['siteurl']);
if ($independent) {
    $username = trim($_POST['manager']);
    $password = trim($_POST['manager_pwd']);
    //网站名称
    $site_name = addslashes(trim($_POST['sitename']));

    //描述
    $seo_description = trim($_POST['sitedescription']);
    //关键词
    $seo_keywords = trim($_POST['sitekeywords']);
    //更新配置信息
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = '$site_name' WHERE code='shop_name'");
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = '$site_name' WHERE code='shop_title' ");
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = '$seo_description' WHERE code='shop_desc'");
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = '$seo_keywords' WHERE code='shop_keywords'");
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = 'default' WHERE code='template'");
    mysqli_query($conn, "UPDATE `{$dbPrefix}shop_config` SET  `value` = '".time()."' WHERE code='install_date'");
}

//插入微信菜单
$query = "INSERT INTO `{$dbPrefix}wechat_menu` (`id`, `wechat_id`, `pid`, `name`, `type`, `key`, `url`, `sort`, `status`) VALUES
(1, 1, 0, '微商城', 'click', '', '#', 1, 1),
(2, 1, 0, '微互动', 'click', '', '#', 2, 1),
(3, 1, 0, '个人中心', 'click', '', '#', 3, 1),
(4, 1, 1, '商城首页', 'view', '', '{$site_url}', 1, 1),
(5, 1, 1, '最新商品', 'click', 'news', '', 2, 1),
(6, 1, 1, '推荐商品', 'click', 'best', '', 3, 1),
(7, 1, 1, '热销商品', 'click', 'hot', '', 4, 1),
(8, 1, 2, '大转盘', 'click', 'dzp', '', 1, 1),
(9, 1, 2, '砸金蛋', 'click', 'zjd', '', 2, 1),
(10, 1, 2, '刮刮卡', 'click', 'ggk', '', 3, 1),
(11, 1, 3, '会员中心', 'view', '', '{$site_url}/?c=user', 1, 1),
(12, 1, 3, '订单查询', 'click', 'ddcx', '', 2, 1),
(13, 1, 3, '积分查询', 'click', 'jfcx', '', 3, 1),
(14, 1, 3, '签到送积分', 'click', 'sign', '', 4, 1)";
mysqli_query($conn, $query);

//读取配置文件，并替换真实配置数据
$strConfig = file_get_contents(INSTALL_PATH . $config['dbSetFile']);
$strConfig = str_replace('#DB_HOST#', $dbHost, $strConfig);
$strConfig = str_replace('#DB_NAME#', $dbName, $strConfig);
$strConfig = str_replace('#DB_USER#', $dbUser, $strConfig);
$strConfig = str_replace('#DB_PWD#', $dbPwd, $strConfig);
$strConfig = str_replace('#DB_PORT#', $dbPort, $strConfig);
$strConfig = str_replace('#DB_PREFIX#', $dbPrefix, $strConfig);
@file_put_contents(ROOT_PATH . $config['dbConfig'], $strConfig);

if ($independent) {
    //插入管理员
    $verify = rand(1000, 9999); //生成随机认证码
    $time = time();
    $ip = get_client_ip();
    $password = md5(md5($password).$verify);
    $email = trim($_POST['manager_email']);
    $query = "INSERT INTO `{$dbPrefix}admin_user` (user_name, password, ec_salt, email, add_time, last_ip, action_list, nav_list, agency_id) VALUES ('{$username}', '{$password}', '{$verify}', '{$email}', '{$time}', '{$ip}', 'all', '', 0)";
    if (mysqli_query($conn, $query)) {
        return array('status'=>2,'info'=>'成功添加管理员<br />成功写入配置文件<br>安装完成...');
    }
} else {
    return array('status'=>2,'info'=>'成功写入配置文件<br>安装完成...');
}
return array('status'=>0,'info'=>'安装失败...');
