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
class X3_Model extends X3_Component implements ArrayAccess {

    private $class = null;
    private $model = null;

    public function __construct($tableName, $module=null) {
        $db = X3::app()->db;
        $this->class = $class = $db->modelClass;
        $this->model = new $class($tableName, $module);
        return $this->model;
    }
    
    public static function create($module) {
        $db = X3::app()->db;
        $tableName = "";
        if($module instanceof X3_Module_Table){
            $tableName = $module->tableName;
        }elseif(is_string($module)){
            $tableName = $module;
            $module = null;
        }else
            throw new X3_Exception("Can't create model with no table",500);
        $class = $db->modelClass;
        return new $class($tableName, $module);
    }

    public function __get($name) {
        if (property_exists($this->class, $name))
            return $this->model->$name;
        elseif (isset($this->model[$name]))
            return $this->model[$name];
        return parent::__get($name);
    }

    public function __set($name, $value) {
        if (property_exists($this->class, $name))
            $this->model->$name = $value;
        elseif (isset($this->model[$name]))
            $this->model[$name] = $value;
        parent::__set($name, $value);
    }

    public static function __callStatic($name, $arguments) {
        if (property_exists($this->class, $name))
            return $this->model->$name;
    }

    public function __call($name, $parameters) {
        if (method_exists($this->class, $name))
            return call_user_func_array(array($this->class, $name), $parameters);
        else
            return $this->model->__call($name, $parameters);
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->model->attributes);
    }

    public function offsetSet($offset, $value) {
        if (isset($this->model->module->_fields[$offset])) {
            $this->model->attributes[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        if (isset($this->model->module->_fields[$offset]))
            unset($this->model->attributes[$offset]);
    }

    public function offsetGet($offset) {
        return array_key_exists($offset,$this->model->attributes) ? $this->model->attributes[$offset] : null;
    }


}

?>
