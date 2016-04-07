<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 获得当前格林威治时间的时间戳
 *
 * @return  integer
 */
function gmtime() {
    return (time() - date('Z'));
}

/**
 * 获得服务器的时区
 *
 * @return  integer
 */
function server_timezone() {
    if (function_exists('date_default_timezone_get')) {
        return date_default_timezone_get();
    } else {
        return date('Z') / 3600;
    }
}

/**
 *  生成一个用户自定义时区日期的GMT时间戳
 *
 * @access  public
 * @param   int     $hour
 * @param   int     $minute
 * @param   int     $second
 * @param   int     $month
 * @param   int     $day
 * @param   int     $year
 *
 * @return void
 */
function local_mktime($hour = NULL, $minute = NULL, $second = NULL, $month = NULL, $day = NULL, $year = NULL) {
    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : C('timezone');

    /**
     * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
     * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
     */
    $time = mktime($hour, $minute, $second, $month, $day, $year) - $timezone * 3600;

    return $time;
}

/**
 * 将GMT时间戳格式化为用户自定义时区日期
 *
 * @param  string       $format
 * @param  integer      $time       该参数必须是一个GMT的时间戳
 *
 * @return  string
 */
function local_date($format, $time = NULL) {
    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : C('timezone');

    if ($time === NULL) {
        $time = gmtime();
    } elseif ($time <= 0) {
        return '';
    }

    $time += ($timezone * 3600);

    return date($format, $time);
}

/**
 * 转换字符串形式的时间表达式为GMT时间戳
 *
 * @param   string  $str
 *
 * @return  integer
 */
function gmstr2time($str) {
    $time = strtotime($str);

    if ($time > 0) {
        $time -= date('Z');
    }

    return $time;
}

/**
 *  将一个用户自定义时区的日期转为GMT时间戳
 *
 * @access  public
 * @param   string      $str
 *
 * @return  integer
 */
function local_strtotime($str) {
    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : C('timezone');

    /**
     * $time = mktime($hour, $minute, $second, $month, $day, $year) - date('Z') + (date('Z') - $timezone * 3600)
     * 先用mktime生成时间戳，再减去date('Z')转换为GMT时间，然后修正为用户自定义时间。以下是化简后结果
     * */
    $time = strtotime($str) - $timezone * 3600;

    return $time;
}

/**
 * 获得用户所在时区指定的时间戳
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
function local_gettime($timestamp = NULL) {
    $tmp = local_getdate($timestamp);
    return $tmp[0];
}

/**
 * 获得用户所在时区指定的日期和时间信息
 *
 * @param   $timestamp  integer     该时间戳必须是一个服务器本地的时间戳
 *
 * @return  array
 */
function local_getdate($timestamp = NULL) {
    $timezone = isset($_SESSION['timezone']) ? $_SESSION['timezone'] : C('timezone');

    /* 如果时间戳为空，则获得服务器的当前时间 */
    if ($timestamp === NULL) {
        $timestamp = time();
    }

    $gmt = $timestamp - date('Z');       // 得到该时间的格林威治时间
    $local_time = $gmt + ($timezone * 3600);    // 转换为用户所在时区的时间戳

    return getdate($local_time);
}

/* * ********************************************************
 * 基础函数库
 * ******************************************************** */

/**
 * 截取UTF-8编码下字符串的函数
 *
 * @param   string      $str        被截取的字符串
 * @param   int         $length     截取的长度
 * @param   bool        $append     是否附加省略号
 *
 * @return  string
 */
function sub_str($str, $length = 0, $append = true) {
    $str = trim($str);
    $strlength = strlen($str);

    if ($length == 0 || $length >= $strlength) {
        return $str;
    } elseif ($length < 0) {
        $length = $strlength + $length;
        if ($length < 0) {
            $length = $strlength;
        }
    }

    if (function_exists('mb_substr')) {
        $newstr = mb_substr($str, 0, $length, EC_CHARSET);
    } elseif (function_exists('iconv_substr')) {
        $newstr = iconv_substr($str, 0, $length, EC_CHARSET);
    } else {
        //$newstr = trim_right(substr($str, 0, $length));
        $newstr = substr($str, 0, $length);
    }

    if ($append && $str != $newstr) {
        $newstr .= '...';
    }

    return $newstr;
}

/**
 * 获得用户的真实IP地址
 *
 * @access  public
 * @return  string
 */
function real_ip() {
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);

                if ($ip != 'unknown') {
                    $realip = $ip;

                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    }

    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';

    return $realip;
}

/**
 * 计算字符串的长度（汉字按照两个字符计算）
 *
 * @param   string      $str        字符串
 *
 * @return  int
 */
function str_len($str) {
    $length = strlen(preg_replace('/[\x00-\x7F]/', '', $str));

    if ($length) {
        return strlen($str) - $length + intval($length / 3) * 2;
    } else {
        return strlen($str);
    }
}

/**
 * 获得用户操作系统的换行符
 *
 * @access  public
 * @return  string
 */
function get_crlf() {
    /* LF (Line Feed, 0x0A, \N) 和 CR(Carriage Return, 0x0D, \R) */
    if (stristr($_SERVER['HTTP_USER_AGENT'], 'Win')) {
        $the_crlf = '\r\n';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], 'Mac')) {
        $the_crlf = '\r'; // for old MAC OS
    } else {
        $the_crlf = '\n';
    }

    return $the_crlf;
}

/**
 * 邮件发送
 *
 * @param: $name[string]        接收人姓名
 * @param: $email[string]       接收人邮件地址
 * @param: $subject[string]     邮件标题
 * @param: $content[string]     邮件内容
 * @param: $type[int]           0 普通邮件， 1 HTML邮件
 * @param: $notification[bool]  true 要求回执， false 不用回执
 *
 * @return boolean
 */
