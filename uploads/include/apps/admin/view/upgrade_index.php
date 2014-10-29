{include file="pageheader"}

<div class="row" style="margin:0">
  <div class="ectouch-mb5">
  	<a href="{url('index')}" class="btn btn-info">{$lang['upgrade']}</a>
    <a href="{url('checkfile')}" class="btn btn-success">{$lang['checkfile']}</a>
  </div>
</div>

<p class="bg-success" style="padding: 10px;line-height: 24px;">{$lang['upgrade_notice']}</p>

<form action="{url('init')}" method="post">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">{$lang['upgrade']}</h3>
    </div>
    <div class="panel-body" style="padding:0;">
      <table class="table table-hover ectouch-table">
        <tr>
          <td width="70%">{$lang['currentversion']}
            <?php if(empty($pathlist)){ echo $lang['lastversion']; }?></td>
          <td width="30%" class="text-right">{$lang['updatetime']}</td>
        </tr>
        <tr>
          <td>{VERSION}</td>
          <td class="text-right">{RELEASE}</td>
        </tr>
      </table>
    </div>
  </div>
  <?php if(!empty($pathlist)) {?>
  <table class="table table-hover ectouch-table" style="border:1px #ddd solid; margin-bottom:10px;">
    <tr>
      <th width="70%">{$lang['updatelist']}</th>
      <th width="30%" class="text-right">{$lang['updatetime']}</th>
    </tr>
    <?php foreach($pathlist as $v) { ?>
    <tr>
      <td><?php echo $v;?></td>
      <td class="text-right"><?php echo substr($v, 7, 8);?></td>
    </tr>
    <?php }?>
  </table>
  <p>
  <input name="cover" id="cover" type="checkbox" value="1" />
  <label for="cover"><font color="red">{$lang['covertemplate']}</font></label>
  </p>
  <p>
  <input name="dosubmit" type="submit" id="dosubmit" value="{$lang['begin_upgrade']}" class="btn btn-warning" />
  </p>
  <?php }?>
</form>
{include file="pagefooter"}