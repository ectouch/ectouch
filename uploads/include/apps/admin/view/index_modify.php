{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('modify')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['user_name']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[user_name]' maxlength="20" value='{$info['user_name']}' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['email']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[email]' maxlength="20" value='{$info['email']}' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['old_password']}:</td>
          <td><div class="col-md-4">
              <input type="password" name='old_password' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['new_password']}:</td>
          <td><div class="col-md-4">
              <input type="password" name='password' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['pwd_confirm']}:</td>
          <td><div class="col-md-4">
              <input type="password" name='pwd_confirm' class="form-control input-sm" />
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
    </form>
  </div>
</div>
{include file="pagefooter"}