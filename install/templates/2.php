<?php require INSTALL_PATH . 'templates/header.php';?>
	<div class="section">
		<div class="main server">
			<table width="100%">
				<tr>
					<td class="td1">环境检测</td>
					<td class="td1" width="25%">推荐配置</td>
					<td class="td1" width="25%">当前状态</td>
					<td class="td1" width="25%">最低要求</td>
				</tr>
				<tr>
					<td>操作系统</td>
					<td>类UNIX</td>
					<td><span class="correct_span">&radic;</span> <?php echo $server['os']; ?></td>
					<td>不限制</td>
				</tr>
				<tr>
					<td>PHP版本</td>
					<td>>5.3.x</td>
					<td><span class="correct_span">&radic;</span> <?php echo $server['php']; ?></td>
					<td>5.3.0</td>
				</tr>
				<tr>
					<td>Mysql版本</td>
					<td>>5.x.x</td>
					<td><?php echo $server['mysql']; ?></td>
					<td>5.0</td>
				</tr>
				<tr>
					<td>附件上传</td>
					<td>>2M</td>
					<td><?php echo $server['uploadSize']; ?></td>
					<td>不限制</td>
				</tr>
				<tr>
					<td>session</td>
					<td>开启</td>
					<td><?php echo $server['session']; ?></td>
					<td>开启</td>
				</tr>
				<tr>
					<td>curl扩展</td>
					<td>开启</td>
					<td><?php echo $server['curl']; ?></td>
					<td>开启</td>
				</tr>
			</table>
			<table width="100%">
				<tr>
					<td class="td1">目录、文件权限检查</td>
					<td class="td1" width="25%">写入</td>
					<td class="td1" width="25%">读取</td>
				</tr>
				<?php
                foreach ($folder as $dir) {
                    $Testdir = $site_path.$dir;
                    dir_create($Testdir);
                    if (TestWrite($Testdir)) {
                        $w = '<span class="correct_span">&radic;</span>可写 ';
                    } else {
                        $w = '<span class="correct_span error_span">&radic;</span>不可写 ';
                        $error++;
                    }
                    if (is_readable($Testdir)) {
                        $r = '<span class="correct_span">&radic;</span>可读' ;
                    } else {
                        $r = '<span class="correct_span error_span">&radic;</span>不可读';
                        $error++;
                    } ?>
				<tr>
					<td><?php echo $dir; ?></td>
					<td><?php echo $w; ?></td>
					<td><?php echo $r; ?></td>
				</tr>
				<?php
                } ?>
			</table>
			</div>
		</div>
		<div class="btn-box">
		<a href="./index.php?step=2" class="btn">重新检测</a>
		<?php if (empty($error)) {
                    ?>
			<a href="./index.php?step=3" class="btn">下一步</a>
		<?php
                } else {
                    ?>
			<button href="#" class="btn error" disabled>当前有误</button>
		<?php
                } ?>
		</div>
<?php require INSTALL_PATH . 'templates/footer.php';?>