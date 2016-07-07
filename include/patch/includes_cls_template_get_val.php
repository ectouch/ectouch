<?php
$val = preg_replace_callback("/\[([^\[\]]*)\]/is", function($r){return '.' . $r[1];}, $val);