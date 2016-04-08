{include file="pageheader"}
<table cellspacing='1' cellpadding='3' id='list-table' class="table table-bordered table-striped table-hover">
  <tr>
    <th class="text-center" width="15%">{$lang['article_id']}</th>
    <th class="text-center" width="20%">{$lang['title']}</th>
    <th class="text-center" width="15%">{$lang['cat']}</th>
    <th class="text-center" width="15%">{$lang['is_open']}</th>
    <th class="text-center" width="15%">{$lang['add_time']}</th>
    <th class="text-center" width="15%">{$lang['handler']}</th>
  </tr>
  {loop $article_list $key $list}
  <tr>
    <td class="text-center"><span>{$list['article_id']}</span></td>
    <td class="text-center"><span onclick="javascript:listTable.edit(this, 'edit_title', {$list['article_id']})">{$list['title']}</span></td>
    <td class="text-center"><span><!-- {if $list['cat_id'] > 0} -->{$list['cat_name']}<!-- {else} -->{$lang['reserve']}<!-- {/if} --></span></td>
    <td class="text-center">{if $list['cat_id'] > 0}<span> <img src="__ASSETS__/images/{if $list['is_open'] == 1}yes{else}no{/if}.gif" onclick="listTable.toggle(this, 'toggle_show', {$list['article_id']})" /></span>{else}<img src="__ASSETS__/images/yes.gif" alt="yes" />{/if}</td>
    <td class="text-center""><span>{$list['date']}</span></td>
    <td class="text-center"><span><a href="{url('edit', array('id'=>$list['article_id']))}" title="{$lang['edit']}"><img src="__ASSETS__/images/icon_edit.gif" border="0" height="16" width="16" /></a>&nbsp; 
      <!-- {if $list['cat_id'] > 0} --><a href="{url('del', array('id'=>$list['article_id']))}" title="{$lang['remove']}"><img src="__ASSETS__/images/icon_drop.gif" border="0" height="16" width="16"></a><!-- {/if} --></span></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}