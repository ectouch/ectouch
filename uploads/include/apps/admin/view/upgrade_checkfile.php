{include file="pageheader"}

<div class="row" style="margin:0">
  <div class="ectouch-mb5">
  	<a href="{url('index')}" class="btn btn-info">{$lang['upgrade']}</a>
    <a href="{url('checkfile')}" class="btn btn-success">{$lang['checkfile']}</a>
  </div>
</div>

<p class="bg-success" style="padding: 10px;line-height: 24px;">{$lang['check_file_notice']}</p>

<table class="table table-hover ectouch-table" style="border:1px #ddd solid; margin-bottom:10px;">
  <tr class="active">
      <th>{$lang['modifyedfile']}</th>
      <th width="33%">{$lang['lostfile']}</th>
      <th width="33%">{$lang['unknowfile']}</th>
  </tr>
  <tr>
      <td><?php echo count($diff);?></td>
      <td><?php echo count($lostfile);?></td>
      <td><?php echo count($unknowfile);?></td>
  </tr>
</table>

<?php if(!empty($diff)) {?>
<table class="table table-hover ectouch-table" style="border:1px #ddd solid; margin-bottom:10px;">
  <tr class="warning">
    <th>{$lang['modifyedfile']}</th>
    <th width="20%">{$lang['lastmodifytime']}</th>
    <th width="15%">{$lang['filesize']}</th>
  </tr>
<?php
	foreach($diff as $k=>$v) {
?>
  <tr>
	<td><?php echo base64_decode($k)?></td>
	<td><?php echo date("Y-m-d H:i:s", filemtime(base64_decode($k)))?></td>
	<td><?php echo byte_format(filesize(base64_decode($k)))?></td>
  </tr>
<?php
	}
?>
</table>
<?php }?>

<?php if(!empty($unknowfile)) {?>
<table class="table table-hover ectouch-table" style="border:1px #ddd solid; margin-bottom:10px;">
  <tr class="info">
    <th>{$lang['unknowfile']}</th>
    <th width="20%">{$lang['lastmodifytime']}</th>
    <th width="15%">{$lang['filesize']}</th>
  </tr>
<?php
	foreach($unknowfile as $k=>$v) {
?>
  <tr>
	<td><?php echo base64_decode($v)?></td>
	<td><?php echo date("Y-m-d H:i:s", filectime(base64_decode($v)))?></td>
	<td><?php echo byte_format(filesize(base64_decode($v)))?></td>
  </tr>
<?php
	}
?>
</table>
<?php }?>

<?php if(!empty($lostfile)) {?>
<table class="table table-hover ectouch-table" style="border:1px #ddd solid; margin-bottom:10px;">
  <tr class="danger">
	<th>{$lang['lostfile']}</th>
  </tr>
<?php
	foreach($lostfile as $k) {
?>
    <tr>
		<td><?php echo base64_decode($k)?></td>
    </tr>
<?php
	}
?>
</table>
<?php }?>

{include file="pagefooter"}