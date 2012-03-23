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
class X3_Module_Table extends X3_Module implements Iterator, ArrayAccess {
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

    public function count() {
        if(!empty($this->tables))
            return sizeof($this->tables);
        else return 0;
    }

    public function fieldNames(){return array();}

    public function fieldName($name){
        $names = $this->fieldNames();
        if(isset($names[$name]))
            return $names[$name];
        else
            return ucfirst($name);
    }

    public function getAttributes() {
        $attributes = array();
        foreach($this->tables as $table){
            $attributes[] = $table->attributes;
        }
        return $attributes;
    }

    public function beforeValidate(){return true;}
    public function afterValidate(){return true;}

    public function beforeSave(){return true;}
    public function afterSave($bNew=false){return true;}

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
     *  <b>WARNING</b>
     *  <i>For PHP lower than 5.3 you must implement this function</i>
     * @param <type> $pk
     * @param string $class Class name for static creation
     * @return X3_Module_Table current class
     */
    public static function getByPk($pk,$class=null) {
        if($class==null)
            $class = get_called_class();
        elseif($class==null)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getByPk(\$pk,\$class=__CLASS__)");
        $class = new $class();
        $pk = mysql_real_escape_string($pk);
        return $class->table->select('*')->where("`".$class->table->_PK."`='$pk'")->asObject(true);
    }

    public static function get($params=array(),$single=false,$class=null) {
        if($class==null)
            $class = get_called_class();
        elseif($class==null)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию get(\$params,\$single=false,\$class=__CLASS__)");
        if(!is_array($params)) return NULL;
        $class = new $class();
        if(empty($params))
            $query = '1';
        else
            $query = $class->table->formQuery($params);
        return  $class->table->select('*')->where($query)->asObject($single);
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
        if(in_array($name, X3_Model::$_queries)){
            return X3_Model::$_queries[$name];
        }else
            return parent::__get($name);
    }

    public function __call($name, $parameters) {
        if(method_exists($this->table, $name) || method_exists(X3_Model::$_queries[$this->tableName]->getQueryClass(),$name))
            return call_user_func_array (array($this->table,$name), $parameters);

        return parent::__call($name, $parameters);
    }

    function rewind() {
        $this->position = 0;
        if(isset($this->tables[$this->position]))
            $this->table = $this->tables[$this->position];
    }

    function current() {
        if(isset($this->tables[$this->position]))
            return $this->tables[$this->position];
        else
            return $this->table;
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
        if(isset($this->tables[$this->position]))
            $this->table = $this->tables[$this->position];
    }

    function valid() {
        return isset($this->tables[$this->position]);
    }

    public function offsetExists($offset) {
        return isset($this->tables[$offset]);
    }

    public function offsetSet($offset, $value) {
            $this->tables[$offset]->accuire($value);
    }

    public function offsetUnset($offset) {
        if (isset($this->tables[$offset]))
            unset($this->tables[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->tables[$offset]) ? $this->tables[$offset] : null;
    }

}
?>
