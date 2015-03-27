{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('add')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['brand_name']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[brand_name]' maxlength="20" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['site_url']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[site_url]' maxlength="100" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['brand_logo']}:</td>
          <td><div class="col-md-4">
              <input type="file" name="brand_logo" class="form-control input-sm" />
              </div>
              {if $info['brand_logo']}
              <div class="col-md-1">
                <a href="javascript:;" class="glyphicon glyphicon-picture ectouch-fs16" style="text-decoration:none;" onClick="showImg('brand_logo_layer', '{$lang['brand_logo']}')" title="View"></a>
				<div id="brand_logo_layer" style="display:none"> <img src="{$info['brand_logo']}" border="0" style="max-width:320px; max-height:320px;" /> </div>
              </div>
              {/if}
          </td>
        </tr>
        <tr>
          <td>{$lang['brand_banner']}:</td>
          <td><div class="col-md-4">
              <input type="file" name="brand_banner" class="form-control input-sm" />
              </div>
              {if $info['brand_banner']}
              <div class="col-md-1">
                <a href="javascript:;" class="glyphicon glyphicon-picture ectouch-fs16" style="text-decoration:none;" onClick="showImg('brand_banner_layer', '{$lang['brand_banner']}')" title="View"></a>
				<div id="brand_banner_layer" style="display:none"> <img src="{$info['brand_banner']}" border="0" style="max-width:320px; max-height:320px;" /> </div>
              </div>
              {/if}
          </td>
        </tr>
        <tr>
          <td>{$lang['brand_desc']}</td>
          <td><div class="col-md-6">
              <textarea name='data[brand_desc]' rows="6" class="form-control input-sm"></textarea>
            </div></td>
        </tr>
        <tr>
          <td>{$lang['brand_text']}</td>
          <td><div class="col-md-9">
              <script id="container" name="content" type="text/plain" style="width:810px; height:360px;"></script>
            </div></td>
        </tr>
        <tr>
          <td>{$lang['sort_order']}</td>
          <td><div class="col-md-2">
              <input type='text' name='data[sort_order]' maxlength="20" class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['is_show']}</td>
          <td><div class="col-md-2">
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-primary btn-sm active">
                  <input type="radio" name="data[is_show]" id="ifshow1" value="1" checked>
                  {$lang['yes']} </label>
                <label class="btn btn-primary btn-sm">
                  <input type="radio" name="data[is_show]" id="ifshow2" value="0">
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
    </form>
  </div>
</div>
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.config.js"></script>
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.all.min.js"></script>
<script type="text/javascript">var ue = UE.getEditor('container');</script>
{include file="pagefooter"}