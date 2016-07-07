<?php
$pattern     = '/<!--\s#BeginLibraryItem\s\"\/(.*?)\"\s-->.*?<!--\s#EndLibraryItem\s-->/s';
$source      = preg_replace_callback($pattern, function($r){return '{include file=' . strtolower($r[1]). '}';}, $source);