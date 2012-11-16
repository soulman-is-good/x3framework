<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Model
 *
 * @author Soul_man
 */
class X3_MongoConnection extends X3_Component {

    protected static $_db = NULL;

    protected static $_query = NULL;

    protected $config = array(
                'host'=>'localhost',
                'user'=>'root',
                'password'=>'',
                'database'=>null,
            );

    protected $sql = NULL;

    private $_lazyConnect = false;
    private $bTransaction = false;
    private $transaction = array();

    public $query_num = 0;

    public $queryClass = "X3_Mongo_Query";
    public $modelClass = "X3_Mongo_Model";
    
    public function __construct($config = null) {
        if(isset($config['lazyConnect'])){
            $this->_lazyConnect = (bool)$config['lazyConnect'];
            unset($config['lazyConnect']);
        }
        if($config == null || !is_array($config))
            $config = $this->config;
        else
            $this->config = $config;
        if(!$this->_lazyConnect)
            $this->connect($config);
        
    }

    public function connect($config = array()) {
        if(self::$_db !== NULL) return false;
        if(empty($config)) $config = $this->config;
        $server  = (isset($config['user']))?$config['user']:'';
        $server .= (isset($config['password']))?':'.$config['password']:'';
        $server .= (isset($config['user']) || isset($config['user']))?'@':'';
        $server .= (isset($config['host']))?$config['host']:'localhost';
        $dbname = (isset($config['database']))?$config['database']:'local';
        $connection = new Mongo($server);        
        if($connection===false){
            throw new X3_Exception("Could not connect to mongo server", 500);
        }if(false===(self::$_db = $connection->selectDB($dbname))){
            throw new X3_Exception("Could not connect to database", 500);
        }
    }

    public function validate() {
        //$this->sql = mysql_escape_string($this->sql); // strange it is not working
        return $this->sql;
    }

    public function fetch($sql=null) {
        return $this->query($sql);
    }

    public function fetchAll($sql=null) {
        $this->connect();
        $data = array();
        if(!($query = $this->query($sql))) return NULL;        
        return iterator_to_array($query);
    }
    
    public function query($val=null,$pass=true) {
        $this->connect();
        if(is_string($val)){
            //TODO: if json to array() if sql to array;
//            if($this->bTransaction && $pass && (strpos($val,"INSERT")!==false || strpos($val, "UPDATE")!==false || strpos($val, "ALTER")!==false)){
//                $this->transaction[]=$sql;
//                return true;
//            }
        }elseif(is_array($val)){
            $this->sql = $val;
            $func = key($val);
            $val = $val[$func];
            list($coll,$func) = explode(':',$func);
            if(X3_DEBUG)
                X3::log(json_encode($val),'db');
            $this->query_num++;
            return self::$_db->$coll->$func($val);
        }
        return FALSE;
    }
    
    public function startTransaction() {
        $this->bTransaction = true;
        $this->transaction = array();
    }

    public function isTransaction() {
        return $this->bTransaction;
    }

    public function addTransaction($sql) {
        $this->transaction[]=$sql;
    }

    public function getTransaction() {
        return $this->transaction;
    }

    public function commit() {
        $this->connect();
        $this->bTransaction = false;
        if(!mysql_query("START TRANSACTION",self::$_db))
            return false;
        try{
            if(X3_DEBUG) X3::log('Starting transaction','db');
            foreach($this->transaction as $trans){
                mysql_query($trans,self::$_db);
                if(X3_DEBUG) X3::log("\t".$trans,'db');
            }
            if(X3_DEBUG) X3::log('Going for commit','db');
            return $this->query("COMMIT");
        }catch (Exception $e){
            throw new X3_Exception($e->getMessage(), '500');
            X3::log('Transaction failure: '.$e->getMessage(),'db');
            return false;
        }
    }

    public function rollback() {
        $this->bTransaction = false;
        return $this->query("ROLLBACK");
    }

    public function getErrors() {
        if(($msg=mysql_error(self::$_db))=="") $msg = false;
        return $msg;
    }
}
?>
