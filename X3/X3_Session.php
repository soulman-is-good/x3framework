<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Session
 *
 * @author Soul_man
 */
class X3_Session extends X3_Component{

    private static $_instance = null;
    public static $class = 'X3_Session_File';

    public static function getInstance() {
        $class = self::$class;
        if (self::$_instance === null)
            self::$_instance = new $class();
        return self::$_instance;
    }

    public function __construct() {
        if (self::$_instance != null)
            return self::$_instance;

        self::$_instance = new $class();

        return self::$_instance;
    }

    public static function read($key) {
        $I = self::getInstance();
        return $I->read($key);
    }

    public static function readOnce($key) {
        $I = self::getInstance();
        return $I->readOnce($key);
    }

    public static function readAjax($key) {
        $I = self::getInstance();
        return $I->readAjax($key);
    }

    public static function write($key,$value) {
        $I = self::getInstance();
        return $I->write($key,$value);
    }

    public static function writeOnce($key,$value) {
        $I = self::getInstance();
        return $I->writeOnce($key,$value);
    }

    public static function writeAjax($key,$value) {
        $I = self::getInstance();
        return $I->writeAjax($key,$value);
    }
}
?>
