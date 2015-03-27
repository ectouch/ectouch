{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center" width="20%">{$lang['payment_name']}</th>
    <th class="text-center" width="30%">{$lang['payment_desc']}</th>
    <th class="text-center">{$lang['version']}</th>
    <th class="text-center" width="15%">{$lang['payment_author']}</th>
    <th class="text-center" width="10%">{$lang['short_pay_fee']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $modules $key $vo}
  <tr>
    <td class="text-center">{$vo['name']}</td>
    <td class="text-center">{php echo html_out($vo['desc']);}</td>
    <td class="text-center">{$vo['version']}</td>
    <td class="text-center">{$vo['author']}</td>
    <td class="text-center">{$vo['pay_fee']}</td>
    <td class="text-center">
    	{if $vo['install'] == 1}
    	<a href="{url('edit', array('code'=>$vo['code']))}">{$lang['edit']}</a> | <a href="javascript:if(confirm('{$lang['confirm_uninstall']}')){window.location.href='{url('uninstall', array('code'=>$vo['code']))}'};">{$lang['uninstall']}</a>
    	{else}
    	<a href="{url('install', array('code'=>$vo['code']))}">{$lang['install']}</a>
    	{/if}
    </td>
  </tr>
  {/loop}
</table>
{include file="pagefooter"}