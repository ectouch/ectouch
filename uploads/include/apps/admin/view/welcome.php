{include file="pageheader"}

<script src="http://ectouch.cn/api/notice.html"></script>
<script src="http://ectouch.cn/api/patch.html?release=<?php echo RELEASE;?>"></script>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{$lang['system_info']}</h3>
  </div>
  <div class="panel-body" style="padding:0;">
    <table class="table table-hover ectouch-table">
      <tr>
        <td width="20%">{$lang['os']}</td>
        <td width="30%">{$sys_info['os']} ({$sys_info['ip']})</td>
        <td width="20%">{$lang['web_server']}</td>
        <td width="30%">{$sys_info['web_server']}</td>
      </tr>
      <tr>
        <td>{$lang['php_version']}</td>
        <td>{$sys_info['php_ver']}</td>
        <td>{$lang['mysql_version']}</td>
        <td>{$sys_info['mysql_ver']}</td>
      </tr>
      <tr>
        <td>{$lang['safe_mode']}</td>
        <td>{$sys_info['safe_mode']}</td>
        <td>{$lang['safe_mode_gid']}</td>
        <td>{$sys_info['safe_mode_gid']}</td>
      </tr>
      <tr>
        <td>{$lang['socket']}</td>
        <td>{$sys_info['socket']}</td>
        <td>{$lang['timezone']}</td>
        <td>{$sys_info['timezone']}</td>
      </tr>
      <tr>
        <td>{$lang['gd_version']}</td>
        <td>{$sys_info['gd']}</td>
        <td>{$lang['zlib']}</td>
        <td>{$sys_info['zlib']}</td>
      </tr>
      <tr>
        <td>{$lang['ip_version']}</td>
        <td>{$sys_info['ip_version']}</td>
        <td>{$lang['max_filesize']}</td>
        <td>{$sys_info['max_filesize']}</td>
      </tr>
      <tr>
        <td>{$lang['ecs_version']}</td>
        <td>{$ecs_version} RELEASE {$ecs_release} ({$ecs_charset}){$empower}</td>
        <td>{$lang['install_date']}</td>
        <td>{$install_date}</td>
      </tr>
    </table>
  </div>
</div>
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">安全提示</h3>
  </div>
  <div class="panel-body ectouch-line"> 强烈建议您将 data/config.php 文件属性设置为644（linux/unix）或只读权限（WinNT）<br>
    强烈建议您在网站上线之后将后台入口目录 admin 重命名，可增加系统安全性<br>
    请注意定期做好数据备份，数据的定期备份可最大限度的保障您网站数据的安全 </div>
</div>
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">许可协议</h3>
  </div>
  <div class="panel-body ectouch-line"> 未经商业授权，不得将本软件用于商业用途(企业网站或以盈利为目的经营性网站)，否则我们将保留追究的权力。<br>
    用户自由选择是否使用本软件，在使用中出现任何问题和由此造成的一切损失官方将不承担任何责任；<br>
    利用 ECTouch 构建网站的任何信息内容以及导致的任何版权纠纷和法律争议及后果，ECTouch 官方不承担任何责任；<br>
    所有用户均可查看 ECTouch 的全部源代码，您可以对本系统进行修改和美化，但必须保留完整的版权信息;<br>
    本软件受中华人民共和国《著作权法》《计算机软件保护条例》等相关法律、法规保护，软件作者保留一切权利。 </div>
</div>
{include file="pagefooter"}