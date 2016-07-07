<?php
$out = "<?php \n" . '$k = ' . preg_replace_callback("/(\'\\$[^,]+)/", function($r){return stripcslashes(trim($r[1], '\''));}, var_export($t, true)) . ";\n";