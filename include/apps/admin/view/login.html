<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{$lang['cp_home']}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="__PUBLIC__/js/jquery.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/artDialog/jquery.artDialog.js?skin=aero"></script>
<!--[if lt IE 8]>
<script src="__PUBLIC__/bootstrap/js/ie8-responsive-file-warning.js"></script>
<![endif]-->
<style type="text/css">
* {
	margin: 0;
	padding: 0;
	list-style: none;
	vertical-align: middle;
}
body {
	background: #fff;
	font: normal 12px/22px 微软雅黑;
	background: url(__ASSETS__/images/bg.jpg) repeat-x top center #ededed;
}
img {
	border: 0;
}
a {
	text-decoration: none;
	color: #333;
}
a.fcolor {
	color: #727272
}
.loginbox td {
	padding: 5px;
	text-align: left;
}
.loginbg {
	width: 521px;
	height: 327px;
	background: url(__ASSETS__/images/loginbg.png);
	color: #727272;
	font-size: #727272;
	margin: auto;
	margin-top: 212px;
}
.loginlogo {
	border: none;
	display: block;
	margin-top: 15px;
	text-align: center;
}
.logininput {
	width: 216px;
	height: 24px;
	padding: 0;
	line-height: 24px;
	background: url(__ASSETS__/images/txtbg.png) no-repeat;
	border: none;
	padding: 0px 3px;
}
.logincaptcha {
	width: 108px;
	height: 24px;
	padding: 0;
	background: url(__ASSETS__/images/txtbg.png) no-repeat;
	border: none;
	padding: 0px 3px;
	text-transform: uppercase;
}
.loginsignin {
	background: url(__ASSETS__/images/btn.jpg);
	width: 118px;
	height: 28px;
	padding: 0;
	color: #FFF;
	line-height: 28px;
	border: none;
	cursor: pointer;
}
</style>
</head>

<body>
<form method="post" action="{url('login')}" name='theForm'>
  <table cellspacing="0" cellpadding="0" align="center" class="loginbg">
    <tr>
      <td height="100" align="center" valign="middle"><a href="http://www.ectouch.cn" target="_blank"><img src="__ASSETS__/images/logo.jpg" class="loginlogo" /></a></td>
    </tr>
    <tr>
      <td height="200" align="center"><table cellspacing="0" cellpadding="5" width="320" class="loginbox">
          <tr>
            <td width="120">{$lang['label_username']}</td>
            <td colspan="2"><input type="text" name="username" class="logininput" /></td>
          </tr>
          <tr>
            <td>{$lang['label_password']}</td>
            <td colspan="2"><input type="password" name="password" class="logininput" /></td>
          </tr>
          {if $gd_version > 0}
          <tr>
            <td>{$lang['label_captcha']}</td>
            <td width="100"><input name="captcha" type="text" class="logincaptcha" maxlength="4" /></td>
            <td width="100"><img src="{url('verify')}" onclick="this.src='{url('verify')}&v='+Math.random();" width="50" title="{$lang['click_for_another']}" style="cursor: pointer;" /></td>
          </tr>
          {/if}
          <tr>
            <td></td>
            <td colspan="2"><input type="checkbox" value="1" name="remember" id="remember" />
              <label for="remember">{$lang['remember']}</label></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:center"><input type="submit" value="{$lang['signin_now']}" class="loginsignin" /></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:center">&raquo; <a href="./" class="fcolor">{$lang['back_home']}</a> &nbsp;&nbsp;&raquo; <a href="{url('forget')}" class="fcolor">{$lang['forget_pwd']}</a></td>
          </tr>
        </table></td>
    </tr>
    <tr>
      <td></td>
    </tr>
  </table>
  <input type="hidden" name="act" value="signin" />
</form>
<script type="text/javascript">
if (window.parent != window)
{
  window.top.location.href = location.href;
}
document.forms['theForm'].elements['username'].focus();

$(function(){
	$('form[name="theForm"]').submit(function(){
		var username = $('input[name="username"]').val();
		var password = $('input[name="password"]').val();
		var captcha = $('input[name="captcha"]').val();
		var remember = $('input[name="remember"]').is(':checked') ? 1:0;
		$.post("{url('login')}", {username:username, password:password, captcha:captcha, remember:remember}, function(result){
			if(result.err > 0){
				art.dialog({
					id: 'tipBox',
					time: 1,
					icon: 'warning',
					content: result.msg,
					lock: true
				});
			}else{
				window.location.href = "__ROOT__/index.php?m=admin";
			}
		}, 'json');
		return false;
	})
})
</script>
</body>
</html>