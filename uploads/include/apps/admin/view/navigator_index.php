{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center">{$lang['item_name']}</th>
    <th class="text-center" width="20%">{$lang['item_ifshow']}</th>
    <th class="text-center" width="20%">{$lang['item_opennew']}</th>
    <th class="text-center" width="20%">{$lang['item_vieworder']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['id']))}">{$vo['name']}</a></td>
    <td class="text-center"><img src="__ASSETS__/images/{if $vo['ifshow'] == '1'}yes{else}no{/if}.gif" /></td>
    <td class="text-center"><img src="__ASSETS__/images/{if $vo['opennew'] == '1'}yes{else}no{/if}.gif" /></td>
    <td class="text-center">{$vo['vieworder']}</td>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['id']))}">{$lang['edit']}</a> | <a href="{url('del', array('id'=>$vo['id']))}">{$lang['remove']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pagefooter"}