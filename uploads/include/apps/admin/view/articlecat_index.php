{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center">{$lang['cat_name']}</th>
    <th class="text-center" width="10%">{$lang['type']}</th>
    <th class="text-center" width="10%">{$lang['cat_desc']}</th>
    <th class="text-center" width="10%">{$lang['sort_order']}</th>
    <th class="text-center" width="10%">{$lang['show_in_touch']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $articlecat $key $cat}
  <tr align="center" class="{$cat['level']}" id="{$cat['level']}_{$cat['cat_id']}">
    <td align="left" class="first-cell" > {if $cat['is_leaf'] <> 1} <img src="__ASSETS__/images/menu_minus.gif" id="icon_{$cat['level']}_{$cat['cat_id']}" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" onclick="rowClicked(this)" /> {else} <img src="__ASSETS__/images/menu_arrow.gif" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" /> {/if} <span><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$cat['cat_name']}</a></span></td>
    <td>{$cat['type_name']}</td>
    <td><!-- {if $cat['cat_desc']} -->{$cat['cat_desc']}<!-- {/if} --></td>
    <td>{$cat['sort_order']}</td>
    <td><img src="__ASSETS__/images/{if $cat['is_mobile'] == '1'}yes{else}no{/if}.gif" onclick="listTable.toggle(this, 'toggle_show_in_nav', {$cat['cat_id']})" /></td>
    <td><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$lang['edit']}</a></td>
  </tr>
  {/loop}
</table>
{include file="pagefooter"}