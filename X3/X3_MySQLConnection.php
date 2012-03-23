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
class X3_MySQLConnection extends X3_Component {

    protected static $_db = NULL;

    protected static $_query = NULL;

    protected $config = array(
                'host'=>'localhost',
                'user'=>'root',
                'password'=>'',
                'database'=>null,
            );

    protected $sql = NULL;

    private $bTransaction = false;
    private $transaction = array();

    public $query_num = 0;

    public $queryClass = "X3_MySQL_Query";
    
    public function __construct($config = null) {
        if($config == null || !is_array($config))
            $config = $this->config;
        else
            $this->config = $config;
        
        $this->connect($config);
        
    }

    public function connect($config = array()) {
        if(self::$_db !== NULL) return false;
        if(empty($config)) $config = $this->config;
        $server = (isset($config['host']))?$config['host']:'localhost';
        $username = (isset($config['user']))?$config['user']:'root';
        $password = (isset($config['password']))?$config['password']:'';
        $dbname = (isset($config['database']))?$config['database']:'information_schema';
        self::$_db = @mysql_connect($server, $username, $password);
        if(self::$_db===false){
            throw new X3_Exception("Could not connect to mysql server", 500);
        }if(!mysql_select_db($dbname, self::$_db)){
            throw new X3_Exception("Could not connect to database", 500);
        }
        @mysql_query("SET NAMES utf8");
    }

    public function validateSQL() {
        //$this->sql = mysql_escape_string($this->sql); // strange it is not working
        return $this->sql;
    }

    public function fetch($sql=null) {
        $res = $this->query($sql);
        if(is_resource($res))
            return mysql_fetch_assoc($res);
        return false;
    }

    public function query($sql=null,$pass=true) {
        $this->connect();
        if(empty($sql) && empty($this->sql))
            throw new X3_Exception ("Empty query", "500");
        if($this->bTransaction && $pass && (strpos($sql,"INSERT")!==false || strpos($sql, "UPDATE")!==false || strpos($sql, "ALTER")!==false)){
            $this->transaction[]=$sql;
            return true;
        }
        if(!empty($sql)) {
            $this->sql = $sql;
            if(X3_DEBUG)
            X3::log($sql,'db');
            $this->validateSql();
        }
        $this->query_num++;
        //TODO:Handling mysql_unbuffered_query
        return mysql_query($this->sql, self::$_db);
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
    
    public function fetchAll($sql=null) {
        $this->connect();
        $data = array();
        if(!($query = $this->query($sql))) return NULL;
        $query = mysql_query($this->sql, self::$_db);
        if(!$query) {
            throw new X3_Exception($this->getErrors(), 500);
            return false;
        }
        while($data[] = mysql_fetch_assoc($query)) {}
        array_pop($data);
        return $data;
    }

    public function getErrors() {
        if(($msg=mysql_error(self::$_db))=="") $msg = false;
        return $msg;
    }
}
?>
