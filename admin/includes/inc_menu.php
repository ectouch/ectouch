<?php

/**
 * 管理中心菜单数组
 */

if (!defined('IN_ECTOUCH')) {
    die('Hacking attempt');
}

// 控制台
$modules['00_menu_dashboard']['01_dashboard_welcome'] = 'index.php?act=main';
//$modules['00_menu_dashboard']['02_favorite'] = 'index.php?act=main';
//$modules['00_menu_dashboard']['03_notepad'] = 'index.php?act=main';
//$modules['00_menu_dashboard']['04_calc'] = 'index.php?act=calculator';

// 设置
$modules['01_menu_system']['01_shop_config']             = 'shop_config.php?act=list_edit';
$modules['01_menu_system']['02_payment_list']            = 'payment.php?act=list';
$modules['01_menu_system']['03_shipping_list']           = 'shipping.php?act=list';
$modules['01_menu_system']['05_area_list']               = 'area_manage.php?act=list';
$modules['01_menu_system']['admin_logs']             = 'admin_logs.php?act=list';
$modules['01_menu_system']['admin_list']             = 'privilege.php?act=list';
//$modules['01_menu_system']['admin_role']             = 'role.php?act=list';
//$modules['01_menu_system']['agency_list']            = 'agency.php?act=list';
//$modules['01_menu_system']['suppliers_list']         = 'suppliers.php?act=list';
//$modules['01_menu_system']['shop_authorized']             = 'license.php?act=list_edit';
$modules['01_menu_system']['11_sms_list']            = 'sms.php?act=list';
$modules['01_menu_system']['shop_authorized']             = '../index.php?m=admin&a=license';

// 商品
$modules['02_menu_goods']['01_goods_list']       = 'goods.php?act=list'; // 商品列表
$modules['02_menu_goods']['02_goods_add']        = 'goods.php?act=add';          // 添加商品
$modules['02_menu_goods']['03_category_list']    = 'category.php?act=list';
$modules['02_menu_goods']['05_comment_manage']   = 'comment_manage.php?act=list';
$modules['02_menu_goods']['06_goods_brand_list'] = 'brand.php?act=list';
$modules['02_menu_goods']['08_goods_type']       = 'goods_type.php?act=manage';
$modules['02_menu_goods']['11_goods_trash']      = 'goods.php?act=trash';        // 商品回收站
//$modules['02_menu_goods']['12_batch_pic']        = 'picture_batch.php';
//$modules['02_menu_goods']['13_batch_add']        = 'goods_batch.php?act=add';    // 商品批量上传
//$modules['02_menu_goods']['14_goods_export']     = 'goods_export.php?act=goods_export';
//$modules['02_menu_goods']['15_batch_edit']       = 'goods_batch.php?act=select'; // 商品批量修改
//$modules['02_menu_goods']['16_goods_script']     = 'gen_goods_script.php?act=setup';
//$modules['02_menu_goods']['17_tag_manage']       = 'tag_manage.php?act=list';
$modules['02_menu_goods']['50_virtual_card_list']   = 'goods.php?act=list&extension_code=virtual_card';
$modules['02_menu_goods']['51_virtual_card_add']    = 'goods.php?act=add&extension_code=virtual_card';
$modules['02_menu_goods']['52_virtual_card_change'] = 'virtual_card.php?act=change';
//$modules['02_menu_goods']['goods_auto']             = 'goods_auto.php?act=list';

// 订单
$modules['03_menu_order']['02_order_list']               = 'order.php?act=list';
$modules['03_menu_order']['03_order_query']              = 'order.php?act=order_query';
$modules['03_menu_order']['04_merge_order']              = 'order.php?act=merge';
$modules['03_menu_order']['05_edit_order_print']         = 'order.php?act=templates';
$modules['03_menu_order']['06_undispose_booking']        = 'goods_booking.php?act=list_all';
//$modules['04_order']['07_repay_application']        = 'repay.php?act=list_all';
$modules['03_menu_order']['08_add_order']                = 'order.php?act=add';
$modules['03_menu_order']['09_delivery_order']           = 'order.php?act=delivery_list';
// $modules['03_menu_order']['10_back_order']               = 'order.php?act=back_list';
//by ECTouch Leah
$modules['03_menu_order']['13_service_type']               = 'aftermarket_type.php?act=service_type';//by Leah
$modules['03_menu_order']['11_back_cause']               = 'aftermarket_cause.php?act=back_cause_list'; //by Leah
$modules['03_menu_order']['12_back_apply']               = 'aftermarket.php?act=aftermarket_list';//by Leah