function send_mail($name, $email, $subject, $content, $type = 0, $notification = false) {
    /* 如果邮件编码不是EC_CHARSET，创建字符集转换对象，转换编码 */
    if (C('mail_charset') != EC_CHARSET) {
        $name = ecs_iconv(EC_CHARSET, C('mail_charset'), $name);
        $subject = ecs_iconv(EC_CHARSET, C('mail_charset'), $subject);
        $content = ecs_iconv(EC_CHARSET, C('mail_charset'), $content);
        $shop_name = ecs_iconv(EC_CHARSET, C('mail_charset'), C('shop_name'));
    }
    $charset = C('mail_charset');
    /**
     * 使用mail函数发送邮件
     */
    if (C('mail_service') == 0 && function_exists('mail')) {
        /* 邮件的头部信息 */
        $content_type = ($type == 0) ? 'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
        $headers = array();
        $headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($shop_name) . '?=' . '" <' . C('smtp_mail') . '>';
        $headers[] = $content_type . '; format=flowed';
        if ($notification) {
            $headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($shop_name) . '?=' . '" <' . C('smtp_mail') . '>';
        }

        $res = @mail($email, '=?' . $charset . '?B?' . base64_encode($subject) . '?=', $content, implode("\r\n", $headers));

        if (!$res) {
            ECTouch::err()->add(L('sendemail_false'));

            return false;
        } else {
            return true;
        }
    }
    /**
     * 使用smtp服务发送邮件
     */ else {
        /* 邮件的头部信息 */
        $content_type = ($type == 0) ?
                'Content-Type: text/plain; charset=' . $charset : 'Content-Type: text/html; charset=' . $charset;
        $content = base64_encode($content);

        $headers = array();
        $headers[] = 'Date: ' . gmdate('D, j M Y H:i:s') . ' +0000';
        $headers[] = 'To: "' . '=?' . $charset . '?B?' . base64_encode($name) . '?=' . '" <' . $email . '>';
        $headers[] = 'From: "' . '=?' . $charset . '?B?' . base64_encode($shop_name) . '?=' . '" <' . C('smtp_mail') . '>';
        $headers[] = 'Subject: ' . '=?' . $charset . '?B?' . base64_encode($subject) . '?=';
        $headers[] = $content_type . '; format=flowed';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'Content-Disposition: inline';
        if ($notification) {
            $headers[] = 'Disposition-Notification-To: ' . '=?' . $charset . '?B?' . base64_encode($shop_name) . '?=' . '" <' . C('smtp_mail') . '>';
        }

        /* 获得邮件服务器的参数设置 */
        $params['host'] = C('smtp_host');
        $params['port'] = C('smtp_port');
        $params['user'] = C('smtp_user');
        $params['pass'] = C('smtp_pass');

        if (empty($params['host']) || empty($params['port'])) {
            // 如果没有设置主机和端口直接返回 false
            ECTouch::err()->add(L('smtp_setting_error'));

            return false;
        } else {
            // 发送邮件
            if (!function_exists('fsockopen')) {
                //如果fsockopen被禁用，直接返回
                ECTouch::err()->add(L('disabled_fsockopen'));

                return false;
            }

            static $smtp;

            $send_params['recipients'] = $email;
            $send_params['headers'] = $headers;
            $send_params['from'] = C('smtp_mail');
            $send_params['body'] = $content;

            if (!isset($smtp)) {
                $smtp = new EcsSmtp($params);
            }

            if ($smtp->connect() && $smtp->send($send_params)) {
                return true;
            } else {
                $err_msg = $smtp->error_msg();
                if (empty($err_msg)) {
                    ECTouch::err()->add('Unknown Error');
                } else {
                    if (strpos($err_msg, 'Failed to connect to server') !== false) {
                        ECTouch::err()->add(sprintf(L('smtp_connect_failure'), $params['host'] . ':' . $params['port']));
                    } else if (strpos($err_msg, 'AUTH command failed') !== false) {
                        ECTouch::err()->add(L('smtp_login_failure'));
                    } elseif (strpos($err_msg, 'bad sequence of commands') !== false) {
                        ECTouch::err()->add(L('smtp_refuse'));
                    } else {
                        ECTouch::err()->add($err_msg);
                    }
                }

                return false;
            }
        }
    }
}

/**
 * 获得服务器上的 GD 版本
 *
 * @access      public
 * @return      int         可能的值为0，1，2
 */
function gd_version() {
    return EcsImage::gd_version();
}

/**
 * 文件或目录权限检查函数
 *
 * @access          public
 * @param           string  $file_path   文件路径
 * @param           bool    $rename_prv  是否在检查修改权限时检查执行rename()函数的权限
 *
 * @return          int     返回值的取值范围为{0 <= x <= 15}，每个值表示的含义可由四位二进制数组合推出。
 *                          返回值在二进制计数法中，四位由高到低分别代表
 *                          可执行rename()函数权限、可对文件追加内容权限、可写入文件权限、可读取文件权限。
 */
function file_mode_info($file_path) {
    /* 如果不存在，则不可读、不可写、不可改 */
    if (!file_exists($file_path)) {
        return false;
    }

    $mark = 0;

    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
        /* 测试文件 */
        $test_file = $file_path . '/cf_test.txt';

        /* 如果是目录 */
        if (is_dir($file_path)) {
            /* 检查目录是否可读 */
            $dir = @opendir($file_path);
            if ($dir === false) {
                return $mark; //如果目录打开失败，直接返回目录不可修改、不可写、不可读
            }
            if (@readdir($dir) !== false) {
                $mark ^= 1; //目录可读 001，目录不可读 000
            }
            @closedir($dir);

            /* 检查目录是否可写 */
            $fp = @fopen($test_file, 'wb');
            if ($fp === false) {
                return $mark; //如果目录中的文件创建失败，返回不可写。
            }
            if (@fwrite($fp, 'directory access testing.') !== false) {
                $mark ^= 2; //目录可写可读011，目录可写不可读 010
            }
            @fclose($fp);

            @unlink($test_file);

            /* 检查目录是否可修改 */
            $fp = @fopen($test_file, 'ab+');
            if ($fp === false) {
                return $mark;
            }
            if (@fwrite($fp, "modify test.\r\n") !== false) {
                $mark ^= 4;
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false) {
                $mark ^= 8;
            }
            @unlink($test_file);
        }
        /* 如果是文件 */ elseif (is_file($file_path)) {
            /* 以读方式打开 */
            $fp = @fopen($file_path, 'rb');
            if ($fp) {
                $mark ^= 1; //可读 001
            }
            @fclose($fp);

            /* 试着修改文件 */
            $fp = @fopen($file_path, 'ab+');
            if ($fp && @fwrite($fp, '') !== false) {
                $mark ^= 6; //可修改可写可读 111，不可修改可写可读011...
            }
            @fclose($fp);

            /* 检查目录下是否有执行rename()函数的权限 */
            if (@rename($test_file, $test_file) !== false) {
                $mark ^= 8;
            }
        }
    } else {
        if (@is_readable($file_path)) {
            $mark ^= 1;
        }

        if (@is_writable($file_path)) {
            $mark ^= 14;
        }
    }

    return $mark;
}

function log_write($arg, $file = '', $line = '') {
    if ((DEBUG_MODE & 4) != 4) {
        return;
    }

    $str = "\r\n-- " . date('Y-m-d H:i:s') . " --------------------------------------------------------------\r\n";
    $str .= "FILE: $file\r\nLINE: $line\r\n";

    if (is_array($arg)) {
        $str .= '$arg = array(';
        foreach ($arg AS $val) {
            foreach ($val AS $key => $list) {
                $str .= "'$key' => '$list'\r\n";
            }
        }
        $str .= ")\r\n";
    } else {
        $str .= $arg;
    }

    file_put_contents(ROOT_PATH . DATA_DIR . '/log.txt', $str);
}

/**
 * 检查目标文件夹是否存在，如果不存在则自动创建该目录
 *
 * @access      public
 * @param       string      folder     目录路径。不能使用相对于网站根目录的URL
 *
 * @return      bool
 */
function make_dir($folder) {
    $reval = false;

    if (!file_exists($folder)) {
        /* 如果目录不存在则尝试创建该目录 */
        @umask(0);

        /* 将目录路径拆分成数组 */
        preg_match_all('/([^\/]*)\/?/i', $folder, $atmp);

        /* 如果第一个字符为/则当作物理路径处理 */
        $base = ($atmp[0][0] == '/') ? '/' : '';

        /* 遍历包含路径信息的数组 */
        foreach ($atmp[1] AS $val) {
            if ('' != $val) {
                $base .= $val;

                if ('..' == $val || '.' == $val) {
                    /* 如果目录为.或者..则直接补/继续下一个循环 */
                    $base .= '/';

                    continue;
                }
            } else {
                continue;
            }

            $base .= '/';

            if (!file_exists($base)) {
                /* 尝试创建目录，如果创建失败则继续循环 */
                if (@mkdir(rtrim($base, '/'), 0777)) {
                    @chmod($base, 0777);
                    $reval = true;
                }
            }
        }
    } else {
        /* 路径已经存在。返回该路径是不是一个目录 */
        $reval = is_dir($folder);
    }

    clearstatcache();

    return $reval;
}

