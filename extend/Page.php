<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 分页类，提供四种显示样式，和ajax支持
 */
class Page {

    public $pageName = "page"; //page标签，用来控制url页。比如说xxx.php?page=2中的page
    public $pageSeparator = '='; //页面参数分隔符
    public $pageParameter = '&'; //页面参数连接符
    public $pageSuffix = '.html'; //页面后缀
    public $nextPage = '下一页'; //下一页
    public $prePage = '上一页'; //上一页
    public $firstPage = '首页'; //首页
    public $lastPage = '尾页'; //尾页
    public $preBar = '<<'; //上一分页条
    public $nextBar = '>>'; //下一分页条
    public $isAjax = false; //是否支持AJAX分页模式 
    public $pageBarNum = 10; //控制记录条的个数。
    public $totalPage = 0; //总页数
    public $ajaxActionName = ''; //AJAX动作名
    public $nowIndex = 0; //当前页
    public $url = ""; //url地址头
    public $requestUri = "";

    /**
     * 构造函数
     * @param type $array
     */
    public function __construct($array = array()) {
        if (isset($array['pageName']))
            $this->set('pageName', $array['pageName']); //设置pagename

        if (isset($array['ajax']) && !empty($array['ajax']))
            $this->openAjax($array['ajax']); //打开AJAX模式
    }

    /**
     * 处理分页参数
     * @param type $url
     * @param type $total
     * @param type $perPage
     * @param type $pageBarNum
     */
    public function doPage($url, $total, $perPage, $pageBarNum) {
        $this->totalPage = ceil($total / $perPage); //计算总页数	
        $this->pageBarNum = $pageBarNum;
        //获取到当前页,同时避免两次执行getCurPage()方法
        $this->nowIndex = $this->nowIndex == 0 ? $this->getCurPage($url) : $this->nowIndex;
    }

