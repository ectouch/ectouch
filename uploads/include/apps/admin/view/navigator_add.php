{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('add')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['item_name']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[name]' maxlength="20" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['item_url']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[url]' maxlength="100" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['item_pic']}:</td>
          <td><div class="col-md-4">
              <input type="file" name="pic" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['item_vieworder']}</td>
          <td><div class="col-md-2">
              <input type='text' name='data[vieworder]' maxlength="20" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['item_ifshow']}</td>
          <td><div class="col-md-2">
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-primary btn-sm active">
                  <input type="radio" name="data[ifshow]" id="ifshow1" value="1" checked />
                  {$lang['yes']} </label>
                <label class="btn btn-primary btn-sm">
                  <input type="radio" name="data[ifshow]" id="ifshow2" value="0" />
                  {$lang['no']} </label>
              </div>
            </div></td>
        </tr>
        <tr>
          <td>{$lang['item_opennew']}</td>
          <td><div class="col-md-2">
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-primary btn-sm active">
                  <input type="radio" name="data[opennew]" id="opennew1" value="1" checked />
                  {$lang['yes']} </label>
                <label class="btn btn-primary btn-sm">
                  <input type="radio" name="data[opennew]" id="opennew2" value="0" />
                  {$lang['no']} </label>
              </div>
            </div></td>
        </tr>
        <tr>
          <td></td>
          <td><div class="col-md-4">
              <input type="hidden" value="middle" name="data[type]" />
              <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
    </form>
  </div>
</div>
{include file="pagefooter"}