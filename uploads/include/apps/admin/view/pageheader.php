<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$lang['cp_home']}</title>
<link href="__PUBLIC__/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="__PUBLIC__/fancybox/jquery.fancybox.css?v=2.1.5" media="screen" />
<script src="__PUBLIC__/js/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="__PUBLIC__/fancybox/jquery.fancybox.js?v=2.1.5"></script>
<script src="__PUBLIC__/artDialog/jquery.artDialog.js?skin=aero"></script>
<script src="__PUBLIC__/artDialog/plugins/iframeTools.js"></script>
<script src="__PUBLIC__/bootstrap/js/bootstrap.min.js"></script>
<script src="__ASSETS__/js/common.js"></script>
<!--[if lt IE 9]>
<script src="__PUBLIC__/bootstrap/js/html5shiv.min.js"></script>
<script src="__PUBLIC__/bootstrap/js/respond.min.js"></script>
<![endif]-->
<style type="text/css">
body {padding:5px; font-size:12px; margin-bottom:0px; font-family:'微软雅黑'}
.ectouch-table {margin:0;}
.ectouch-table tr:first-child td {border-top:none;}
.ectouch-line {line-height: 1.8;}
.ectouch-mb5, .breadcrumb {margin-bottom:5px}
.ectouch-fs16 {font-size:16px}
.ectouch-fs18 {font-size:18px}
.btn-primary {background-color:#1b9ad5}
.panel-title {font-size:14px}
.pagination {margin-top:0}
.pagination .glyphicon {top:0}
.ectouch-mb{margin-bottom:5px;}
.clear{clear:both;}
.borderno{border:0;}
</style>
</head>

<body>
<ol class="breadcrumb">
  <li class="glyphicon glyphicon-home"></li>
  <li><a href="{url('index/welcome')}">{$lang['cp_home']}</a></li>
  {if $ur_here}
  <li class="active">{$ur_here}</li>
  {/if}
</ol>
{if $action_link}
<div class="row" style="margin:0">
	<div class="pull-right ectouch-mb5"><a href="{$action_link['href']}" class="btn btn-primary">{$action_link['text']}</a></div>
</div>
{/if}