/**
 * 获得系统是否启用了 gzip
 *
 * @access  public
 *
 * @return  boolean
 */
function gzip_enabled() {
    static $enabled_gzip = NULL;

    if ($enabled_gzip === NULL) {
        $enabled_gzip = (C('enable_gzip') && function_exists('ob_gzhandler'));
    }

    return $enabled_gzip;
}

/**
 * 递归方式的对变量中的特殊字符进行转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function addslashes_deep($value) {
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('addslashes_deep', $value) : addslashes($value);
    }
}

/**
 * 将对象成员变量或者数组的特殊字符进行转义
 *
 * @access   public
 * @param    mix        $obj      对象或者数组
 * @author   Xuan Yan
 *
 * @return   mix                  对象或者数组
 */
function addslashes_deep_obj($obj) {
    if (is_object($obj) == true) {
        foreach ($obj AS $key => $val) {
            $obj->$key = addslashes_deep($val);
        }
    } else {
        $obj = addslashes_deep($obj);
    }

    return $obj;
}

/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value) {
    if (empty($value)) {
        return $value;
    } else {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

/**
 *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 *
 * @access  public
 * @param   string       $str         待转换字串
 *
 * @return  string       $str         处理后字串
 */
function make_semiangle($str) {
    $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z',
        '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
        '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
        '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
        '》' => '>',
        '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
        '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
        '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
        '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
        '　' => ' ');

    return strtr($str, $arr);
}

/**
 * 检查文件类型
 *
 * @access      public
 * @param       string      filename            文件名
 * @param       string      realname            真实文件名
 * @param       string      limit_ext_types     允许的文件类型
 * @return      string
 */
function check_file_type($filename, $realname = '', $limit_ext_types = '') {
    if ($realname) {
        $extname = strtolower(substr($realname, strrpos($realname, '.') + 1));
    } else {
        $extname = strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $extname . '|') === false) {
        return '';
    }

    $str = $format = '';

    $file = @fopen($filename, 'rb');
    if ($file) {
        $str = @fread($file, 0x400); // 读取前 1024 个字节
        @fclose($file);
    } else {
        if (stristr($filename, ROOT_PATH) === false) {
            if ($extname == 'jpg' || $extname == 'jpeg' || $extname == 'gif' || $extname == 'png' || $extname == 'doc' ||
                    $extname == 'xls' || $extname == 'txt' || $extname == 'zip' || $extname == 'rar' || $extname == 'ppt' ||
                    $extname == 'pdf' || $extname == 'rm' || $extname == 'mid' || $extname == 'wav' || $extname == 'bmp' ||
                    $extname == 'swf' || $extname == 'chm' || $extname == 'sql' || $extname == 'cert' || $extname == 'pptx' ||
                    $extname == 'xlsx' || $extname == 'docx') {
                $format = $extname;
            }
        } else {
            return '';
        }
    }

    if ($format == '' && strlen($str) >= 2) {
        if (substr($str, 0, 4) == 'MThd' && $extname != 'txt') {
            $format = 'mid';
        } elseif (substr($str, 0, 4) == 'RIFF' && $extname == 'wav') {
            $format = 'wav';
        } elseif (substr($str, 0, 3) == "\xFF\xD8\xFF") {
            $format = 'jpg';
        } elseif (substr($str, 0, 4) == 'GIF8' && $extname != 'txt') {
            $format = 'gif';
        } elseif (substr($str, 0, 8) == "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") {
            $format = 'png';
        } elseif (substr($str, 0, 2) == 'BM' && $extname != 'txt') {
            $format = 'bmp';
        } elseif ((substr($str, 0, 3) == 'CWS' || substr($str, 0, 3) == 'FWS') && $extname != 'txt') {
            $format = 'swf';
        } elseif (substr($str, 0, 4) == "\xD0\xCF\x11\xE0") {   // D0CF11E == DOCFILE == Microsoft Office Document
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'doc') {
                $format = 'doc';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xls') {
                $format = 'xls';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'ppt') {
                $format = 'ppt';
            }
        } elseif (substr($str, 0, 4) == "PK\x03\x04") {
            if (substr($str, 0x200, 4) == "\xEC\xA5\xC1\x00" || $extname == 'docx') {
                $format = 'docx';
            } elseif (substr($str, 0x200, 2) == "\x09\x08" || $extname == 'xlsx') {
                $format = 'xlsx';
            } elseif (substr($str, 0x200, 4) == "\xFD\xFF\xFF\xFF" || $extname == 'pptx') {
                $format = 'pptx';
            } else {
                $format = 'zip';
            }
        } elseif (substr($str, 0, 4) == 'Rar!' && $extname != 'txt') {
            $format = 'rar';
        } elseif (substr($str, 0, 4) == "\x25PDF") {
            $format = 'pdf';
        } elseif (substr($str, 0, 3) == "\x30\x82\x0A") {
            $format = 'cert';
        } elseif (substr($str, 0, 4) == 'ITSF' && $extname != 'txt') {
            $format = 'chm';
        } elseif (substr($str, 0, 4) == "\x2ERMF") {
            $format = 'rm';
        } elseif ($extname == 'sql') {
            $format = 'sql';
        } elseif ($extname == 'txt') {
            $format = 'txt';
        }
    }

    if ($limit_ext_types && stristr($limit_ext_types, '|' . $format . '|') === false) {
        $format = '';
    }

    return $format;
}

/**
 * 对 MYSQL LIKE 的内容进行转义
 *
 * @access      public
 * @param       string      string  内容
 * @return      string
 */
