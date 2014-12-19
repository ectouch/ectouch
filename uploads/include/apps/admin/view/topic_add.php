{include file="pageheader"}
<link rel="stylesheet" type="text/css" href="__ASSETS__/css/jquery.datetimepicker.css"/ >
<script src="__ASSETS__/js/jquery.datetimepicker.js" type="text/javascript"></script>
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$ur_here}</h3>
  </div>
  <div class="panel-body">
    <ul class="nav nav-tabs" role="tablist" id="myTab">
      <li class="active"><a href="#general" role="tab" data-toggle="tab">{$lang['tab_general']}</a></li>
      <li><a href="#goods" role="tab" data-toggle="tab">{$lang['tab_goods']}</a></li>
      <li><a href="#desc" role="tab" data-toggle="tab">{$lang['tab_desc']}</a></li>
      <li><a href="#advanced" role="tab" data-toggle="tab">{$lang['tab_advanced']}</a></li>
    </ul>
    <form action="{url('add')}" method="post" name="theForm" enctype="multipart/form-data" class="form-horizontal" role="form">
      <div class="tab-content">
        <div class="tab-pane active" id="general">
          <table id="general-table" class="table table-hover ectouch-table">
            <tr>
              <td width="200">{$lang['topic_title']}</td>
              <td><div class="col-md-4">
                  <input type='text' name='topic_name' maxlength="20"  datatype="*" class="form-control input-sm" />
                </div></td>
            </tr>
            <tr>
              <td width="200">{$lang['lable_topic_keywords']}</td>
              <td><div class="col-md-6">
                  <textarea class="form-control input-sm" rows="6" name='data[keywords]'></textarea>
                </div></td>
            </tr>
            <tr>
              <td width="200">{$lang['lable_topic_description']}</td>
              <td><div class="col-md-4">
                  <input type='text' name='data[description]' maxlength="20"  class="form-control input-sm" />
                </div></td>
            </tr>
            <tr>
              <td width="200">{$lang['lable_topic_type']}</td>
              <td><div class="col-md-3">
                  <select name="topic_type" id="topic_type" onchange="showMedia(this.value)">
                    <option value='0'>{$lang['top_img']}</option>
                    <option value='1'>{$lang['top_flash']}</option>
                    <option value='2'>{$lang['top_html']}</option>
                  </select>
                </div></td>
            </tr>
            <tbody id="content_01">
              <tr>
                <td>{$lang['lable_upload']}</td>
                <td><div class="col-md-4">
                    <input type="file" name="topic_img" class="form-control input-sm" />
                  </div></td>
              </tr>
              <tr>
                <td width="200">{$lang['lable_from_web']}</td>
                <td><div class="col-md-4">
                    <input type='text' name='url' maxlength="20"  class="form-control input-sm" />
                  </div></td>
              </tr>
            </tbody>
            <tbody id="edit_img">
              <tr>
                <td width="200">&nbsp;</td>
                <td><div class="col-md-4">
                    <input type="text" name="img_url" id="img_url" class="form-control input-sm" value="{$topic['topic_img']}" size="35" readonly="readonly"/>
                  </div></td>
              </tr>
            </tbody>
            <tbody id="content_23">
              <tr>
                <td width="200">{$lang['topic_class_list']}</td>
                <td><div class="col-md-4">
                    <textarea name="htmls" id="htmls" class="form-control input-sm" rows="6">{$topic['htmls']}</textarea>
                  </div></td>
              </tr>
            </tbody>
            <tr>
              <td width="200">{$lang['lable_title_upload']}</td>
              <td><div class="col-md-4">
                  <input type="file" name="title_pic" class="form-control input-sm" />
                </div></td>
            </tr>
            <tr>
              <td width="200">{$lang['lable_from_web']}</td>
              <td><div class="col-md-4">
                  <input type='text' name='title_url' maxlength="20"  class="form-control input-sm" />
                </div></td>
            </tr>
            <tbody id="edit_title_img">
            <tr>
              <td width="200">&nbsp;</td>
              <td><div class="col-md-4">
                  <input type='text' name='title_img_url' maxlength="20" value="{$topic['title_pic']}"  readonly="readonly" class="form-control input-sm" />
                </div></td>
            </tr>
            </tbody>
            <tr>
              <td width="200">{$lang['start_time']}</td>
              <td><div class="col-md-4">
                  <input type='text' name='start_time' id="start_time" maxlength="100"  class="form-control input-sm" />
                </div></td>
            </tr>
            <tr>
              <td width="200">{$lang['end_time']}</td>
              <td><div class="col-md-4">
                  <input type='text' name='end_time' id="end_time" maxlength="100"  class="form-control input-sm"  />
                </div></td>
            </tr>
          </table>
        </div>
        <div class="tab-pane" id="goods">
          <table width="90%" class="table table-hover ectouch-table">
            <tr>
              <td><div class="col-md-9">{$lang['topic_class']}
                  <select name="topic_class_list" id="topic_class_list" onchange="showTargetList()">
                  </select>
                  <input name="new_cat_name" type="text" id="new_cat_name" />
                  <input name="create_class_btn" type="button" id="create_class_btn" value="{$lang['add']}" class="button" onclick="addClass()" />
                  <input name="delete_class_btn" type="button" id="delete_class_btn" value="{$lang['remove']}" class="button" onclick="deleteClass()" />
                </div></td>
            </tr>
            <tr>
              <td width="100%"><div class="col-md-9" ><i class="glyphicon glyphicon-search"></i>
                  <select name="cat_id2">
                    <option value="0">{$lang['all_category']}</option>
                    {$cat_list}
                  </select>
                  <select name="brand_id2">
                    <option value="0">{$lang['all_brand']}</option>
                    {loop $brand_list $key $posit}
                    <option value="{$key}">{$posit}</option>
                    {/loop}  
                  </select>
                  <input type="text" name="keyword2"/>
                  <input name="button" type="button" class="button" onclick="searchGoods('cat_id2', 'brand_id2', 'keyword2')" value="{$lang['button_search']}" />
                </div></td>
            </tr>
            <tr>
              <td width="100%"><table width="90%" class="table table-hover ectouch-table">
                  <tr height="37">
                    <th>{$lang['all_goods']}</th>
                    <th>{$lang['handler']}</th>
                    <th>{$lang['selected_goods']}</th>
                  </tr>
                  <tr>
                    <td width="42%"><select name="source_select" id="source_select" size="20" style="width:100%;height:300px;"  ondblclick="addItem(this)">
                      </select></td>
                    <td align="center"><p>
                        <input name="button" type="button" class="button" onclick="addAllItem(document.getElementById('source_select'))" value="&gt;&gt;" />
                      </p>
                      <p>
                        <input name="button" type="button" class="button" onclick="addItem(document.getElementById('source_select'))" value="&gt;" />
                      </p>
                      <p>
                        <input name="button" type="button" class="button" onclick="removeItem(document.getElementById('target_select'))" value="&lt;" />
                      </p>
                      <p>
                        <input name="button" type="button" class="button" value="&lt;&lt;" onclick="removeItem(document.getElementById('target_select'), true)" />
                      </p></td>
                    <td width="42%"><select name="target_select" id="target_select" size="20" style="width:100%;height:300px" multiple="multiple">
                      </select></td>
                  </tr>
                </table></td>
            </tr>
          </table>
          <input type="hidden" name="id" value="{$info['act_id']}" />
        </div>
        <div class="tab-pane" id="desc">
          <table width="90%" class="table table-hover ectouch-table">
            <tr>
              <td><div class="col-md-9"> 
                  <script id="container" name="topic_intro" type="text/plain" style="width:810px; height:360px;"></script> 
                </div></td>
            </tr>
          </table>
          <input type="hidden" name="id" value="{$info['act_id']}" />
        </div>
        <div class="tab-pane" id="advanced">
          <table width="90%" class="table table-hover ectouch-table">
            <tr>
              <td width="200">{$lang['template_file']}</td>
              <td><div class="col-md-4">
                  <select name="topic_template_file" class="form-control input-sm"> 
                    {loop $template_list $key $tmp}
                    <option value="{$tmp}">{$tmp}</option>
                    {/loop}  
                  </select>
                  <div class="row"><div style="padding-top:10px;display: block;" class="col-md-12"> {$lang['notice_template_file']}</div></div>
                </div></td>
            </tr>
          </table>
        </div>
      </div>
      <div class="button-div">
        <input  name="act" type="hidden" value="{$form_action}" />
        <input  name="topic_data" type="hidden" id="topic_data" value='' />
        <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary" onclick="return checkForm()"/>
        <input type="reset" value="{$lang['button_reset']}" class="btn btn-default" />
      </div>
    </form>
  </div>
