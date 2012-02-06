<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Log_File
 *
 * @author Soul_man
 */
class X3_Log_Web extends X3_Log {

    private $prefix = '[d.m.Y H:i:s] ';

    public function __construct($props) {
        parent::__construct($props['category']);
        foreach ($props as $key => $value) {
            if(property_exists($this, $key))
                $this->$key = $value;
        }

        if(isset($props['prefix']))
            $this->prefix = $props['prefix'];
    }
    public function  processLog($log) {
        echo date($this->prefix) . $log . "<br/>";

        parent::processLog($log);
    }
}
?>
