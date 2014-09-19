{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center" width="20%">{$lang['brand_name']}</th>
    <th class="text-center" width="20%">{$lang['site_url']}</th>
    <th class="text-center">{$lang['brand_desc']}</th>
    <th class="text-center" width="10%">{$lang['sort_order']}</th>
    <th class="text-center" width="10%">{$lang['is_show']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['brand_id']))}">{$vo['brand_name']}</a></td>
    <td class="text-center">{$vo['site_url']}</td>
    <td class="text-left">{php echo text_in($vo['brand_desc']);}</td>
    <td class="text-center">{$vo['sort_order']}</td>
    <td class="text-center"><img src="__ASSETS__/images/{if $vo['is_show'] == '1'}yes{else}no{/if}.gif" /></td>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['brand_id']))}">{$lang['edit']}</a> | <a href="{url('del', array('id'=>$vo['brand_id']))}">{$lang['remove']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}