<?php
return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {return strtoupper($match[1]);}, $name));