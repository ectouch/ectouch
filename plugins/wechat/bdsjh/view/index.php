<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>绑定手机号</title>
	<link rel="stylesheet" type="text/css" href="<?php echo __PUBLIC__;?>/bootstrap/css/bootstrap.min.css" />
	<script src="<?php echo __PUBLIC__;?>/js/jquery.min.js"></script>
	<script src="<?php echo __PUBLIC__;?>/bootstrap/js/bootstrap.min.js"></script>
	<script src="<?php echo __PUBLIC__;?>/js/validform.js"></script>
</head>
<body>
<div class="container-fluid" style="margin-top: 1.6rem;">
	<div class="row">
	<div class="col-sm-12 col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">绑定手机号</div>
			<div class="panel-body">
			<form action="<?php echo url('wechat/plugin_action', array('name'=>'bdsjh'));?>" method="post" class="form-horizontal validforms" role="form" onsubmit="if(!confirm('您确认要进行绑定操作吗'))return false;">
				<div class="form-group">
				    <!-- <label class="col-sm-2 control-label">手机号</label> -->
				    <div class="col-sm-10">
				      <input type="text" class="form-control" placeholder="填写真实手机号" name="data[username]" datatype="*" />
				    </div>
			  	</div>
			  	<div class="form-group">
				    <div class="col-sm-offset-2 col-sm-10" style="text-align:center;">
						<input type="submit" class="btn btn-primary" style="width:100%" value="确认" />
						<!-- <input type="reset" class="btn btn-default" value="重置" /> -->
				    </div>
				 </div>
				<div class="form-group">
					<p class="col-sm-12 control-label">微信用户绑定手机号，即可用手机号登录。</p>
				</div>
			</form>
			</div>
		</div>
	</div>
	</div>
</div>
</body>
</html>