</div>
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.config.js"></script> 
<script type="text/javascript" src="__PUBLIC__/ueditor/ueditor.all.min.js"></script> 
<script type="text/javascript">var ue = UE.getEditor('container');</script> 
{include file="pagefooter"} 
<script type="text/javascript">
var data = '{$topic['data']}';
var defaultClass = "{$lang['default_class']}";

var myTopic = Object();
var status_code = "{$topic['topic_type']}"; // 初始页面参数
onload = function()
{
  // 开始检查订单
  //startCheckOrder();
  var classList = document.getElementById("topic_class_list");

  // 初始化表单项
  initialize_form(status_code);
  if (data == "")
  {
    classList.innerHTML = "";
    myTopic['default'] = new Array();
    var newOpt    = document.createElement("OPTION");
    newOpt.value  = -1;
    newOpt.text   = defaultClass;
    classList.options.add(newOpt);
    return;
  }
  var temp    = jQuery.parseJSON(data); 
  var counter = 0;
  for (var k in temp)
  {
    if(typeof(myTopic[k]) != "function")
    {
      myTopic[k] = temp[k];
      var newOpt    = document.createElement("OPTION");
      newOpt.value  = k == "default" ? -1 : counter;
      newOpt.text   = k == "default" ? defaultClass : k;
      classList.options.add(newOpt);
      counter++;
    }
  }
  showTargetList();
}
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
/**
 * 初始化表单项目
 */
