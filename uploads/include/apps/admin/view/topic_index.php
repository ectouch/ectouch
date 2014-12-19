{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center">{$lang['record_id']}</th>
    <th class="text-center">{$lang['topic_title']}</th>
    <th class="text-center">{$lang['start_time']}</th>
    <th class="text-center">{$lang['end_time']}</th>
    <th class="text-center">{$lang['handler']}</th>
  </tr>
  {loop $topic_list $key $vo}
  <tr>
    <td class="text-center"><a href="{url('edit', array('id'=>$vo['topic_id']))}">{$vo['topic_id']}</a></td>
    <td class="text-center">{$vo['title']}</td>
    <td class="text-center">{$vo['start_time']}</td>
    <td class="text-center">{$vo['end_time']}</td>
    <td class="text-center"><a href="index.php?c=topic&a=index&id={$vo['topic_id']}" target="_blank">{$lang['view']}</a> | <a href="{url('edit', array('id'=>$vo['topic_id']))}">{$lang['edit']}</a> | <a href="{url('del', array('id'=>$vo['topic_id']))}">{$lang['remove']}</a></td>
  </tr>
  {/loop}
</table>

{include file="pageview"}

{include file="pagefooter"}