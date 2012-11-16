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
            foreach ($this->_fields as $name => $field) {
                if (in_array('language', $field)) {
                    foreach (X3::app()->languages as $lang) {
                        $attr = "{$name}_$lang";
                        $fld = $field;
                        $del = array_search('language', $fld);
                        unset($fld[$del]);
                        array_insert(array($attr => $fld), $this->_fields, $name);
                    }
                }
            }
            $this->addTrigger('beforeGet');
            $this->addTrigger('afterGet');
            $this->addTrigger('onDelete');
            $this->addTrigger('onValidate');
            $this->table = new $class($this->tableName, $this);
        } else {
            if ($this->tableName == null)
                X3::log('WARNING: No tableName set in ' . get_class($this) . ' class instance of X3_Module_Table. You may instance X3_Module instead.');
            else
                X3::log('WARNING: Must specify fields array in ' . get_class($this));
        }
        return parent::__construct($action);
    }

    public function push($table) {
        if ($table instanceof X3_Model) {
            //TODO: check if table already exist in the array (with help of primary key)
            $pk = $table->getPK();
            foreach ($this->tables as $i => &$tbl) {
                if ($tbl->tableName == $table->tableName && $tbl->getAttribute($pk) == $table->getAttribute($pk)) {
                    $this->position = $i;
                    $this->table = $tbl;
                    return;
                }
            }
            $this->tables[] = $table;
            $this->position = count($this->tables) - 1;
            $this->table = $this->tables[$this->position];
        }
    }

    public function count() {
        if (!empty($this->tables))
            return sizeof($this->tables);
        else
            return 0;
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

    public function getAttributes() {
        $attributes = array();
        foreach ($this->tables as $table) {
            $attributes[] = $table->attributes;
        }
        return $attributes;
    }

    public function toArray($current = false) {
        if ($current)
            return $this->getTable()->getAttributes();
        $models = array();
        foreach ($this->tables as $table) {
            $models[] = $table->getAttributes();
        }
        if (count($models) == 1)
            return array_shift($models);
        else
            return $models;
    }

    public function beforeValidate() {
        return true;
    }

    public function afterValidate() {
        return true;
    }

    public function beforeGet($table) {
        return true;
    }

    public function afterGet($module) {
        $module->getTable()->setIsNewRecord(false);
        return true;
    }

    public function beforeSave() {
        return true;
    }

    public function afterSave($bNew = false) {
        return true;
    }

    public function onDelete($tables, $condition) {
        return true;
    }

    public function getTable() {
        return $this->table;
    }

    public function formQuery($params = array()) {
        return $this->getTable()->getQuery()->formQuery($params);
    }

    public function getDefaultScope() {
        return array();
    }

    public function addError($field, $error) {
        $this->getTable()->addError($field, $error);
    }

    public function save() {
        return $this->getTable()->save();
    }

    private function formCond($params) {
        $result = array();
        foreach ($params as $name => $value) {
            if (is_numeric($name) && is_array($value)) {
                $result[] = array($this->formCond($param));
            } elseif (is_numeric($name) && is_string($value) && isset($this->_fields[$value])) {
                $result[] = "\$this->$value==1";
            } elseif (is_string($name) && is_string($value)) {
                $value = addcslashes($value, "'");
                $result[] = "\$this->$name=='$value'";
            } elseif (is_string($name) && is_array($value)) {
return array(false);//TODO: not tested!!!
                $o = key($value);
                $v = $value[$o];
                if (is_string($o)) {
                    $o = strtoupper($o);
                    if ($o == '<>') {
                        $o = '!=';
                    } elseif ($o == 'LIKE') {
                        if (stripos($v, "'%") === 0 && stripos($v, "%'") > 0)
                            $result[] = "stripos(\$this->$name,'" . trim($v, "'%") . "')!==false";
                        elseif (stripos($v, "'%") === false && stripos($v, "%'") > 0)
                            $result[] = "stripos(\$this->$name,'" . trim($v, "'%") . "')===0";
                        elseif (stripos($v, "'%") === 0 && stripos($v, "%'") === false) {
                            $v = trim($v, "'%");
                            $pos = "mb_strlen(\$this->$name,\$this->encoding) - mb_strlen('$v',\$this->encoding);";
                            $result[] = "stripos(\$this->$name,'$v')===$pos";
                        }else
                            $result[] = "stripos(\$this->$name,'" . trim($v, "'%") . "')!==false";
                    }elseif ($o == 'REGEXP') {
                        //TODO regexp
                    } elseif ($o == 'IN') {
                        //TODO IN (wohoo!) if SELECT then DIE!!))
                    } elseif ($o == 'BETWEEN') {
                        //TODO BETWEEN
                    }
                    if ($o == '!=') {
                        $result[] = "\$this->$name != $v";
                    }
                }
            } else {
                //todo:what else?
            }
        }
        return $result;
    }

    public function getExistent($param) {
        if (empty($param) || empty($this->tables))
            return NULL;
        $result = $this->formCond($param);
        $AND = "\$x=(" . implode(" && ", $result) . ");";
        $out = new self;
        foreach ($this as $model) {
            $x = false;
            eval($AND);
            if ($x)
                $out->push($model->table);
        }
        return $out->count()>0?$out:NULL;
        //TODO: further development
    }

    /**
     *  <b>WARNING</b>
     *  <i>For PHP lower than 5.3 you must implement this function</i>
     * @param <type> $pk
     * @param string $class Class name for static creation
     * @return X3_Module_Table current class
     */
    public static function getByPk($pk, $class = null, $asArray = false) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getByPk(\$pk,\$class=__CLASS__,\$asArray=false)");
        elseif ($class == null)
            $class = get_called_class();
        $class = self::newInstance($class);
        $pk = mysql_real_escape_string($pk);
        //if (NULL !== ($model = $class->getExistent(array((string) $class->getTable()->getPK() => $pk)))) {
            //if ($asArray)
                //return $model->toArray(true);
            //else
                //return $model;
        //}
        if ($asArray)
            return $class->getTable()->select('*')->where("`" . $class->getTable()->getPK() . "`='$pk'")->asArray(true);
        else
            return $class->getTable()->select('*')->where("`" . $class->getTable()->getPK() . "`='$pk'")->asObject(true);
    }
    
    /**
     *  <b>WARNING</b>
     *  <i>For PHP lower than 5.3 you must implement this function</i>
     * @param <type> $pk
     * @param string $class Class name for static creation
     * @return X3_Module_Table current class
     */
    public static function findByPk($pk, $class = null, $asArray = false) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getByPk(\$pk,\$class=__CLASS__,\$asArray=false)");
        elseif ($class == null)
            $class = get_called_class();
        $class = self::getInstance($class);
        $pk = mysql_real_escape_string($pk);
        if (NULL !== ($model = $class->getExistent(array((string) $class->getTable()->getPK() => $pk)))) {
            if ($asArray)
                return $model->toArray(true);
            else
                return $model;
        }else
            $class = self::newInstance ($class);
        if ($asArray)
            return $class->getTable()->select('*')->where("`" . $class->getTable()->getPK() . "`='$pk'")->asArray(true);
        else
            return $class->getTable()->select('*')->where("`" . $class->getTable()->getPK() . "`='$pk'")->asObject(true);
    }

    public static function deleteByPk($pk, $class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию deleteByPk(\$pk,\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        $class = self::newInstance($class);
        $pk = mysql_real_escape_string($pk);
        return $class->getTable()->delete()->where("`" . $class->getTable()->getPK() . "`='$pk'")->execute();
    }

    public static function get($params = array(), $single = false, $class = null, $asArray = false) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию get(\$params,\$single=false,\$class=__CLASS__,\$asArray=false)");
        elseif ($class == null)
            $class = get_called_class();
        if (!is_array($params))
            return NULL;
        $class = self::newInstance($class);
        if (empty($params))
            $params = array();
        //TODO: handle emty array parameters on Existent entities
        if(NULL!==($model = $class->getExistent($params))){
            if($asArray)
                return $model->toArray(true);
            else
                return $model;
        }
        if ($asArray)
            return $class->getTable()->formQuery($params)->asArray($single);
        else
            return $class->getTable()->formQuery($params)->asObject($single);
        //return  $class->getTable()->select('*')->where($query)->asObject($single);
    }

    public static function update($fields, $params = array(), $class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию update(\$fields,\$params=array(),\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        if (!is_array($params))
            return NULL;
        $class = self::newInstance($class);
        return $class->getTable()->formQuery($params)->update($fields)->execute();
    }

    public static function insert($fields, $params = array(), $returnStatus = false, $class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию update(\$fields,\$params=array(),\$returnStatus = false, \$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        if (!is_array($params))
            return NULL;
        $class = self::newInstance($class);
        if ($returnStatus)
            return $class->getTable()->formQuery($params)->insert($fields)->execute();
        else {
            if ($class->getTable()->formQuery($params)->insert($fields)->execute()) {
                if (isset($fields[0])) {
                    $tableClass = X3::app()->db->modelClass;
                    foreach ($fields as $field) {
                        if (is_array($field)) {
                            $table = new $tableClass($class->tableName, $class);
                            $table->acquire($field);
                            $class->push($table);
                        }
                    }
                } else {
                    $class->getTable()->acquire($fields);
                }
            } else {
                throw new X3_Exception("Ошибка при вставке данных!");
            }
            throw new X3_Exception("Ошибка при получении сущности!");
        }
    }

    public static function num_rows($params = array(), $class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию get(\$params=array(),\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        if (!is_array($params))
            $params = array();
        $class = self::newInstance($class);
        return X3::db()->count($class->getTable()->formQuery($params)->buildSQL());
    }

    public static function delete($params = array(), $class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию delete(\$params,\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        if (!is_array($params))
            return NULL;
        $class = self::newInstance($class);
        if (empty($params))
            $params = array();
        return $class->getTable()->formQuery($params)->delete()->execute();
    }

    public function getRelation($relation, $asArray = false) {
        if (!isset(self::$relations[$relation]))
            throw new X3_Exception("Missing relation '$relation'.");
        $R = self::$relations[$relation];
        if ($asArray)
            return $this->getTable()->formQuery($params)->asArray($single);
        else
            return $class->getTable()->formQuery($params)->asObject($single);
    }

    /**
     * Getters setters and other routine
     */
    public function __set($name, $value) {
        if (isset($this->_fields[$name])) {
            $call = '_set' . ucfirst($name);
            if (method_exists($this, $call))
                $this->$call($value);
            else {
                $this->getTable()->setAttribute($name, $value);
            }
        }else
            parent::__set($name, $value);
    }

    public function __get($name) {
        if (isset($this->table[$name])) {
            $call = '_get' . ucfirst($name);
            if (method_exists($this, $call))
                return $this->$call();
            else {
                return $this->getTable()->getAttribute($name);
            }
        }
        if (isset($this->_fields) && array_key_exists($name, $this->_fields))
            return $this->table[$name] = isset($this->_fields[$name]['default']) ? $this->_fields[$name]['default'] : "";
        if ($this->table != null && in_array($name, $this->getTable()->getQueries())) {
            return $this->getTable()->getQueries($name);
        }else
            return parent::__get($name);
    }

    public function __call($name, $parameters) {
        if (method_exists($this->table, $name) || method_exists(X3::db()->queryClass, $name))
            return call_user_func_array(array($this->table, $name), $parameters);

        return parent::__call($name, $parameters);
    }

    public static function __callStatic($name, $arguments) {
        if (PHP_VERSION_ID < 50300)
            throw new X3_Exception('The version of PHP (' . PHP_VERSION . ') on this server is not allow such interface. Use non static getRealtion method instead');
        if (strpos($name, 'get') === 0) {
            $_name = substr($name, 3);
            $class = get_called_class();
            if (isset(X3_Module_Table::newInstance($class)->relations[$_name]))
                return self::getInstance($class)->getRelation($_name, !empty($arguments));
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
        return $this->table != null && !empty($this->tables);
    }

    function rewind() {
        $this->position = 0;
        if (isset($this->tables[$this->position]))
            $this->table = $this->tables[$this->position];
    }

    function current() {
        return $this;
        if (isset($this->tables[$this->position]))
            return $this->tables[$this->position];
        else
            return $this->table;
    }

    function key() {
        return $this->position;
    }

    function next() {
        ++$this->position;
        if (isset($this->tables[$this->position]))
            $this->table = $this->tables[$this->position];
        return $this;
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
