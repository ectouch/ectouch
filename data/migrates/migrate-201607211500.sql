-- ------------------------------------------------------------
-- author:carson wang
-- date:20160721
-- description:增加退换货权限
-- ------------------------------------------------------------

-- 增加退换货权限

INSERT INTO `{pre}admin_action` (`action_id`,`parent_id`, `action_code`, `relevance`) VALUES
(158, 6, 'service_type', ''),
(159, 6, 'back_cause_list', ''),
(160, 6, 'aftermarket_list', ''),
(161, 6, 'add_return_cause', '');
