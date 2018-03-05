-- ------------------------------------------------------------
-- author: xiaorui
-- date:20170714
-- description:订单表增加纳税人识别号字段
-- ------------------------------------------------------------
--
-- 字段 `inv_text_id`
--

ALTER TABLE  `{pre}order_info` ADD  `inv_text_id` varchar(120) NOT NULL DEFAULT '' AFTER `inv_content`;

