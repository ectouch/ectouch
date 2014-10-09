{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center" width="20%">{$lang['ad_name']}</th>
    <th class="text-center" width="20%">{$lang['ad_position']}</th>
    <th class="text-center">{$lang['media_type']}</th>
    <th class="text-center" width="10%">{$lang['start_time']}</th>
    <th class="text-center" width="10%">{$lang['end_time']}</th>
    <th class="text-center" width="10%">{$lang['click_count']}</th>
    <th class="text-center" width="10%">{$lang['orders']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('ad_edit', array('id'=>$vo['ad_id']))}">{$vo['ad_name']}</a></td>
    <td class="text-center">{$vo['position_name']}</td>
    <td class="text-center">{$vo['media_type']}</td>
    <td class="text-center">{$vo['start_time']}</td>
    <td class="text-center">{$vo['end_time']}</td>
    <td class="text-center">{$vo['click_count']}</td>
    <td class="text-center">{$vo['orders']}</td>
    <td class="text-center"><a href="{url('ad_edit', array('id'=>$vo['ad_id']))}">{$lang['edit']}</a> | <a href="{url('ad_del', array('id'=>$vo['ad_id']))}">{$lang['remove']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}