function mysql_like_quote($str) {
    return strtr($str, array("\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%', "\'" => "\\\\\'"));
}

/**
 * 获取服务器的ip
 *
 * @access      public
 *
 * @return string
 * */
function real_server_ip() {
    static $serverip = NULL;

    if ($serverip !== NULL) {
        return $serverip;
    }

    if (isset($_SERVER)) {
        if (isset($_SERVER['SERVER_ADDR'])) {
            $serverip = $_SERVER['SERVER_ADDR'];
        } else {
            $serverip = '0.0.0.0';
        }
    } else {
        $serverip = getenv('SERVER_ADDR');
    }

    return $serverip;
}

/**
 * 自定义 header 函数，用于过滤可能出现的安全隐患
 *
 * @param   string  string  内容
 *
 * @return  void
 * */
function ecs_header($string, $replace = true, $http_response_code = 0) {
    if (strpos($string, '../upgrade/index.php') === 0) {
        echo '<script type="text/javascript">window.location.href="' . $string . '";</script>';
    }
    $string = str_replace(array("\r", "\n"), array('', ''), $string);

    if (preg_match('/^\s*location:/is', $string)) {
        @header($string . "\n", $replace);

        exit();
    }

    if (empty($http_response_code) || PHP_VERSION < '4.3') {
        @header($string, $replace);
    } else {
        @header($string, $replace, $http_response_code);
    }
}

function ecs_iconv($source_lang, $target_lang, $source_string = '') {
    static $chs = NULL;

    /* 如果字符串为空或者字符串不需要转换，直接返回 */
    if ($source_lang == $target_lang || $source_string == '' || preg_match("/[\x80-\xFF]+/", $source_string) == 0) {
        return $source_string;
    }

    if ($chs === NULL) {
        $chs = new EcsIconv(ROOT_PATH);
    }

    return $chs->Convert($source_lang, $target_lang, $source_string);
}

function ecs_geoip($ip) {
    static $fp = NULL, $offset = array(), $index = NULL;

    $ip = gethostbyname($ip);
    $ipdot = explode('.', $ip);
    $ip = pack('N', ip2long($ip));

    $ipdot[0] = (int) $ipdot[0];
    $ipdot[1] = (int) $ipdot[1];
    if ($ipdot[0] == 10 || $ipdot[0] == 127 || ($ipdot[0] == 192 && $ipdot[1] == 168) || ($ipdot[0] == 172 && ($ipdot[1] >= 16 && $ipdot[1] <= 31))) {
        return 'LAN';
    }

    if ($fp === NULL) {
        $fp = fopen(ROOT_PATH . 'data/common/ipdata.dat', 'rb');
        if ($fp === false) {
            return 'Invalid IP data file';
        }
        $offset = unpack('Nlen', fread($fp, 4));
        if ($offset['len'] < 4) {
            return 'Invalid IP data file';
        }
        $index = fread($fp, $offset['len'] - 4);
    }

    $length = $offset['len'] - 1028;
    $start = unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);
    for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {
        if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
            $index_offset = unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
            $index_length = unpack('Clen', $index{$start + 7});
            break;
        }
    }

    fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
    $area = fread($fp, $index_length['len']);

    fclose($fp);
    $fp = NULL;

    return $area;
}

/**
 * 去除字符串右侧可能出现的乱码
 *
 * @param   string      $str        字符串
 *
 * @return  string
 */
function trim_right($str) {
    $len = strlen($str);
    /* 为空或单个字符直接返回 */
    if ($len == 0 || ord($str{$len - 1}) < 127) {
        return $str;
    }
    /* 有前导字符的直接把前导字符去掉 */
    if (ord($str{$len - 1}) >= 192) {
        return substr($str, 0, $len - 1);
    }
    /* 有非独立的字符，先把非独立字符去掉，再验证非独立的字符是不是一个完整的字，不是连原来前导字符也截取掉 */
    $r_len = strlen(rtrim($str, "\x80..\xBF"));
    if ($r_len == 0 || ord($str{$r_len - 1}) < 127) {
        return sub_str($str, 0, $r_len);
    }

    $as_num = ord(~$str{$r_len - 1});
    if ($as_num > (1 << (6 + $r_len - $len))) {
        return $str;
    } else {
        return substr($str, 0, $r_len - 1);
    }
}

/**
 * 将上传文件转移到指定位置
 *
 * @param string $file_name
 * @param string $target_name
 * @return blog
 */
