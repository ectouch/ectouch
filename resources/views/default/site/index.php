<?php

use yii\helpers\Url;

?>
<div class="">
  <a href="javascript:test();">Test AJAX</a>
</div>
<script type="text/javascript">
function test(){
  $.post('<?= Url::to(['test']) ?>', function(){
    alert('test');
  })
}
</script>
