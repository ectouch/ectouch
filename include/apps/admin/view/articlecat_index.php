{include file="pageheader"}
<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center" width="40%">{$lang['cat_name']}</th>
    <th class="text-center" width="20%">{$lang['cat_desc']}</th>
    <th class="text-center" width="20%">{$lang['sort_order']}</th>
    <th class="text-center" width="20%">{$lang['handler']}</th>
  </tr>
  {loop $articlecat $key $cat}
  <tr align="center" class="{$cat['level']}" id="{$cat['level']}_{$cat['cat_id']}">
    <td align="left" class="first-cell" > {if $cat['is_leaf'] <> 1} <img src="__ASSETS__/images/menu_minus.gif" id="icon_{$cat['level']}_{$cat['cat_id']}" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" onclick="rowClicked(this)" /> {else} <img src="__ASSETS__/images/menu_arrow.gif" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" /> {/if} <span><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$cat['cat_name']}</a></span></td>
    <td><!-- {if $cat['cat_desc']} -->{$cat['cat_desc']}<!-- {/if} --></td>
    <td>{$cat['sort_order']}</td>
    <td><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$lang['edit']}</a>&nbsp; 
     <a href="{url('del', array('cat_id'=>$cat['cat_id']))}" title="{$lang['remove']}">{$lang['remove']}</a></span></td>
  </tr>
  {/loop}
</table>
{include file="pagefooter"}