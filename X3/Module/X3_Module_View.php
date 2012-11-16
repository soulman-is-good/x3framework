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
class X3_Module_View extends X3_Module implements Iterator, ArrayAccess {

    /**
     * @var string MySQL VIEW name
     */
    public $viewName = null;

    /**
     *  
     * @var array view structure definition
     */
    public $fieldset = array();
    public $table = null;
    private $tables = array();
    public $view = null;

    public function __construct($action = null) {
        $qclass = X3::app()->db->queryClass;
        $this->view = new $qclass($this->viewName);
        if ($this->viewName != null && !empty($this->fieldset)) {
            if (($alter = $this->verifyView()) === 1)
                $target = "ALTER";
            elseif ($alter === 0)
                $target = "CREATE";
            else
                return parent::__construct($action);
            $select = array();
            $tables = array();
            foreach ($this->fieldset['select'] as $table => $fields) {
                if (is_integer($table) && is_string($fields)) {
                    $tables[] = $fields;
                    $table = $fields;
                    $fields = array();
                    $cols = X3::app()->db->getSchema('columns',$table);
                    foreach($cols as $col)
                        $fields[] = $col['Field'];
                } elseif (is_string($fields)){
                    $tables[] = $table;
                    $select[] = "`$table`.`$fields` AS `{$table}_{$fields}`";
                }
                if (is_string($table) && is_array($fields)){
                    $tables[] = $table;
                    foreach ($fields as $alias => $field) {
                        if(is_numeric($alias))
                            $alias = "{$table}_{$field}";
                        $select[] = "`$table`.`$field` AS `$alias`";
                    }
                }
            }
            //throw out join tables
            foreach($this->fieldset['join'] as $join){
                if(false!==($i=array_search($join['table'],$tables)))
                    unset($tables[$i]);                
            }
            if(empty($tables))
                throw new X3_Exception('CREATE VIEW must have correct table names',500);
            $query = new $qclass($tables);
            if (empty($select))
                $select = array('*');
            $query->select(implode(", ", $select));
            $fieldset = $this->fieldset;
            unset($fieldset['select']);
            $query->formQuery($fieldset);
            $query = $query->buildSql();
            $view = new $qclass($this->viewName);
            $view->action = "$target/VIEW";
            $view->as[$this->viewName] = $query;
            $view->execute();
        } else {
            if ($this->viewName == null)
                X3::log('WARNING: No tableName set in ' . get_class($this) . ' class instance of X3_Module_Table. You may instance X3_Module instead.');
            else
                X3::log('WARNING: Must specify fields array in ' . get_class($this));
        }        
        return parent::__construct($action);
    }

    public function push($table) {
        if ($table instanceof X3_Model) {
            //TODO: check if table already exist in the array (with help of primary key)
            $this->tables[] = $table;
        }
    }

    public function fieldNames() {
        return array();
    }

    public function fieldName($name) {
        $names = $this->fieldNames();
        if (isset($names[$name]))
            return $names[$name];
        else
            return ucfirst($name);
    }

    public function verifyView() {
        //TODO: on alter return ready sql string formed of fields
        $tables = X3::app()->db->getSchema('tables');
        if(!in_array($this->viewName, $tables))
            return 0;
        $cols = X3::app()->db->getSchema('columns',$this->viewName);
        $view_columns = array();
        foreach ($cols as $col) {
            $view_columns[] = $col['Field'];
        }
        foreach ($this->fieldset['select'] as $key => $value) {
            //if using table name without field definition;
            if (is_numeric($k) && is_string($value)) {
                $cols = X3::app()->db->getSchema('columns',$value);
                $k = $value;
                $value = array();
                foreach ($cols as $col) {
                    $field = $col['Field'];
                    $alias = "{$k}_{$field}";
                    //TODO: make cycle end correctly!
                    if (false===($i=array_search($alias, $view_columns)))
                        return 1;
                    else
                        unset($view_columns[$i]);
                    $value[$alias] = $field;
                }
            }elseif(is_string($k) && is_string($value)){
                $alias = "{$k}_$value";
                if (false===($i=array_search($alias, $view_columns)))
                    return 1;
                else
                    unset($view_columns[$i]);
            }elseif(is_array($value)){
                foreach($value as $alias=>$field){
                    if (false===($i=array_search($alias, $view_columns)))
                        return 1;
                    else
                        unset($view_columns[$i]);
                }
            }
        }
        return 2;
        /*$data = X3::app()->db->fetchAll("SHOW CREATE VIEW `$this->viewName`");
        if ($data == null)
            return 0;*/
    }

    public function beforeValidate() {
        return true;
    }

    public function afterValidate() {
        return true;
    }

    public function beforeSave() {
        return true;
    }

    public function afterSave() {
        return true;
    }

    public function getTable() {
        return $this->table;
    }

    public function addError($field, $error) {
        $this->getTable()->addError($field, $error);
    }

    public function save() {
        return $this->getTable()->save();
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
        if (isset($this->table[$name]))
            return $this->table[$name];
        if (isset($this->fieldset) && array_key_exists($name, $this->fieldset))
            return $this->table[$name] = isset($this->_fields[$name]['default']) ? $this->_fields[$name]['default'] : "";
        else
            return parent::__get($name);
    }

    function rewind() {
        $this->position = 0;
        $this->table = $this->tables[$this->position];
    }

    function current() {
        return $this->tables[$this->position];
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
        if (isset($this->tables[$this->position]))
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
