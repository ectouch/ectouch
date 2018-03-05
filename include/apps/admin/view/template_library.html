
{include file="pageheader"}

<form method="post" class="form-inline" onsubmit="return false">
<div class="panel-heading">
  {$lang['select_library']}
  <select id="selLib" onchange="loadLibrary()" class="form-control">
  {loop $libraries $key $libs}
      <option value="{$key}" {if $curr_library == $key}selected{/if}>{$libs}</option>
  {/loop}
  </select>
</div>

<div class="main-div">
  <div class="button-div ">
    <textarea id="libContent" rows="25" style="font-family: Courier New; width:99%">{$library_html}</textarea>
  </div>
  <div class="button-div" style="text-align:center;">
    <input type="button"  value="{$lang[button_submit]}" class="btn btn-primary" onclick="updateLibrary()" />
    <input type="button" value="{$lang[button_restore]}" class="btn btn-default" onclick="restoreLibrary()" />
  </div>
</div>
</form>
<script type="text/javascript" >

var currLibrary = "{$curr_library}";
var content = '';
onload = function()
{
    document.getElementById('libContent').focus();
}

/**
 * 载入库项目内容
 */
function loadLibrary()
{
    curContent = document.getElementById('libContent').value;

    if (content != curContent && content != '')
    {
        if (!confirm(save_confirm))
        {
            return;
        }
    }

    selLib  = document.getElementById('selLib');
    currLib = selLib.options[selLib.selectedIndex].value;

    $.get('{url("load_library_ajax")}', 'lib=' + currLib, function(result){
      loadLibraryResponse(result);
    }, 'JSON');
    //Ajax.call('template.php?is_ajax=1&act=load_library', 'lib='+ currLib, loadLibraryResponse, "GET", "JSON");
}

/**
 * 还原库项目内容
 */
function restoreLibrary()
{
    selLib  = document.getElementById('selLib');
    currLib = selLib.options[selLib.selectedIndex].value;

    $.get('{url("restore_library")}', 'lib=' + currLib, function(result){
      loadLibraryResponse(result);
    }, 'JSON');
    //Ajax.call('template.php?is_ajax=1&act=restore_library', "lib="+currLib, loadLibraryResponse, "GET", "JSON");
}

/**
 * 处理载入的反馈信息
 */
function loadLibraryResponse(result)
{
    if (result.error == 0)
    {
        document.getElementById('libContent').value=result.content;
    }

    if (result.message.length > 0)
    {
      alert(result.message);
    }
}

/**
 * 更新库项目内容
 */
function updateLibrary()
{
    var selLib  = document.getElementById('selLib');
    var currLib = selLib.options[selLib.selectedIndex].value;
    var content = document.getElementById('libContent').value;

    if (checkhtml(content) == "")
    {
        alert(empty_content);
        return;
    }
    // content = encodeURIComponent(content);
    $.post("{url('update_library')}", {lib:currLib, html:content}, function(result){
      updateLibraryResponse(result);
    }, 'json');

    //Ajax.call('template.php?act=update_library&is_ajax=1', 'lib=' + currLib + "&html=" + encodeURIComponent(content), updateLibraryResponse, "POST", "JSON");
}

/**
 * 处理更新的反馈信息
 */
function updateLibraryResponse(result)
{
  if (result.message.length > 0)
  {
    alert(result.message);
  }
}

function checkhtml(content)
{
  if (typeof(content) == "string")
  {
    return content.replace(/^\s*|\s*$/g, "");
  }
  else
  {
    return content;
  }
}

</script>
{include file="pagefooter"}