function move_upload_file($file_name, $target_name = '') {
    if (function_exists("move_uploaded_file")) {
        if (move_uploaded_file($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        } else if (copy($file_name, $target_name)) {
            @chmod($target_name, 0755);
            return true;
        }
    } elseif (copy($file_name, $target_name)) {
        @chmod($target_name, 0755);
        return true;
    }
    return false;
}

/**
 * 将JSON传递的参数转码
 *
 * @param string $str
 * @return string
 */
function json_str_iconv($str) {
    if (EC_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return addslashes(stripslashes(ecs_iconv('utf-8', EC_CHARSET, $str)));
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = json_str_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = json_str_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/**
 * 循环转码成utf8内容
 *
 * @param string $str
 * @return string
 */
function to_utf8_iconv($str) {
    if (EC_CHARSET != 'utf-8') {
        if (is_string($str)) {
            return ecs_iconv(EC_CHARSET, 'utf-8', $str);
        } elseif (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = to_utf8_iconv($value);
            }
            return $str;
        } elseif (is_object($str)) {
            foreach ($str as $key => $value) {
                $str->$key = to_utf8_iconv($value);
            }
            return $str;
        } else {
            return $str;
        }
    }
    return $str;
}

/**
 * 获取文件后缀名,并判断是否合法
 *
 * @param string $file_name
 * @param array $allow_type
 * @return blob
 */
function get_file_suffix($file_name, $allow_type = array()) {
    $file_suffix = strtolower(array_pop(explode('.', $file_name)));
    if (empty($allow_type)) {
        return $file_suffix;
    } else {
        if (in_array($file_suffix, $allow_type)) {
            return true;
        } else {
            return false;
        }
    }
}

/* * ********************************************************
 * 公共函数库
 * ******************************************************** */

/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '') {
    if (empty($item_list)) {
        return $field_name . " IN ('') ";
    } else {
        if (!is_array($item_list)) {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item) {
            if ($item !== '') {
                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp)) {
            return $field_name . " IN ('') ";
        } else {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}

/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email) {
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false) {
        if (preg_match($chars, $user_email)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * 验证输入的手机号是否合法
 *
 * @access  public
 * @param   string      $mobile      需要验证的手机号
 *
 * @return bool
 */
function is_mobile($user_email) {
    $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$/";
    if (preg_match($chars, $user_email)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 检查是否为一个合法的时间格式
 *
 * @access  public
 * @param   string  $time
 * @return  void
 */
function is_time($time) {
    $pattern = '/[\d]{4}-[\d]{1,2}-[\d]{1,2}\s[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/';

    return preg_match($pattern, $time);
}

/**
 * 获得查询时间和次数，并赋值给smarty
 *
 * @access  public
 * @return  void
 */
function assign_query_info() {
    if (M()->queryTime == '') {
        $query_time = 0;
    } else {
        $query_time = number_format(microtime(true) - M()->queryTime, 6);
    }
    ECTouch::view()->assign('query_info', sprintf(L('query_info'), M()->queryCount, $query_time));

    /* 内存占用情况 */
    if (L('memory_info') && function_exists('memory_get_usage')) {
        ECTouch::view()->assign('memory_info', sprintf(L('memory_info'), memory_get_usage() / 1048576));
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? L('gzip_enabled') : L('gzip_disabled');
    ECTouch::view()->assign('gzip_enabled', $gzip_enabled);
}

/**
 * 创建地区的返回信息
 *
 * @access  public
 * @param   array   $arr    地区数组 *
 * @return  void
 */
function region_result($parent, $sel_name, $type) {
    global $cp;

    $arr = model('RegionBase')->get_regions($type, $parent);
    foreach ($arr AS $v) {
        $region = & $cp->add_node('region');
        $region_id = & $region->add_node('id');
        $region_name = & $region->add_node('name');

        $region_id->set_data($v['region_id']);
        $region_name->set_data($v['region_name']);
    }
    $select_obj = & $cp->add_node('select');
    $select_obj->set_data($sel_name);
}

/**
 * 初始化会员数据整合类
 *
 * @access  public
 * @return  object
 */
function init_users() {
    $set_modules = false;
    static $cls = null;
    if ($cls != null) {
        return $cls;
    }
    $integrate_code = C('integrate_code');
    $integrate_file = ROOT_PATH . 'plugins/integrates/' . $integrate_code . '.php';
    if (file_exists($integrate_file)) {
        include_once($integrate_file);
    } else {
        echo $integrate_code . ' not exist.';
        exit;
    }
    $cfg = unserialize(C('integrate_config'));
    $cls = new $integrate_code($cfg);

    return $cls;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */
function cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true) {
    static $res = NULL;

    if ($res === NULL) {
        $data = read_static_cache('cat_pid_releate');
        if ($data === false) {
            $res = model('Base')->get_all_cat_list();
            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000) {
                write_static_cache('cat_pid_releate', $res);
            }
        } else {
            $res = $data;
        }
    }

    if (empty($res) == true) {
        return $re_type ? '' : array();
    }

    $options = cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false) {
        foreach ($options as $key => $val) {
            if ($val['level'] > $children_level) {
                unset($options[$key]);
            } else {
                if ($val['is_show'] == 0) {
                    unset($options[$key]);
                    if ($children_level > $val['level']) {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                } else {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0) {
        if ($cat_id == 0) {
            $end_level = $level;
        } else {
            $first_item = reset($options); // 获取第一个元素
            $end_level = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val) {
            if ($val['level'] >= $end_level) {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true) {
        $select = '';
        foreach ($options AS $var) {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0) {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    } else {
        foreach ($options AS $key => $value) {
            $options[$key]['url'] = url('category/index', array('id' => $value['cat_id']));
        }

        return $options;
    }
}

/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options($spec_cat_id, $arr) {
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id])) {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0])) {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('cat_option_static');
        if ($data === false) {
            while (!empty($arr)) {
                foreach ($arr AS $key => $value) {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0) {
                        if ($value['parent_id'] > 0) {
                            break;
                        }

                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0) {
                            continue;
                        }
                        $last_cat_id = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id) {
                        $options[$cat_id] = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id'] = $cat_id;
                        $options[$cat_id]['name'] = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0) {
                            if (end($cat_id_array) != $last_cat_id) {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    } elseif ($value['parent_id'] > $last_cat_id) {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1) {
                    $last_cat_id = array_pop($cat_id_array);
                } elseif ($count == 1) {
                    if ($last_cat_id != end($cat_id_array)) {
                        $last_cat_id = end($cat_id_array);
                    } else {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id])) {
                    $level = $level_array[$last_cat_id];
                } else {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000) {
                write_static_cache('cat_option_static', $options);
            }
        } else {
            $options = $data;
        }
        $cat_options[0] = $options;
    } else {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id) {
        return $options;
    } else {
        if (empty($options[$spec_cat_id])) {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value) {
            if ($key != $spec_cat_id) {
                unset($options[$key]);
            } else {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value) {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                    ($spec_cat_id_level > $value['level'])) {
                break;
            } else {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 * @return  string
 */
function get_children($cat = 0) {
    return 'g.cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(cat_list($cat, 0, false)))));
}

/**
 * 获得指定文章分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 *
 * @return void
 */
function get_article_children($cat = 0) {
    return db_create_in(array_unique(array_merge(array($cat), array_keys(model('ArticleBase')->article_cat_list($cat, 0, false)))), 'cat_id');
}

/**
 * 获得指定分类下所有底层分类的ID
 * @param type $str
 * @return type
 */
function get_children_cat($str) {
    $act_id = explode(',', $str);
    foreach ($act_id as $val) {
        $cat[] = array_unique(array_merge(array($val), array_keys(cat_list($val, 0, false))));
    }
    foreach ($cat as $key => $val) {
        foreach ($val as $k => $v) {
            $res[] = $v;
        }
    }
    return array_unique($res);
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $change_price = true) {
    if ($price === '') {
        $price = 0;
    }
    $price = 0 + $price;//添加这一行，转换成数值
    if ($change_price && defined('ECS_ADMIN') === false) {
        switch (C('price_format')) {
            case 0:
                $price = number_format($price, 2, '.', '');
                break;
            case 1: // 保留不为 0 的尾数
                $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                if (substr($price, -1) == '.') {
                    $price = substr($price, 0, -1);
                }
                break;
            case 2: // 不四舍五入，保留1位
                $price = substr(number_format($price, 2, '.', ''), 0, -1);
                break;
            case 3: // 直接取整
                $price = intval($price);
                break;
            case 4: // 四舍五入，保留 1 位
                $price = number_format($price, 1, '.', '');
                break;
            case 5: // 先四舍五入，不保留小数
                $price = round($price);
                break;
        }
    } else {
        $price = number_format($price, 2, '.', '');
    }

    return sprintf(C('currency_format'), $price);
}

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($is_cache = true, $ext = '') {
    $dirs = array();

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0) {
        $tmp_dir = DATA_DIR;
    } else {
        $tmp_dir = 'temp';
    }
    if ($is_cache) {
        $cache_dir = ROOT_PATH . $tmp_dir . '/caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/query_caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/static_caches/';
        for ($i = 0; $i < 16; $i++) {
            $hash_dir = $cache_dir . dechex($i);
            $dirs[] = $hash_dir . '/';
        }
    } else {
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count = 0;

    foreach ($dirs AS $dir) {
        $folder = @opendir($dir);

        if ($folder === false) {
            continue;
        }

        while ($file = readdir($folder)) {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html') {
                continue;
            }
            if (is_file($dir . $file)) {
                /* 如果有文件名则判断是否匹配 */
                $pos = ($is_cache) ? strrpos($file, '_') : strrpos($file, '.');

                if ($str_len > 0 && $pos !== false) {
                    $ext_str = substr($file, 0, $pos);

                    if ($ext_str == $ext) {
                        if (@unlink($dir . $file)) {
                            $count++;
                        }
                    }
                } else {
                    if (@unlink($dir . $file)) {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}

/**
 * 清除模版编译文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_compiled_files($ext = '') {
    return clear_tpl_files(false, $ext);
}

/**
 * 清除缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_cache_files($ext = '') {
    return clear_tpl_files(true, $ext);
}

/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = '') {
    del_dir(ROOT_PATH . 'data/cache');
    @mkdir(ROOT_PATH . 'data/cache', 0777);
}

/**
 * 页面上调用的js文件
 *
 * @access  public
 * @param   string      $files
 * @return  void
 */
function smarty_insert_scripts($args) {
    static $scripts = array();

    $arr = explode(',', str_replace(' ', '', $args['files']));

    $str = '';
    foreach ($arr AS $val) {
        if (in_array($val, $scripts) == false) {
            $scripts[] = $val;
            if ($val{0} == '.') {
                $str .= '<script type="text/javascript" src="' . $val . '"></script>';
            } else {
                $str .= '<script type="text/javascript" src="js/' . $val . '"></script>';
            }
        }
    }

    return $str;
}

/**
 * 创建分页的列表
 *
 * @access  public
 * @param   integer $count
 * @return  string
 */
function smarty_create_pages($params) {
    extract($params);

    $str = '';
    $len = 10;

    if (empty($page)) {
        $page = 1;
    }

    if (!empty($count)) {
        $step = 1;
        $str .= "<option value='1'>1</option>";

        for ($i = 2; $i < $count; $i += $step) {
            $step = ($i >= $page + $len - 1 || $i <= $page - $len + 1) ? $len : 1;
            $str .= "<option value='$i'";
            $str .= $page == $i ? " selected='true'" : '';
            $str .= ">$i</option>";
        }

        if ($count > 1) {
            $str .= "<option value='$count'";
            $str .= $page == $count ? " selected='true'" : '';
            $str .= ">$count</option>";
        }
    }

    return $str;
}

/**
 * 重写 URL 地址
 *
 * @access  public
 * @param   string  $app        执行程序
 * @param   array   $params     参数数组
 * @param   string  $append     附加字串
 * @param   integer $page       页数
 * @param   string  $keywords   搜索关键词字符串
 * @return  void
 */
function build_uri($app, $params, $append = '', $page = 0, $keywords = '', $size = 0) {
    return url($app, $params);
}

/**
 * 格式化重量：小于1千克用克表示，否则用千克表示
 * @param   float   $weight     重量
 * @return  string  格式化后的重量
 */
function formated_weight($weight) {
    $weight = round(floatval($weight), 3);
    if ($weight > 0) {
        if ($weight < 1) {
            /* 小于1千克，用克表示 */
            return intval($weight * 1000) . L('gram');
        } else {
            /* 大于1千克，用千克表示 */
            return $weight . L('kilogram');
        }
    } else {
        return 0;
    }
}

/**
 * 调用UCenter的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function uc_call($func, $params = null) {
    restore_error_handler();
    if (!function_exists($func)) {
        include_once(ADDONS_PATH . 'uc_client/client.php');
    }

    $res = call_user_func_array($func, $params);

    set_error_handler('exception_handler');

    return $res;
}

/**
 * error_handle回调函数
 *
 * @return
 */
function exception_handler($errno, $errstr, $errfile, $errline) {
    return;
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
function get_image_path($goods_id, $image = '', $thumb = false, $call = 'goods', $del = false) {
    $url = C('no_picture');
    if(!empty($image)){
      $base_url = substr(C('SHOP_URL'), -1) == '/' ? C('SHOP_URL') : C('SHOP_URL') . '/';
      if(strtolower(substr($image, 0, 4)) == 'http'){
          $url = $image;
      }else if(strtolower(substr($image, 0, 13)) == 'data/attached'){
          $url = __ROOT__ . '/' . $image;
      }else{
          $url = $base_url . $image;
      }
    }
    return $url;
}

/**
 * 获取touch新增图片地址
 * @param type $img
 * @return type
 */
function get_banner_path($img) {
    $img = empty($img) ? C('no_picture') : $img;
    return $img;
}

/**
 * 调用使用UCenter插件时的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function user_uc_call($func, $params = null) {
    $integrate_code = C('integrate_code');
    if (isset($integrate_code) && C('integrate_code') == 'ucenter') {
        restore_error_handler();
        $res = call_user_func_array($func, $params);
        set_error_handler('exception_handler');
        return $res;
    } else {
        return;
    }
}

/**
 * 调用array_combine函数
 *
 * @param   array  $keys
 * @param   array  $values
 *
 * @return  $combined
 */
if (!function_exists('array_combine')) {

    function array_combine($keys, $values) {
        if (!is_array($keys)) {
            user_error('array_combine() expects parameter 1 to be array, ' .
                    gettype($keys) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_array($values)) {
            user_error('array_combine() expects parameter 2 to be array, ' .
                    gettype($values) . ' given', E_USER_WARNING);
            return;
        }

        $key_count = count($keys);
        $value_count = count($values);
        if ($key_count !== $value_count) {
            user_error('array_combine() Both parameters should have equal number of elements', E_USER_WARNING);
            return false;
        }

        if ($key_count === 0 || $value_count === 0) {
            user_error('array_combine() Both parameters should have number of elements at least 0', E_USER_WARNING);
            return false;
        }

        $keys = array_values($keys);
        $values = array_values($values);

        $combined = array();
        for ($i = 0; $i < $key_count; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }

        return $combined;
    }

}



/* * ********************************************************
 * 商品相关函数库
 * ******************************************************** */

/**
 * 商品推荐usort用自定义排序行数
 */
function goods_sort($goods_a, $goods_b) {
    if ($goods_a['sort_order'] == $goods_b['sort_order']) {
        return 0;
    }
    return ($goods_a['sort_order'] < $goods_b['sort_order']) ? -1 : 1;
}

/**
 * 判断某个商品是否正在特价促销期
 *
 * @access  public
 * @param   float   $price      促销价格
 * @param   string  $start      促销开始日期
 * @param   string  $end        促销结束日期
 * @return  float   如果还在促销期则返回促销价，否则返回0
 */
function bargain_price($price, $start, $end) {
    if ($price == 0) {
        return 0;
    } else {
        $time = gmtime();
        if ($time >= $start && $time <= $end) {
            return $price;
        } else {
            return 0;
        }
    }
}

/**
 * 计算拍卖活动状态（注意参数一定是原始信息）
 * @param   array   $auction    拍卖活动原始信息
 * @return  int
 */
function auction_status($auction) {
    $now = gmtime();
    if ($auction['is_finished'] == 0) {
        if ($now < $auction['start_time']) {
            return PRE_START; // 未开始
        } elseif ($now > $auction['end_time']) {
            return FINISHED; // 已结束，未处理
        } else {
            return UNDER_WAY; // 进行中
        }
    } elseif ($auction['is_finished'] == 1) {
        return FINISHED; // 已结束，未处理
    } else {
        return SETTLED; // 已结束，已处理
    }
}

/**
 * 添加商品名样式
 * @param   string     $goods_name     商品名称
 * @param   string     $style          样式参数
 * @return  string
 */
function add_style($goods_name, $style) {
    $goods_style_name = $goods_name;

    $arr = explode('+', $style);

    $font_color = !empty($arr[0]) ? $arr[0] : '';
    $font_style = !empty($arr[1]) ? $arr[1] : '';

    if ($font_color != '') {
        $goods_style_name = '<font color=' . $font_color . '>' . $goods_style_name . '</font>';
    }
    if ($font_style != '') {
        $goods_style_name = '<' . $font_style . '>' . $goods_style_name . '</' . $font_style . '>';
    }
    return $goods_style_name;
}

/* * ********************************************************
 * 用户相关函数库
 * ******************************************************** */

/**
 *  获取用户的tags
 *
 * @access  public
 * @param   int         $user_id        用户ID
 *
 * @return array        $arr            tags列表
 */
function get_user_tags($user_id = 0) {
    if (empty($user_id)) {
        $GLOBALS['error_no'] = 1;

        return false;
    }

    $tags = model('ClipsBase')->get_tags(0, $user_id);

    if (!empty($tags)) {
        color_tag($tags);
    }

    return $tags;
}

/**
 * 标签着色
 *
 * @access   public
 * @param    array
 * @author   Xuan Yan
 *
 * @return   none
 */
function color_tag(&$tags) {
    $tagmark = array(
        array('color' => '#666666', 'size' => '0.8em', 'ifbold' => 1),
        array('color' => '#333333', 'size' => '0.9em', 'ifbold' => 0),
        array('color' => '#006699', 'size' => '1.0em', 'ifbold' => 1),
        array('color' => '#CC9900', 'size' => '1.1em', 'ifbold' => 0),
        array('color' => '#666633', 'size' => '1.2em', 'ifbold' => 1),
        array('color' => '#993300', 'size' => '1.3em', 'ifbold' => 0),
        array('color' => '#669933', 'size' => '1.4em', 'ifbold' => 1),
        array('color' => '#3366FF', 'size' => '1.5em', 'ifbold' => 0),
        array('color' => '#197B30', 'size' => '1.6em', 'ifbold' => 1),
    );

    $maxlevel = count($tagmark);
    $tcount = $scount = array();

    foreach ($tags AS $val) {
        $tcount[] = $val['tag_count']; // 获得tag个数数组
    }
    $tcount = array_unique($tcount); // 去除相同个数的tag

    sort($tcount); // 从小到大排序

    $tempcount = count($tcount); // 真正的tag级数
    $per = $maxlevel >= $tempcount ? 1 : $maxlevel / ($tempcount - 1);

    foreach ($tcount AS $key => $val) {
        $lvl = floor($per * $key);
        $scount[$val] = $lvl; // 计算不同个数的tag相对应的着色数组key
    }

    $rewrite = intval(C('rewrite')) > 0;

    /* 遍历所有标签，根据引用次数设定字体大小 */
    foreach ($tags AS $key => $val) {
        $lvl = $scount[$val['tag_count']]; // 着色数组key

        $tags[$key]['color'] = $tagmark[$lvl]['color'];
        $tags[$key]['size'] = $tagmark[$lvl]['size'];
        $tags[$key]['bold'] = $tagmark[$lvl]['ifbold'];
        if ($rewrite) {
            if (strtolower(EC_CHARSET) !== 'utf-8') {
                $tags[$key]['url'] = 'tag-' . urlencode(urlencode($val['tag_words'])) . '.html';
            } else {
                $tags[$key]['url'] = 'tag-' . urlencode($val['tag_words']) . '.html';
            }
        } else {
            $tags[$key]['url'] = 'search.php?keywords=' . urlencode($val['tag_words']);
        }
    }
    shuffle($tags);
}

/* * ********************************************************
 * 加密解密函数
 * ******************************************************** */

/**
 * 加密函数
 * @param   string  $str    加密前的字符串
 * @param   string  $key    密钥
 * @return  string  加密后的字符串
 */
function encrypt($str, $key = AUTH_KEY) {
    $coded = '';
    $keylength = strlen($key);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength) {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return str_replace('=', '', base64_encode($coded));
}

/**
 * 解密函数
 * @param   string  $str    加密后的字符串
 * @param   string  $key    密钥
 * @return  string  加密前的字符串
 */
function decrypt($str, $key = AUTH_KEY) {
    $coded = '';
    $keylength = strlen($key);
    $str = base64_decode($str);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength) {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return $coded;
}

/* * ********************************************************
 * LICENSE 相关函数库
 * ******************************************************** */

/**
 * 功能：生成certi_ac验证字段
 * @param   string     POST传递参数
 * @param   string     证书token
 * @return  string
 */
function make_shopex_ac($post_params, $token) {
    if (!is_array($post_params)) {
        return;
    }
    // core
    ksort($post_params);
    $str = '';
    foreach ($post_params as $key => $value) {
        if ($key != 'certi_ac') {
            $str .= $value;
        }
    }

    return md5($str . $token);
}

/**
 * 功能：与 ECShop 交换数据
 *
 * @param   array     $certi    登录参数
 * @param   array     $license  网店license信息
 * @param   bool      $use_lib  使用哪一个json库，0为ec，1为shopex
 * @return  array
 */
function exchange_shop_license($certi, $license, $use_lib = 0) {
    if (!is_array($certi)) {
        return array();
    }

    $params = '';
    foreach ($certi as $key => $value) {
        $params .= '&' . $key . '=' . $value;
    }
    $params = trim($params, '&');

    $transport = new EcsTransport;
    //$transport->connect_timeout = 1;
    $request = $transport->request($license['certi'], $params, 'POST');
    $request_str = json_str_iconv($request['body']);

    if (empty($use_lib)) {
        $json = new EcsJson();
        $request_arr = $json->decode($request_str, 1);
    } else {
        $request_arr = json_decode($request_str, 1);
    }

    return $request_arr;
}

/**
 * 功能：处理登录返回结果
 *
 * @param   array     $cert_auth    登录返回的用户信息
 * @return  array
 */
function process_login_license($cert_auth) {
    if (!is_array($cert_auth)) {
        return array();
    }

    $cert_auth['auth_str'] = trim($cert_auth['auth_str']);
    if (!empty($cert_auth['auth_str'])) {
        $cert_auth['auth_str'] = L('license_' . $cert_auth['auth_str']);
    }

    $cert_auth['auth_type'] = trim($cert_auth['auth_type']);
    if (!empty($cert_auth['auth_type'])) {
        $cert_auth['auth_type'] = L('license_' . $cert_auth['auth_type']);
    }

    return $cert_auth;
}

/**
 * 功能：license 登录
 *
 * @param   array     $certi_added    配置信息补充数组 array_key 登录信息的key；array_key => array_value；
 * @return  array     $return_array['flag'] = login_succ、login_fail、login_ping_fail、login_param_fail；
 *                    $return_array['request']；
 */
function license_login($certi_added = '') {
    // 登录信息配置
    $certi['certi_app'] = ''; // 证书方法
    $certi['app_id'] = 'ectouch_free'; // 说明客户端来源
    $certi['app_instance_id'] = ''; // 应用服务ID
    $certi['version'] = LICENSE_VERSION; // license接口版本号
    $certi['shop_version'] = VERSION . '#' . RELEASE; // 网店软件版本号
    $certi['certi_url'] = sprintf(__URL__); // 网店URL
    $certi['certi_session'] = ECTouch::sess()->get_session_id(); // 网店SESSION标识
    $certi['certi_validate_url'] = sprintf(__URL__ . url('api/certi')); // 网店提供于官方反查接口
    $certi['format'] = 'json'; // 官方返回数据格式
    $certi['certificate_id'] = ''; // 网店证书ID
    // 标识
    $certi_back['succ'] = 'succ';
    $certi_back['fail'] = 'fail';
    // return 返回数组
    $return_array = array();

    if (is_array($certi_added)) {
        foreach ($certi_added as $key => $value) {
            $certi[$key] = $value;
        }
    }

    // 取出网店 license
    $license = model('LicenseBase')->get_shop_license();

    // 检测网店 license
    if (!empty($license['certificate_id']) && !empty($license['token']) && !empty($license['certi'])) {
        // 登录
        $certi['certi_app'] = 'certi.login'; // 证书方法
        $certi['app_instance_id'] = 'cert_auth'; // 应用服务ID
        $certi['certificate_id'] = $license['certificate_id']; // 网店证书ID
        $certi['certi_ac'] = make_shopex_ac($certi, $license['token']); // 网店验证字符串

        $request_arr = exchange_shop_license($certi, $license);
        if (is_array($request_arr) && $request_arr['res'] == $certi_back['succ']) {
            $return_array['flag'] = 'login_succ';
            $return_array['request'] = $request_arr;
        } elseif (is_array($request_arr) && $request_arr['res'] == $certi_back['fail']) {
            $return_array['flag'] = 'login_fail';
            $return_array['request'] = $request_arr;
        } else {
            $return_array['flag'] = 'login_ping_fail';
            $return_array['request'] = array('res' => 'fail');
        }
    } else {
        $return_array['flag'] = 'login_param_fail';
        $return_array['request'] = array('res' => 'fail');
    }

    return $return_array;
}

/* * ********************************************************
 * 模版相关公用函数库
 * ******************************************************** */

/**
 * 获得模版的信息
 *
 * @access  private
 * @param   string      $template_name      模版名
 * @param   string      $template_style     模版风格名
 * @return  array
 */
function get_template_info($template_name, $template_style = '') {
    if (empty($template_style) || $template_style == '') {
        $template_style = '';
    }

    $info = array();
    $ext = array('png', 'gif', 'jpg', 'jpeg');

    $info['code'] = $template_name;
    $info['screenshot'] = '';
    $info['stylename'] = $template_style;

    if ($template_style == '') {
        foreach ($ext AS $val) {
            if (file_exists(ROOT_PATH . 'themes/' . $template_name . "/images/screenshot.$val")) {
                $info['screenshot'] = ROOT_PATH . 'themes/' . $template_name . "/images/screenshot.$val";

                break;
            }
        }
    } else {
        foreach ($ext AS $val) {
            if (file_exists(ROOT_PATH . 'themes/' . $template_name . "/images/screenshot_$template_style.$val")) {
                $info['screenshot'] = ROOT_PATH . 'themes/' . $template_name . "/images/screenshot_$template_style.$val";

                break;
            }
        }
    }

    $css_path = ROOT_PATH . 'themes/' . $template_name . '/style.css';
    if ($template_style != '') {
        $css_path = ROOT_PATH . 'themes/' . $template_name . "/style_$template_style.css";
    }
    if (file_exists($css_path) && !empty($template_name)) {
        $arr = array_slice(file($css_path), 0, 10);

        $template_name = explode(': ', $arr[1]);
        $template_uri = explode(': ', $arr[2]);
        $template_desc = explode(': ', $arr[3]);
        $template_version = explode(': ', $arr[4]);
        $template_author = explode(': ', $arr[5]);
        $author_uri = explode(': ', $arr[6]);
        $logo_filename = explode(': ', $arr[7]);
        $template_type = explode(': ', $arr[8]);


        $info['name'] = isset($template_name[1]) ? trim($template_name[1]) : '';
        $info['uri'] = isset($template_uri[1]) ? trim($template_uri[1]) : '';
        $info['desc'] = isset($template_desc[1]) ? trim($template_desc[1]) : '';
        $info['version'] = isset($template_version[1]) ? trim($template_version[1]) : '';
        $info['author'] = isset($template_author[1]) ? trim($template_author[1]) : '';
        $info['author_uri'] = isset($author_uri[1]) ? trim($author_uri[1]) : '';
        $info['logo'] = isset($logo_filename[1]) ? trim($logo_filename[1]) : '';
        $info['type'] = isset($template_type[1]) ? trim($template_type[1]) : '';
    } else {
        $info['name'] = '';
        $info['uri'] = '';
        $info['desc'] = '';
        $info['version'] = '';
        $info['author'] = '';
        $info['author_uri'] = '';
        $info['logo'] = '';
    }

    return $info;
}

/**
 * 获得模版文件中的编辑区域及其内容
 *
 * @access  public
 * @param   string  $tmp_name   模版名称
 * @param   string  $tmp_file   模版文件名称
 * @return  array
 */
function get_template_region($tmp_name, $tmp_file, $lib = true) {
    global $dyna_libs;

    $file = '../themes/' . $tmp_name . '/' . $tmp_file;

    /* 将模版文件的内容读入内存 */
    $content = file_get_contents($file);

    /* 获得所有编辑区域 */
    static $regions = array();

    if (empty($regions)) {
        $matches = array();
        $result = preg_match_all('/(<!--\\s*TemplateBeginEditable\\sname=")([^"]+)("\\s*-->)/', $content, $matches, PREG_SET_ORDER);

        if ($result && $result > 0) {
            foreach ($matches AS $key => $val) {
                if ($val[2] != 'doctitle' && $val[2] != 'head') {
                    $regions[] = $val[2];
                }
            }
        }
    }

    if (!$lib) {
        return $regions;
    }

    $libs = array();
    /* 遍历所有编辑区 */
    foreach ($regions AS $key => $val) {
        $matches = array();
        $pattern = '/(<!--\\s*TemplateBeginEditable\\sname="%s"\\s*-->)(.*?)(<!--\\s*TemplateEndEditable\\s*-->)/s';

        if (preg_match(sprintf($pattern, $val), $content, $matches)) {
            /* 找出该编辑区域内所有库项目 */
            $lib_matches = array();

            $result = preg_match_all('/([\s|\S]{0,20})(<!--\\s#BeginLibraryItem\\s")([^"]+)("\\s-->)/', $matches[2], $lib_matches, PREG_SET_ORDER);
            $i = 0;
            if ($result && $result > 0) {
                foreach ($lib_matches AS $k => $v) {
                    $v[3] = strtolower($v[3]);
                    $libs[] = array('library' => $v[3], 'region' => $val, 'lib' => basename(substr($v[3], 0, strpos($v[3], '.'))), 'sort_order' => $i);
                    $i++;
                }
            }
        }
    }

    return $libs;
}

/**
 * 获得指定库项目在模板中的设置内容
 *
 * @access  public
 * @param   string  $lib    库项目
 * @param   array   $libs    包含设定内容的数组
 * @return  void
 */
function get_setted($lib, &$arr) {
    $options = array('region' => '', 'sort_order' => 0, 'display' => 0);

    foreach ($arr AS $key => $val) {
        if ($lib == $val['library']) {
            $options['region'] = $val['region'];
            $options['sort_order'] = $val['sort_order'];
            $options['display'] = 1;

            break;
        }
    }

    return $options;
}

/**
 * 从相应模板xml文件中获得指定模板文件中的可编辑区信息
 *
 * @access  public
 * @param   string  $curr_template    当前模板文件名
 * @param   array   $curr_page_libs   缺少xml文件时的默认编辑区信息数组
 * @return  array   $edit_libs        返回可编辑的库文件数组
 */
function get_editable_libs($curr_template, $curr_page_libs) {
    global $_CFG;
    $vals = array();
    $edit_libs = array();

    if ($xml_content = @file_get_contents(ROOT_PATH . 'themes/' . $_CFG['template'] . '/libs.xml')) {
        $p = xml_parser_create();                                                   //把xml解析到数组
        xml_parse_into_struct($p, $xml_content, $vals, $index);
        xml_parser_free($p);

        $i = 0;
        for (; $i < sizeof($vals); $i++) {                                      //找到相应模板文件的位置
            if ($vals[$i]['tag'] == 'FILE' && isset($vals[$i]['attributes'])) {
                if ($vals[$i]['attributes']['NAME'] == $curr_template . '.dwt') {
                    break;
                }
            }
        }

        while ($vals[++$i]['tag'] != 'FILE' || !isset($vals[$i]['attributes'])) {     //读出可编辑区库文件名称，放到一个数组中
            if ($vals[$i]['tag'] == 'LIB') {
                $edit_libs[] = $vals[$i]['value'];
            }
        }
    }

    return $edit_libs;
}

/**
 * 过滤用户输入的基本数据，防止script攻击
 *
 * @access      public
 * @return      string
 */
function compile_str($str)
{
    $arr = array('<' => '＜', '>' => '＞','"'=>'”',"'"=>'’');

    return strtr($str, $arr);
}

/**
 * 默认返回 model('Base')->model  用来直接使用ecModel的方法
 * @param string $model
 */
function M($model='Base'){
    return model($model)->model;
}