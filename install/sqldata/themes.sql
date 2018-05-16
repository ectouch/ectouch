--
-- 转存表中的数据 `ecs_touch_ad_position`
--
DELETE FROM `ecs_ad_position` WHERE `position_id` = 256;
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(256, '手机端首页主题精选广告位', 360, 168, '', '{foreach from=$ads item=ad name=ads}{if $smarty.foreach.ads.iteration % 2 == 0}<li class="fl">{else}<li class="fr">{/if}{$ad}</li>{/foreach}');

DELETE FROM `ecs_ad` WHERE `position_id` = 256;
INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(256, 0, '1', '', 'index-theme-icon1.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon2.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon3.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon4.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon5.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon6.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon7.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon8.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon9.gif', 1396339200, 1625161600, '', '', '', 0, 1),
(256, 0, '1', '', 'index-theme-icon10.gif', 1396339200, 1625161600, '', '', '', 0, 1);

INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(257, '手机端首页限时促销广告位1', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(258, '手机端首页限时促销广告位2', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(259, '手机端首页热门活动广告位1', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(260, '手机端首页热门活动广告位2', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(261, '手机端首页精品推荐广告位1', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(262, '手机端首页精品推荐广告位2', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(263, '手机端首页品牌街广告位1', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');
INSERT INTO `ecs_ad_position` (`position_id`, `position_name`, `ad_width`, `ad_height`, `position_desc`, `position_style`) VALUES
(264, '手机端首页品牌街广告位2', 360, 168, '', '{foreach from=$ads item=ad name=ads}{$ad}{/foreach}');

INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(257, 0, '1', '', 'index_ads_1.jpg', 1396339200, 1625161600, '', '', '', 0, 1);
INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(258, 0, '1', '', 'index_ads_2.jpg', 1396339200, 1625161600, '', '', '', 0, 1),
(258, 0, '1', '', 'index_ads_3.jpg', 1396339200, 1625161600, '', '', '', 0, 1);

INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(259, 0, '1', '', 'index_ads_4.jpg', 1396339200, 1625161600, '', '', '', 0, 1),
(259, 0, '1', '', 'index_ads_5.jpg', 1396339200, 1625161600, '', '', '', 0, 1);
INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(260, 0, '1', '', 'index_ads_6.jpg', 1396339200, 1625161600, '', '', '', 0, 1);

INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(261, 0, '1', '', 'index_ads_7.jpg', 1396339200, 1625161600, '', '', '', 0, 1);
INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(262, 0, '1', '', 'index_ads_8.jpg', 1396339200, 1625161600, '', '', '', 0, 1),
(262, 0, '1', '', 'index_ads_9.jpg', 1396339200, 1625161600, '', '', '', 0, 1);

INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(263, 0, '1', '', 'index_ads_10.jpg', 1396339200, 1625161600, '', '', '', 0, 1),
(263, 0, '1', '', 'index_ads_11.jpg', 1396339200, 1625161600, '', '', '', 0, 1);
INSERT INTO `ecs_ad` (`position_id`, `media_type`, `ad_name`, `ad_link`, `ad_code`, `start_time`, `end_time`, `link_man`, `link_email`, `link_phone`, `click_count`, `enabled`) VALUES
(264, 0, '1', '', 'index_ads_12.jpg', 1396339200, 1625161600, '', '', '', 0, 1);

-- ----------------------------
-- Records of ecs_attribute
-- ----------------------------
INSERT INTO `ecs_attribute` VALUES ('1', '1', '内存', '1', '1', '32GB\r\n128GB\r\n256GB', '0', '0', '0', '0');
INSERT INTO `ecs_attribute` VALUES ('2', '1', '颜色', '1', '1', '磨砂黑\r\n星空灰', '0', '0', '0', '0');


-- ----------------------------
-- Records of ecs_category
-- ----------------------------
INSERT INTO `ecs_category` VALUES ('1', '电脑', '', '', '0', '50', '', '', '0', '', '1', '0', '0');
INSERT INTO `ecs_category` VALUES ('2', '鼠标', '', '', '1', '50', '', '', '0', '', '1', '0', '0');
INSERT INTO `ecs_category` VALUES ('3', '耳机', '', '', '1', '50', '', '', '0', '', '1', '0', '0');
INSERT INTO `ecs_category` VALUES ('4', '通讯', '', '', '0', '50', '', '', '0', '', '1', '0', '0');
INSERT INTO `ecs_category` VALUES ('5', '手机', '', '', '4', '50', '', '', '0', '', '1', '0', '0');
INSERT INTO `ecs_category` VALUES ('6', '智能手表', '', '', '4', '50', '', '', '0', '', '1', '0', '0');

-- ----------------------------
-- Records of ecs_goods
-- ----------------------------
INSERT INTO `ecs_goods` VALUES ('1', '2', 'ECS000000', '雷蛇鼠标', '+', '2', '0', '', '100', '0.000', '105.60', '88.00', '0.00', '0', '0', '1', '雷蛇 鼠标', '', '', 'data/attached/images/201805/thumb_img/1_thumb_G_1526429499181.jpg', 'data/attached/images/201805/goods_img/1_G_1526429499336.jpg', 'data/attached/images/201805/source_img/1_G_1526429499247.jpg', '1', '', '1', '1', '0', '0', '1526429499', '100', '0', '1', '0', '0', '0', '0', '1526429526', '0', '', '-1', '-1', '0', null, '0');
INSERT INTO `ecs_goods` VALUES ('2', '3', 'ECS000002', '雷蛇游戏耳机', '+', '1', '0', '', '1000', '0.000', '153.60', '128.00', '99.00', '1494316800', '1592121600', '1', '耳机 游戏', '', '', 'data/attached/images/201805/thumb_img/2_thumb_G_1526429627326.jpg', 'data/attached/images/201805/goods_img/2_G_1526429627021.jpg', 'data/attached/images/201805/source_img/2_G_1526429627024.jpg', '1', '', '1', '1', '0', '1', '1526429627', '100', '0', '1', '1', '0', '1', '0', '1526429645', '0', '', '-1', '-1', '0', null, '0');
INSERT INTO `ecs_goods` VALUES ('3', '5', 'ECS000003', 'iphone 7', '+', '1', '0', '', '5600', '0.000', '5865.59', '4888.00', '0.00', '0', '0', '1', 'iphone 手机', '', '', 'data/attached/images/201805/thumb_img/3_thumb_G_1526429781740.jpg', 'data/attached/images/201805/goods_img/3_G_1526429781255.jpg', 'data/attached/images/201805/source_img/3_G_1526429781223.jpg', '1', '', '1', '1', '0', '48', '1526429781', '100', '0', '1', '0', '0', '0', '0', '1526429920', '1', '', '-1', '-1', '0', null, '0');
INSERT INTO `ecs_goods` VALUES ('4', '6', 'ECS000004', 'watch', '+', '0', '0', '', '100', '0.000', '3945.60', '3288.00', '0.00', '0', '0', '1', 'iwatch 手表', '', '', 'data/attached/images/201805/thumb_img/4_thumb_G_1526430080529.jpg', 'data/attached/images/201805/goods_img/4_G_1526430080347.jpg', 'data/attached/images/201805/source_img/4_G_1526430080432.jpg', '1', '', '1', '1', '0', '32', '1526430080', '100', '0', '1', '0', '0', '0', '0', '1526430080', '0', '', '-1', '-1', '0', null, '0');

-- ----------------------------
-- Records of ecs_goods_attr
-- ----------------------------
INSERT INTO `ecs_goods_attr` VALUES ('1', '3', '1', '32GB', '0');
INSERT INTO `ecs_goods_attr` VALUES ('2', '3', '1', '128GB', '800');
INSERT INTO `ecs_goods_attr` VALUES ('3', '3', '1', '256GB', '1700');
INSERT INTO `ecs_goods_attr` VALUES ('4', '3', '2', '磨砂黑', '0');
INSERT INTO `ecs_goods_attr` VALUES ('5', '3', '2', '星空灰', '0');

-- ----------------------------
-- Records of ecs_goods_gallery
-- ----------------------------
INSERT INTO `ecs_goods_gallery` VALUES ('1', '1', 'data/attached/images/201805/goods_img/1_P_1526429499914.jpg', '', 'data/attached/images/201805/thumb_img/1_thumb_P_1526429499459.jpg', 'data/attached/images/201805/source_img/1_P_1526429499037.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('2', '1', 'data/attached/images/201805/goods_img/1_P_1526429499043.jpg', '', 'data/attached/images/201805/thumb_img/1_thumb_P_1526429499023.jpg', 'data/attached/images/201805/source_img/1_P_1526429499978.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('3', '1', 'data/attached/images/201805/goods_img/1_P_1526429499143.jpg', '', 'data/attached/images/201805/thumb_img/1_thumb_P_1526429499798.jpg', 'data/attached/images/201805/source_img/1_P_1526429499268.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('4', '2', 'data/attached/images/201805/goods_img/2_P_1526429627669.jpg', '', 'data/attached/images/201805/thumb_img/2_thumb_P_1526429627131.jpg', 'data/attached/images/201805/source_img/2_P_1526429627827.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('5', '2', 'data/attached/images/201805/goods_img/2_P_1526429627998.jpg', '', 'data/attached/images/201805/thumb_img/2_thumb_P_1526429627043.jpg', 'data/attached/images/201805/source_img/2_P_1526429627481.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('6', '2', 'data/attached/images/201805/goods_img/2_P_1526429627409.jpg', '', 'data/attached/images/201805/thumb_img/2_thumb_P_1526429627758.jpg', 'data/attached/images/201805/source_img/2_P_1526429627916.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('7', '3', 'data/attached/images/201805/goods_img/3_P_1526429781603.jpg', '', 'data/attached/images/201805/thumb_img/3_thumb_P_1526429781735.jpg', 'data/attached/images/201805/source_img/3_P_1526429781534.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('8', '3', 'data/attached/images/201805/goods_img/3_P_1526429781858.jpg', '', 'data/attached/images/201805/thumb_img/3_thumb_P_1526429781010.jpg', 'data/attached/images/201805/source_img/3_P_1526429781112.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('9', '3', 'data/attached/images/201805/goods_img/3_P_1526429781458.jpg', '', 'data/attached/images/201805/thumb_img/3_thumb_P_1526429781404.jpg', 'data/attached/images/201805/source_img/3_P_1526429781634.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('10', '4', 'data/attached/images/201805/goods_img/4_P_1526430080200.jpg', '', 'data/attached/images/201805/thumb_img/4_thumb_P_1526430080054.jpg', 'data/attached/images/201805/source_img/4_P_1526430080895.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('11', '4', 'data/attached/images/201805/goods_img/4_P_1526430080457.jpg', '', 'data/attached/images/201805/thumb_img/4_thumb_P_1526430080646.jpg', 'data/attached/images/201805/source_img/4_P_1526430080544.jpg');
INSERT INTO `ecs_goods_gallery` VALUES ('12', '4', 'data/attached/images/201805/goods_img/4_P_1526430080552.jpg', '', 'data/attached/images/201805/thumb_img/4_thumb_P_1526430080774.jpg', 'data/attached/images/201805/source_img/4_P_1526430080667.jpg');

-- ----------------------------
-- Records of ecs_goods_type
-- ----------------------------
INSERT INTO `ecs_goods_type` VALUES ('1', '手机', '1', '');

-- ----------------------------
-- Records of ecs_products
-- ----------------------------
INSERT INTO `ecs_products` VALUES ('1', '3', '1|4', 'ECS000003g_p1', '100');
INSERT INTO `ecs_products` VALUES ('2', '3', '2|4', 'ECS000003g_p2', '1100');
INSERT INTO `ecs_products` VALUES ('3', '3', '3|4', 'ECS000003g_p3', '1100');
INSERT INTO `ecs_products` VALUES ('4', '3', '1|5', 'ECS000003g_p4', '1100');
INSERT INTO `ecs_products` VALUES ('5', '3', '2|5', 'ECS000003g_p5', '1100');
INSERT INTO `ecs_products` VALUES ('6', '3', '3|5', 'ECS000003g_p6', '1100');

-- ----------------------------
-- Records of ecs_touch_goods
-- ----------------------------
INSERT INTO `ecs_touch_goods` VALUES ('2', '0');

