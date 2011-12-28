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

    protected $sql = NULL;

    public $query_num = 0;

    public $queryClass = "X3_MySQL_Query";
    
    public function __construct($config = null) {
        if($config == null || !is_array($config))
            $config = array(
                'host'=>'localhost',
                'user'=>'root',
                'password'=>'',
                'database'=>null,
            );
        
        if(self::$_db === NULL) {
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
    }

    public function validateSQL() {
        //$this->sql = mysql_escape_string($this->sql); // strange it is not working
        return $this->sql;
    }

    public function fetch($sql=null) {
        $res = $this->query($sql);
        if(is_bool($res))
            return $res;
        return mysql_fetch_assoc($res);
    }

    public function query($sql=null) {
        if(empty($sql) && empty($this->sql)) return false;
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
    
    public function fetchAll($sql=null) {
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
