{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <form action="{url('edit')}" method="post" enctype="multipart/form-data" class="form-horizontal" role="form">
      <table id="general-table" class="table table-hover ectouch-table">
        <tr>
          <td width="200">{$lang['label_act_name']}</td>
          <td><div class="col-md-4">
              <input type='text' name='data[brand_name]' maxlength="20" disabled="disabled" value='{$favourable['act_name']}' class="form-control input-sm" />
            </div></td>
        </tr>
        <tr>
          <td>{$lang['label_banner']}</td>
          <td><div class="col-md-4">
              <input type="file" name="act_banner" class="form-control input-sm" />
              </div>
              {if $favourable['act_banner']}
              <div class="col-md-1">
                <a href="javascript:;" class="glyphicon glyphicon-picture ectouch-fs16" style="text-decoration:none;" onClick="showImg('act_banner', '{$lang['label_banner']}')" title="View"></a>
				<div id="act_banner_layer" style="display:none"> <img src="{$favourable['act_banner']}" border="0" style="max-width:320px; max-height:320px;" /> </div>
              </div>
              {/if}
          </td>
        </tr>
        <tr>
          <td></td>
          <td><div class="col-md-4">
              <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" />
              <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
            </div></td>
        </tr>
      </table>
      <input type="hidden" name="id" value="{$favourable['act_id']}" />
    </form>
  </div>
</div>
<script language="JavaScript">
<!--
onload = function()
{
    // 开始检查订单
//  startCheckOrder();
//    changeRange(document.forms['theForm'].elements['act_range'].value);
//    changeType(document.forms['theForm'].elements['act_type'].value);
}
/**
 * 检查表单输入的数据
 */
function validate()
{
    validator = new Validator("theForm");
    validator.required('act_name', act_name_not_null);
    validator.isNumber('min_amount', min_amount_not_number, true);
    validator.isNumber('max_amount', max_amount_not_number, true);
    validator.isNumber('act_type_ext', act_type_ext_not_number, true);
    validator.islt('start_time', 'end_time', start_lt_end);
    if (document.forms['theForm'].elements['max_amount'].value > 0)
    {
      validator.gt('max_amount', 'min_amount', amount_invalid);
    }

    return validator.passed();
}

function searchItem()
{
  var filter = new Object;
  filter.keyword  = document.forms['theForm'].elements['keyword'].value;
  filter.act_range = document.forms['theForm'].elements['act_range'].value;
  if (filter.act_range == 0)
  {
    alert(all_need_not_search);
    return;
  }

  Ajax.call('favourable.php?is_ajax=1&act=search', filter, searchResponse, 'GET', 'JSON');
}

function searchResponse(result)
{
  if (result.error == '1' && result.message != '')
  {
    alert(result.message);
	return;
  }

  var sel = document.forms['theForm'].elements['result'];

  sel.length = 0;

  /* 创建 options */
  var goods = result.content;
  if (goods)
  {
    for (i = 0; i < goods.length; i++)
    {
      var opt = document.createElement("OPTION");
      opt.value = goods[i].id;
      opt.text  = goods[i].name;
      sel.options.add(opt);
    }
  }

  return;
}

/**
 * 改变优惠范围
 * @param int rangeId
 */
function changeRange(rangeId)
{
  document.getElementById('range-div').innerHTML = '';
  document.getElementById('result').length = 0;
  var row = document.getElementById('range_search');
  if (rangeId <= 0)
  {
    row.style.display = 'none';
  }
  else
  {
    row.style.display = '';
  }
}

function addRange()
{
  var selRange = document.forms['theForm'].elements['act_range'];
  if (selRange.value == 0)
  {
    alert(all_need_not_search);
    return;
  }
  var selResult = document.getElementById('result');
  if (selResult.value == 0)
  {
    alert(pls_search);
    return;
  }
  var id = selResult.options[selResult.selectedIndex].value;
  var name = selResult.options[selResult.selectedIndex].text;

  // 检查是否已经存在
  var exists = false;
  var eles = document.forms['theForm'].elements;
  for (var i = 0; i < eles.length; i++)
  {
    if (eles[i].type=="checkbox" && eles[i].name.substr(0, 13) == 'act_range_ext')
    {
      if (eles[i].value == id)
      {
        exists = true;
        alert(range_exists);
        break;
      }
    }
  }

  // 创建checkbox
  if (!exists)
  {
    var html = '<input name="act_range_ext[]" type="checkbox" value="' + id + '" checked="checked" />' + name + '<br />';
    document.getElementById('range-div').innerHTML += html;
  }
}

/**
 * 搜索赠品
 */
function searchItem1()
{
  if (document.forms['theForm'].elements['act_type'].value == 1)
  {
    alert(price_need_not_search);
    return;
  }
  var filter = new Object;
  filter.keyword  = document.forms['theForm'].elements['keyword1'].value;
  filter.act_range = 3;
  Ajax.call('{url('install')}?is_ajax=1&act=search', filter, searchResponse1, 'GET', 'JSON');
}

function searchResponse1(result)
{
  if (result.error == '1' && result.message != '')
  {
    alert(result.message);
	return;
  }

  var sel = document.forms['theForm'].elements['result1'];

  sel.length = 0;

  /* 创建 options */
  var goods = result.content;
  if (goods)
  {
    for (i = 0; i < goods.length; i++)
    {
      var opt = document.createElement("OPTION");
      opt.value = goods[i].id;
      opt.text  = goods[i].name;
      sel.options.add(opt);
    }
  }

  return;
}

function addGift()
{
  var selType = document.forms['theForm'].elements['act_type'];
  if (selType.value == 1)
  {
    alert(price_need_not_search);
    return;
  }
  var selResult = document.getElementById('result1');
  if (selResult.value == 0)
  {
    alert(pls_search);
    return;
  }
  var id = selResult.options[selResult.selectedIndex].value;
  var name = selResult.options[selResult.selectedIndex].text;

  // 检查是否已经存在
  var exists = false;
  var eles = document.forms['theForm'].elements;
  for (var i = 0; i < eles.length; i++)
  {
    if (eles[i].type=="checkbox" && eles[i].name.substr(0, 7) == 'gift_id')
    {
      if (eles[i].value == id)
      {
        exists = true;
        alert(range_exists);
        break;
      }
    }
  }

  // 创建checkbox
  if (!exists)
  {
    var table = document.getElementById('gift-table');
    if (table.rows.length == 0)
    {
        var row = table.insertRow(-1);
        var cell = row.insertCell(-1);
        cell.align = 'center';
        cell.innerHTML = '<strong>' + gift + '</strong>';
        var cell = row.insertCell(-1);
        cell.align = 'center';
        cell.innerHTML = '<strong>' + price + '</strong>';
    }
    var row = table.insertRow(-1);
    var cell = row.insertCell(-1);
    cell.innerHTML = '<input name="gift_id[]" type="checkbox" value="' + id + '" checked="checked" />' + name;
    var cell = row.insertCell(-1);
    cell.align = 'right';
    cell.innerHTML = '<input name="gift_price[]" type="text" value="0" size="10" style="text-align:right" />' +
                     '<input name="gift_name[]" type="hidden" value="' + name + '" />';
  }
}

function changeType(typeId)
{
  document.getElementById('gift-div').innerHTML = '<table id="gift-table"></table>';
  document.getElementById('result1').length = 0;
  var row = document.getElementById('type_search');
  if (typeId <= 0)
  {
    row.style.display = '';
  }
  else
  {
    row.style.display = 'none';
  }
}

//-->
</script>  
{include file="pagefooter"}