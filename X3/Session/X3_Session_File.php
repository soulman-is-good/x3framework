<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Session_File
 *
 * @author Soul_man
 */
class X3_Session_File extends X3_Component implements X3_Interface_Session, ArrayAccess, Iterator {

    public function __construct($config=array()) {
        foreach ($config as $var => $val) {
            if (property_exists($this, $var))
                $this->$var = $val;
        }
        $this->open();

        foreach ($_SESSION as $key => $val) {
            if (strpos($key, 'X3-ONCE-') === 0 || (strpos($key, 'X3-AJAX-') === 0 && !IS_AJAX)) {
                $val['count']--;
                if ($val['count'] < 0)
                    unset($_SESSION[$key]);
                else
                    $_SESSION[$key] = $val;
            }
        }
    }

    public function __destruct() {
        session_write_close();
    }

    public function open() {
        session_start();
        register_shutdown_function(array($this, 'close'));
    }

    public function close() {
        session_write_close();
    }

    public function erase() {
        session_destroy();
    }

    public function regenerate($bDelete=false) {
        session_regenerate_id($bDelete);
    }

    public function write($key, $value) {
        if ($value === null && isset($_SESSION[$key]))
            unset($_SESSION[$key]);
        else
            $_SESSION[$key] = $value;
    }

    public function writeOnce($key, $value) {
        if ($value === null && isset($_SESSION['X3-ONCE-' . $key]))
            unset($_SESSION['X3-ONCE-' . $key]);
        else
            $_SESSION['X3-ONCE-' . $key] = array('value' => $value, 'count' => 1);
    }

    public function writeAjax($key, $value) {
        if ($value === null && isset($_SESSION['X3-AJAX-' . $key]))
            unset($_SESSION['X3-AJAX-' . $key]);
        else
            $_SESSION['X3-AJAX-' . $key] = array('value' => $value, 'count' => 0);
    }

    public function read($key) {
        if (isset($_SESSION[$key]))
            return $_SESSION[$key];
        elseif (isset($_SESSION['X3-ONCE-' . $key])) {
            $val = $_SESSION['X3-ONCE-' . $key];
            return $val['value'];
        } elseif (isset($_SESSION['X3-AJAX-' . $key])) {
            $val = $_SESSION['X3-AJAX-' . $key];
            return $val['value'];
        }
        return null;
    }

    public function readOnce($key) {
        if (isset($_SESSION['X3-ONCE-' . $key])) {
            $val = $_SESSION['X3-ONCE-' . $key];
            return $val['value'];
        }else
            return null;
    }

    public function readAjax($key) {
        if (isset($_SESSION['X3-AJAX-' . $key])) {
            $val = $_SESSION['X3-AJAX-' . $key];
            return $val['value'];
        }else
            return null;
    }

    public function destroy($key) {
        unset($_SESSION[$key]);
    }

    public function getSessionId() {
        return session_id();
    }

    public function clean($expire) {
        if((int)$expire<time()){
            session_unset();
        }
        return $_SESSION;
    }

    /**
     * Setters, getters and other
     */
    public function __unset($name) {
        if (isset($_SESSION[$key]))
            unset($_SESSION[$key]);
        elseif (isset($_SESSION['X3-ONCE-' . $key])) {
            unset($_SESSION['X3-ONCE-' . $key]);
        } elseif (isset($_SESSION['X3-AJAX-' . $key])) {
            unset($_SESSION['X3-AJAX-' . $key]);
        }else
            parent::__unset($name);
    }
    
    public function offsetExists($offset) {
        return array_key_exists($offset, $_SESSION);
    }

    public function offsetSet($offset, $value) {
        $_SESSION[$offset] = $value;
    }

    public function offsetUnset($offset) {
        if (isset($_SESSION[$offset]))
            unset($_SESSION[$offset]);
        if (isset($_COOKIE[$offset]))
            setcookie ($offset);
    }

    public function offsetGet($offset) {
        return array_key_exists($offset, $_SESSION) ? $_SESSION[$offset] : (isset($_COOKIE[$offset])?$_COOKIE[$offset]:null);
    }

    public function rewind() {
        return reset($_SESSION);
    }

    public function current() {
        return current($_SESSION);
    }

    public function key() {
        return key($_SESSION);
    }

    public function next() {
        return next($_SESSION);
    }

    public function valid() {        
        return !is_null(key($_SESSION));
    }

}
?>
