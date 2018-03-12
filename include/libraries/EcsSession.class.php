<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * ECSHOP SESSION
 */
class EcsSession
{
    public $db = null;
    public $session_table = '';
    public $max_life_time = 1800;
    public $session_name = '';
    public $session_id = '';
    public $session_expiry = '';
    public $session_md5 = '';
    public $session_cookie_path = '/';
    public $session_cookie_domain = '';
    public $session_cookie_secure = false;
    public $_ip = '';
    public $_time = 0;

    public function __construct(&$db, $session_table, $session_data_table, $session_name = 'ECS_ID', $session_id = '')
    {
        $GLOBALS['_SESSION'] = array();

        if (!empty($GLOBALS['cookie_path'])) {
            $this->session_cookie_path = $GLOBALS['cookie_path'];
        } else {
            $this->session_cookie_path = '/';
        }

        if (!empty($GLOBALS['cookie_domain'])) {
            $this->session_cookie_domain = $GLOBALS['cookie_domain'];
        } else {
            $this->session_cookie_domain = '';
        }

        if (!empty($GLOBALS['cookie_secure'])) {
            $this->session_cookie_secure = $GLOBALS['cookie_secure'];
        } else {
            $this->session_cookie_secure = false;
        }

        $this->session_name = $session_name;
        $this->session_table = $session_table;
        $this->session_data_table = $session_data_table;

        $this->db = &$db;
        $this->_ip = '';

        // 防御cc或DDOS lite
        $enable_firewall = false;
        $cc_nowtime = time();
        $black_path = ROOT_PATH . 'data/caches/black_ip/';
        if ($enable_firewall) {
            if (!is_dir($black_path)) {
                mkdir($black_path, 755);
            }
            // 如果是黑名单IP，禁止访问
            if (file_exists($black_path . $this->_ip)) {
                $cc_lasttime = file_get_contents($black_path . $this->_ip);
                if (($cc_nowtime - $cc_lasttime) < 10) {
                    header('HTTP/1.1 404 Not Found');
                    header("status: 404 Not Found");
                    exit();
                }
            }
        }

        if ($session_id == '' && !empty($_COOKIE[$this->session_name])) {
            $this->session_id = $_COOKIE[$this->session_name];
        } else {
            $this->session_id = $session_id;
        }

        if ($this->session_id) {
            $tmp_session_id = substr($this->session_id, 0, 32);
            if ($this->gen_session_key($tmp_session_id) == substr($this->session_id, 32)) {
                $this->session_id = $tmp_session_id;
            } else {
                $this->session_id = '';
            }
        }

        $this->_time = time();

        if ($this->session_id) {
            $this->load_session();
        } else {
            $this->gen_session_id();

            setcookie($this->session_name, $this->session_id . $this->gen_session_key($this->session_id), 0, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);
        }

        // 防御cc或DDOS lite
        if ($enable_firewall) {
            if (isset($_SESSION['cc_lasttime'])) {
                $cc_lasttime = $_SESSION['cc_lasttime'];
                $cc_times = $_SESSION['cc_times'] + 1;
                $_SESSION['cc_times'] = $cc_times;
            } else {
                $cc_lasttime = $cc_nowtime;
                $cc_times = 1;
                $_SESSION['cc_times'] = $cc_times;
                $_SESSION['cc_lasttime'] = $cc_lasttime;
            }
            // 当前ip的用户数量
            $cc_users = $this->db->getOne('SELECT count(*) FROM ' . $this->session_table . " WHERE ip = '". $this->_ip ."'");
            // handler
            if (($cc_nowtime - $cc_lasttime) < 5 || $cc_users >= 3) {
                if ($cc_times >= 10 || $cc_users >= 3) {
                    // 回收游客数据
                    $this->db->query('DELETE FROM ' . $this->session_table . " WHERE userid = 0 AND ip = '" . $this->_ip . "'");
                    // 记录黑名单IP
                    file_put_contents($black_path . $this->_ip, $cc_lasttime);
                    header('HTTP/1.1 404 Not Found');
                    header("status: 404 Not Found");
                    exit();
                }
            } else {
                $cc_times = 0;
                $_SESSION['cc_lasttime'] = $cc_nowtime;
                $_SESSION['cc_times'] = $cc_times;
            }
        }

        register_shutdown_function(array(&$this, 'close_session'));
    }

    public function gen_session_id()
    {
        $this->session_id = md5(uniqid(mt_rand(), true));

        return $this->insert_session();
    }

    public function gen_session_key($session_id)
    {
        static $ip = '';

        if ($ip == '') {
            $ip = substr($this->_ip, 0, strrpos($this->_ip, '.'));
        }

        return sprintf('%08x', crc32(ROOT_PATH . $ip . $session_id));
    }

    public function insert_session()
    {
        return $this->db->query('INSERT INTO ' . $this->session_table . " (sesskey, expiry, ip, data) VALUES ('" . $this->session_id . "', '" . $this->_time . "', '" . $this->_ip . "', 'a:0:{}')");
    }

