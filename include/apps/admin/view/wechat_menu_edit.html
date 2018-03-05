{include file="wechat_header"}
<div class="panel panel-default" style="margin:0;">
    <div class="panel-heading">{$lang['menu_edit']}</div>
    <div class="panel-body">
	<form action="{url('wechat/menu_edit')}" method="post" class="form-horizontal" role="form" onSubmit="return false;">
      <table id="general-table" class="table table-hover ectouch-table">
       <tr>
          <td width="200">{$lang['menu_parent']}:</td>
          <td><div class="col-md-4">
              <select name="data[pid]" class="form-control input-sm">
              	<option value="">{$lang['menu_select']}</option>
              	{loop $top_menu $m}
              		<option value="{$m['id']}" {if $info['pid'] == $m['id']}selected{/if}>{$m['name']}</option>
              	{/loop}
              </select>
            </div></td>
        </tr>
        <tr>
          <td width="200">{$lang['menu_name']}:</td>
          <td><div class="col-md-4">
              <input type="text" name="data[name]" class="form-control" value="{$info['name']}" />
            </div></td>
        </tr>
        <tr>
          <td width="200">{$lang['menu_type']}:</td>
          <td><div class="col-md-10">
          		<div class="btn-group" data-toggle="buttons">
	              	<label class="btn btn-primary {if $info['type'] == 'click' || empty($info['type'])}active{/if} clicktype">
					  <input type="radio" name="data[type]" value="click" {if $info['type'] == 'click' || empty($info['type'])}checked{/if} />{$lang['menu_click']}
					</label>
					<label class="btn btn-primary {if $info['type'] == 'view'}active{/if} clicktype">
					  <input type="radio" name="data[type]" value="view" {if $info['type'] == 'view'}checked{/if} />{$lang['menu_view']}
					</label>
				</div>
            </div></td>
        </tr>
        <tr id="click" {if $info['type'] == 'view'}class="hidden"{/if}>
          <td width="200">{$lang['menu_keyword']}:</td>
          <td><div class="col-md-4">
              <input type="text" name="data[key]" class="form-control" value="{$info['key']}" />
            </div></td>
        </tr>
        <tr id="view" {if $info['type'] == 'click' || empty($info)}class="hidden"{/if}>
          <td width="200">{$lang['menu_url']}:</td>
          <td><div class="col-md-10">
              <input type="text" name="data[url]" class="form-control" value="{$info['url']}" />
            </div></td>
        </tr>
        <tr>
          <td width="200">{$lang['menu_show']}:</td>
          <td><div class="col-md-10">
          		<div class="btn-group" data-toggle="buttons">
	              	<label class="btn btn-primary {if $info['status'] == 1}active{/if}">
					  <input type="radio" name="data[status]" value="1" {if $info['status'] == 1}checked{/if} />{$lang['yes']}
					</label>
					<label class="btn btn-primary {if $info['status'] == 0}active{/if}">
					  <input type="radio" name="data[status]" value="0" {if $info['status'] == 0}checked{/if} />{$lang['no']}
					</label>
				</div>
            </div></td>
        </tr>
        <tr>
          <td width="200">{$lang['sort_order']}:</td>
          <td><div class="col-md-2">
              <input type="text" name="data[sort]" class="form-control" value="{$info['sort']}" />
            </div></td>
        </tr>
        <tr>
          <td width="200"></td>
          <td><div class="col-md-4">
          		<input type="hidden" name="id" value="{$info['id']}" />
				<input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              	<input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
        </table>
	</form>
</div>
</div>
<script type="text/javascript">
$(function(){
	$(".clicktype").click(function(){
		var val = $(this).find("input[type=radio]").val();
		
		if('click' == val && $("#click").hasClass("hidden")){
			$("#view").hide().addClass("hidden");
			$("#click").show().removeClass("hidden");
		}
		else if('view' == val && $("#view").hasClass("hidden")){
			$("#click").hide().addClass("hidden");
			$("#view").show().removeClass("hidden");
		}
	});
	$(".form-horizontal").submit(function(){
	    var ajax_data = $(this).serialize();
	    $.post("{url('wechat/menu_edit')}", ajax_data, function(data){
	        if(data.status > 0){
	            window.parent.location.reload();
			}
	        else{
	            alert(data.msg);
	            return false;
		    }
	    }, 'json');
	});
})
</script>
{include file="pagefooter"}