<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>ECTouch - 安装程序</title>
<meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE">
<meta name="viewport" content="width=device-width"/>
<link rel="stylesheet" href="__ASSETS__/css/install.css"/>
</head>
<body>
<div class="blank68"></div>
<div class="m-install">
  <div class="install-head"> ECTouch - 安装程序 </div>
  <div class="install-model">
    <div class="m-form">
      <form action="<?php echo url('install/index/importing');?>" method="post" id="form">
        <?php if(empty($error)){ ?>
        <fieldset>
          <div class="formitm">
            <label class="lab" >数据库主机：</label>
            <div class="ipt">
              <input name="DB[DB_HOST]" type="text" class="u-ipt" id="DB_HOST" value="{$data['db_host']}" style="width:185px;" />
            </div>
          </div>
          <div class="formitm">
            <label class="lab" >数据库用户名：</label>
            <div class="ipt">
              <input name="DB[DB_USER]" type="text" class="u-ipt" id="DB_USER" value="{$data['db_user']}" />
            </div>
          </div>
          <div class="formitm">
            <label class="lab" >数据库密码：</label>
            <div class="ipt">
              <input name="DB[DB_PWD]" type="text" class="u-ipt" id="DB_PWD" value="{$data['db_pass']}" />
            </div>
          </div>
          <div class="formitm">
            <label class="lab" >数据库名：</label>
            <div class="ipt">
              <input name="DB[DB_NAME]" type="text" class="u-ipt" id="DB_NAME" value="{$data['db_name']}" />
            </div>
          </div>
          <div class="formitm">
            <label class="lab" >表前缀：</label>
            <div class="ipt">
              <input name="DB[DB_PREFIX]" type="text" class="u-ipt" id="DB_PREFIX" value="{$data['db_pre']}" />
            </div>
          </div>
          <div class="formitm">
            <label class="lab"></label>
            <div class="ipt">
              <label class="u-opt">
                <input name="agree" type="checkbox" value="1">
                同意《<a href="http://ectouch.cn/docs/license.txt" target="_blank">使用协议</a>》</label>
            </div>
          </div>
          <div class="install-status"></div>
          <div class="install-button">
            <button class="u-install-btn" type="submit">安装</button>
          </div>
          <?php }else{ ?>
          <div class="install-button">
            <div class="install-status">{$error}</div>
          </div>
          <?php } ?>
        </fieldset>
      </form>
    </div>
  </div>
</div>
</body>
<script src="__PUBLIC__/js/jquery.min.js"></script>
<script src="__PUBLIC__/js/validform.js"></script>
<script type="text/javascript">
$(function() {
	$('#form').Validform({
		ajaxPost: true,
		postonce: true,
		tiptype: function(){},
		callback: function(data) {
			if(data.status == '200' && data.statusText == 'OK'){
				$('.install-status').html('请核对数据库信息，调试信息可在浏览器控制台查看');
			} else if (data.status == "n") {
				$('.install-status').html(data.info);
			} else if (data.status == "y"){
				window.location.href = '<?php echo url('install/index/success');?>';
				$('.install-status').html('安装成功，正在为您跳转！');
			}
		}
	});
});
</script>
<!--Copyright © 2014 ectouch.cn. All Rights Reserved.-->
</html>