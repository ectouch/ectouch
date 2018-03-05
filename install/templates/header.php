<!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title><?php echo $config['name'].' '.$config['version']; ?> - <?php echo $config['powered']; ?></title>
		<link rel="stylesheet" href="./templates/css/install.css" />
	</head>
	<body>
		<div class="wrap">
			<div class="header">
				<h1 class="header-title"><?php echo $config['name']; ?>安装向导</h1>
				<div class="header-version">版本：<?php echo $config['version'];?></div>
			</div>
			<div class="step">
				<ul>
					<?php echo $step_html;?>
				</ul>
			</div>