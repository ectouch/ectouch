{include file="pageheader"}
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$lang['preview']}</h3>
  </div>
  <div class="panel-body ectouch-line text-center">
      <div class="browser" style="display:none"><iframe src="__URL__/" width="360" height="640" scrolling="yes" frameborder="no"></iframe></div>
      <div class="qrimg"><img src="{$mobile_qr}" width="320" height="320" /></div>
      <a href="javascript:;" class="switch">扫码二维码，可以直接预览手机网站</div>
  </div>
</div>
<script type="text/javascript">
$(function(){
    $('.switch').click(function(){
        $('.browser').show().siblings('.qrimg').hide();
    })
})
</script>
{include file="pagefooter"} 