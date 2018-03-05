{include file="pageheader"}
<div class="panel panel-default">
	<div class="panel-heading">{$lang['wechat_num']}</div>
	<table border="0" cellpadding="0" cellspacing="0"  class="table table-bordered table-striped table-hover">
		<tr class="active">
			<th class="text-center" width="15%">{$lang['wechat_name']}</th>
			<th class="text-center" width="15%">{$lang['wechat_type']}</th>
			<th class="text-center" width="15%">{$lang['wechat_add_time']}</th>
			<th class="text-center" width="10%">{$lang['wechat_status']}</th>
			<th class="text-center" width="10%">{$lang['sort_order']}</th>
			<th class="text-center" width="35%">{$lang['handler']}</th>
		</tr>
		{loop $list $key $vo}
		<tr>
			<td class="text-center">{$vo['name']}</td>
			<td class="text-center">{if $vo['type'] == 0}{$lang['wechat_type0']}{elseif $vo['type'] == 1}{$lang['wechat_type1']}{elseif $vo['type'] == 2}{$lang['wechat_type2']}{/if}</td>
			<td class="text-center">{date('Y-m-d H:i:s', $vo['time'])}</td>
			<td class="text-center">{if $vo['status'] == 1}{$lang['wechat_open']}{else}{$lang['wechat_close']}{/if}</td>
			<td class="text-center">{$vo['sort']}</td>
			<td class="text-center" width="20%">
				<a href="{$vo['manage_url']}" class="btn btn-primary">{$lang['wechat_manage']}</a>
				<a href="{url('wechat/modify', array('id'=> $vo['id']))}" class="btn btn-primary">{$lang['edit']}</a>
				<a href="javascript:if(confirm('{$lang[drop_confirm]}')){window.location.href = '{url('wechat/delete', array('id'=> $vo['id']))}'}" class="btn btn-default">{$lang['drop']}</a>
				{if $vo['default_wx'] == 1}
				<a href="javascript:;" class="btn btn-success">默认使用</a>
				{else}
				<a href="{url('set_default', array('id'=>$vo['id']))}" class="btn btn-info">设为默认</a>
				{/if}
			</td>
		</tr>
		{/loop}
		{if empty($list)}
		<tr>
			<td colspan="6"  class="text-center">{$wechat_register}</td>
		</tr>
		{/if}
	</table>
</div>
{include file="pagefooter"}