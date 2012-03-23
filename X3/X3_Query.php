<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Query
 *
 * @author Soul_man
 */
class X3_Query extends X3_Component{
    private $class = null;

    public function __construct() {
        $args = func_get_args();
        $this->class = X3::app()->db->queryClass;
        $this->class = new $this->class($args[0],$args[1]);
        return $this->class;
        //return call_user_func(array($class,'__construct'),$args);
    }

    public function getQueryClass() {
        return $this->class;
    }

    public function __get($name) {
        if(property_exists($this->class,$name))
            return $this->class->$name;
        return parent::__get($name);
    }

    public function __set($name,$value) {
        if(property_exists($this->class,$name))
            $this->class->$name = $value;
        else
            parent::__set($name,$value);
    }

    public function __call($name, $parameters) {
        if(method_exists($this->class, $name))
            return call_user_func_array(array($this->class,$name),$parameters);
        return parent::__call($name, $parameters);
    }
    
    public static function __callStatic($name, $arguments) {
        if(method_exists($this->class, $name))
            return call_user_func_array(array($this->class,$name),$arguments);
        parent::__call($name, $arguments);
    }
}
?>