    public function load_session()
    {
        $session = $this->db->getRow('SELECT userid, adminid, user_name, user_rank, discount, email, data, expiry FROM ' . $this->session_table . " WHERE sesskey = '" . $this->session_id . "'");
        if (empty($session)) {
            $this->insert_session();

            $this->session_expiry = 0;
            $this->session_md5 = '40cd750bba9870f18aada2478b24840a';
            $GLOBALS['_SESSION'] = array();
        } else {
            if (!empty($session['data']) && $this->_time - $session['expiry'] <= $this->max_life_time) {
                $this->session_expiry = $session['expiry'];
                $this->session_md5 = md5($session['data']);
                $GLOBALS['_SESSION'] = unserialize($session['data']);
                $GLOBALS['_SESSION']['user_id'] = $session['userid'];
                $GLOBALS['_SESSION']['admin_id'] = $session['adminid'];
                $GLOBALS['_SESSION']['user_name'] = $session['user_name'];
                $GLOBALS['_SESSION']['user_rank'] = $session['user_rank'];
                $GLOBALS['_SESSION']['discount'] = $session['discount'];
                $GLOBALS['_SESSION']['email'] = $session['email'];
            } else {
                $session_data = $this->db->getRow('SELECT data, expiry FROM ' . $this->session_data_table . " WHERE sesskey = '" . $this->session_id . "'");
                if (!empty($session_data['data']) && $this->_time - $session_data['expiry'] <= $this->max_life_time) {
                    $this->session_expiry = $session_data['expiry'];
                    $this->session_md5 = md5($session_data['data']);
                    $GLOBALS['_SESSION'] = unserialize($session_data['data']);
                    $GLOBALS['_SESSION']['user_id'] = $session['userid'];
                    $GLOBALS['_SESSION']['admin_id'] = $session['adminid'];
                    $GLOBALS['_SESSION']['user_name'] = $session['user_name'];
                    $GLOBALS['_SESSION']['user_rank'] = $session['user_rank'];
                    $GLOBALS['_SESSION']['discount'] = $session['discount'];
                    $GLOBALS['_SESSION']['email'] = $session['email'];
                } else {
                    $this->session_expiry = 0;
                    $this->session_md5 = '40cd750bba9870f18aada2478b24840a';
                    $GLOBALS['_SESSION'] = array();
                }
            }
        }
    }

    public function update_session()
    {
        $adminid = !empty($GLOBALS['_SESSION']['admin_id']) ? intval($GLOBALS['_SESSION']['admin_id']) : 0;
        $userid = !empty($GLOBALS['_SESSION']['user_id']) ? intval($GLOBALS['_SESSION']['user_id']) : 0;
        $user_name = !empty($GLOBALS['_SESSION']['user_name']) ? trim($GLOBALS['_SESSION']['user_name']) : 0;
        $user_rank = !empty($GLOBALS['_SESSION']['user_rank']) ? intval($GLOBALS['_SESSION']['user_rank']) : 0;
        $discount = !empty($GLOBALS['_SESSION']['discount']) ? round($GLOBALS['_SESSION']['discount'], 2) : 0;
        $email = !empty($GLOBALS['_SESSION']['email']) ? trim($GLOBALS['_SESSION']['email']) : 0;
        unset($GLOBALS['_SESSION']['admin_id']);
        unset($GLOBALS['_SESSION']['user_id']);
        unset($GLOBALS['_SESSION']['user_name']);
        unset($GLOBALS['_SESSION']['user_rank']);
        unset($GLOBALS['_SESSION']['discount']);
        unset($GLOBALS['_SESSION']['email']);

        $data = serialize($GLOBALS['_SESSION']);
        $this->_time = time();

        if ($this->session_md5 == md5($data) && $this->_time < $this->session_expiry + 10) {
            return true;
        }

        $data = addslashes($data);

        if (isset($data{255})) {
            $this->db->autoReplace($this->session_data_table, array('sesskey' => $this->session_id, 'expiry' => $this->_time, 'data' => $data), array('expiry' => $this->_time, 'data' => $data));

            $data = '';
        }

        return $this->db->query('UPDATE ' . $this->session_table . " SET expiry = '" . $this->_time . "', ip = '" . $this->_ip . "', userid = '" . $userid . "', adminid = '" . $adminid . "', user_name='" . $user_name . "', user_rank='" . $user_rank . "', discount='" . $discount . "', email='" . $email . "', data = '$data' WHERE sesskey = '" . $this->session_id . "' LIMIT 1");
    }

    public function close_session()
    {
        $this->update_session();

        /* 闅忔満瀵 sessions_data 鐨勫簱杩涜?鍒犻櫎鎿嶄綔 */
        if (mt_rand(0, 2) == 2) {
            $this->db->query('DELETE FROM ' . $this->session_data_table . ' WHERE expiry < ' . ($this->_time - $this->max_life_time));
        }

        if ((time() % 2) == 0) {
            return $this->db->query('DELETE FROM ' . $this->session_table . ' WHERE expiry < ' . ($this->_time - $this->max_life_time));
        }

        return true;
    }

    public function delete_spec_admin_session($adminid)
    {
        if (!empty($GLOBALS['_SESSION']['admin_id']) && $adminid) {
            return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE adminid = '$adminid'");
        } else {
            return false;
        }
    }

    public function destroy_session()
    {
        $GLOBALS['_SESSION'] = array();

        setcookie($this->session_name, $this->session_id, 1, $this->session_cookie_path, $this->session_cookie_domain, $this->session_cookie_secure);

        /* ECSHOP 鑷?畾涔夋墽琛岄儴鍒 */
        if (!empty($GLOBALS['ecs'])) {
            $this->db->query('DELETE FROM ' . $GLOBALS['ecs']->table('cart') . " WHERE session_id = '$this->session_id'");
        }
        /* ECSHOP 鑷?畾涔夋墽琛岄儴鍒 */

        $this->db->query('DELETE FROM ' . $this->session_data_table . " WHERE sesskey = '" . $this->session_id . "' LIMIT 1");

        return $this->db->query('DELETE FROM ' . $this->session_table . " WHERE sesskey = '" . $this->session_id . "' LIMIT 1");
    }

    public function get_session_id()
    {
        return $this->session_id;
    }

    public function get_users_count()
    {
        return $this->db->getOne('SELECT count(*) FROM ' . $this->session_table);
    }
}
