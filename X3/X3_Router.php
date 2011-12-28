<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Router
 *
 * @author Soul_man
 */
class X3_Router extends X3_Component {

    public $routes = array();

    public function __construct($config = array()) {
        foreach($config as $var=>$val){
            if(property_exists($this, $var))
                $this->$var = $val;
        }
        $this->addTrigger('onStartApp');
    }

    public function onStartApp(&$module,&$action) {
        if(!is_array($this->routes)) return;
        foreach($this->routes as $path=>$directives){
            $redirect = false;
            $route = array_shift($directives);
            if(!empty($directives))
                $redirect = array_shift($directives);
            //TODO: either way redirect
            $matches = array();
            if(preg_match ($path, $_SERVER['REQUEST_URI'], $matches)>0){
                array_shift($matches);
                foreach($matches as $i=>$replacement){
                    $route = str_replace('$'.($i+1), $replacement, $route);
                }
                list($module,$action) = X3::app()->request->resolveURI($route);
            }
        }
    }
}
?>
