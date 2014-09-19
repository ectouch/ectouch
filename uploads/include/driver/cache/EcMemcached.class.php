<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

class EcMemcached {

    private $mmc = NULL;
    private $group = '';
    private $ver = 0;

    public function __construct($memConfig = array()) {
        $this->mmc = new Memcached;
        if (empty($memConfig)) {
            $memConfig['MEM_SERVER'] = array(array('127.0.0.1', 11211));
            $memConfig['MEM_GROUP'] = '';
        }
        foreach ($memConfig['MEM_SERVER'] as $config) {
            call_user_func_array(array($this->mmc, 'addServer'), $config);
        }
        $this->group = $memConfig['MEM_GROUP'];
        $this->ver = intval($this->mmc->get($this->group . '_ver'));
    }

    //读取缓存
    public function get($key) {
        return $this->mmc->get($this->group . '_' . $this->ver . '_' . $key);
    }

    //设置缓存
    public function set($key, $value, $expire = 1800) {
        return $this->mmc->set($this->group . '_' . $this->ver . '_' . $key, $value, $expire);
    }

    //自增1
    public function inc($key, $value = 1) {
        return $this->mmc->increment($this->group . '_' . $this->ver . '_' . $key, $value);
    }

    //自减1
    public function des($key, $value = 1) {
        return $this->mmc->decrement($this->group . '_' . $this->ver . '_' . $key, $value);
    }

    //删除
    public function del($key) {
        return $this->mmc->delete($this->group . '_' . $this->ver . '_' . $key);
    }

    //全部清空
    public function clear() {
        return $this->mmc->set($this->group . '_ver', $this->ver + 1);
    }

}
