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
<form action="{url('edit', array('ks'=>'zjd'))}" method="post" class="form-horizontal" role="form">
<table class="table table-hover ectouch-table">
    <tr>
        <td width="200">功能名称</td>
        <td><div class="col-md-4">{$config['name']}</div></td>
    </tr>
    <tr>
        <td width="200">关键词</td>
        <td><div class="col-md-4">{$config['command']}</div></td>
    </tr>
    <tr>
        <td width="200">扩展词</td>
        <td>
            <div class="col-md-4">
                <input type="text" name="data[keywords]" class="form-control" value="{$config['keywords']}" />
                <p class="help-block">多个变形词，请用“,”隔开</p>
            </div>
        </td>
    </tr>
    <tr>
        <td width="200">积分赠送</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <div class="btn-group" data-toggle="buttons">
                    <label class="btn btn-primary {if $config['config']['point_status']}active{/if}">
                        <input type="radio" name="cfg_value[point_status]" {if $config['config']['point_status']}checked{/if} value="1" />开启
                    </label>
                    <label class="btn btn-primary {if empty($config['config']['point_status'])}active{/if}">
                        <input type="radio" name="cfg_value[point_status]" {if empty($config['config']['point_status'])}checked{/if} value="0" />关闭
                    </label>
                </div>
           </div>
        </td>
    </tr>
    <tr>
        <td width="200">积分值</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[point_value]" class="form-control" value="{$config['config']['point_value']}" />
           </div>
        </td>
    </tr>
   <tr>
        <td width="200">有效次数</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[point_num]" class="form-control" value="{$config['config']['point_num']}" />
           </div>
        </td>
    </tr>
    <tr>
        <td width="200">时间间隔</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <select name="cfg_value[point_interval]" class="form-control">
                        <option value="86400" {if $config['config']['point_interval'] == 86400}selected{/if}>24小时</option>
                        <option value="3600" {if $config['config']['point_interval'] == 3600}selected{/if}>1小时</option>
                        <option value="60" {if $config['config']['point_interval'] == 60}selected{/if}>1分钟</option>
                </select>
           </div>
        </td>
    </tr>
   <tr>
        <td width="200">砸金蛋参与人数</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[people_num]" value="{if $config['config']['people_num']}{$config['config']['people_num']}{else}0{/if}" class="form-control" readonly />
           </div>
        </td>
    </tr>
    <tr>
        <td width="200">抽奖次数</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[prize_num]" class="form-control" value="{$config['config']['prize_num']}" />
          </div>
        </td>
    </tr>
    <tr>
        <td width="200">开始时间</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[starttime]" class="form-control" id="starttime" value="{$config['config']['starttime']}" />
          </div>
        </td>
    </tr>
    <tr>
        <td width="200">结束时间</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <input type="text" name="cfg_value[endtime]" class="form-control" id="endtime" value="{$config['config']['endtime']}" />
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
                        <th class="text-center" width="20%">概率(总数为100%)</th>
                    </tr>
                    {loop $config['config']['prize'] $v}
                    <tr>
                        <td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td>
                        <td class="text-center"><input type="text" name="cfg_value[prize_level][]" class="form-control" placeholder="例如：一等奖" value="{$v['prize_level']}"></td>
                        <td class="text-center"><input type="text" name="cfg_value[prize_name][]" class="form-control" placeholder="例如：法拉利跑车" value="{$v['prize_name']}"></td>
                        <td class="text-center"><input type="text" name="cfg_value[prize_count][]" class="form-control" placeholder="例如：3" value="{$v['prize_count']}"></td>
                        <td class="text-center">
                            <div class="input-group">
                                <input type="text" name="cfg_value[prize_prob][]"  class="form-control" placeholder="例如：1%" value="{$v['prize_prob']}">
                                <span class="input-group-addon">%</span>
                            </div>
                        </td>
                   </tr>
                   {/loop}
            </table>
            </div>
            <p class="help-block">最后一项必须设为未中奖项，内容可随意填写（例如：差一点就中奖了）。</p>
            </div>
        </td>
    </tr>
    <tr>
        <td width="200">活动规则</td>
        <td>
            <div class="col-md-4 col-sm-4">
                <textarea name="cfg_value[description]" class="form-control" rows="3">{$config['config']['description']}</textarea>
          </div>
        </td>
    </tr>
    <tr>
        <td width="200">素材信息</td>
        <td>
            <div class="col-md-2 col-sm-2">
                <input type="text" name="cfg_value[media_id]" class="form-control" value="{$config['config']['media_id']}" readonly />
                <span class="help-block">对应素材管理中的素材id</span>
          </div>
        </td>
    </tr>                                              
    <tr>
        <td></td>
        <td>
            <div class="col-md-4">
                <input type="hidden" name="data[command]" value="{$config['command']}" />
                <input type="hidden" name="data[name]" value="{$config['name']}" />
                <input type="hidden" name="data[author]" value="{$config['author']}">
                <input type="hidden" name="data[website]" value="{$config['website']}">
                <input type="hidden" name="cfg_value[haslist]" value="1">
                <input type="hidden" name="handler" value="{$config['handler']}">
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
    	format:'Y-m-d',
    	timepicker:false    
    });
    //添加奖项
    function addprize(obj){
    	var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="cfg_value[prize_level][]" class="form-control" placeholder="例如：一等奖"></td><td class="text-center"><input type="text" name="cfg_value[prize_name][]" class="form-control" placeholder="例如：法拉利跑车"></td><td class="text-center"><input type="text" name="cfg_value[prize_count][]" class="form-control" placeholder="例如：3"></td><td class="text-center"><div class="input-group"><input type="text" name="cfg_value[prize_prob][]"  class="form-control" placeholder="例如：1"><span class="input-group-addon">%</span></div></td></tr>';
        $(obj).parent().parent().parent().append(html);
    }
    //删除奖项
    function delprize(obj){
        $(obj).parent().parent().remove();
    }
</script>