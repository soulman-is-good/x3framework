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
    public $_fields = array();
    public $relations = array();
    private $tables = array();
    private $position = 0;
    private $table = null;    

    public function __construct($action = null) {
        if ($this->tableName != null && !empty($this->_fields)) {
            $class = X3::app()->db->modelClass;
            foreach($this->_fields as $name=>$field){
                if(in_array('language',$field)){
                    foreach(X3::app()->languages as $lang){
                        $attr = "{$name}_$lang";
                        array_insert(array($attr=>$field),$this->_fields,$name);
                    }
                }
            }
            $this->addTrigger('beforeGet');
            $this->addTrigger('afterGet');
            $this->addTrigger('onDelete');
            $this->table = new $class($this->tableName,$this);
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
            $this->position = count($this->tables)-1;
            $this->table = $this->tables[$this->position];
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
    
    public function toArray() {
        $models = array();
        foreach ($this->tables as $table){
            $models[] = $table->getAttributes();
        }
        if(count($models)==1) 
            return array_shift($models);
        else
            return $models;
    }
    
    public function beforeValidate(){return true;}
    public function afterValidate(){return true;}
    
    public function beforeGet($table){return true;}
    public function afterGet($module){$module->table->setIsNewRecord(false);return true;}

    public function beforeSave(){return true;}
    public function afterSave($bNew=false){return true;}

    public function onDelete($tables,$condition){return true;}
    
    public function getTable() {
        return $this->table;
    }
    
    public function getDefaultScope() {
        return array();
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
    public static function getByPk($pk,$class=null,$asArray=false) {
        if($class==null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getByPk(\$pk,\$class=__CLASS__,\$asArray=false)");
        elseif($class==null)
            $class = get_called_class();
        $class = new $class();
        $pk = mysql_real_escape_string($pk);
        if($asArray)
            return $class->table->select('*')->where("`".$class->table->getPK()."`='$pk'")->asArray(true);
        else
            return $class->table->select('*')->where("`".$class->table->getPK()."`='$pk'")->asObject(true);
    }
    
    public static function deleteByPk($pk,$class=null) {
        if($class==null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию deleteByPk(\$pk,\$class=__CLASS__)");
        elseif($class==null)
            $class = get_called_class();
        $class = new $class();
        $pk = mysql_real_escape_string($pk);
        return $class->table->delete()->where("`".$class->table->getPK()."`='$pk'")->execute();
    }

    public static function get($params=array(),$single=false,$class=null,$asArray=false) {
        if($class==null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию get(\$params,\$single=false,\$class=__CLASS__,\$asArray=false)");
        elseif($class==null)
            $class = get_called_class();
        if(!is_array($params)) return NULL;
        $class = new $class();
        if(empty($params))
            $params = array();
        if($asArray)
            return $class->table->formQuery($params)->asArray($single);
        else
            return $class->table->formQuery($params)->asObject($single);
        //return  $class->table->select('*')->where($query)->asObject($single);
    }

    public static function delete($params=array(),$class=null) {
        if($class==null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию delete(\$params,\$class=__CLASS__)");
        elseif($class==null)
            $class = get_called_class();
        if(!is_array($params)) return NULL;
        $class = new $class();
        if(empty($params))
            $params = array();
        return $class->table->formQuery($params)->delete()->execute();
    }
    
    public function getRelation($relation,$asArray = false) {
        if(!isset(self::$relations[$relation]))
            throw new X3_Exception("Missing relation '$relation'.");
        $R = self::$relations[$relation];
        if($asArray)
            return $this->table->formQuery($params)->asArray($single);
        else
            return $class->table->formQuery($params)->asObject($single);        
    }
        
/**
 * Getters setters and other routine
 */

    public function __set($name, $value) {
        if (isset($this->_fields[$name])) {
            $call = '_set' . ucfirst($name);            
            if(method_exists($this, $call))
                $this->$call($value);
            else{
                $this->getTable()->setAttribute($name,$value);
            }
        }else
            parent::__set($name, $value);
    }

    public function __get($name) {
        if(isset($this->table[$name])){
            $call = '_get' . ucfirst($name);            
            if(method_exists($this, $call))
                return $this->$call();
            else{                
                return $this->getTable()->getAttribute($name);
            }
        }
        if(isset($this->_fields) && array_key_exists($name,$this->_fields))
            return $this->table[$name]=isset($this->_fields[$name]['default'])?$this->_fields[$name]['default']:"";
        if($this->table!= null && in_array($name, $this->table->getQueries())){
            return $this->table->getQueries($name);
        }else
            return parent::__get($name);
    }

    public function __call($name, $parameters) {
        if(method_exists($this->table, $name) || method_exists($this->table->getQueries($this->tableName)->getQueryClass(),$name))
            return call_user_func_array (array($this->table,$name), $parameters);

        return parent::__call($name, $parameters);
    }
    
    public static function __callStatic($name, $arguments) {
        if(PHP_VERSION_ID<50300)
            throw new X3_Exception('The version of PHP ('.PHP_VERSION.') on this server is not allow such interface. Use non static getRealtion method instead');
        if(strpos($name,'get') === 0){
            $_name = substr($name, 3);
            $class = get_called_class();        
            if(isset(X3_Module_Table::getInstance($class)->relations[$_name]))
                return self::getInstance($class)->getRelation($_name,!empty($arguments));
        }
        throw new X3_Exception("Method you are requesting does not exists '$name'");
        //return parent::__callStatic($name,$arguments);
    }
    
    public function __clone() {
        $clone = clone $this->table;
        $clone['id'] = null;
        $clone->setIsNewRecord(true);
        $this->push($clone);
        return $this;
    }
    
    public function __isset($name) {
        return $this->table!=null && !empty($this->tables);
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
            $this->tables[$offset]->acquire($value);
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
