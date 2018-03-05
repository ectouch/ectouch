
{include file="pageheader"}

<div class="panel-heading">
  <form class="form-inline" name="searchForm" action="{url('Languages/index')}" method="post" onSubmit="return validate();">
    <select name="lang_file" class="form-control">
    {loop $lang_arr $key $langs}
      <option value="{$key}" {if $lang_file == $key}selected{/if}>{$langs}</option>
    {/loop}
    </select>
    &nbsp;&nbsp;&nbsp;
    <input type="text" placeholder="{$lang[enter_keywords]}" name="keyword" size="30" class="form-control"/>
    <input type="submit" value="{$lang[button_search]}" class="btn btn-primary" />
  </form>
</div>
<div>
<ul style="padding:0; margin: 0; list-style-type:none; color: #CC0000;">
  {if $file_attr}
  <li style="border: 1px solid #CC0000; background: #FFFFCC; padding: 10px; margin-bottom: 5px;" >{$file_attr}</li>
  {/if}
</ul>
</div>

<form method="post" action="{url('Languages/edit')}">
<table id="list-table" class="table table-bordered table-striped table-hover">
{if $language_arr}
  <tr class="active">
    <th>{$lang[item_name]}</th>
    <th>{$lang[item_value]}</th>
  </tr>
 {loop $language_arr $key $list}
  <tr>
    <td width="30%" align="left" class="first-cell">
    {$list[item_id]}<input type="hidden" name="item_id[]" value="{$list[item_id]}" />
    </td>
    <td width="70%">
      <input type='text' name='item_content[]' value='{$list[item_content]}' size='100' />
    </td>
  </tr>
  <tr style="display:none">
    <td width="30%" align="left" class="first-cell">&nbsp;</td>
    <td width="70%">
      <input type="hidden" name="item[]" value="{$list[item]}" size="100"/>
    </td>
  </tr>
  {/loop}
  <tr>
    <td colspan="2">
      <div align="center">
        <input type="hidden" name="file_path" value="{$file_path}" />
        <input type="hidden" name="keyword" value="{$keyword}" />
        <input type="submit" value="{$lang[edit_button]}" class="btn btn-primary"  />
&nbsp;&nbsp;&nbsp;
        <input type="reset" value="{$lang[reset_button]}" class="btn btn-default" />
      </div></td>
    </tr>
  <tr>
    <td colspan="2"><strong>{$lang[notice_edit]}</strong></td>
    </tr>
  {/if}

</table>
</form>


<script type="text/javascript" >

onload = function()
{
    document.forms['searchForm'].elements['keyword'].focus();
}

function validate()
{
    var frm     = document.forms['searchForm'];
    var keyword = frm.elements['keyword'].value;
    if (keyword.length == 0)
    {
        alert(keyword_empty_error);

        return false;
    }
    return true;
}

</script>

{include file="pagefooter"}