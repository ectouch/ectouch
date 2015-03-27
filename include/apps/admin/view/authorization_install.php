{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('install')}" method="post" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        {loop $info['config'] $key $vo}
        <tr>
          <td width="200">{$vo['label']}</td>
          <td><input type="text" name="cfg_value[]" maxlength="50" class="form-control input-sm" value="{$data['app_id']}" />
          <input name="cfg_name[]" type="hidden" value="{$vo['name']}" />
          <input name="cfg_type[]" type="hidden" value="{$vo['type']}" />
          <input name="cfg_label[]" type="hidden" value="{$vo['label']}" />
          </td>
        </tr>
        {/loop}
        <tr>
          <td width="200">{$lang['website_web']}</td>
          <td><div class="col-md-4"> <a href="{$info['website']}" target="_blank">{$lang['once']}</a></div></td>
        </tr>
        <tr>
          <td></td>
          <td><div class="col-md-4">
              <input type="hidden"  name="from" value="{$info['type']}" />
              <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
    </form>
  </div>
</div>
{include file="pagefooter"}