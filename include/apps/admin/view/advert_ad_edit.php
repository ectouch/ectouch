{include file="pageheader"}
<link rel="stylesheet" type="text/css" href="__ASSETS__/css/jquery.datetimepicker.css"/ >
<script src="__ASSETS__/js/jquery.datetimepicker.js" type="text/javascript"></script>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('ad_edit')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form" >
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['ad_name']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[ad_name]' maxlength="20"  class="form-control input-sm" value="{$info['ad_name']}" />
            </div>
            <span>广告名称只是作为辨别多个广告条目之用，并不显示在广告中</span>
          </td>
        </tr>
        <input type="hidden" name="data[media_type]" value="{$info['media_type']}" />
        <tr>
          <td>广告位置</td>
          <td><div class="col-md-3">
              <select name='data[position_id]'>
                <option value='0'>站外广告</option>
                {loop $posi_arr $key $posit}
                <option value="{$posit['position_id']}" {if $info['position_id'] == $posit['position_id']}selected="true"{/if}>{$posit['position_name_str']}</option>
                {/loop}
              </select>
            </div>
          </td>
        </tr>
        <tr>
          <td>{$lang['start_time']}</td>
          <td><div class="col-md-3">
              <input type='text' name='data[start_time]' id="start_time" maxlength="100" class="form-control input-sm" value="{$info[start_time]}" />
            </div>
          </td>
        </tr>
        <tr>
          <td>{$lang['end_time']}</td>
          <td>
            <div class="col-md-3">
              <input type='text' name='data[end_time]' id="end_time" maxlength="100" class="form-control input-sm" value="{$info[end_time]}" />
            </div>
          </td>
        </tr>
{if $info['media_type'] == '0'}
        <tbody id="0">
        <tr>
          <td>{$lang['ad_link']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[ad_link]' maxlength="100" class="form-control input-sm" value="{$info['ad_link']}" />
            </div>
          </td>
        </tr>
        <tr>
          <td>上传广告图片</td>
          <td>
            <div class="col-md-4">
              <input type="file" name="ad_img" class="form-control input-sm" />
              </div>
               {if $ad_img}
              <div class="col-md-1">
                <a href="javascript:;" class="glyphicon glyphicon-picture ectouch-fs16" style="text-decoration:none;" onClick="showImg('ad_img_layer', '{$lang['ad_img']}')" title="View"></a>
        <div id="ad_img_layer" style="display:none"> <img src="{$ad_img}" border="0" style="max-width:320px; max-height:320px;" /> </div>
              </div>
              {/if}
            <span>上传该广告的图片文件,或者你也可以指定一个远程URL地址为广告的图片</span>
          </td>
        </tr>
        <tr>
          <td>或图片网址</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[img_url]' maxlength="100" class="form-control input-sm" value="{$img_url}" />
            </div>
          </td>
        </tr>
        </tbody>
{/if}
{if $info['media_type'] == '1'}
        <tbody id="1" >
        <tr>
          <td>上传Flash文件</td>
          <td>
            <div class="col-md-5">
              <input type="file" name="upfile_flash" class="form-control input-sm" />
              </div>
            <span>上传该广告的Flash文件,或者你也可以指定一个远程的Flash文件</span>
          </td>
        </tr>
        <tr>
          <td>或Flash网址</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[flash_url]' maxlength="100" class="form-control input-sm" value="{$flash_url}" />
            </div>
          </td>
        </tr>
        </tbody>
{/if}
{if $info['media_type'] == '2'}
        <tbody id="2" >
        <tr>
          <td>{$lang['ad_code']}</td>
          <td>
            <div class="col-md-6"><textarea name="data[ad_code]" cols="50"  rows="7" class="form-control input-sm">{$info['ad_code']}</textarea></div>
        </td>
        </tr>
        </tbody>
{/if}
{if $info['media_type'] == '3'}
        <tbody id="3" >
        <tr>
          <td>{$lang['ad_link']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[ad_link2]' maxlength="100" class="form-control input-sm" value="{$info['ad_link']}" />
            </div>
          </td>
        </tr>
        <tr>
          <td>{$lang['ad_text']}</td>
          <td>
            <div class="col-md-5">
              <textarea name="data[ad_text]" cols="40" rows="3" class="form-control input-sm">{$info['ad_code']}</textarea>
            </div>
          </td>
        </tr>
        </tbody>
{/if}
        <tr>
          <td>是否开启</td>
          <td>
            <div class="col-md-2">
              <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-primary btn-sm active">
                  <input type="radio" name="data[enabled]" id="enabled1" value="1" {if $info['enabled'] == '1'} checked="true" {/if} />
                  {$lang['enabled']} </label>
                <label class="btn btn-primary btn-sm">
                  <input type="radio" name="data[enabled]" id="enabled2" value="0" {if $info['enabled'] == '0'}checked="true" {/if} />
                  {$lang['is_enabled']}</label>
              </div>
            </div>
            </div>
          </td>
        </tr>

        <tr>
          <td>{$lang['link_man']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[link_man]' maxlength="100" class="form-control input-sm" value="{$info['link_man']}" />
            </div>
          </td>
        </tr>

        <tr>
          <td>{$lang['link_email']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[link_email]' maxlength="100" class="form-control input-sm" value="{$info['link_email']}" />
            </div>
          </td>
        </tr>

        <tr>
          <td>{$lang['link_phone']}</td>
          <td>
            <div class="col-md-5">
              <input type='text' name='data[link_phone]' maxlength="100" class="form-control input-sm" value="{$info['link_phone']}" />
            </div>
          </td>
        </tr>

        <tr>
          <td></td>
          <td><div class="col-md-4">
              <input type="hidden" name="data[ad_id]" value="{$info[ad_id]}" />
              <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
    </form>
  </div>
</div>
<script type="text/javascript">
$('#start_time').datetimepicker({
  lang:'ch',
  format:'Y-m-d',
  timepicker:false
});
$('#end_time').datetimepicker({
  lang:'ch',
  format:'Y-m-d',
  timepicker:false
});
</script>

{include file="pagefooter"}