// 会员
$modules['04_menu_members']['03_users_list']             = 'users.php?act=list';
$modules['04_menu_members']['04_users_add']              = 'users.php?act=add';
$modules['04_menu_members']['05_user_rank_list']         = 'user_rank.php?act=list';
//$modules['04_menu_members']['06_list_integrate']         = 'integrate.php?act=list';
$modules['04_menu_members']['08_unreply_msg']            = 'user_msg.php?act=list_all';
$modules['04_menu_members']['09_user_account']           = 'user_account.php?act=list';
//$modules['04_menu_members']['10_user_account_manage']    = 'user_account_manage.php?act=list';
//$modules['04_menu_members']['ucenter_setup']              = 'integrate.php?act=setup&code=ucenter';
//$modules['04_menu_members']['021_reg_fields']             = 'reg_fields.php?act=list';

// 内容
$modules['05_menu_content']['ad_position']                = 'ad_position.php?act=list';
$modules['05_menu_content']['ad_list']                    = 'ads.php?act=list';

$modules['05_menu_content']['03_article_list']           = 'article.php?act=list';
$modules['05_menu_content']['02_articlecat_list']        = 'articlecat.php?act=list';
//$modules['05_menu_content']['vote_list']                 = 'vote.php?act=list';
//$modules['05_menu_content']['article_auto']              = 'article_auto.php?act=list';
//$modules['05_menu_content']['shop_help']                 = 'shophelp.php?act=list_cat';
//$modules['05_menu_content']['shop_info']                 = 'shopinfo.php?act=list';
//$modules['05_menu_content']['08_friendlink_list']         = 'friend_link.php?act=list';

// 营销
//$modules['06_menu_promotion']['02_snatch_list']          = 'snatch.php?act=list';
$modules['06_menu_promotion']['04_bonustype_list']       = 'bonus.php?act=list';
// $modules['06_menu_promotion']['06_pack_list']            = 'pack.php?act=list';
// $modules['06_menu_promotion']['07_card_list']            = 'card.php?act=list';
$modules['06_menu_promotion']['08_group_buy']            = 'group_buy.php?act=list';
$modules['06_menu_promotion']['09_topic']                = 'topic.php?act=list';
//$modules['06_menu_promotion']['10_auction']              = 'auction.php?act=list';
$modules['06_menu_promotion']['12_favourable']           = 'favourable.php?act=list';
//$modules['06_menu_promotion']['13_wholesale']            = 'wholesale.php?act=list';
//$modules['06_menu_promotion']['14_package_list']         = 'package.php?act=list';
//$modules['03_promotion']['ebao_commend']            = 'ebao_commend.php?act=list';
$modules['06_menu_promotion']['15_exchange_goods']       = 'exchange_goods.php?act=list';


// 报表
// $modules['07_menu_stats']['flow_stats']                  = 'flow_stats.php?act=view';
//$modules['07_menu_stats']['searchengine_stats']          = 'searchengine_stats.php?act=view';
//$modules['07_menu_stats']['z_clicks_stats']              = 'adsense.php?act=list';
$modules['07_menu_stats']['report_guest']                = 'guest_stats.php?act=list';
$modules['07_menu_stats']['report_order']                = 'order_stats.php?act=list';
$modules['07_menu_stats']['report_sell']                 = 'sale_general.php?act=list';
$modules['07_menu_stats']['sale_list']                   = 'sale_list.php?act=list';
$modules['07_menu_stats']['sell_stats']                  = 'sale_order.php?act=goods_num';
$modules['07_menu_stats']['report_users']                = 'users_order.php?act=order_num';
//$modules['07_menu_stats']['visit_buy_per']               = 'visit_sold.php?act=list';

// 模板
$modules['08_menu_template']['02_template_select']       = 'template.php?act=list';
// $modules['08_menu_template']['03_template_setup']        = 'template.php?act=setup';
// $modules['08_menu_template']['04_template_library']      = 'template.php?act=library';
$modules['08_menu_template']['05_edit_languages']        = 'edit_languages.php?act=list';
// $modules['08_menu_template']['06_template_backup']       = 'template.php?act=backup_setting';
$modules['08_menu_template']['mail_template_manage']     = 'mail_template.php?act=list';

