-- ------------------------------------------------------------
-- author:carson wang
-- date:20160419
-- description:增加pc端url，支持mobile目录绑定域名
-- ------------------------------------------------------------

-- 隐藏wap配置项
UPDATE `{pre}shop_config` SET `type` = 'hidden' WHERE `code` = 'wap';

-- 增加pc端url配置
INSERT INTO `{pre}shop_config` (`parent_id`, `code`, `type`, `store_range`, `store_dir`, `value`, `sort_order`) VALUES
(1, 'shop_url', 'text', '', '', '', '1');
