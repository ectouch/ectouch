--
-- 表的结构 `ecs_touch_activity`
--

DROP TABLE IF EXISTS `ecs_touch_activity`;

CREATE TABLE IF NOT EXISTS `ecs_touch_activity` (
  `act_id` int(10) NOT NULL,
  `act_banner` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ecs_touch_activity`
--

INSERT INTO `ecs_touch_activity` (`act_id`, `act_banner`) VALUES
(1, 'http://d8.yihaodianimg.com/N00/M08/9A/E0/CgMBmVPPNHqAXRx1AACfU7I8J8857100.jpg'),
(2, 'http://img13.360buyimg.com/cms/jfs/t184/306/2459217274/143660/f83440cc/53d20980N337e37e1.jpg!q35.jpg'),
(3, 'http://img10.360buyimg.com/cms/jfs/t157/153/2494576813/117819/654b2854/53d20fe1N246c1e4a.jpg!q35.jpg'),
(4, 'http://img11.360buyimg.com/cms/jfs/t145/259/2655815990/39930/9c6e8426/53d772c7N26e261e4.jpg!q35.jpg'),
(5, 'data/attached/banner_image/ea725b8e67518d05c5cd80e5fed8d04f.jpg');

--
-- 表的结构 `ecs_touch_ad`
--

DROP TABLE IF EXISTS `ecs_touch_ad`;

CREATE TABLE IF NOT EXISTS `ecs_touch_ad` (
  `ad_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `position_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `media_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `ad_name` varchar(255) NOT NULL DEFAULT '',
  `ad_link` varchar(255) NOT NULL DEFAULT '',
  `ad_code` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(11) NOT NULL DEFAULT '0',
  `link_man` varchar(60) NOT NULL DEFAULT '',
  `link_email` varchar(60) NOT NULL DEFAULT '',
  `link_phone` varchar(60) NOT NULL DEFAULT '',
  `click_count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `enabled` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ad_id`),
  KEY `position_id` (`position_id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- 转存表中的数据 `ecs_touch_ad`
--

INSERT INTO `ecs_touch_ad` (`ad_id`, `position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(1, 1, 0, '1', '', 'http://ectouch.cn/data/assets/images/ectouch_ad1.jpg', 1396339200, 1525161600, '', '', '', 0, 1),
(2, 1, 0, '2', '', 'http://ectouch.cn/data/assets/images/ectouch_ad2.jpg', 1396339200, 1525161600, '', '', '', 0, 1);

--
-- 表的结构 `ecs_touch_ad_position`
--

DROP TABLE IF EXISTS `ecs_touch_ad_position`;

CREATE TABLE IF NOT EXISTS `ecs_touch_ad_position` (
  `position_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `position_name` varchar(255) NOT NULL DEFAULT '',
  `ad_width` smallint(5) unsigned NOT NULL DEFAULT '0',
  `ad_height` smallint(5) unsigned NOT NULL DEFAULT '0',
  `position_desc` varchar(255) NOT NULL DEFAULT '',
  `position_style` text NOT NULL,
  PRIMARY KEY (`position_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `ecs_touch_ad_position`
--

INSERT INTO `ecs_touch_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(1, '首页Banner广告位', 360, 168, '', '<ul>\r\n{foreach from=$ads item=ad}\r\n  <li>{$ad}</li>\r\n{/foreach}\r\n</ul>\r\n');

--
-- 表的结构 `ecs_touch_article_cat`
--

DROP TABLE IF EXISTS `ecs_touch_article_cat`;

CREATE TABLE IF NOT EXISTS `ecs_touch_article_cat` (
  `cat_id` smallint(8) NOT NULL,
  `is_mobile` tinyint(2) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='文章扩展表';

--
-- 表的结构 `ecs_touch_brand`
--

DROP TABLE IF EXISTS `ecs_touch_brand`;

CREATE TABLE IF NOT EXISTS `ecs_touch_brand` (
  `brand_id` int(8) NOT NULL,
  `brand_banner` varchar(255) NOT NULL COMMENT '广告位',
  `brand_content` text NOT NULL COMMENT '详情'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_category`
--

DROP TABLE IF EXISTS `ecs_touch_category`;

CREATE TABLE IF NOT EXISTS `ecs_touch_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(10) unsigned DEFAULT NULL COMMENT '外键',
  `cat_image` varchar(255) DEFAULT NULL COMMENT '分类ICO图标',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=735 ;

--
-- 表的结构 `ecs_touch_feedback`
--

DROP TABLE IF EXISTS `ecs_touch_feedback`;

CREATE TABLE IF NOT EXISTS `ecs_touch_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` mediumint(8) unsigned NOT NULL,
  `msg_read` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=19 ;

-- --------------------------------------------------------

--
-- 表的结构 `ecs_touch_goods`
--
DROP TABLE IF EXISTS `ecs_touch_goods`;

CREATE TABLE IF NOT EXISTS `ecs_touch_goods` (
  `goods_id` int(10) unsigned DEFAULT NULL COMMENT '外键',
  `sales_volume` int(10) unsigned DEFAULT NULL COMMENT '销量统计'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_goods_activity`
--

DROP TABLE IF EXISTS `ecs_touch_goods_activity`;

CREATE TABLE IF NOT EXISTS `ecs_touch_goods_activity` (
  `act_id` int(10) DEFAULT NULL,
  `act_banner` varchar(255) DEFAULT NULL,
  `sales_count` int(10) DEFAULT NULL,
  `click_num` int(10) NOT NULL DEFAULT '0',
  `cur_price` decimal(10,2) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_nav`
--

DROP TABLE IF EXISTS `ecs_touch_nav`;

CREATE TABLE IF NOT EXISTS `ecs_touch_nav` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `ctype` varchar(10) DEFAULT NULL,
  `cid` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `ifshow` tinyint(1) NOT NULL,
  `vieworder` tinyint(1) NOT NULL,
  `opennew` tinyint(1) NOT NULL,
  `url` varchar(255) NOT NULL,
  `pic` varchar(255) NOT NULL,
  `type` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `ifshow` (`ifshow`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

--
-- 转存表中的数据 `ecs_touch_nav`
--

INSERT INTO `ecs_touch_nav` (`id`, `ctype`, `cid`, `name`, `ifshow`, `vieworder`, `opennew`, `url`, `pic`, `type`) VALUES
(1, '', 0, '全部分类', 1, 0, 0, 'index.php?c=category&amp;a=all', 'data/attached/nav/c78d6a6b9b1ef0f58760c1c26fcd1ed3.png', 'middle'),
(2, '', 0, '我的订单', 1, 0, 0, 'index.php?m=default&amp;c=user&amp;a=order_list', 'data/attached/nav/fa2f3f5df8dfa7ca5740515d47d2381d.png', 'middle'),
(3, '', 0, '最新团购', 1, 0, 0, 'index.php?m=default&amp;c=groupbuy', 'data/attached/nav/0c71ca825682cad7222266a7e7cd052a.png', 'middle'),
(4, '', 0, '促销活动', 1, 0, 0, 'index.php?m=default&amp;c=activity', 'data/attached/nav/ca0a1b9798403546b2b3b9ccdc7a3fcc.png', 'middle'),
(5, '', 0, '热门搜索', 1, 0, 0, '#', 'data/attached/nav/cea26362c1ba667b48ff26d5a7f06fe1.png', 'middle'),
(6, '', 0, '品牌街', 1, 0, 0, 'index.php?m=default&amp;c=brand', 'data/attached/nav/d4eaf0ee8bf517fb0d7368481a170df0.png', 'middle'),
(7, '', 0, '个人中心', 1, 0, 0, 'index.php?m=default&amp;c=user', 'data/attached/nav/b0e139079e5c5d052f1f05d0f400dfa1.png', 'middle'),
(8, '', 0, '购物车', 1, 0, 0, 'index.php?m=default&amp;c=flow&amp;a=cart', 'data/attached/nav/3355eb17b4a10ab97ac5bf4d5ceef3ef.png', 'middle');

--
-- 表的结构 `ecs_touch_payment`
--

DROP TABLE IF EXISTS `ecs_touch_payment`;

CREATE TABLE IF NOT EXISTS `ecs_touch_payment` (
  `pay_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `pay_code` varchar(20) NOT NULL DEFAULT '',
  `pay_name` varchar(120) NOT NULL DEFAULT '',
  `pay_fee` varchar(10) NOT NULL DEFAULT '0',
  `pay_desc` text NOT NULL,
  `pay_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `pay_config` text NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_cod` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_online` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pay_id`),
  UNIQUE KEY `pay_code` (`pay_code`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- 转存表中的数据 `ecs_touch_payment`
--

INSERT INTO `ecs_touch_payment` (`pay_id`, `pay_code`, `pay_name`, `pay_fee`, `pay_desc`, `pay_order`, `pay_config`, `enabled`, `is_cod`, `is_online`) VALUES
(1, 'balance', '余额支付', '0', '&lt;p&gt;使用帐户余额支付。只有会员才能使用，通过设置信用额度，可以透支。&lt;/p&gt;', 0, 'a:0:{}', 1, 0, 1),
(2, 'alipay_wap', '支付宝（手机版）', '0', '&lt;p&gt;支付宝（手机版）网站(www.alipay.com) 是国内先进的网上支付平台。&lt;/p&gt;', 0, 'a:4:{i:0;a:3:{s:4:"name";s:14:"alipay_account";s:4:"type";s:4:"text";s:5:"value";s:15:"pay@ecmoban.com";}i:1;a:3:{s:4:"name";s:10:"alipay_key";s:4:"type";s:4:"text";s:5:"value";s:32:"m2rgo80qokhtn14vjp2b9zjjbjfqiyce";}i:2;a:3:{s:4:"name";s:14:"alipay_partner";s:4:"type";s:4:"text";s:5:"value";s:16:"2088011125312949";}i:3;a:3:{s:4:"name";s:10:"relate_pay";s:4:"type";s:6:"select";s:5:"value";s:1:"0";}}', 1, 0, 1);

--
-- 表的结构 `ecs_touch_shop_config`
--

DROP TABLE IF EXISTS `ecs_touch_shop_config`;

CREATE TABLE IF NOT EXISTS `ecs_touch_shop_config` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `code` varchar(30) NOT NULL DEFAULT '',
  `type` varchar(10) NOT NULL DEFAULT '',
  `store_range` varchar(255) NOT NULL DEFAULT '',
  `store_dir` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=908 ;

--
-- 转存表中的数据 `ecs_touch_shop_config`
--

INSERT INTO `ecs_touch_shop_config` SELECT * FROM `ecs_shop_config`;

INSERT INTO `ecs_touch_shop_config` (parent_id, code, type, store_range, store_dir, value, sort_order)VALUES (1, 'shop_url', 'text', '', '', '', 1);

--
-- 表的结构 `ecs_touch_user`
--

DROP TABLE IF EXISTS `ecs_touch_auth`;

CREATE TABLE IF NOT EXISTS `ecs_touch_auth` (
  `id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `auth_config` varchar(255) NOT NULL,
  `from` varchar(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='登录插件' AUTO_INCREMENT=11 ;

--
-- 表的结构 `ecs_touch_user_info`
--

DROP TABLE IF EXISTS `ecs_touch_user_info`;

CREATE TABLE IF NOT EXISTS `ecs_touch_user_info` (
  `user_id` int(10) NOT NULL,
  `aite_id` varchar(200) NOT NULL COMMENT '标识'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户信息';