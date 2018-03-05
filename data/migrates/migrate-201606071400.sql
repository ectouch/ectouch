-- ------------------------------------------------------------
-- author:xiao rui
-- date:20160607
-- description:新增退换货插件数据表
-- ------------------------------------------------------------

--
-- 转存表中的数据 `{pre}admin_action`
--

--INSERT INTO `{pre}admin_action` (`action_id`, `parent_id`, `action_code`, `relevance`) VALUES
--(12, 0, 'service_manage', ''),
--(139, 12, 'service_type', ''),
--(140, 12, 'back_cause_list', ''),
--(141, 12, 'aftermarket_list', ''),
--(142, 12, 'aftermarket_rf_edit', ''),
--(143, 12, 'aftermarket_rc_edit', ''),
--(144, 12, 'aftermarket_ff_edit', ''),
--(145, 12, 'aftermarket_edit', '');


--
-- 表的结构 `{pre}order_return`
--
DROP TABLE IF EXISTS `{pre}order_return`;

CREATE TABLE IF NOT EXISTS `{pre}order_return` (
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
-- 表的结构 `{pre}return_action`
--
DROP TABLE IF EXISTS `{pre}return_action`;

CREATE TABLE IF NOT EXISTS `{pre}return_action` (
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
-- 表的结构 `{pre}return_cause`
--
DROP TABLE IF EXISTS `{pre}return_cause`;

CREATE TABLE IF NOT EXISTS `{pre}return_cause` (
  `cause_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `cause_name` varchar(50) NOT NULL COMMENT '退换货原因',
  `parent_id` int(11) NOT NULL COMMENT '父级id',
  `sort_order` int(10) NOT NULL COMMENT '排序',
  `is_show` tinyint(3) NOT NULL COMMENT '是否显示',
  PRIMARY KEY (`cause_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='退换货原因说明' AUTO_INCREMENT=24 ;

--
-- 转存表中的数据 `{pre}return_cause`
--
INSERT INTO `{pre}return_cause` (`cause_name`, `parent_id`, `sort_order`, `is_show`) VALUES
('颜色问题', 0, 50, 1),
('质量问题', 0, 50, 1);

--
-- 表的结构 `{pre}return_goods`
--
DROP TABLE IF EXISTS `{pre}return_goods`;

CREATE TABLE IF NOT EXISTS `{pre}return_goods` (
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
-- 表的结构 `{pre}aftermarket_attachments`
--
DROP TABLE IF EXISTS `{pre}aftermarket_attachments`;

CREATE TABLE IF NOT EXISTS `{pre}aftermarket_attachments` (
  `img_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `rec_id` mediumint(8) NOT NULL,
  `img_url` varchar(255) NOT NULL,
  `goods_id` mediumint(8) NOT NULL,
  UNIQUE KEY `img_id` (`img_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=17 ;

--
-- 表的结构 `{pre}service_type`
--
DROP TABLE IF EXISTS `{pre}service_type`;

CREATE TABLE IF NOT EXISTS `{pre}service_type` (
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
-- 转存表中的数据 `{pre}service_type`
--

INSERT INTO `{pre}service_type` (`service_id`, `service_name`, `service_desc`, `received_days`, `unreceived_days`, `is_show`, `sort_order`, `service_type`) VALUES
(1, '退货退款', '已收到货，需要退还已收到的货物1', 7, 8, 1, 9, 1),
(3, '换货', '对已收到的货物不满意，联系卖家协商换货', 7, 10, 1, 3, 3);

