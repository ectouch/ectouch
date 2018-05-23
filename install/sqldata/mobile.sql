--
-- 后台权限控制
--

-- 
INSERT INTO `ecs_admin_action` VALUES ('201', '0', 'ext_wechat', '');
-- 
INSERT INTO `ecs_admin_action` VALUES ('147', '201', 'wechat_config', '');
INSERT INTO `ecs_admin_action` VALUES ('148', '201', 'wechat_masssend', '');
INSERT INTO `ecs_admin_action` VALUES ('149', '201', 'wechat_autoreply', '');
INSERT INTO `ecs_admin_action` VALUES ('150', '201', 'wechat_selfmenu', '');
INSERT INTO `ecs_admin_action` VALUES ('151', '201', 'wechat_tmplmsg', '');
INSERT INTO `ecs_admin_action` VALUES ('152', '201', 'wechat_contactmanage', '');
INSERT INTO `ecs_admin_action` VALUES ('153', '201', 'wechat_appmsg', '');
INSERT INTO `ecs_admin_action` VALUES ('154', '201', 'wechat_qrcode', '');
INSERT INTO `ecs_admin_action` VALUES ('155', '201', 'wechat_extends', '');
INSERT INTO `ecs_admin_action` VALUES ('157', '201', 'wechat_customer', '');
INSERT INTO `ecs_admin_action` (`action_id`,`parent_id`, `action_code`, `relevance`) VALUES
('158', '6', 'service_type', ''),
('159', '6', 'back_cause_list', ''),
('160', '6', 'aftermarket_list', ''),
('161', '6', 'add_return_cause', '');
INSERT INTO `ecs_admin_action` (`action_id`, `parent_id`, `action_code`, `relevance`) VALUES
(162, 0, 'menu_tools', ''),
(163, 162, 'navigator', ''),
(164, 162, 'authorization', ''),
(165, 162, 'mail_settings', ''),
(166, 162, 'view_sendlist', ''),
(167, 162, 'captcha_manage', ''),
(168, 162, 'upgrade', '');
INSERT INTO `ecs_admin_action` (`action_id`, `parent_id`, `action_code`, `relevance`) VALUES
(169, 0, 'menu_stats', ''),
(170, 169, 'report_guest', ''),
(171, 169, 'report_order', ''),
(172, 169, 'report_sell', ''),
(173, 169, 'sale_list', ''),
(174, 169, 'sell_stats', ''),
(175, 169, 'report_users', '');
--
-- 表的结构 `ecs_touch_activity`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_activity` (
  `act_id` int(10) NOT NULL,
  `act_banner` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_topic`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_topic` (
  `topic_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `intro` text NOT NULL,
  `start_time` int(11) NOT NULL DEFAULT '0',
  `end_time` int(10) NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  `template` varchar(255) NOT NULL DEFAULT '',
  `css` text NOT NULL,
  `topic_img` varchar(255) DEFAULT NULL,
  `title_pic` varchar(255) DEFAULT NULL,
  `base_style` varchar(6) DEFAULT NULL,
  `htmls` text NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ecs_touch_ad`
--

INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(255, 0, '1', '', 'index_banner_1.png', 1396339200, 1625161600, '', '', '', 0, 1),
(255, 0, '2', '', 'index_banner_2.png', 1396339200, 1625161600, '', '', '', 0, 1),
(255, 0, '3', '', 'index_banner_3.png', 1396339200, 1625161600, '', '', '', 0, 1);

--
-- 转存表中的数据 `ecs_touch_ad_position`
--
ALTER TABLE `ecs_ad_position` MODIFY COLUMN `position_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT FIRST;
DELETE FROM `ecs_ad_position` WHERE `position_id` = 255;
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(255, '手机端首页Banner广告位', 360, 168, '', '{foreach from=$ads item=ad}<div class="swiper-slide">{$ad}</div>{/foreach}');

