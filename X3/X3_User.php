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
    private $group = '*';
    private static $session = null;

    public function __construct() {
        self::$session = X3_Session::getInstance();
        if(isset(self::$session[$this->key_prefix . 'role']))
            $this->group = self::$session[$this->key_prefix . 'role'];
        $this->init();
    }
    
    public function init() {}

    public function  __get($name) {
        if($name=='group' || $name == 'role') 
            return ($this->group=='root')?'admin':$this->group;
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
            return ($this->group==='root') || ($this->group===$name);
        }
        parent::__call($name, $parameters);
    }


    public function authenticate(){
        throw new X3_Exception("authenticate method must be implemented", 500);
        return false;
    }
    
    public function justLoggedOut() {
        return $this->isGuest() && isset($_COOKIE[$this->key_prefix."user.remind"]);
    }
    
    public function isRemembered() {
        return isset($_COOKIE[$this->key_prefix."user.hash"]);
    }

    public function login(){
        if($this->authenticate()) {
            setcookie($this->key_prefix . "user.remind", "1", time()+86400, "/",null, false, true);//by day
            return true;
        }else
            return false;
    }
    
    public function remember($time=3600) {
        if(is_string($time))
            $time = strtotime($time,time());
        else
            $time = time()+(int)$time;
        $hash = base64_encode(sprintf("%-255s%-255s",  $this->username,$this->password));
        setcookie($this->key_prefix . "user.hash", $hash, $time, "/", null, false, true);//by month
    }
    
    public function recall() {
        if($this->isGuest() && isset($_COOKIE[$this->key_prefix."user.hash"])){
            $hash = base64_decode($_COOKIE[$this->key_prefix."user.hash"]);
            if(strlen($hash)==510){
                $params = array();
                $params['username'] = trim(substr($hash,0,255));
                $params['password'] = trim(substr($hash,255));
                return $param;
            }
        }
        return false;
    }
    
    public function store($name, $value, $time=3600) {
        setcookie($this->key_prefix . $name, $value, $time, "/", null, false, true);
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
                setcookie($this->key_prefix."user.hash", "", time()-3600,"/");
            }
            self::$session->erase();
            self::$session = null;
            return true;
        }else
            return false;
    }

}
?>
