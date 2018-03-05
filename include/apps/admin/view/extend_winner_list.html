{include file="pageheader"}
<div class="container-fluid" style="padding:0">
	<div class="row" style="margin:0">
	  <div class="col-md-12 col-sm-12 col-lg-12" style="padding:0;">
		<div class="panel panel-default">
			<div class="panel-heading">中奖名单</div>
			<table class="table table-hover table-striped table-bordered">
                <tr>
                    <th class="text-center">微信昵称</th>
                    <th class="text-center">奖品</th>
                    <th class="text-center">是否发放</th>
                    <th class="text-center">中奖用户信息</th>
                    <th class="text-center">中奖时间</th>
                    <th class="text-center">操作</th>
                </tr>
                {loop $list $val}
                <tr>
                    <td class="text-center">{$val['nickname']}</td>
                    <td class="text-center">{$val['prize_name']}</td>
                    <td class="text-center">{if $val['issue_status']}已发放{else}未发放{/if}</td>
                    <td class="text-center">{if is_array($val['winner'])}{$val['winner']['name']}<br />{$val['winner']['phone']}<br />{$val['winner']['address']}{/if}</td>
                    <td class="text-center">{date('Y-m-d H:i:s',$val['dateline'])}</td>
                    <td class="text-center">
                        {if $val['issue_status']}<a href="{url('winner_issue', array('id'=>$val['id'], 'cancel'=>1))}" class="btn btn-primary">取消发放</a>{else}<a href="{url('winner_issue', array('id'=>$val['id']))}" class="btn btn-primary">立即发放</a>{/if}
                        <a href="{url('wechat/send_custom_message', array('openid'=>$val['openid']))}" class="btn  btn-info fancybox fancybox.iframe">通知用户</a>
                        <a href="javascript:if(confirm('{$lang['confirm_delete']}'))window.location.href='{url('winner_del', array('id'=>$val['id']))};';" class="btn btn-default">删除</a></td>
                </tr>
                {/loop}
			</table>
		</div>
	  </div>
	</div>
</div>
{include file="pagefooter"}