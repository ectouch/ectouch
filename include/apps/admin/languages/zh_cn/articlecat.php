<?php

/**
 * ECTouch Open Source Project
 * ============================================================================
 * Copyright (c) 2012-2014 http://ectouch.cn All rights reserved.
 * ----------------------------------------------------------------------------
 * 文件名称：articlecat.php
 * ----------------------------------------------------------------------------
 * 功能描述：文章分类管理程序语言
 * ----------------------------------------------------------------------------
 * Licensed ( http://www.ectouch.cn/docs/license.txt )
 * ----------------------------------------------------------------------------
 */
$_LANG['cat_name'] = '文章分类名称';
$_LANG['type'] = '分类类型';
$_LANG['type_name'][COMMON_CAT] = '普通分类';
$_LANG['type_name'][SYSTEM_CAT] = '系统分类';
$_LANG['type_name'][INFO_CAT] = '网店信息';
$_LANG['type_name'][UPHELP_CAT] = '帮助分类';
$_LANG['type_name'][HELP_CAT] = '网店帮助';

$_LANG['cat_keywords'] = '关键字';
$_LANG['cat_desc'] = '描述';
$_LANG['parent_cat'] = '上级分类';
$_LANG['cat_top'] = '顶级分类';
$_LANG['not_allow_add'] = '你所选分类不允许添加子分类';
$_LANG['not_allow_remove'] = '系统保留分类，不允许删除';
$_LANG['is_fullcat'] = '该分类下还有子分类，请先删除其子分类';
$_LANG['show_in_nav'] = '是否显示在导航栏';
$_LANG['show_in_touch'] = '是否在touch端显示';

$_LANG['isopen'] = '显示';
$_LANG['isclose'] = '不显示';
$_LANG['add_article'] = '添加文章';



$_LANG['articlecat_edit'] = '文章分类编辑';


/* 提示信息 */
$_LANG['catname_exist'] = '分类名已经存在';
$_LANG['parent_id_err'] = '分类名的父分类不能设置成本身或本身的子分类';
$_LANG['back_list'] = '返回分类列表';
$_LANG['continue_add'] = '继续添加新分类';
$_LANG['catadd_succed'] = '已成功添加';
$_LANG['catedit_succed'] = '分类编辑成功';
$_LANG['back_list'] = '返回分类列表';
$_LANG['continue_add'] = '继续添加分类';
$_LANG['no_catname'] = '请填入分类名';
$_LANG['edit_fail'] = '编辑失败';
$_LANG['enter_int'] = '请输入一个整数';
$_LANG['not_emptycat'] = '分类下还有文章，不允许删除非空分类';
$_LANG['drop_succeed'] = '已成功删除！';

/* 帮助信息 */
$_LANG['notice_keywords'] = '关键字为选填项，其目的在于方便外部搜索引擎搜索';
$_LANG['notice_isopen'] = '该文章分类是否显示在前台的主导航栏中。';

/* JS 语言项 */
$_LANG['js_languages']['no_catname'] = '没有输入分类的名称';
$_LANG['js_languages']['sys_hold'] = '系统保留分类，不允许添加子分类';
$_LANG['js_languages']['remove_confirm'] = '您确定要删除选定的分类吗？';
?>