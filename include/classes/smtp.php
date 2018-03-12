<?php

/**
 * SMTP 邮件类
 */

define('SMTP_STATUS_NOT_CONNECTED', 1, true);
define('SMTP_STATUS_CONNECTED', 2, true);

class smtp
{
    public $connection;
    public $recipients;
    public $headers;
    public $timeout;
    public $errors;
    public $status;
    public $body;
    public $from;
    public $host;
    public $port;
    public $helo;
    public $auth;
    public $user;
    public $pass;

    /**
     *  参数为一个数组
     *  host        SMTP 服务器的主机       默认：localhost
     *  port        SMTP 服务器的端口       默认：25
     *  helo        发送HELO命令的名称      默认：localhost
     *  user        SMTP 服务器的用户名     默认：空值
     *  pass        SMTP 服务器的登陆密码   默认：空值
     *  timeout     连接超时的时间          默认：5
     *  @return  bool
     */
    public function __construct($params = array())
    {
        if (!defined('CRLF')) {
            define('CRLF', "\r\n", true);
        }

        $this->timeout  = 10;
        $this->status   = SMTP_STATUS_NOT_CONNECTED;
        $this->host     = 'localhost';
        $this->port     = 25;
        $this->auth     = false;
        $this->user     = '';
        $this->pass     = '';
        $this->errors   = array();

        foreach ($params as $key => $value) {
            $this->$key = $value;
        }

        $this->helo     = $this->host;

        //  如果没有设置用户名则不验证
        $this->auth = ('' == $this->user) ? false : true;
    }

    public function connect($params = array())
    {
        if (!isset($this->status)) {
            $obj = new smtp($params);

            if ($obj->connect()) {
                $obj->status = SMTP_STATUS_CONNECTED;
            }

            return $obj;
        } else {
            $smtp_ssl = C('smtp_ssl');
            if (!empty($smtp_ssl)) {
                $this->host = "ssl://" . $this->host;
            }
            $this->connection = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);

            if ($this->connection === false) {
                $this->errors[] = 'Access is denied.';

                return false;
            }

            @socket_set_timeout($this->connection, 0, 250000);

            $greeting = $this->get_data();

            if (is_resource($this->connection)) {
                $this->status = 2;

                return $this->auth ? $this->ehlo() : $this->helo();
            } else {
                log_write($errstr, __FILE__, __LINE__);
                $this->errors[] = 'Failed to connect to server: ' . $errstr;

                return false;
            }
        }
    }

    /**
     * 参数为数组
     * recipients      接收人的数组
     * from            发件人的地址，也将作为回复地址
     * headers         头部信息的数组
     * body            邮件的主体
     */

    public function send($params = array())
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }

        if ($this->is_connected()) {
            //  服务器是否需要验证
            if ($this->auth) {
                if (!$this->auth()) {
                    return false;
                }
            }

            $this->mail($this->from);

            if (is_array($this->recipients)) {
                foreach ($this->recipients as $value) {
                    $this->rcpt($value);
                }
            } else {
                $this->rcpt($this->recipients);
            }

            if (!$this->data()) {
                return false;
            }

            $headers = str_replace(CRLF . '.', CRLF . '..', trim(implode(CRLF, $this->headers)));
            $body    = str_replace(CRLF . '.', CRLF . '..', $this->body);
            $body    = substr($body, 0, 1) == '.' ? '.' . $body : $body;

            $this->send_data($headers);
            $this->send_data('');
            $this->send_data($body);
            $this->send_data('.');

            return (substr($this->get_data(), 0, 3) === '250');
        } else {
            $this->errors[] = 'Not connected!';

            return false;
        }
    }

    public function helo()
    {
        if (is_resource($this->connection)
                and $this->send_data('HELO ' . $this->helo)
                and substr($error = $this->get_data(), 0, 3) === '250') {
            return true;
        } else {
            $this->errors[] = 'HELO command failed, output: ' . trim(substr($error, 3));

            return false;
        }
    }

    public function ehlo()
    {
        if (is_resource($this->connection)
                and $this->send_data('EHLO ' . $this->helo)
                and substr($error = $this->get_data(), 0, 3) === '250') {
            return true;
        } else {
            $this->errors[] = 'EHLO command failed, output: ' . trim(substr($error, 3));

            return false;
        }
    }

    public function auth()
    {
        if (is_resource($this->connection)
                and $this->send_data('AUTH LOGIN')
                and substr($error = $this->get_data(), 0, 3) === '334'
                and $this->send_data(base64_encode($this->user))            // Send username
                and substr($error = $this->get_data(), 0, 3) === '334'
                and $this->send_data(base64_encode($this->pass))            // Send password
                and substr($error = $this->get_data(), 0, 3) === '235') {
            return true;
        } else {
            $this->errors[] = 'AUTH command failed: ' . trim(substr($error, 3));

            return false;
        }
    }

    public function mail($from)
    {
        if ($this->is_connected()
            and $this->send_data('MAIL FROM:<' . $from . '>')
            and substr($this->get_data(), 0, 2) === '250') {
            return true;
        } else {
            return false;
        }
    }

    public function rcpt($to)
    {
        if ($this->is_connected()
            and $this->send_data('RCPT TO:<' . $to . '>')
            and substr($error = $this->get_data(), 0, 2) === '25') {
            return true;
        } else {
            $this->errors[] = trim(substr($error, 3));

            return false;
        }
    }

    public function data()
    {
        if ($this->is_connected()
            and $this->send_data('DATA')
            and substr($error = $this->get_data(), 0, 3) === '354') {
            return true;
        } else {
            $this->errors[] = trim(substr($error, 3));

            return false;
        }
    }

    public function is_connected()
    {
        return (is_resource($this->connection) and ($this->status === SMTP_STATUS_CONNECTED));
    }

    public function send_data($data)
    {
        if (is_resource($this->connection)) {
            return fwrite($this->connection, $data . CRLF, strlen($data) + 2);
        } else {
            return false;
        }
    }

    public function get_data()
    {
        $return = '';
        $line   = '';

        if (is_resource($this->connection)) {
            while (strpos($return, CRLF) === false or $line{3} !== ' ') {
                $line    = fgets($this->connection, 512);
                $return .= $line;
            }

            return trim($return);
        } else {
            return '';
        }
    }

    /**
     * 获得最后一个错误信息
     *
     * @access  public
     * @return  string
     */
    public function error_msg()
    {
        if (!empty($this->errors)) {
            $len = count($this->errors) - 1;
            return $this->errors[$len];
        } else {
            return '';
        }
    }
}
