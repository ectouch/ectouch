{include file="pageheader"}

<div class="row" style="margin-top:120px;">
  <div class="col-md-4 col-md-offset-4">
    <div class="panel panel-default">
      <div class="panel-heading">{$data['title']}</div>
      <div class="panel-body">
      	<?php if($data['type'] != 'error') {?>
      	<p class="text-success">{$data['message']}</p>
        <?php }else{?>
      	<p class="text-danger">{$data['message']}</p>
        <?php }?>
      	<p>等待<b id="wait">{$data['second']}</b>秒之后自动跳转<br><a id="href" href="{$data['url']}">如果页面没有跳转，请点击这里</a></p>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 1000);
})();
</script>

{include file="pagefooter"}