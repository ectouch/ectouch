<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{$lang['cp_home']}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<script type="text/javascript" src="__PUBLIC__/js/jquery.min.js"></script>
<script type="text/javascript" src="__PUBLIC__/artDialog/jquery.artDialog.js?skin=aero"></script>
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
<form method="post" action="{url('forget')}" name='theForm'>
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
            <td>{$lang['label_email']}</td>
            <td colspan="2"><input type="text" name="email" class="logininput" /></td>
          </tr>
          <tr>
            <td>{$lang['label_captcha']}</td>
            <td width="100"><input name="captcha" type="text" class="logincaptcha" maxlength="4" /></td>
            <td width="100"><img src="{url('verify')}" onclick="this.src='{url('verify')}&v='+Math.random();" width="50" title="{$lang['click_for_another']}" style="cursor: pointer;" /></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:center"><input type="submit" value="{$lang['get_new_pwd']}" class="loginsignin" /></td>
          </tr>
          <tr>
            <td colspan="3" style="text-align:center">&raquo; <a href="./" class="fcolor">{$lang['back_home']}</a> &nbsp;&nbsp;&raquo; <a href="{url('login')}" class="fcolor">{$lang['goto_login']}</a></td>
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
$(function(){
    $('form[name="theForm"]').submit(function(){
        var username = $('input[name="username"]').val();
        var email = $('input[name="email"]').val();
        var captcha = $('input[name="captcha"]').val();
        $.post("{url('forget')}", {username:username, email:email, captcha:captcha}, function(result){
            if(result.err > 0){
                art.dialog({
                    id: 'tipBox',
                    time: 1,
                    icon: 'warning',
                    content: result.msg,
                    lock: true
                });
            }else{
                window.location.href = "{url('login')}";
            }
        }, 'json');
        return false;
    })
})
</script>
</body>
</html>