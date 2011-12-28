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
}
?>
