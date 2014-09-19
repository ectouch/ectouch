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
              <input type='text' name='data[cat_name]' disabled="disabled" maxlength="20" value='{$cat['cat_name']}' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['show_in_touch']}</td>
          <td><div class="col-md-2">
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-primary btn-sm{if $cat['is_mobile'] == '1'} active{/if}">
                  <input type="radio" name="data[is_mobile]" id="ifshow1" value="1"{if $cat['is_mobile'] == '1'} checked{/if}>
                  {$lang['yes']} </label>
                <label class="btn btn-primary btn-sm{if $cat['is_mobile'] == '0'} active{/if}">
                  <input type="radio" name="data[is_mobile]" id="ifshow2" value="0"{if $cat['is_mobile'] == '0'} checked{/if}>
                  {$lang['no']} </label>
              </div>
            </div></td>
        </tr>
        <tr>
          <td></td>
          <td><div class="col-md-4">
              <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
      <input type="hidden" name="cat_id" value="{$cat['cat_id']}" />
    </form>
  </div>
</div>
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">var ue = UE.getEditor('container');</script>
{include file="pagefooter"}