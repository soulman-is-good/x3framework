<?php
/**
 * X3_MySQL_Query
 *
 * @author Soul_man
 */
class X3_MySQL_Query extends X3_Component implements Iterator {

    /**
     *
     * @var X3_MySQL_Connection 
     */
    protected $db = null;
    protected $class = null;
    protected $position = 0;
    protected $queryString = null;
    protected $query = null;
    protected $current = null;

    public function __construct($queryString = null,$className=null,$db = null) {
        if ($className != null)
            $this->class = $className;

        if($db==null)
            $db = X3::db();
        $this->db = $db;
        $this->query = $this->db->query($queryString,false);
        if(!is_resource($this->query)){
            throw new X3_Exception($this->db->getErrors(),500);
        }
    }
        
    public function count(){
        return mysql_num_rows($this->query);
    }

    public function current() {
        if($this->current != false){
            if(is_null($this->class)){
                $this->position++;
                return $this->current;
            }
            if(is_string($this->class) && (class_exists($this->class))){
                $class = new $this->class;
            }
            array_walk($this->current, create_function('&$v,$k,$obj', '$obj->$k = $v;'),&$class);
            $this->position++;
            return $class;
        }
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        return $this->current = mysql_fetch_assoc($this->query);
    }

    public function rewind() { 
        if($this->count()>0){
            mysql_data_seek($this->query, 0);
            $this->current = mysql_fetch_assoc ($this->query);
        }
        $this->position = 0;
    }

    public function valid() {
        return is_resource($this->query) && $this->current!==FALSE;
    }

}