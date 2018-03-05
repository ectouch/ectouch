-- ------------------------------------------------------------
-- author: han
-- date:20160607
-- description:店铺未审核开启插入帐户无效记录
-- ------------------------------------------------------------
--
-- 表的结构 `drp_invalid_log`
--

CREATE TABLE IF NOT EXISTS `{pre}drp_invalid_log` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `user_money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pay_points` mediumint(9) NOT NULL DEFAULT '0',
  `change_time` int(10) unsigned NOT NULL DEFAULT '0',
  `change_desc` varchar(255) NOT NULL DEFAULT '',
  `change_type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `invalid_desc`  text COMMENT '无效说明',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;