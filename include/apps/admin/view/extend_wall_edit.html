{include file="pageheader"}
<link rel="stylesheet" type="text/css" href="__PUBLIC__/fancybox/jquery.fancybox.css?v=2.1.5" media="screen" />
<link rel="stylesheet" type="text/css" href="__ASSETS__/css/jquery.datetimepicker.css" />
<style>
.article{border:1px solid #ddd;padding:5px 5px 0 5px;}
.cover{height:160px; position:relative;margin-bottom:5px;overflow:hidden;}
.article .cover img{width:100%; height:auto;}
.article span{height:40px; line-height:40px; display:block; z-index:5; position:absolute;width:100%;bottom:0px; color:#FFF; padding:0 10px; background-color:rgba(0,0,0,0.6)}
.article_list{padding:5px;border:1px solid #ddd;border-top:0;overflow:hidden;}
.thumbnail{padding:0;}
</style>
<form action="{url('wall_edit')}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data">
<table class="table table-hover ectouch-table">
    <tr>
        <td width="200">活动名称</td>
        <td>
            <div class="col-md-4">
                <input type="text" name="data[name]" class="form-control" value="{$wall['name']}" />
            </div>
        </td>
    </tr>
    <tr>
        <td width="200">公司LOGO</td>
        <td>
            <div class="col-md-4">
                <input type="file" name="logo" />
                {if $wall['logo']}
                <img src="{$wall['logo']}" width="150" height="150" />
                {else}
                <img src="http://www.gbtags.com/gb/laitu/150&text=image" />
                {/if}

            </div>
        </td>
    </tr>
    <tr>
        <td width="200">活动背景</td>
        <td>
            <div class="col-md-4">
                <input type="file" name="background" />
                {if $wall['background']}
                <img src="{$wall['background']}" width="150" height="150" />
                {else}
                <img src="http://www.gbtags.com/gb/laitu/150&text=image" />
                {/if}
            </div>
        </td>
    </tr>
    <tr>
        <td width="200">开始时间</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="data[starttime]" class="form-control" id="starttime" value="{$wall['starttime']}" />
          </div>
        </td>
    </tr>
    <tr>
        <td width="200">结束时间</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="data[endtime]" class="form-control" id="endtime" value="{$wall['endtime']}" />
           </div>
        </td>
    </tr>
    <tr>
        <td width="200">奖品列表</td>
        <td>
            <div class="col-md-6 col-sm-6">
                <div class="form-group">
                <table class="table ectouch-table">
                    <tr>
                        <th class="text-center" width="10%"><a href="javascript:;" class="glyphicon glyphicon-plus" onClick="addprize(this)"></a></th>
                        <th class="text-center"  width="20%">奖项</th>
                        <th class="text-center" width="20%">奖品</th>
                        <th class="text-center" width="20%">数量</th>
                    </tr>
                    {loop $wall['prize_arr'] $v}
                    <tr>
                        <td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td>
                        <td class="text-center"><input type="text" name="prize[prize_level][]" class="form-control" placeholder="例如：一等奖" value="{$v['prize_level']}"></td>
                        <td class="text-center"><input type="text" name="prize[prize_name][]" class="form-control" placeholder="例如：法拉利跑车" value="{$v['prize_name']}"></td>
                        <td class="text-center"><input type="text" name="prize[prize_count][]" class="form-control" placeholder="例如：3" value="{$v['prize_count']}"></td>
                   </tr>
                   {/loop}
            </table>
            </div>
            <p class="help-block">最后一项必须设为未中奖项，内容可随意填写（例如：差一点就中奖了）。</p>
            </div>
        </td>
    </tr>
    <tr>
        <td width="200">活动说明</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <textarea name="data[content]" class="form-control" rows="3">{$wall['content']}</textarea>
          </div>
        </td>
    </tr>
    <tr>
        <td width="200">赞助支持</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <textarea name="data[support]" class="form-control" rows="3">{$wall['support']}</textarea>
            </div>
        </td>
    </tr>
    <tr>
        <td></td>
        <td>
            <div class="col-md-4">
                <input type="hidden" name="id" value="{$wall['id']}">
                <input type="submit" name="submit" class="btn btn-primary" value="确认" />
                <input type="reset" name="reset" class="btn btn-default" value="重置" />
            </div>
        </td>
    </tr>
</table>
</form>
<script type="text/javascript" src="__PUBLIC__/fancybox/jquery.fancybox.js?v=2.1.5"></script>
<script src="__ASSETS__/js/jquery.datetimepicker.js"></script>
<script type="text/javascript">
    //iframe显示
    $(function(){
    	//弹出框
    	$(".fancybox").fancybox({
    		title : '',
    		closeBtn : false,
    		width : '60%'
    	});
    })
    //日历显示
    $("#starttime, #endtime").datetimepicker({
    	lang:'ch',
    	format:'Y-m-d H:i',
    	timepicker:true
    });
    //添加奖项
    function addprize(obj){
    	var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="prize[prize_level][]" class="form-control" placeholder="例如：一等奖"></td><td class="text-center"><input type="text" name="prize[prize_name][]" class="form-control" placeholder="例如：法拉利跑车"></td><td class="text-center"><input type="text" name="prize[prize_count][]" class="form-control" placeholder="例如：3"></td></tr>';
        $(obj).parent().parent().parent().append(html);
    }
    //删除奖项
    function delprize(obj){
        $(obj).parent().parent().remove();
    }
</script>
{include file="pagefooter"}