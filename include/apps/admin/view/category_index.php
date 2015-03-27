{include file="pageheader"}

<table id="list-table" class="table table-bordered table-striped table-hover">
  <tr class="active">
    <th class="text-center">{$lang['cat_name']}</th>
    <th class="text-center" width="10%">{$lang['goods_number']}</th>
    <th class="text-center" width="10%">{$lang['measure_unit']}</th>
    <th class="text-center" width="10%">{$lang['is_show']}</th>
    <th class="text-center" width="10%">{$lang['short_grade']}</th>
    <th class="text-center" width="10%">{$lang['sort_order']}</th>
    <th class="text-center" width="10%">{$lang['handler']}</th>
  </tr>
  {loop $cat_list $key $cat}
  <tr align="center" class="{$cat['level']}" id="{$cat['level']}_{$cat['cat_id']}">
    <td align="left" class="first-cell" > {if $cat['is_leaf'] <> 1} <img src="__ASSETS__/images/menu_minus.gif" id="icon_{$cat['level']}_{$cat['cat_id']}" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" onclick="rowClicked(this)" /> {else} <img src="__ASSETS__/images/menu_arrow.gif" width="9" height="9" border="0" style="margin-left:{$cat['level']}em" /> {/if} <span><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$cat['cat_name']}</a></span></td>
    <td>{$cat['goods_num']}</td>
    <td><!-- {if $cat['measure_unit']} -->{$cat['measure_unit']}<!-- {/if} --></td>
    <td><img src="__ASSETS__/images/{if $cat['is_show'] == '1'}yes{else}no{/if}.gif" /></td>
    <td>{$cat['grade']}</td>
    <td>{$cat['sort_order']}</td>
    <td><a href="{url('edit', array('cat_id'=>$cat['cat_id']))}">{$lang['edit']}</a></td>
  </tr>
  {/loop}
</table>

<script type="text/javascript">
<!--
var imgPlus = new Image();
imgPlus.src = "__ASSETS__/images/menu_plus.gif";

/**
 * 折叠分类列表
 */
function rowClicked(obj){
  // 当前图像
  img = obj;
  // 取得上二级tr>td>img对象
  obj = obj.parentNode.parentNode;
  // 整个分类列表表格
  var tbl = document.getElementById("list-table");
  // 当前分类级别
  var lvl = parseInt(obj.className);
  // 是否找到元素
  var fnd = false;
  var sub_display = img.src.indexOf('menu_minus.gif') > 0 ? 'none' : (Browser.isIE) ? 'block' : 'table-row' ;
  // 遍历所有的分类
  for (i = 0; i < tbl.rows.length; i++) {
      var row = tbl.rows[i];
      if (row == obj) {
          // 找到当前行
          fnd = true;
          //document.getElementById('result').innerHTML += 'Find row at ' + i +"<br/>";
      } else {
          if (fnd == true) {
              var cur = parseInt(row.className);
              var icon = 'icon_' + row.id;
              if (cur > lvl) {
                  row.style.display = sub_display;
                  if (sub_display != 'none') {
                      var iconimg = document.getElementById(icon);
                      iconimg.src = iconimg.src.replace('plus.gif', 'minus.gif');
                  }
              } else {
                  fnd = false;
                  break;
              }
          }
      }
  }

  for (i = 0; i < obj.cells[0].childNodes.length; i++) {
      var imgObj = obj.cells[0].childNodes[i];
      if (imgObj.tagName == "IMG" && imgObj.src != '__ASSETS__/images/menu_arrow.gif') {
          imgObj.src = (imgObj.src == imgPlus.src) ? '__ASSETS__/images/menu_minus.gif' : imgPlus.src;
      }
  }
}
//-->
</script> 
{include file="pagefooter"}