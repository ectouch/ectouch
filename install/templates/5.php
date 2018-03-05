<?php require INSTALL_PATH . 'templates/header.php';?>
	<div class="section">
		<div class="main">
			<div class="success_tip cc"> 
				<a href="../admin">安装完成，进入后台管理</a>
				<p><?php echo $config['alreadyInstallInfo']?><p>
			</div>
		</div>
	</div>
	<div class="btn-box">
		<a href="../index.php" class="btn">进入前台</a>
		<a href="../admin" class="btn">进入后台</a>
	</div>
<?php require INSTALL_PATH . 'templates/footer.php';?>