function initialize_form(status_code)
{
  var nt = navigator_type();
  var display_yes = (nt == 'IE') ? 'block' : 'table-row-group';
  status_code = parseInt(status_code);
  status_code = status_code ? status_code : 0;
  document.getElementById('topic_type').options[status_code].selected = true;
 
  switch (status_code)
  {
    case 0 :
      document.getElementById('content_01').style.display = display_yes;
      document.getElementById('content_23').style.display = 'none';	
 	  document.getElementById('edit_img').style.display = display_yes;
    break;
		
    case 1 :
      document.getElementById('content_01').style.display = display_yes;
      document.getElementById('content_23').style.display = 'none';
	  document.getElementById('edit_img').style.display = display_yes;
    break;
		
    case 2 :
      document.getElementById('content_01').style.display = 'none';
      document.getElementById('content_23').style.display = display_yes;
	  document.getElementById('edit_img').style.display = 'none';
    break;
  }
  document.getElementById('edit_img').style.display = 'none';
  document.getElementById('edit_title_img').style.display = 'none';
  return true;
}

/**
 * 类型表单项切换
 */
function showMedia(code)
{
  var obj = document.getElementById('topic_type');

  initialize_form(code);
}

function checkForm()
{
//  var validator = new Validator('theForm');
//  validator.required('topic_name', topic_name_empty);
//  validator.required('start_time', start_time_empty);
//  validator.required('end_time', end_time_empty);
//  validator.islt('start_time', 'end_time', start_lt_end);
    document.getElementById("topic_data").value = $.toJSON(myTopic);  
}

function chanageSize(num, id)
{
  var obj = document.getElementById(id);
  if (obj.tagName == "TEXTAREA")
  {
    var tmp = parseInt(obj.rows);
    tmp += num;
    if (tmp <= 0) return;
    obj.rows = tmp;
  }
}

function searchGoods(catId, brandId, keyword)
{
  var elements = document.forms['theForm'].elements;
  var filters = new Object;
  filters.cat_id = elements[catId].value;
  filters.brand_id = elements[brandId].value;
  filters.keyword = elements[keyword].value;
  
  $.post("{url('get_goods_list')}", {filters:$.toJSON(filters)}, function(result){
	   clearOptions("source_select");
	   var obj = document.getElementById("source_select");
	   for (var i=0; i < result.content.length; i++)
	   {
		  var opt   = document.createElement("OPTION");
		  opt.value = result.content[i].value;
		  opt.text  = result.content[i].text;
		  opt.id    = result.content[i].data;
		  obj.options.add(opt);
	   }	  
	}, 'json');
}

function clearOptions(id)
{
  var obj = document.getElementById(id);
  while(obj.options.length>0)
  {
    obj.remove(0);
  }
}

function addAllItem(sender)
{
  if(sender.options.length == 0) return false;
  for (var i = 0; i < sender.options.length; i++)
  {
    var opt = sender.options[i];
    addItem(null, opt.value, opt.text);
  }
}

