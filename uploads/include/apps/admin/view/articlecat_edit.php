{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('edit')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['cat_name']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[cat_name]' maxlength="20" value='{$cat['cat_name']}' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['parent_cat']}</td>
          <td><div class="col-md-4">
              <select name="data[parent_id]" onchange="catChanged()" {if $disabled }disabled="disabled"{/if} >
                <option value="0">{$lang['cat_top']}</option>
                
                 {$cat_select}
                 
              </select>
            </div></td>
        </tr>
        <tr>
          <td>{$lang['sort_order']}:</td>
          <td><div class="col-md-4">
              <input type="text" name='data[sort_order]' {if $cat['sort_order']}value='{$cat['sort_order']}'{else} value="50"{/if} size="15" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['cat_keywords']}</td>
          <td><div class="col-md-4">
            <input type="text" name="data[keywords]" maxlength="60" value="{$cat['keywords']}" size="50"  />
            <button type="button" class="btn btn-xs btn-info">Info</button></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><div class="alert alert-info" role="alert" style="padding:5px 15px;margin:0 15px;">{$lang['notice_keywords']}</div></td>
        </tr>
        <tr>
          <td>{$lang['cat_desc']}</td>
          <td><div class="col-md-4">
              <textarea  name="data[cat_desc]" cols="60" rows="4">{$cat['cat_desc']}</textarea>
            </div></td>
        </tr>
        <tr>
          <td colspan="2" align="center"><br />
            <input type="submit" class="button" value="{$lang['button_submit']}" />
            <input type="reset" class="button" value="{$lang['button_reset']}" /></td>
        </tr>
      </table>
      <input type="hidden" name="cat_id" value="{$cat['cat_id']}" />
    </form>
  </div>
</div>
<script type="text/javascript">
//提示信息显示隐藏
$(function(){
	$(".btn-info").click(function(){
		if($(this).hasClass("info_hide")){
			$(this).removeClass("info_hide").closest("tr").siblings("tr").find(".alert-info").show();
		}
		else{
			$(this).addClass("info_hide").closest("tr").siblings("tr").find(".alert-info").hide();
		}
	});
})
</script> 
{include file="pagefooter"}