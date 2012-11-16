<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Log
 *
 * @author Soul_man
 */
class X3_LogRouter extends X3_Component {

    public static $_stack = array();

    public function __construct($logs) {
        foreach($logs as $name=>$log){
            if(!isset($log['category'])) $log['category'] = '*';
            self::$_stack[$name] = new $log['class']($log);
        }
    }

    public function processLog($msg,$category) {
        foreach(self::$_stack as $log){
            $cs = $log->getCategory();
            if($cs[0] == '*' || in_array($category,$cs)){         
                $log->processLog($msg,$category);
            }
        }
    }

    public function __get($name) {
        if(isset(self::$_stack[$name]))
            return self::$_stack[$name];
        parent::__get($name);
    }
}
?>