function addItem(sender, value, text)
{
  var target_select = document.getElementById("target_select");
  var sortList = document.getElementById("topic_class_list");
  var newOpt   = document.createElement("OPTION");
  if (sender != null)
  {
    if(sender.options.length == 0) return false;
    var option = sender.options[sender.selectedIndex];
    newOpt.value = option.value;
    newOpt.text  = option.text;
  }
  else
  {
    newOpt.value = value;
    newOpt.text  = text;
  }
  if (targetItemExist(newOpt)) return false;
  if (target_select.length>=50)
  {
    alert(item_upper_limit);
  }
  target_select.options.add(newOpt);
  var key = sortList.options[sortList.selectedIndex].value == "-1" ? "default" : sortList.options[sortList.selectedIndex].text;
  
  if(!myTopic[key])
  {
    myTopic[key] = new Array();
  }
  myTopic[key].push(newOpt.text + "|" + newOpt.value);
}

// 商品是否存在
function targetItemExist(opt)
{
  var options = document.getElementById("target_select").options;
  for ( var i = 0; i < options.length; i++)
  {
    if(options[i].text == opt.text && options[i].value == opt.value) 
    {
      return true;
    }
  }
  return false;
}

function addClass()
{
  var obj = document.getElementById("topic_class_list");
  var newClassName = document.getElementById("new_cat_name");
  var regExp = /^[a-zA-Z0-9]+$/;
  if (newClassName.value == ""){
    alert(sort_name_empty);
    return;
  }
  for(var i=0;i < obj.options.length; i++)
  {
    if(obj.options[i].text == newClassName.value)
    {
      alert(sort_name_exist);
      newClassName.focus(); 
      return;
    }
  }
  var className = document.getElementById("new_cat_name").value;
  document.getElementById("new_cat_name").value = "";
  var newOpt    = document.createElement("OPTION");
  newOpt.value  = obj.options.length;
  newOpt.text   = className;
  obj.options.add(newOpt);
  newOpt.selected = true;
  if ( obj.options[0].value == "-1")
  {
    if (myTopic["default"].length > 0)
      alert(move_item_confirm.replace("className",className));
    myTopic[className] = myTopic["default"];
    delete myTopic["default"];
    obj.remove(0);
  }
  else
  {
    myTopic[className] = new Array();
    clearOptions("target_select");
  }
}

function deleteClass()
{
  var classList = document.getElementById("topic_class_list");
  if (classList.value != "-1")
  {
    delete myTopic[classList.options[classList.selectedIndex].text];
    classList.remove(classList.selectedIndex);
    clearOptions("target_select");
  }
  if (classList.options.length < 1)
  {
    var newOpt    = document.createElement("OPTION");
    newOpt.value  = "-1";
    newOpt.text   = defaultClass;
    classList.options.add(newOpt);
    myTopic["default"] = new Array();
  }
}

function showTargetList()
{
  clearOptions("target_select");
  var obj = document.getElementById("topic_class_list");
  var index = obj.options[obj.selectedIndex].text;
  if (index == defaultClass)
  {
    index = "default";
  }
  var options = myTopic[index];
  
  for ( var i = 0; i < options.length; i++)
  {
    var newOpt    = document.createElement("OPTION");
    var arr = options[i].split('|');
    newOpt.value  = arr[1];
    newOpt.text   = arr[0];
    document.getElementById("target_select").options.add(newOpt);
  }
}

function removeItem(sender,isAll)
{
  var classList = document.getElementById("topic_class_list");
  var key = 'default';
  if (classList.value != "-1")
  {
    key = classList.options[classList.selectedIndex].text;
  }
  var arr = myTopic[key];
  if (!isAll)
  {
    var goodsName = sender.options[sender.selectedIndex].text;
    for (var j = 0; j < arr.length; j++)
    {
      if (arr[j].indexOf(goodsName) >= 0)
      {
          myTopic[key].splice(j,1);
      }
    }

    for (var i = 0; i < sender.options.length;)
    {
      if (sender.options[i].selected) {
        sender.remove(i);
        myTopic[key].splice(i, 0);
      }
      else
      {
        i++;
      }
    }
  }
  else
  {
    myTopic[key] = new Array();
    sender.innerHTML = "";
  }
}

/**
 * 判断当前浏览器类型
 */
function navigator_type()
{
  var type_name = '';

  if (navigator.userAgent.indexOf('MSIE') != -1)
  {
    type_name = 'IE'; // IE
  }
  else if(navigator.userAgent.indexOf('Firefox') != -1)
  {
    type_name = 'FF'; // FF
  }
  else if(navigator.userAgent.indexOf('Opera') != -1)
  {
    type_name = 'Opera'; // Opera
  }
  else if(navigator.userAgent.indexOf('Safari') != -1)
  {
    type_name = 'Safari'; // Safari
  }
  else if(navigator.userAgent.indexOf('Chrome') != -1)
  {
    type_name = 'Chrome'; // Chrome
  }

  return type_name;
}
</script> 