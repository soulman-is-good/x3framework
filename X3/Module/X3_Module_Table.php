<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Module_Table
 *
 * @author Soul_man
 */
class X3_Module_Table extends X3_Module implements Iterator {
    public $tableName = null;
    /**
     * has such stucture:
     * Array
     * (
     *  [0] => Array
     *   (
     *       [Field] => id
     *       [Type] => int(10) unsigned
     *       [Null] => NO
     *       [Key] => PRI
     *       [Default] =>
     *       [Extra] => auto_increment
     *   ),
     * ...etc.
     * )
     * @var array
     */
    public $_fields = array();
    private $tables = array();
    private $position = 0;
    private $table = null;

    public function __construct($action = null) {
        if ($this->tableName != null && !empty($this->_fields)) {
            $this->addTrigger('beforeSave');
            $this->addTrigger('afterSave');
            $this->table = new X3_Model($this->tableName,$this);
        }else{
            if($this->tableName==null)
                X3::log('WARNING: No tableName set in '.get_class($this).' class instance of X3_Module_Table. You may instance X3_Module instead.');
            else
                X3::log('WARNING: Must specify fields array in '.get_class($this));
        }
        return parent::__construct($action);
    }

    public function push($table){
        if($table instanceof X3_Model){
            //TODO: check if table already exist in the array (with help of primary key)
            $this->tables[]=$table;
        }
    }

    public function fieldNames(){return array();}

    public function fieldName($name){
        $names = $this->fieldNames();
        if(isset($names[$name]))
            return $names[$name];
        else
            return ucfirst($name);
    }

    public function beforeValidate(){return true;}
    public function afterValidate(){return true;}

    public function getTable() {
        return $this->table;
    }

    public function addError($field,$error) {
        $this->table->addError($field, $error);
    }

    public function save() {
        return $this->table->save();
    }

/**
 * Getters setters and other routine
 */

    public function __set($name, $value) {
        if (isset($this->_fields[$name])) {
            $this->table[$name] = $value;
        }else
            parent::__set($name, $value);
    }

    public function __get($name) {
        if(isset($this->table[$name]))
            return $this->table[$name];
        if(isset($this->_fields) && array_key_exists($name,$this->_fields))
            return $this->table[$name]=isset($this->_fields[$name]['default'])?$this->_fields[$name]['default']:"";
        if(in_array($name, $this->table->_queries)){
            return $this->table->_queries[$name];
        }else
            return parent::__get($name);
    }

    function rewind() {
        $this->position = 0;
    }

    function current() {
        return $this->tables[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
    }

    function valid() {
        return isset($this->tables[$this->position]);
    }


}
?>
