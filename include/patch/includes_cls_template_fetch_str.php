<?php
$template = $this;
return preg_replace_callback("/{([^\}\{\n]*)}/", function($r) use(&$template){return $template->select($r[1]);}, $source);