// 数据库
$modules['09_menu_backup']['02_db_manage']               = 'database.php?act=backup';
$modules['09_menu_backup']['03_db_optimize']             = 'database.php?act=optimize';
$modules['09_menu_backup']['04_sql_query']               = 'sql.php?act=main';
//$modules['09_menu_backup']['05_synchronous']             = 'integrate.php?act=sync';
//$modules['09_menu_backup']['convert']                    = 'convert.php?act=main';

// 工具箱
//$modules['10_menu_tools']['02_sms_my_info']                = 'sms.php?act=display_my_info';
//$modules['10_menu_tools']['03_sms_send']                   = 'sms.php?act=display_send_ui';
//$modules['10_menu_tools']['04_sms_sign']                   = 'sms.php?act=sms_sign';
//$modules['10_menu_tools']['04_sms_charge']                 = 'sms.php?act=display_charge_ui';
//$modules['10_menu_tools']['05_sms_send_history']           = 'sms.php?act=display_send_history_ui';
//$modules['10_menu_tools']['06_sms_charge_history']         = 'sms.php?act=display_charge_history_ui';

//$modules['10_menu_tools']['email_list']           = 'email_list.php?act=list';
//$modules['10_menu_tools']['magazine_list']        = 'magazine_list.php?act=list';
//$modules['10_menu_tools']['attention_list']       = 'attention_list.php?act=list';
$modules['10_menu_tools']['04_view_sendlist']        = 'view_sendlist.php?act=list';

$modules['10_menu_tools']['03_mail_settings']           = 'shop_config.php?act=mail_settings';
//$modules['10_menu_tools']['06_plugins']                 = 'plugins.php?act=list';
//$modules['10_menu_tools']['07_cron_schcron']            = 'cron.php?act=list';
//$modules['10_menu_tools']['sitemap']                    = 'sitemap.php';
//$modules['10_menu_tools']['check_file_priv']            = 'check_file_priv.php?act=check';
$modules['10_menu_tools']['05_captcha_manage']             = 'captcha_manage.php?act=main';
//$modules['10_menu_tools']['flashplay']                  = 'flashplay.php?act=list';
//$modules['10_menu_tools']['navigator']                  = 'navigator.php?act=list';
//$modules['10_menu_tools']['file_check']                 = 'filecheck.php';
//$modules['10_menu_tools']['fckfile_manage']             = 'fckfile_manage.php?act=list';

$modules['10_menu_tools']['01_navigator']                 = '../index.php?m=admin&c=navigator';
$modules['10_menu_tools']['02_authorization']             = '../index.php?m=admin&c=authorization';
$modules['10_menu_tools']['99_upgrade']                 = '../index.php?m=admin&c=upgrade';
$modules['10_menu_tools']['06_affiliate']                       = 'affiliate.php?act=list';
$modules['10_menu_tools']['07_affiliate_ck']                    = 'affiliate_ck.php?act=list';


// 微信通
$modules['12_menu_wechat']['01_wechat_config'] = '../index.php?m=admin&c=wechat';
$modules['12_menu_wechat']['02_wechat_masssend'] = '../index.php?m=admin&c=wechat&a=mass_message';
$modules['12_menu_wechat']['03_wechat_autoreply'] = '../index.php?m=admin&c=wechat&a=reply_subscribe';
$modules['12_menu_wechat']['04_wechat_selfmenu'] = '../index.php?m=admin&c=wechat&a=menu_list';
$modules['12_menu_wechat']['05_wechat_tmplmsg'] = '../index.php?m=admin&c=wechat&a=template_massage_list';
$modules['12_menu_wechat']['06_wechat_contactmanage'] = '../index.php?m=admin&c=wechat&a=subscribe_list';
$modules['12_menu_wechat']['07_wechat_appmsg'] = '../index.php?m=admin&c=wechat&a=article';
$modules['12_menu_wechat']['08_wechat_qrcode'] = '../index.php?m=admin&c=wechat&a=share_list';
$modules['12_menu_wechat']['09_wechat_extends'] = '../index.php?m=admin&c=Extend&a=index';
// $modules['12_menu_wechat']['10_wechat_remind'] = '../index.php?m=admin&c=wechat&a=remind';
$modules['12_menu_wechat']['11_wechat_customer'] = '../index.php?m=admin&c=wechat&a=customer_service';
