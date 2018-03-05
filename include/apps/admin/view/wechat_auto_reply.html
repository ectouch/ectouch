{include file="wechat_header"}
<style type="text/css">
.article{border:1px solid #ddd;padding:5px 5px 0 5px;}
.cover{height:160px; position:relative;margin-bottom:5px;overflow:hidden;}
.article .cover img{width:100%; height:auto;}
.article span{height:40px; line-height:40px; display:block; z-index:5; position:absolute;width:100%;bottom:0px; color:#FFF; padding:0 10px; background-color:rgba(0,0,0,0.6)}
.article_list{padding:5px;border:1px solid #ddd;border-top:0;overflow:hidden;}
.radio label{width:100%;position:relative;padding:0;}
.radio .news_mask{position:absolute;left:0;top:0;background-color:#000;opacity:0.5;width:100%;height:100%;z-index:10;}
</style>
<div class="panel panel-default" style="margin:0;">
<div class="panel-heading">素材选择</div>
    {if $type == 'news'}
    <div class="panel-body">
    <div class="col-md-4 col-sm-4 col-md-offset-2 col-sm-offset-2">
    {loop $list $key $val}
    {if $key%2 == 0}
        <div class="radio">
            <label>
            <input type="radio" name="id" value="{$val['id']}" file_type="{$type}" file_name="{$val['file_name']}" class="hidden" no_list={$no_list} />
            {if $val['article_id']}
                <div>
                {loop $val['articles'] $k $v}
                    {if $k == 0}
                    <div class="article">
                        <p>{date('Y年m月d日', $v['add_time'])}</p>
                        <div class="cover"><img src="{$v['file']}" /><span>{$v['title']}</span></div>
                    </div>
                        {else}
                    <div class="article_list">
                        <span>{$v['title']}</span>
                        <img src="{$v['file']}" width="78" height="78" class="pull-right" />
                    </div>
                    {/if}
                {/loop}
                </div>
                {else}
                <div>
                    <div class="article">
                        <h4>{$val['title']}</h4>
                        <p>{date('Y年m月d日', $val['add_time'])}</p>
                        <div class="cover"><img src="{$val['file']}" /></div>
                        <p>{$val['content']}</p>
                    </div>
                </div>
            {/if}
            <div class="news_mask hidden"></div>
        </label>
      </div>
    {/if}
    {/loop}
    </div>
    <div class="col-md-4 col-sm-4">
        {loop $list $key $val}
            {if ($key+1)%2 == 0}
            <div class="radio">
            <label>
            <input type="radio" name="id" value="{$val['id']}" file_type="{$type}" file_name="{$val['file_name']}" class="hidden" no_list={$no_list} />
            {if $val['article_id']}
                <div>
                {loop $val['articles'] $k $v}
                    {if $k == 0}
                    <div class="article">
                        <p>{date('Y年m月d日', $v['add_time'])}</p>
                        <div class="cover"><img src="{$v['file']}" /><span>{$v['title']}</span></div>
                    </div>
                        {else}
                    <div class="article_list">
                        <span>{$v['title']}</span>
                        <img src="{$v['file']}" width="78" height="78" class="pull-right" />
                    </div>
                    {/if}
                {/loop}
                </div>
                {else}
                <div>
                    <div class="article">
                        <h4>{$val['title']}</h4>
                        <p>{date('Y年m月d日', $val['add_time'])}</p>
                        <div class="cover"><img src="{$val['file']}" /></div>
                        <p>{$val['content']}</p>
                    </div>
                </div>
            {/if}
            <div class="news_mask hidden"></div>
        </label>
      </div>
            {/if}
        {/loop}
    </div>
    </div>
    {else}
    <ul class="list-group">
    {loop $list $key $val}
    <li class="list-group-item">
        <div class="form-group">
            <div class="col-md-6 col-sm-6 radio">
              <label><input type='radio' name='id' value="{$val['id']}" data="{$val['file']}" file_type="{$type}" file_name="{$val['file_name']}" />{$val['file_name']}</label>
            </div>
            <div class="col-md-3 col-sm-3">{$val['size']}</div>
            <div class="col-md-3 col-sm-3">{date('Y-m-d H:i:s', $val['add_time'])}</div>
        </div>
        <div class="clear">
        {if $val['type'] == 'voice'}
            <img src="__PUBLIC__/images/voice.png" class="img-rounded" width="100" height="70" />
        {elseif $val['type'] == 'video'}
            <img src="__PUBLIC__/images/video.png" class="img-rounded" width="100" height="70" />
        {else}
            <img src="{$val['file']}" class="img-rounded" width="100" height="70" />
        {/if}
        </div>
    </li>
    {/loop}
    </ul>
    {/if}
    <div class="panel-footer">
        {include file="pageview"}
        <div class="form-group" style="margin:20px 0;">
		  <input type="submit" value="{$lang['button_submit']}" class="btn btn-primary closebox" name="file_submit" />
       </div>
    </div>
</div>
<script type="text/javascript">
$(function(){
	//选择素材
	$("input[name=file_submit]").click(function(){
		var obj = $("input[name=id]:checked");
	    var id = obj.val();
	    var file = obj.attr("data");
	    var type = obj.attr("file_type");
	    var file_name = obj.attr("file_name");
	    if(type == 'voice'){
	    	window.parent.$(".content").html("<input type='hidden' name='media_id' value='"+id+"'><img src='__PUBLIC__/images/voice.png' class='img-rounded' /><span class='help-block'>"+file_name+"</span>");		     
		}
	    else if(type == 'video'){
	    	window.parent.$(".content").html("<input type='hidden' name='media_id' value='"+id+"'><img src='__PUBLIC__/images/video.png' class='img-rounded' /><span class='help-block'>"+file_name+"</span>");		     
		}
	    else if(type == 'news'){
		    var no_list = obj.attr("no_list");
	        var html = obj.siblings("div").html();
	        if(no_list == 1){
	        	html += '<input type="hidden" name="cfg_value[media_id]" value="'+id+'">';
		    }
	        else{
	        	html += '<input type="hidden" name="media_id" value="'+id+'">';
		    }
	        window.parent.$(".content").html(html);
		}
	    else if(type == 'image'){
		    window.parent.$(".content").html("<input type='hidden' name='media_id' value='"+id+"'><img src='"+file+"' class='img-rounded' />");
		}

		if(id != undefined){
		  window.parent.$(".content").removeClass("hidden").siblings("div").addClass("hidden");
		  window.parent.$("input[name=content_type]").val(type);
		  window.parent.$.fancybox.close();
		}
	});
	$("input[name=id]").click(function(){
	    if($(this).is(":checked")){
		    $(".news_mask").addClass("hidden");
	        $(this).siblings(".news_mask").removeClass("hidden");
		}
	});
})
</script>
{include file="pagefooter"}