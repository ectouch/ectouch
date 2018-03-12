<?php

/**
 * 系统文件检测
 */

define('IN_ECTOUCH', true);

require(dirname(__FILE__) . '/includes/init.php');

if ($_REQUEST['act']== 'check') {
    /* 检查权限 */
    admin_priv('file_priv');

    /* 要检查目录文件列表 */
    $goods_img_dir = array();
    $folder = opendir(ROOT_PATH . 'images');
    while ($dir = readdir($folder)) {
        if (is_dir(ROOT_PATH . IMAGE_DIR . '/' . $dir) && preg_match('/^[0-9]{6}$/', $dir)) {
            $goods_img_dir[] = IMAGE_DIR . '/' . $dir;
        }
    }
    closedir($folder);

    $dir[]                     = ADMIN_PATH;
    $dir[]                     = 'cert';

    $dir_subdir['images'][]    = IMAGE_DIR;
    $dir_subdir['images'][]    = IMAGE_DIR . '/upload';
    $dir_subdir['images'][]    = IMAGE_DIR . '/upload/Image';
    $dir_subdir['images'][]    = IMAGE_DIR . '/upload/File';
    $dir_subdir['images'][]    = IMAGE_DIR . '/upload/Flash';
    $dir_subdir['images'][]    = IMAGE_DIR . '/upload/Media';
    $dir_subdir['data'][]      = DATA_DIR;
    $dir_subdir['data'][]      = DATA_DIR . '/attached/afficheimg';
    $dir_subdir['data'][]      = DATA_DIR . '/attached/brandlogo';
    $dir_subdir['data'][]      = DATA_DIR . '/attached/cardimg';
    $dir_subdir['data'][]      = DATA_DIR . '/attached/feedbackimg';
    $dir_subdir['data'][]      = DATA_DIR . '/attached/packimg';
    $dir_subdir['data'][]      = DATA_DIR . '/attached/sqldata';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/backup';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/caches';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/compiled';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/compiled/admin';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/query_caches';
    $dir_subdir['temp'][]      = DATA_DIR . '/attached/static_caches';

    /* 将商品图片目录加入检查范围 */
    foreach ($goods_img_dir as $val) {
        $dir_subdir['images'][] = $val;
    }

    $tpl = 'themes/'.$_CFG['template'].'/';



    $list = array();

    /* 检查目录 */
    foreach ($dir as $val) {
        $mark = file_mode_info(ROOT_PATH .$val);
        $list[] = array('item' => $val.$_LANG['dir'], 'r' => $mark&1, 'w' => $mark&2, 'm' => $mark&4);
    }

    /* 检查目录及子目录 */
    $keys = array_unique(array_keys($dir_subdir));
    foreach ($keys as $key) {
        $err_msg = array();
        $mark = check_file_in_array($dir_subdir[$key], $err_msg);
        $list[] = array('item' => $key.$_LANG['dir_subdir'], 'r' => $mark&1, 'w' => $mark&2, 'm' => $mark&4, 'err_msg' => $err_msg);
    }

    /* 检查当前模板可写性 */
    $dwt = @opendir(ROOT_PATH .$tpl);
    $tpl_file = array(); //获取要检查的文件
    while ($file = readdir($dwt)) {
        if (is_file(ROOT_PATH .$tpl .$file) && strrpos($file, '.dwt') > 0) {
            $tpl_file[] = $tpl .$file;
        }
    }
    @closedir($dwt);
    $lib = @opendir(ROOT_PATH .$tpl.'library/');
    while ($file = readdir($lib)) {
        if (is_file(ROOT_PATH .$tpl.'library/'.$file) && strrpos($file, '.lbi') > 0) {
            $tpl_file[] = $tpl . 'library/' . $file;
        }
    }
    @closedir($lib);

    /* 开始检查 */
    $err_msg = array();
    $mark = check_file_in_array($tpl_file, $err_msg);
    $list[] = array('item' => $tpl.$_LANG['tpl_file'], 'r' => $mark&1, 'w' => $mark & 2, 'm' => $mark & 4, 'err_msg' => $err_msg);

    /* 检查smarty的缓存目录和编译目录及image目录是否有执行rename()函数的权限 */
    $tpl_list   = array();
    $tpl_dirs[] = 'temp/caches';
    $tpl_dirs[] = 'temp/compiled';
    $tpl_dirs[] = 'temp/compiled/admin';

    /* 将商品图片目录加入检查范围 */
    foreach ($goods_img_dir as $val) {
        $tpl_dirs[] = $val;
    }

    foreach ($tpl_dirs as $dir) {
        $mask = file_mode_info(ROOT_PATH .$dir);

        if (($mask & 4) > 0) {
            /* 之前已经检查过修改权限，只有有修改权限才检查rename权限 */
            if (($mask & 8) < 1) {
                $tpl_list[] = $dir;
            }
        }
    }
    $tpl_msg = implode(', ', $tpl_list);
    $smarty->assign('ur_here', $_LANG['check_file_priv']);
    $smarty->assign('list', $list);
    $smarty->assign('tpl_msg', $tpl_msg);
    $smarty->display('file_priv.html');
}

/**
 *  检查数组中目录权限
 *
 * @access  public
 * @param   array    $arr           要检查的文件列表数组
 * @param   array    $err_msg       错误信息回馈数组
 *
 * @return int       $mark          文件权限掩码
 */
function check_file_in_array($arr, &$err_msg)
{
    $read   = true;
    $writen = true;
    $modify = true;
    foreach ($arr as $val) {
        $mark = file_mode_info(ROOT_PATH . $val);
        if (($mark & 1) < 1) {
            $read = false;
            $err_msg['r'][] = $val;
        }
        if (($mark & 2) <1) {
            $writen = false;
            $err_msg['w'][] = $val;
        }
        if (($mark & 4) <1) {
            $modify = false;
            $err_msg['m'][] = $val;
        }
    }

    $mark = 0;
    if ($read) {
        $mark ^= 1;
    }
    if ($writen) {
        $mark ^= 2;
    }
    if ($modify) {
        $mark ^= 4;
    }

    return $mark;
}
