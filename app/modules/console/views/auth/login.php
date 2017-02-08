<div class="login-bg">
  <header>
    <div class="login-header"> ECTouch管理中心 </div>
  </header>
  <section>
    <form method="post" action="privilege.php" name='theForm' onsubmit="return validate()">
      <div class="login-con">
        <input name="username" type="text" placeholder="管理员用户名">
        <input name="password" type="password" placeholder="管理员密码" class="login_password">
                <div class="login-con-code">
          <input name="captcha" type="text" placeholder="验证码">
          <img src="index.php?act=captcha&849328316" onclick="this.src='index.php?act=captcha&'+Math.random();" title="看不清？点击更换另一个验证码。" class="cursor" />
        </div>
                <div style="overflow:hidden;">
          <div class="checkboxes pull-left">
            <label class="label_check" for="checkbox-06">
              <input name="remember" id="checkbox-06" value="1" type="checkbox"> 请保存我这次的登录信息。            </label>
          </div>
          <span class="pull-right"><a href="get_password.php?act=forget_pwd">您忘记了密码吗?</a></span>
        </div>
        <button class="btn ect-btn-login ect-clear" onClick="this.submit">进入管理中心</button>
      </div>
      <input type="hidden" name="act" value="signin" />
    </form>
  </section>
</div>
<footer>
  <div class="login-footer"></div>
</footer>
<div class="passport-bg"></div>