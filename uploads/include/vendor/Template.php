<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

if (!function_exists('tpl_parse_ext')) {

    function tpl_parse_ext($template) {
        return template_ext($template);
    }

}

/**
 * 模板扩展函数
 * @param type $template
 * @return type
 */
function template_ext($template) {
    //php标签
    /*
      {php echo phpinfo();}	=>	<?php echo phpinfo(); ?>
     */
    $template = preg_replace("/\{php\s+(.+)\}/", "<?php \\1?>", $template);

    //if 标签
    /*
      {if $name==1}		=>	<?php if ($name==1){ ?>
      {elseif $name==2}	=>	<?php } elseif ($name==2){ ?>
      {else}				=>	<?php } else { ?>
      {/if}				=>	<?php } ?>
     */
    $template = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $template);
    $template = preg_replace("/\{else\}/", "<?php } else { ?>", $template);
    $template = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $template);
    $template = preg_replace("/\{\/if\}/", "<?php } ?>", $template);


    //for 标签
    /*
      {for $i=0;$i<10;$i++}	=>	<?php for($i=0;$i<10;$i++) { ?>
      {/for}					=>	<?php } ?>
     */
    $template = preg_replace("/\{for\s+(.+?)\}/", "<?php for(\\1) { ?>", $template);
    $template = preg_replace("/\{\/for\}/", "<?php } ?>", $template);

    //loop 标签
    /*
      {loop $arr $vo}			=>	<?php $n=1; if (is_array($arr) foreach($arr as $vo){ ?>
      {loop $arr $key $vo}	=>	<?php $n=1; if (is_array($array) foreach($arr as $key => $vo){ ?>
      {/loop}					=>	<?php $n++;}unset($n) ?>
     */
    $template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php \$n=1;if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $template);
    $template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php \$n=1; if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $template);
    $template = preg_replace("/\{\/loop\}/", "<?php \$n++;}unset(\$n); ?>", $template);

    //函数 标签
    /*
      {date('Y-m-d H:i:s')}	=>	<?php echo date('Y-m-d H:i:s');?>
      {$date('Y-m-d H:i:s')}	=>	<?php echo $date('Y-m-d H:i:s');?>
     */
    $template = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $template);
    $template = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $template);

    //变量/常量 标签
    /*
      {$name}	=>	<?php echo $name; ?>
      {CONSTANCE}	=> <?php echo CONSTANCE;?>
     */
    /* $template = preg_replace ( "/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $template ); */
    /* $template = preg_replace("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/es", "\$this->addquote('<?php echo \\1;?>')",$template); */
    $template = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $template);
    return $template;
}
