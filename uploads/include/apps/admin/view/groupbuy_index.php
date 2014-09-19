{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center">{$lang['record_id']}</th>
    <th class="text-center">{$lang['goods_name']}</th>
    <th class="text-center">{$lang['current_status']}</th>
    <th class="text-center">{$lang['end_date']}</th>
    <th class="text-center">{$lang['deposit']}</th>
    <th class="text-center">{$lang['restrict_amount']}</th>
    <th class="text-center">{$lang['valid_goods']}</th>
    <th class="text-center">{$lang['valid_order']}</th>
    <th class="text-center">{$lang['current_price']}</th>
    <th class="text-center">{$lang['handler']}</th>
  </tr>
  {loop $list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['act_id']))}">{$vo['act_id']}</a></td>
    <td class="text-center">{$vo['goods_name']}</td>
    <td class="text-left">{$vo['cur_status']}</td>
    <td class="text-center">{$vo['end_time']}</td>
    <td class="text-center">{$vo['deposit']}</td>
    <td class="text-center">{$vo['restrict_amount']}</td>
    <td class="text-center">{$vo['valid_goods']}</td>
    <td class="text-center">{$vo['valid_order']}</td>
    <td class="text-center">{$vo['cur_price']}</td>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['act_id']))}">{$lang['edit']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}