{include file="pageheader"}
<link rel="stylesheet" type="text/css" href="__PUBLIC__/webuploader/webuploader.css">
<script type="text/javascript" src="__PUBLIC__/webuploader/webuploader.min.js"></script>
<div class="container-fluid" style="padding:0">
	<div class="row" style="margin:0">
	  <div class="col-md-12 col-sm-12 col-lg-12" style="padding:0;">
		<div class="panel panel-default">
			<div class="panel-heading">素材管理</div>
			<div class="panel-body">
			    <ul class="nav nav-tabs" role="tablist">
                     <li role="presentation"><a href="{url('article')}">图文消息</a></li>
                     <li role="presentation"><a href="{url('picture')}">图片</a></li>
                     <li role="presentation"><a href="{url('voice')}">语音</a></li>
                     <li role="presentation" class="active"><a href="{url('video')}">视频</a></li>
			     </ul>
			</div>
			<div class="panel-body">
    			<form action="{url('video_edit')}" method="post" enctype="multipart/form-data" id="picForm">
    			     <div class="form-group">
        			     <div id="uploader" class="wu-example">
                            <!--用来存放文件信息-->
                            <div id="thelist" class="uploader-list"></div>
                            <div class="btns">
                                <div id="picker" style="display:inline-flex;">选择文件</div><span class="text-muted"> 大小: 不超过10M, 格式: mp4</span>
                            </div>
                        </div>
    			     </div>
    			     <div class="form-group">
    			         <input type="text" name="data[title]" placeholder="标题" class="form-control" value="{$video['title']}" />
    			     </div>
    			     <div class="form-group">
    			      <textarea class="form-control" name="data[content]" placeholder="简介（选填）" rows="5">{$video['content']}</textarea>
    			     </div>
    			     <div class="form-group">
        			     <input type="hidden" name="data[file]" id="file" value="{$video['file']}" />
        			     <input type="hidden" name="data[file_name]" id="file_name" value="{$video['file_name']}" />
        			     <input type="hidden" name="data[size]" id="size" value="{$video['size']}" />
        			     <input type="hidden" name="id" value="{$video['id']}" />
    			         <input type="submit" name="submit" value="保存" class="btn btn-primary" /> <input type="reset" name="reset" value="重置" class="btn btn-default" />
    			     </div>
    			</form>
			</div>
		</div>
	  </div>
	</div>
</div>
<script type="text/javascript">
$(function(){
	var $ = jQuery,
    $list = $('#thelist'),
    state = 'pending',
    uploader;
    uploader = WebUploader.create({
    	formData:{vid:<?php if($video['id'])echo $video['id'];else echo 0;?>},
        // 不压缩image
        resize: false,
        // swf文件路径
        swf: '__PUBLIC__/webuploader/Uploader.swf',
        // 文件接收服务端。
        server: '{url("video_upload")}',
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: '#picker',
        auto: true,
        accept:{title:'Video', extensions:'mp4', mimeTypes:'video/*'},
        fileNumLimit:1
    });    
    // 当有文件添加进来的时候
    uploader.on( 'fileQueued', function( file ) {
        $list.append( '<div id="' + file.id + '" class="item">' +
            '<h4 class="info">' + file.name + '</h4>' +
            '<p class="state">等待上传...</p>' +
        '</div>' );
    });
    // 文件上传过程中创建进度条实时显示。
    uploader.on( 'uploadProgress', function( file, percentage ) {
        var $li = $( '#'+file.id ),
            $percent = $li.find('.progress .progress-bar');
        // 避免重复创建
        if ( !$percent.length ) {
            $percent = $('<div class="progress progress-striped active">' +
              '<div class="progress-bar" role="progressbar" style="width: 0%">' +
              '</div>' +
            '</div>').appendTo( $li ).find('.progress-bar');
        }
    
        $li.find('p.state').text('上传中');
        $percent.css( 'width', percentage * 100 + '%' );
    }); 
    uploader.on( 'uploadSuccess', function( file ) {
        $('#'+file.id ).find('p.state').text('上传成功');
    });
    
    uploader.on( 'uploadAccept', function(object, ret) {
        if(ret.file_name){
            $("#file").val(ret.file);
            $("#file_name").val(ret.file_name);
            $("#size").val(ret.size);
        }
    });
    
    uploader.on( 'uploadError', function( file ) {
        $( '#'+file.id ).find('p.state').text('上传出错');
    });
    
    uploader.on( 'uploadComplete', function( file ) {
        $( '#'+file.id ).find('.progress').fadeOut();
    });
})
</script>
{include file="pagefooter"}