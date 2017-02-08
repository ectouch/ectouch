<?php

if (!function_exists('dd')) {

    function dd($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }
}


if (!function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string $id
     * @param  array $parameters
     * @param  string $domain
     * @param  string $locale
     * @return string
     */
    function trans($category = null, $message, $params = [], $language = null)
    {
        return \Yii::t($category, $message, $params, $language);
    }
}


if (!function_exists('bcrypt')) {
    /**
     * Hash the given value.
     *
     * @param  string $value
     * @param  array $options
     * @return string
     */
    function bcrypt($value, $options = [])
    {
        return app('hash')->make($value, $options);
    }
}


if (!function_exists('endWith')) {
    /**
     * 第一个是原串,第二个是 部份串
     * @param  [type] $haystack [description]
     * @param  [type] $needle   [description]
     * @return [type]           [description]
     */
    function endWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
        return (substr($haystack, -$length) === $needle);
    }
}

if (!function_exists('formatPhoto')) {
    /**
     * Format Photo
     *
     * @param  string $photo
     * @return array
     */
    function formatPhoto($img, $thumb = null, $domain = null)
    {
        if ($img == null) {
            return null;
        }
        if ($thumb == null) {
            $thumb = $img;
        }

        $domain = $domain == null ? config('app.shop_url') : $domain;

        return [
            'width' => null,
            'height' => null,

            //定义图片服务器
            'thumb' => (strpos($thumb, 'http://') === false) ? $domain . '/' . $thumb : $thumb,
            'large' => (strpos($img, 'http://') === false) ? $domain . '/' . $img : $img
        ];
    }
}

if (!function_exists('curl_request')) {
    /**
     * CURL Request
     */
    function curl_request($api, $method = 'GET', $params = array(), $headers = [])
    {
        $curl = curl_init();

        switch (strtoupper($method)) {
            case 'GET' :
                if (!empty($params)) {
                    $api .= (strpos($api, '?') ? '&' : '?') . http_build_query($params);
                }
                curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
                break;
            case 'POST' :
                curl_setopt($curl, CURLOPT_POST, TRUE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

                break;
            case 'PUT' :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
            case 'DELETE' :
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($curl, CURLOPT_URL, $api);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, 0);

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);

        if ($response === FALSE) {
            $error = curl_error($curl);
            curl_close($curl);
            return FALSE;
        } else {
            $response = json_decode($response, true);
        }

        curl_close($curl);

        return $response;
    }
}

if (!function_exists('show_error')) {
    /**
     * Show Error
     */
    function show_error($code, $message)
    {
        $response = response()->json([
            'error' => true,
            'error_code' => $code,
            'error_desc' => $message
        ]);
        $response->header('X-' . config('app.name') . '-ErrorCode', $code);
        $response->header('X-' . config('app.name') . '-ErrorDesc', urlencode($message));
        return $response;
    }
}

if (!function_exists('make_semiangle')) {

    /**
     *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     *
     * @access  public
     * @param   string $str 待转换字串
     *
     * @return  string       $str         处理后字串
     */
    function make_semiangle($str)
    {
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
}

if (!function_exists('keyToPem')) {
    /**
     * key To Pem
     */
    function keyToPem($key, $private = false)
    {
        //Split lines:
        $lines = str_split($key, 65);
        $body = implode("\n", $lines);
        //Get title:
        $title = $private ? 'RSA PRIVATE KEY' : 'PUBLIC KEY';
        //Add wrapping:
        $result = "-----BEGIN {$title}-----\n";
        $result .= $body . "\n";
        $result .= "-----END {$title}-----\n";

        return $result;
    }
}

if (!function_exists('unserialize_config')) {
    /**
     * 处理序列化的支付、配送的配置参数
     * 返回一个以name为索引的数组
     *
     * @access  public
     * @param   string $cfg
     * @return  void
     */
    function unserialize_config($cfg)
    {
        if (is_string($cfg) && ($arr = unserialize($cfg)) !== false) {
            $config = array();

            foreach ($arr AS $key => $val) {
                $config[$val['name']] = $val['value'];
            }

            return $config;
        } else {
            return false;
        }
    }
}

if (!function_exists('is_dev')) {
    function is_dev()
    {
        if (app('request')->cookie('78b5od367n99we5w') == '882q20qxt3089s0s') {
            return true;
        }

        return false;
    }
}

if (!function_exists('format_array')) {
    function format_array($array)
    {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if ($value === null) {
                    $array[$key] = '';
                } else if (is_array($value)) {
                    $value = format_array($value);
                    if ($value === null) {
                        $array[$key] = '';
                    } else {
                        $array[$key] = $value;
                    }
                }
            }
        }

        return $array;
    }
}


if (!function_exists('filterSpecialchar')) {
    /**
     * 正则去除特殊字符
     *
     * @access  public
     * @param   string $osstr
     * @return  string
     */
    function filterSpecialchar($ostr)
    {
        $regex = "/\/|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        return preg_replace($regex, "", $ostr);
    }
}
