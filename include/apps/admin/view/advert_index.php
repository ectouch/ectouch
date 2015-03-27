{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center" >{$lang['position_name']}</th>
    <th class="text-center" width="10%">{$lang['ad_width']}</th>
    <th class="text-center" width="10%">{$lang['ad_height']}</th>
    <th class="text-center" width="20%">{$lang['position_desc']}</th>
    <th class="text-center" width="20%">{$lang['handler']}</th>
  </tr>
  {loop $list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('ad_list', array('id'=>$vo['position_id']))}">{$vo['position_name']}</a></td>
    <td class="text-center">{$vo['ad_width']}</td>
    <td class="text-center">{$vo['ad_height']}</td>
    <td class="text-center">{$vo['position_desc']}</td>
    <td class="text-center"><a href="{url('ad_list', array('id'=>$vo['position_id']))}">{$lang['ad_list']}</a> | <a href="{url('edit', array('id'=>$vo['position_id']))}">{$lang['edit']}</a> | <a href="{url('del', array('id'=>$vo['position_id']))}">{$lang['remove']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}