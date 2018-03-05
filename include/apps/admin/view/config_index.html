{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <ul class="nav nav-tabs" role="tablist" id="myTab">
      {loop $group_list $key $group} <li{if $key == 1} class="active"{/if}><a href="#{$group['code']}" role="tab" data-toggle="tab">{$group['name']}</a>
      </li>
      {/loop}
    </ul>
    <form action="{url('post')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <div class="tab-content"> {loop $group_list $item $group}
        <div class="tab-pane{if $item == 1} active{/if}" id="{$group['code']}">
          <table class="table table-hover ectouch-table">
            {loop $group['vars'] $key $var}
            <tr>
              <td width="200" valign="top"> {$var['name']}: </td>
              <td><div class="row">{if $var['type'] == "text"}
              	<div class="col-md-4"><input name="value[{$var['id']}]" type="text" value="{$var['value']}" class="form-control input-sm" />
                {elseif $var['type'] == "password"}
                <div class="col-md-4"><input name="value[{$var['id']}]" type="password" value="{$var['value']}" class="form-control input-sm" />
                {elseif $var['type'] == "textarea"}
                <div class="col-md-5"><textarea name="value[{$var['id']}]" rows="6" class="form-control input-sm">{$var['value']}</textarea>
                {elseif $var['type'] == "select"}
                <div class="col-md-8">
                <div class="btn-group" data-toggle="buttons">
                {loop $var['store_options'] $k $opt}
                    <label class="btn btn-primary btn-sm{if $var['value'] == $opt} active{/if}">
                      <input type="radio" name="value[{$var['id']}]" id="value_{$var['id']}_{$k}" value="{$opt}"{if $var['value'] == $opt} checked{/if}
                        {if $var['code'] == 'rewrite'}
                          onclick="return ReWriterConfirm(this);"
                        {/if}
                        {if $var['code'] == 'smtp_ssl' and $opt == 1}
                          onclick="return confirm('{$lang['smtp_ssl_confirm']}');"
                        {/if}
                        {if $var['code'] == 'enable_gzip' and $opt == 1}
                          onclick="return confirm('{$lang['gzip_confirm']}');"
                        {/if}
                        {if $var['code'] == 'retain_original_img' and $opt == 0}
                          onclick="return confirm('{$lang['retain_original_confirm']}');"
                        {/if}
                      />
                      {$var['display_options'][$k]} </label>
                {/loop}
                </div>
                {elseif $var['type'] == "options"}
                <div class="col-md-3"><select name="value[{$var['id']}]" id="value_{$var['id']}_{$key}" class="form-control input-sm">
                  {loop $lang['cfg_range'][$var['code']] $key $vo}
                  <option value="{$key}"{if $var['value'] == $key} selected{/if}>{$vo}</option>
                  {/loop}
                </select>
                {elseif $var['type'] == "file"}
                <div class="col-md-4"><input name="{$var['code']}" type="file" size="40" class="form-control input-sm" /></div>
                <div class="col-md-1">
                {if ($var['code'] == "shop_logo" or $var['code'] == "no_picture" or $var['code'] == "watermark" or $var['code'] == "shop_slagon" or $var['code'] == "wap_logo") and $var['value']} 
                <a href="javascript:;" class="glyphicon glyphicon-picture" style="text-decoration:none;" onClick="showImg('{$var['code']}_layer', '{$var['name']}')" title="View"></a><br>
                <a href="{url('del', array('code'=> $var['code']))}" class="glyphicon glyphicon-remove" style="text-decoration:none;" title="Delete"></a> 
				<div id="{$var['code']}_layer" style="display:none"> <img src="{$var['value']}" border="0" style="max-width:320px; max-height:320px;" /> </div>
                {/if}
                {elseif $var['type'] == "manual"}
                
                {if $var['code'] == "shop_country"}
                <div class="col-md-3"><select name="value[{$var['id']}]" id="selCountries" onchange="region.changed(this, 1, 'selProvinces')" class="form-control input-sm">
                  <option value=''>{$lang['select_please']}</option>
                {loop $countries $region}
                  <option value="{$region['region_id']}" {if $region['region_id'] == $cfg['shop_country']}selected{/if}>{$region['region_name']}</option>
                {/loop}
                </select>
                {elseif $var['code'] == "shop_province"}
                <div class="col-md-3"><select name="value[{$var['id']}]" id="selProvinces" onchange="region.changed(this, 2, 'selCities')" class="form-control input-sm">
                  <option value=''>{$lang['select_please']}</option>
                {loop $provinces $region}
                  <option value="{$region['region_id']}" {if $region['region_id'] == $cfg['shop_province']}selected{/if}>{$region['region_name']}</option>
                {/loop}
                </select>
                {elseif $var['code'] == "shop_city"}
                <div class="col-md-3"><select name="value[{$var['id']}]" id="selCities" class="form-control input-sm">
                  <option value=''>{$lang['select_please']}</option>
                {loop $cities $region}
                  <option value="{$region['region_id']}" {if $region['region_id'] == $cfg['shop_city']}selected{/if}>{$region['region_name']}</option>
                {/loop}
                </select>
                {elseif $var['code'] == "lang"}
                <div class="col-md-2"><select name="value[{$var['id']}]" class="form-control input-sm">
                {loop $lang_list $vo}
                <option value="{$vo}"{if $var['value'] == $vo} selected{/if}>{$vo}</option>
                {/loop}
                </select>
                {elseif $var['code'] == "invoice_type"}
                <table>
                  <tr>
                    <th scope="col">{$lang['invoice_type']}</th>
                    <th scope="col">{$lang['invoice_rate']}</th>
                  </tr>
                  <tr>
                    <td><input name="invoice_type[]" type="text" value="{$cfg['invoice_type']['type'][0]}" class="form-control input-sm" /></td>
                    <td><input name="invoice_rate[]" type="text" value="{$cfg['invoice_type']['rate'][0]}" class="form-control input-sm" /></td>
                  </tr>
                  <tr>
                    <td><input name="invoice_type[]" type="text" value="{$cfg['invoice_type']['type'][1]}" class="form-control input-sm" /></td>
                    <td><input name="invoice_rate[]" type="text" value="{$cfg['invoice_type']['rate'][1]}" class="form-control input-sm" /></td>
                  </tr>
                  <tr>
                    <td><input name="invoice_type[]" type="text" value="{$cfg['invoice_type']['type'][2]}" class="form-control input-sm" /></td>
                    <td><input name="invoice_rate[]" type="text" value="{$cfg['invoice_type']['rate'][2]}" class="form-control input-sm" /></td>
                  </tr>
                </table>
                {/if}
                {/if}</div></div>{if is_string($var['desc'])}<div class="row"><div class="col-md-12" style="padding-top:10px;display: block;">{$var['desc']}</div></div>{/if}</td>
            </tr>
            {/loop}
          </table>
        </div>
        {/loop}
        <div style="padding:10px 0 0 200px; border-top:1px #ddd solid;">
          <input name="submit" type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
          <input name="reset" type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
        </div>
      </div>
    </form>
  </div>
</div>
<script type="text/javascript" src="__PUBLIC__/js/region.js"></script>
{include file="pagefooter"}