--
-- 表的结构 `ecs_touch_category`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cat_id` int(10) unsigned DEFAULT NULL COMMENT '外键',
  `cat_image` varchar(255) DEFAULT NULL COMMENT '分类ICO图标',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_feedback`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_feedback` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `msg_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `msg_read` int(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_goods`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_goods` (
  `goods_id` int(10) unsigned default '0' COMMENT '外键',
  `sales_volume` int(10) unsigned default '0' COMMENT '销量统计',
  UNIQUE KEY `goods_id` (`goods_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_goods_activity`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_goods_activity` (
  `act_id` int(10) DEFAULT '0',
  `act_banner` varchar(255) DEFAULT NULL,
  `sales_count` int(10) DEFAULT '0',
  `click_num` int(10) NOT NULL DEFAULT '0',
  `cur_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  UNIQUE KEY `act_id` (`act_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 表的结构 `ecs_touch_nav`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_nav` (
  `id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `ctype` varchar(10) DEFAULT NULL,
  `cid` smallint(5) unsigned DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `ifshow` tinyint(1) NOT NULL DEFAULT '0',
  `vieworder` tinyint(1) NOT NULL DEFAULT '0',
  `opennew` tinyint(1) NOT NULL DEFAULT '0',
  `url` varchar(255) NOT NULL DEFAULT '',
  `pic` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `ifshow` (`ifshow`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ecs_touch_nav`
--

INSERT INTO `ecs_touch_nav` (`id`, `ctype`, `cid`, `name`, `ifshow`, `vieworder`, `opennew`, `url`, `pic`, `type`) VALUES
(1, '', 0, '全部分类', 1, 0, 0, 'index.php?c=category&amp;a=top_all', 'themes/default/images/nav/nav_0.png', 'middle'),
(2, '', 0, '我的订单', 1, 0, 0, 'index.php?c=user&amp;a=order_list', 'themes/default/images/nav/nav_1.png', 'middle'),
(3, '', 0, '最新团购', 1, 0, 0, 'index.php?c=groupbuy', 'themes/default/images/nav/nav_4.png', 'middle'),
(4, '', 0, '促销活动', 1, 0, 0, 'index.php?c=activity', 'themes/default/images/nav/nav_3.png', 'middle'),
(5, '', 0, '积分商城', 1, 0, 0, 'index.php?c=exchange', 'themes/default/images/nav/nav_6.png', 'middle'),
(6, '', 0, '品牌街', 1, 0, 0, 'index.php?c=brand', 'themes/default/images/nav/nav_2.png', 'middle'),
(7, '', 0, '个人中心', 1, 0, 0, 'index.php?c=user', 'themes/default/images/nav/nav_5.png', 'middle'),
(8, '', 0, '购物车', 1, 0, 0, 'index.php?c=flow&amp;a=cart', 'themes/default/images/nav/nav_7.png', 'middle');

-- ----------------------------
-- 增加短信接口配置项
-- ----------------------------
INSERT INTO `ecs_shop_config` (parent_id, code, type, store_range, store_dir, value, sort_order)VALUES (8, 'sms_signin', 'select', '1,0', '', '0', 1);

--
-- 表的结构 `ecs_touch_user`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_auth` (
  `id` tinyint(2) NOT NULL AUTO_INCREMENT,
  `auth_config` text NOT NULL,
  `from` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='登录插件';

--
-- 表的结构 `ecs_touch_user_info`
--

CREATE TABLE IF NOT EXISTS `ecs_touch_user_info` (
  `user_id` int(10) NOT NULL DEFAULT '0',
  `aite_id` varchar(200) NOT NULL DEFAULT '' COMMENT '标识'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='用户信息';


CREATE TABLE IF NOT EXISTS `ecs_connect_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `connect_code` char(30) NOT NULL DEFAULT '' COMMENT '登录插件名sns_qq，sns_wechat',
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否管理员,0是会员 ,1是管理员',
  `open_id` char(64) NOT NULL DEFAULT '' COMMENT '标识',
  `refresh_token` char(64) DEFAULT '',
  `access_token` char(64) NOT NULL DEFAULT '' COMMENT 'token',
  `profile` text COMMENT '序列化用户信息',
  `create_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `expires_in` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
  `expires_at` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'token保存时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
--
-- 


--
-- 表的结构 `ecs_cart_combo`
--

CREATE TABLE IF NOT EXISTS `ecs_cart_combo` (
  `rec_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `session_id` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_sn` varchar(60) NOT NULL DEFAULT '',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `goods_name` varchar(120) NOT NULL DEFAULT '',
  `market_price` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `goods_number` smallint(5) unsigned NOT NULL DEFAULT '0',
  `goods_attr` text NOT NULL,
  `is_real` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `extension_code` varchar(30) NOT NULL DEFAULT '',
  `parent_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `rec_type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_gift` smallint(5) unsigned NOT NULL DEFAULT '0',
  `is_shipping` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `can_handsel` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `goods_attr_id` varchar(255) NOT NULL DEFAULT '',
  `group_id` varchar(255) NOT NULL ,
  PRIMARY KEY (`rec_id`),
  KEY `session_id` (`session_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- --------------------------------------------------------
--
-- 表的结构 `ecs_term_relationship`
--

CREATE TABLE IF NOT EXISTS `ecs_term_relationship` (
  `relation_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `object_type` char(30) NOT NULL DEFAULT '',
  `object_group` char(30) NOT NULL DEFAULT '',
  `object_id` int(11) NOT NULL,
  `item_key1` varchar(60) DEFAULT NULL,
  `item_value1` varchar(60) DEFAULT NULL,
  `item_key2` varchar(60) DEFAULT NULL,
  `item_value2` varchar(60) DEFAULT NULL,
  `item_key3` varchar(60) DEFAULT NULL,
  `item_value3` varchar(60) DEFAULT NULL,
  `item_key4` varchar(60) DEFAULT NULL,
  `item_value4` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`relation_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=100 ;


--
-- 表的结构 `ecs_order_return`
--
DROP TABLE IF EXISTS `ecs_order_return`;

CREATE TABLE IF NOT EXISTS `ecs_order_return` (
  `ret_id` int(10) NOT NULL AUTO_INCREMENT COMMENT '退换货id',
  `service_sn` varchar(20) NOT NULL COMMENT '服务订单编号',
  `goods_id` int(13) NOT NULL COMMENT '商品唯一id',
  `user_id` int(10) NOT NULL COMMENT '用户id',
  `rec_id` int(10) NOT NULL COMMENT '订单商品唯一id',
  `order_id` mediumint(8) NOT NULL COMMENT '所属订单号',
  `order_sn` varchar(20) NOT NULL,
  `service_id` int(2) NOT NULL,
  `cause_id` int(10) NOT NULL COMMENT '退换货原因',
  `add_time` varchar(120) NOT NULL COMMENT '插入时间',
  `should_return` decimal(10,2) NOT NULL COMMENT '应退金额',
  `actual_return` decimal(10,2) NOT NULL COMMENT '实退金额',
  `remark` text NOT NULL COMMENT '备注',
  `country` smallint(5) NOT NULL COMMENT '国家',
  `province` smallint(5) NOT NULL COMMENT '省份',
  `city` smallint(5) NOT NULL COMMENT '城市',
  `district` smallint(5) NOT NULL COMMENT '区',
  `addressee` varchar(30) NOT NULL COMMENT '收件人',
  `phone` varchar(20) NOT NULL COMMENT '联系电话',
  `address` varchar(100) NOT NULL COMMENT '详细地址',
  `zipcode` int(6) DEFAULT NULL COMMENT '邮编',
  `return_status` tinyint(3) NOT NULL COMMENT '退换货状态',
  `refund_status` tinyint(3) NOT NULL COMMENT '退款状态',
  `back_shipping_name` varchar(30) NOT NULL COMMENT '退回快递名称',
  `back_other_shipping` varchar(30) NOT NULL COMMENT '其他快递名称',
  `back_invoice_no` varchar(50) NOT NULL COMMENT '退回快递单号',
  `out_shipping_name` varchar(30) NOT NULL COMMENT '换出快递名称',
  `out_invoice_no` varchar(50) NOT NULL COMMENT '换出快递单号',
  `seller_id` int(11) NOT NULL,
  `is_check` tinyint(1) NOT NULL COMMENT '是否审核',
  `to_buyer` varchar(255) NOT NULL,
  PRIMARY KEY (`ret_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='商品退货表' AUTO_INCREMENT=4 ;


--
-- 表的结构 `ecs_return_action`
--
DROP TABLE IF EXISTS `ecs_return_action`;

CREATE TABLE IF NOT EXISTS `ecs_return_action` (
  `action_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `ret_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `action_user` varchar(30) NOT NULL DEFAULT '',
  `return_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `refund_status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_check` tinyint(2) NOT NULL COMMENT '审核是否通过',
  `action_place` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `action_note` varchar(255) NOT NULL DEFAULT '',
  `action_info` varchar(255) NOT NULL COMMENT '操作介绍',
  `log_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`action_id`),
  KEY `order_id` (`ret_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;


--
-- 表的结构 `ecs_return_cause`
--
DROP TABLE IF EXISTS `ecs_return_cause`;

CREATE TABLE IF NOT EXISTS `ecs_return_cause` (
  `cause_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cause_name` varchar(50) NOT NULL COMMENT '退换货原因',
  `parent_id` int(11) NOT NULL COMMENT '父级id',
  `sort_order` int(10) NOT NULL COMMENT '排序',
  `is_show` tinyint(3) NOT NULL COMMENT '是否显示',
  PRIMARY KEY (`cause_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='退换货原因说明' AUTO_INCREMENT=24 ;

--
-- 转存表中的数据 `ecs_return_cause`
--
INSERT INTO `ecs_return_cause` (`cause_name`, `parent_id`, `sort_order`, `is_show`) VALUES
('颜色问题', 0, 50, 1),
('质量问题', 0, 50, 1);

--
-- 表的结构 `ecs_return_goods`
--
DROP TABLE IF EXISTS `ecs_return_goods`;

CREATE TABLE IF NOT EXISTS `ecs_return_goods` (
  `rg_id` int(10) NOT NULL AUTO_INCREMENT,
  `rec_id` mediumint(8) unsigned NOT NULL,
  `goods_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `product_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `product_sn` varchar(60) DEFAULT NULL,
  `goods_name` varchar(120) DEFAULT NULL,
  `brand_name` varchar(60) DEFAULT NULL,
  `goods_sn` varchar(60) DEFAULT NULL,
  `is_real` tinyint(1) unsigned DEFAULT '0',
  `goods_attr` text,
  `goods_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `return_type` tinyint(3) NOT NULL,
  `back_num` smallint(6) NOT NULL,
  `out_num` smallint(6) NOT NULL,
  `out_attr` varchar(100) NOT NULL,
  `refund` decimal(10,2) NOT NULL,
  PRIMARY KEY (`rg_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;


--
-- 表的结构 `ecs_aftermarket_attachments`
--
DROP TABLE IF EXISTS `ecs_aftermarket_attachments`;

CREATE TABLE IF NOT EXISTS `ecs_aftermarket_attachments` (
  `img_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `rec_id` mediumint(8) NOT NULL,
  `img_url` varchar(255) NOT NULL,
  `goods_id` mediumint(8) NOT NULL,
  UNIQUE KEY `img_id` (`img_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

--
-- 表的结构 `ecs_service_type`
--
DROP TABLE IF EXISTS `ecs_service_type`;

CREATE TABLE IF NOT EXISTS `ecs_service_type` (
  `service_id` int(10) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(60) NOT NULL,
  `service_desc` text NOT NULL,
  `received_days` mediumint(4) NOT NULL,
  `unreceived_days` mediumint(6) NOT NULL,
  `is_show` tinyint(1) NOT NULL,
  `sort_order` tinyint(3) NOT NULL,
  `service_type` tinyint(1) NOT NULL COMMENT '服务类型',
  PRIMARY KEY (`service_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- 转存表中的数据 `ecs_service_type`
--

INSERT INTO `ecs_service_type` (`service_id`, `service_name`, `service_desc`, `received_days`, `unreceived_days`, `is_show`, `sort_order`, `service_type`) VALUES
(1, '退货退款', '已收到货，需要退还已收到的货物1', 7, 8, 1, 9, 1),
(3, '换货', '对已收到的货物不满意，联系卖家协商换货', 7, 10, 1, 3, 3);


ALTER TABLE `ecs_brand` ADD COLUMN `brand_banner` varchar(80)  DEFAULT '';
ALTER TABLE `ecs_goods_activity` ADD COLUMN `touch_img` VARCHAR (50)  DEFAULT '';
ALTER TABLE `ecs_favourable_activity` ADD COLUMN `touch_img` VARCHAR (50)  DEFAULT '';
ALTER TABLE `ecs_cart` ADD COLUMN `group_id` varchar(255) NOT NULL ;
ALTER TABLE `ecs_group_goods` ADD COLUMN `group_id` tinyint(3) unsigned NOT NULL DEFAULT '0';
ALTER TABLE `ecs_goods` ADD COLUMN `virtual_sales` varchar( 10 ) NOT NULL DEFAULT '0';
ALTER TABLE `ecs_order_info` ADD COLUMN `inv_text_id` varchar(120) NOT NULL DEFAULT '' AFTER `inv_content`;

CREATE TABLE IF NOT EXISTS `ecs_sms` (
  `sms_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `sms_code` varchar(20) NOT NULL DEFAULT '' COMMENT '短信code',
  `sms_name` varchar(120) NOT NULL DEFAULT '' COMMENT '短信名称',
  `sms_desc` text NOT NULL COMMENT '短信描述',
  `sms_order` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `sms_config` text NOT NULL COMMENT '短信配置',
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT '0'COMMENT '是否开启',
  PRIMARY KEY (`sms_id`),
  UNIQUE KEY `sms_code` (`sms_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- ----------------------------
-- Records of ecs_sms
-- ----------------------------
INSERT INTO `ecs_sms` VALUES ('1', 'ecmoban', '模板堂短信', '模板堂短信使用更便捷。', '1', 'a:3:{i:0;a:3:{s:4:\"name\";s:15:\"ecmoban_account\";s:4:\"type\";s:4:\"text\";s:5:\"value\";s:0:\"\";}i:1;a:3:{s:4:\"name\";s:11:\"ecmoban_key\";s:4:\"type\";s:4:\"text\";s:5:\"value\";s:0:\"\";}i:2;a:3:{s:4:\"name\";s:14:\"ecmoban_mobile\";s:4:\"type\";s:4:\"text\";s:5:\"value\";s:0:\"\";}}', '1');

