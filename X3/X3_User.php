<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_UserIdentity
 *
 * @author Soul_man
 */
class X3_User extends X3_Component {
    
    private $key_prefix = "X3.User.";
    public $username = null;
    public $password = null;
    public $group = '*';
    private static $session = null;

    public function __construct() {
        self::$session = X3_Session::getInstance();
        if(isset(self::$session[$this->key_prefix . 'role']))
            $this->group = self::$session[$this->key_prefix . 'role'];
    }

    public function  __get($name) {
        if(isset(self::$session[$this->key_prefix . $name]))
            return self::$session[$this->key_prefix . $name];
        return null;
    }

    public function  __set($name, $value) {
        //if(isset(self::$session[$this->key_prefix]))
        self::$session[$this->key_prefix . $name] = $value;
    }

    public function __call($name, $parameters) {
        if(strpos($name,'is')===0){
            $name = strtolower(substr($name,2));
            return ($this->group===$name);
        }
        parent::__call($name, $parameters);
    }


    public function authenticate(){
        throw new X3_Exception("authenticate method must be implemented", 500);
        return false;
    }

    public function login(){
        if($this->authenticate()) {
            return true;
        }else
            return false;
    }

    public function isGuest(){
        return $this->group=='*';
    }

    public function __unset($name) {
        if(isset(self::$session[$this->key_prefix . $name]))
            unset(self::$session[$this->key_prefix . $name]);
        else
            parent::__unset($name);
    }

    /**
     *
     * @return boolean return true if user was logged in and false otherwise
     */
    public function logout(){        
        if(!$this->isGuest()){
            foreach(self::$session as $key=>$value){
                if(strpos($key,$this->key_prefix)===0){
                    unset(self::$session[$key]);
                }
            }
            self::$session->close();
            self::$session = null;
            return true;
        }else
            return false;
    }

}
?>
