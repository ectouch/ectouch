{include file="pageheader"}
<div class="container-fluid" style="padding:0">
	<div class="row" style="margin:0">
	  <div class="col-md-12 col-sm-12 col-lg-12" style="padding:0;">
		<div class="panel panel-default">
			<div class="panel-heading">编辑功能扩展</div>
			<div class="panel-body">
			<form action="{url('function_edit')}" method="post" class="form-horizontal" role="form">
			<table class="table table-hover ectouch-table">
                <tr>
                    <td width="200">功能名称</td>
                    <td><div class="col-md-4">{$modules['name']}</div></td>
                </tr>
                <tr>
                    <td width="200">关键词</td>
                    <td><div class="col-md-4">{$modules['keywords']}</div></td>
                </tr>
                <tr>
                    <td width="200">扩展词</td>
                    <td>
                        <div class="col-md-4">
                            <input type="text" name="command" class="form-control" value="{$modules['command']}" />
                            <p class="help-block">多个变形词，请用“,”隔开</p>
                        </div>
                    </td>
                </tr>
                {if $modules['config']}
                {loop $modules['config'] $key $val}
                {if $val['type'] == 'hidden'}
                <input type="hidden" name="cfg_name[]" value="{$val['name']}" />
                <input type="hidden" name="cfg_value[]" value="{$val['value']}">
                {else}
                <tr>
                    <td width="200">{$val['label']}</td>
                    <td>
                        <div class=" col-md-4 col-sm-4">
                            {if $val['type'] == 'radio'}
                                <input type="hidden" name="cfg_name[]" value="{$val['name']}" />
                                <div class="btn-group" data-toggle="buttons">
                                    <label class="btn btn-primary {if $val['value']}active{/if}">
                                        <input type="radio" name="cfg_value[]" {if $val['value']}checked{/if} value="1" />开启
                                    </label>
                                    <label class="btn btn-primary {if empty($val['value'])}active{/if}">
                                        <input type="radio" name="cfg_value[]" {if empty($val['value'])}checked{/if} value="0" />关闭
                                    </label>
                                </div>
                            {elseif $val['type'] == 'text'}
                                <input type="hidden" name="cfg_name[]" value="{$val['name']}" />
                                {if isset($val['calendar']) && !empty($val['calendar'])}
                                <link rel="stylesheet" type="text/css" href="__ASSETS__/css/jquery.datetimepicker.css" />
                                <script type="text/javascript" src="__ASSETS__/js/jquery.datetimepicker.js"></script>
                                <input type="text" name="cfg_value[]" class="form-control" id="datetime{$key}" value="{$val['value']}" />
                                <script type="text/javascript">
                                $("#datetime{$key}").datetimepicker({
                                	lang:'ch',
                                	format:'Y-m-d',
                                	timepicker:false    
                                });
                                </script>
                                {else}
                                <input type="text" name="cfg_value[]" class="form-control" value="{$val['value']}" />
                                {/if}
                            {elseif $val['type'] == 'select'}
                                <input type="hidden" name="cfg_name[]" value="{$val['name']}" />
                                <select name="cfg_value[]" class="form-control">
                                    {loop $val['options'] $k $v}
                                        <option value="{$v}" {if $v == $val['value']}selected{/if}>{$k}</option>
                                    {/loop}
                                </select>
                            {elseif $val['type'] == 'button'}
                            <p>
                                <a href="javascript:change();" class="btn btn-primary">选择素材</a>
                                <span class="text-muted">请选择单图文素材，否则不能使用</span>
                            </p>
                            <style>
                            .article{border:1px solid #ddd;padding:5px 5px 0 5px;}
                            .cover{height:160px; position:relative;margin-bottom:5px;overflow:hidden;}
                            .article .cover img{width:100%; height:auto;}
                            .article span{height:40px; line-height:40px; display:block; z-index:5; position:absolute;width:100%;bottom:0px; color:#FFF; padding:0 10px; background-color:rgba(0,0,0,0.6)}
                            .article_list{padding:5px;border:1px solid #ddd;border-top:0;overflow:hidden;}
                            .thumbnail{padding:0;}
                            </style>
                            <input type="hidden" name="media" value="media" />
                            <div class="content thumbnail borderno">
                                <input type="hidden" name="media_id" value="{$val['media']['id']}">
                                <div class="article">
                                        <h4>{$val['media']['title']}</h4>
                                        <p>{date('Y年m月d日', $val['media']['add_time'])}</p>
                                        <div class="cover"><img src="{$val['media']['file']}" /></div>
                                        <p>{$val['media']['content']}</p>
                                    </div>
                            </div>
                            <script type="text/javascript">
                            function change(){
                                layer.open({
                                    type: 2, 
                                    title: '选择素材', 
                                    shadeClose: true, 
                                    shade: 0.8, 
                                    area: ['60%', '60%'], 
                                    content: "{url('wechat/auto_reply', array('type'=>'news', 'no_list'=>1))}"
                                });
                            }
                            </script>
                            {else}
                            <input type="text" name="cfg_value[]" value="{$val['value']}" class="form-control" readonly />
                            {/if}
                        </div>
                    </td>
                </tr>
                {/if}
                {/loop}
                {/if}
                <tr>
                    <td></td>
                    <td>
                        <div class="col-md-4">
                            <input type="hidden" name="keywords" value="{$modules['keywords']}" />
                            <input type="submit" name="submit" class="btn btn-primary" value="确认" />
                            <input type="reset" name="reset" class="btn btn-default" value="重置" />
                        </div>
                    </td>
                </tr>
			</table>
			</form>
			</div>
		</div>
	  </div>
	</div>
</div>
{include file="pagefooter"}