    /**
     * 设定类中指定变量名的值，如果改变量不属于这个类，将返回false
     * @param string $var
     * @param string $value
     */
    public function set($var, $value) {
        if (in_array($var, get_object_vars($this))) {
            $this->$var = $value;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 打开AJAX模式
     * @param string $action 默认ajax触发的动作。
     */
    public function openAjax($action) {
        $this->isAjax = true;
        $this->ajaxActionName = $action;
    }

    /**
     * 获取显示"下一页"的代码
     * @param string $style
     * @return string
     */
    public function nextPage($style = 'nextPage') {
        if ($this->nowIndex < $this->totalPage) {
            return $this->_getLink($this->_getUrl($this->nowIndex + 1), $this->nextPage, $style);
        }
        return '';
    }

    /**
     * 获取显示“上一页”的代码
     * @param string $style
     * @return string
     */
    public function prePage($style = 'prePage') {
        if ($this->nowIndex > 1) {
            return $this->_getLink($this->_getUrl($this->nowIndex - 1), $this->prePage, $style);
        }
        return '';
    }

    /**
     * 获取显示“首页”的代码
     * @return string
     */
    public function firstPage($style = 'firstPage') {
        if ($this->nowIndex == 1) {
            return '';
        }
        return $this->_getLink($this->_getUrl(1), $this->firstPage, $style);
    }

    /**
     * 获取显示“尾页”的代码
     * @return string
     */
    public function lastPage($style = 'lastPage') {
        if ($this->nowIndex == $this->totalPage) {
            return '';
        }
        return $this->_getLink($this->_getUrl($this->totalPage), $this->lastPage, $style);
    }

    public function nowBar($style = 'pages', $nowIndex_style = 'current') {
        $plus = ceil($this->pageBarNum / 2);

        if ($this->pageBarNum - $plus + $this->nowIndex > $this->totalPage)
            $plus = ($this->pageBarNum - $this->totalPage + $this->nowIndex);

        $begin = $this->nowIndex - $plus + 1;
        $begin = ($begin >= 1) ? $begin : 1;
        $return = '';
        for ($i = $begin; $i < $begin + $this->pageBarNum; $i++) {
            if ($i <= $this->totalPage) {
                if ($i != $this->nowIndex)
                    $return.=$this->_getText($this->_getLink($this->_getUrl($i), $i, $style));
                else
                    $return.=$this->_getText('<span class="' . $nowIndex_style . '">' . $i . '</span>');
            }
            else {
                break;
            }
            $return.=" ";
        }
        return $return;
    }

    /**
     * 获取显示跳转按钮的代码
     * @return string
     */
    public function select() {
        if ($this->totalPage > 1) {
            $return = '<select onChange="window.location=this.options[this.selectedIndex].value">';
            for ($i = 1; $i <= $this->totalPage; $i++) {
                if ($i == $this->nowIndex) {
                    $return.='<option value="' . $this->_getUrl($i) . '" selected>' . $i . '</option>';
                } else {
                    $return.='<option value="' . $this->_getUrl($i) . '">' . $i . '</option>';
                }
            }
            $return.='</select>';
            return $return;
        }
    }

    /**
     * 控制分页显示风格（你可以增加相应的风格）
      $url，基准网址，若为空，将会自动获取，不建议设置为空
      $total，信息总条数
      $perpage，每页显示行数
      $pagebarnum，分页栏每页显示的页数
      $mode，显示风格，参数可为整数1，2，3，4任意一个
     */
    public function show($url = "", $total = 0, $perPage = 10, $pageBarNum = 10, $mode = 1) {
        $this->doPage($url, $total, $perPage, $pageBarNum);
        //翻页小于1的 不显示页码
        if ($this->totalPage < 1)
            return "";
        switch ($mode) {
            case 1:
                //$this->firstPage() . $this->prePage() . $this->nowBar() . $this->nextPage() . $this->lastPage();

                return $pager = array('page_first' => $this->firstPage(), 'page_prev' => $this->prePage(), 'page_next' => $this->nextPage(), 'page_last' => $this->lastPage(), 'page_number' => $this->showselect(), 'page' => $this->nowIndex, 'page_count' => $this->totalPage , 'count'=>$total);
//                 $GLOBALS['smarty']->assign('pager', $pager);
                break;
            case 2:
                return $this->firstPage() . $this->prePage() . '[第' . $this->nowIndex . '页]' . $this->nextPage() . $this->lastPage() . '第' . $this->select() . '页';
                break;
            case 3:
                return $this->firstPage() . $this->prePage() . $this->nextPage() . $this->lastPage();
                break;
            case 4:
                return $this->prePage() . $this->nowBar() . $this->nextPage();
                break;
            case 5:
                return $this->prePage() . $this->nowBar() . $this->nextPage() . $this->select();
                break;
            default:break;
        }
    }

    /**
     * 显示的下拉页
     */
    public function showselect() {

        $_pagenum = $this->pageBarNum;     // 显示的页码
        $_offset = 2;       // 当前页偏移值
        $_from = $_to = 0;  // 开始页, 结束页
        if ($_pagenum > $this->totalPage) {
            $_from = 1;
            $_to = $this->totalPage;
        } else {
            $_from = $this->nowIndex - $_offset;
            $_to = $_from + $_pagenum - 1;
            if ($_from < 1) {
                $_to = $this->nowIndex + 1 - $_from;
                $_from = 1;
                if ($_to - $_from < $_pagenum) {
                    $_to = $_pagenum;
                }
            } elseif ($_to > $this->totalPage) {
                $_from = $this->totalPage - $_pagenum + 1;
                $_to = $this->totalPage;
            }
        }
        $page_number = array();
        for ($i = $_from; $i <= $_to; ++$i) {
            $page_number[$i] = $this->_getUrl($i);
        }
        return $page_number;
    }

    /**
      获取当前页
     * @param: String $url
     * @return int
     */
    public function getCurPage($url = "") {
        $this->_setUrl($url);
        $nowIndex = 1;
        if (isset($_GET[$this->pageName]) && intval($_GET[$this->pageName]) > 0)
            return intval($_GET[$this->pageName]);

        $pattern = str_replace('\{page\}', '(\d{1,})', preg_quote($this->url, '/'));
        if (preg_match('/' . $pattern . '/i', $this->requestUri, $matches)) {
            if (isset($matches[1]) && $matches[1] > 0)
                return $matches[1];
        }
        return $nowIndex;
    }

    //文章内容分页
    public function contentPage($content, $separator = "[page]", $url = "", $pageBarNum = 10, $mode = 1) {
        $content_array = explode($separator, $content);
        unset($content);
        $total = count($content_array); //计算总行数
        $this->nowIndex = $this->getCurPage($url);
        $index = $this->nowIndex - 1;
        $content = isset($content_array[$index]) ? $content_array[$index] : ""; //获取当前内容
        unset($content_array);
        if ($total > 1)
            $page = $this->show($url, $total, $perPage = 1, $pageBarNum, $mode); //获取分页栏
        else
            $page = "";

        return array('content' => $content, 'page' => $page);
    }

    // private function (私有方法)

    /**
     * 获取REQUEST_URI
     * @return type
     */
    private function _requestUri($url) {
        if (isset($url)) {
            $uri = $url;
        } else {
            if (isset($_SERVER['argv'])) {
                $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
            } else {
                $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            }
        }
        return 'http://' . $_SERVER['HTTP_HOST'] . $uri;
    }

    /**
     * 设置URL
     * @param type $url
     */
    private function _setUrl($url = "") {
        $this->requestUri = $this->_requestUri($url);
        if (!empty($url) && preg_match('/\{page\}/', $url)) {
            $this->url = $url;
        } else {
            $page_str = '&' . $this->pageName . '=';
            if (($url = preg_replace('/' . preg_quote($page_str, '/') . '(\d{1,})/', $page_str . '{page}', $this->requestUri)) == $this->requestUri) {
                if (($url = str_replace($this->pageSuffix, $page_str . '{page}' . $this->pageSuffix, $this->requestUri)) == $this->requestUri) {
                    $uri_arr = explode('?', $this->requestUri, 2);
                    //处理网址中不带操作方法名
                    $str1 = rtrim($uri_arr[0], '/');
                    if (($pos = @strrpos($url, $_GET['_action'])) > 0) {
                        $str1 = substr($str1, 0, $pos);
                    }
                    $str1 = rtrim($str1, '/') . '/' . $_GET['_action'];
                    $str1 = rtrim($str1, '/');
                    $str2 = "";
                    if (isset($uri_arr[1])) {
                        $str2 = '?' . $uri_arr[1];
                    }
                    $url = $str1 . $str2 . $page_str . '{page}';
                }
            }

            $this->url = $url;
        }
    }

    /**
     * 为指定的页面返回地址值
     * @param int $pageNum
     * @return string $url
     */
    private function _getUrl($pageNum = 1) {
        $url = $this->url;
        if ($pageNum > 1) {
            $url = str_replace('{page}', $pageNum, $this->url);
        } else {
            $url = str_replace($this->pageSeparator . '{page}', '', $this->url);
            $url = str_replace($this->pageParameter . $this->pageName, '', $url);
        }
        return $url;
    }

    /**
     * 获取分页显示文字，比如说默认情况下_getText('<a href="">1</a>')将返回[<a href="">1</a>]
     * @param String $str
     * @return string $url
     */
    function _getText($str) {
        return $str;
    }

    /**
     * 获取链接地址
     * @param type $url
     * @param type $text
     * @param type $style
     * @return type
     */
    function _getLink($url, $text, $style = '') {
        $style = empty($style) ? '' : 'class="' . $style . '"';
        if ($this->isAjax) {
            //如果是使用AJAX模式
            return '<a ' . $style . ' href="javascript:' . $this->ajaxActionName . '(\'' . $url . '\')">' . $text . '</a>';
        } else {
//             return '<a ' . $style . ' href="' . $url . '">' . $text . '</a>';

            return $url;
        }
    }

}
