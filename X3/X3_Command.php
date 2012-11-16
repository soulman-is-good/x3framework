<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Command
 *
 * @author Soul_man
 */
abstract class X3_Command extends X3_Component {

    public $action = 'index';
    public $template = null;

    public function __construct($action = null) {
        $this->action = $action;
        $this->template = new X3_Renderer($this);
        $this->template->layout = null;
    }

    public function init(){}

    public function run() {
        $a = $this->action;
        if ($a == null || $a == '')
            return false;
        $a = "run".ucfirst($a);
        if (method_exists($this, $a))
            $this->$a();
        return true;
    }

}

?>
