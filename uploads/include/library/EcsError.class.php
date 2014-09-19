<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * ECSHOP 用户级错误处理类
 */
class EcsError {

    var $_message = array();
    var $_template = '';
    var $error_no = 0;

    /**
     * 构造函数
     *
     * @access  public
     * @param   string  $tpl
     * @return  void
     */
    function __construct($tpl) {
        $this->_template = $tpl;
    }

    /**
     * 添加一条错误信息
     *
     * @access  public
     * @param   string  $msg
     * @param   integer $errno
     * @return  void
     */
    function add($msg, $errno = 1) {
        if (is_array($msg)) {
            $this->_message = array_merge($this->_message, $msg);
        } else {
            $this->_message[] = $msg;
        }

        $this->error_no = $errno;
    }

    /**
     * 清空错误信息
     *
     * @access  public
     * @return  void
     */
    function clean() {
        $this->_message = array();
        $this->error_no = 0;
    }

    /**
     * 返回所有的错误信息的数组
     *
     * @access  public
     * @return  array
     */
    function get_all() {
        return $this->_message;
    }

    /**
     * 返回最后一条错误信息
     *
     * @access  public
     * @return  void
     */
    function last_message() {
        return array_slice($this->_message, -1);
    }

    /**
     * 显示错误信息
     *
     * @access  public
     * @param   string  $link
     * @param   string  $href
     * @return  void
     */
    function show($link = '', $href = '') {
        if ($this->error_no > 0) {
            $message = array();

            $link = (empty($link)) ? L('back_up_page') : $link;
            $href = (empty($href)) ? 'javascript:history.back();' : $href;
            $message['url_info'][$link] = $href;
            $message['back_url'] = $href;

            foreach ($this->_message AS $msg) {
                $message['content'] =  htmlspecialchars($msg);
            }
			$view = ECTouch::view();
            if (isset($view)) {
                assign_template();
                ECTouch::view()->assign('auto_redirect', true);
                ECTouch::view()->assign('message', $message);
                ECTouch::view()->display($this->_template);
            } else {
                die($message['content']);
            }

            exit;
        }
    }

}

?>