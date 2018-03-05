-- ------------------------------------------------------------
-- author:xiao rui
-- date:20160607
-- description:新增评论关联数据表
-- ------------------------------------------------------------
--
-- 表的结构 `{pre}term_relationship`
--

CREATE TABLE IF NOT EXISTS `{pre}term_relationship` (
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
