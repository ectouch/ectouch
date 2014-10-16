{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('install')}" method="post" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['payment_name']}:</td>
          <td><div class="col-md-4">
              <input type='text' name='data[pay_name]' maxlength="20" class="form-control input-sm" value="{$pay['pay_name']}" />
            </div>
          </td>
        </tr>
        {loop $pay['pay_config'] $key $vo}
        <tr>
          <td>{$vo['label']}</td>
          <td><div class="col-md-4">
              {if $vo['type'] == 'text'}
              <input name="cfg_value[]" rows="6" class="form-control input-sm" type="text" value="{$vo['value']}" />
              {elseif $vo['type'] == 'textarea'}
              <textarea name="cfg_value[]">{$vo['value']}</textarea>
              {elseif $vo['type'] == 'select'}
              <select name="cfg_value[]" class="form-control input-sm">
              {loop $vo['range'] $k $v}
              	<option value="{$k}">{$v}</option>
              {/loop}
              </select>
              {/if}
              <input name="cfg_name[]" type="hidden" value="{$vo['name']}" />
		      <input name="cfg_type[]" type="hidden" value="{$vo['type']}" />
		      <input name="cfg_lang[]" type="hidden" value="{$vo['lang']}" />
            </div>
            {if $vo['desc']}
            <button type="button" class="btn btn-xs btn-info">Info</button>
            {/if}
         </td>
        </tr>
        {if $vo['desc']}
        <tr>
        	<td>
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
        	</td>
        	<td><div class="alert alert-info" role="alert" style="padding:5px 15px;margin:0 15px;">{$vo['desc']}</div></td>
        </tr>
        {/if}
        {/loop}
        <tr>
          <td width="200">{$lang['pay_fee']}</td>
          <td><div class="col-md-4">
              {if $pay['is_cod']}
              <input type="hidden" name="data[pay_fee]" maxlength="20" class="form-control input-sm" value="{if $pay['pay_fee']}{$pay['pay_fee']}{else}0{/if}" />{$lang['decide_by_ship']}
              {else}
              <input type="text" name="data[pay_fee]" maxlength="20" class="form-control input-sm" value="{if $pay['pay_fee']}{$pay['pay_fee']}{else}0{/if}" />
              {/if}
            </div>
          </td>
        </tr>
        <tr>
          <td width="200">{$lang['payment_is_cod']}</td>
          <td><div class="col-md-4">
              {if $pay['is_cod'] == "1"}{$lang['yes']}{else}{$lang['no']}{/if}
            </div>
          </td>
        </tr>
        <tr>
          <td width="200">{$lang['payment_is_online']}</td>
          <td><div class="col-md-4">
              {if $pay['is_online'] == "1"}{$lang['yes']}{else}{$lang['no']}{/if}
            </div>
          </td>
        </tr>
        <tr>
          <td></td>
          <td><div class="col-md-4">
      			<input type="hidden"  name="data[pay_code]" value="{$pay['pay_code']}" />
      			<input type="hidden"  name="data[is_cod]" value="{$pay['is_cod']}" />
      			<input type="hidden"  name="data[is_online]" value="{$pay['is_online']}" />
      			<input type="hidden"  name="data[pay_desc]" value="{$pay['pay_desc']}" />
              	<input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              	<input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
    </form>
  </div>
</div>
{include file="pagefooter"}