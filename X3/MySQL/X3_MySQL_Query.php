<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_SQLQuery
 *
 * @author Soul_man
 */
class X3_MySQL_Query extends X3_MySQL_Command implements X3_Interface_Query {

    private $class = null;

    public function __construct($tableName, $classname = null) {
        if ($classname != null && $classname instanceof X3_Module)
            $this->class = $classname;
        else
            $this->class = new stdClass();
        $params = array('tables' => $tableName);
        parent::__construct($params);
    }

    public function select($query = '*') {
        $this->action = "SELECT";
        $this->select = $query;
        return $this;
    }

    public function limit($query = '-1') {
        if (!is_numeric($query))
            return $this;
        $this->limit = (int) $query;
        return $this;
    }

    public function offset($query = '1') {
        if (!is_numeric($query))
            return $this;
        $this->offset = (int) $query;
        return $this;
    }

    public function order($query = 'id') {
        $this->order = $query;
        return $this;
    }

    public function group($query = 'id') {
        $this->group = $query;
        return $this;
    }

    public function join($query = '') {
        if (empty($query))
            return $this;
        $m = $this->class;
        $fields = $m->_fields;
        $tbl = $m->tableName;
        if ($this->select == "$tbl.*" || $this->select == "`$tbl`.*") {
            $arr = array();
            foreach ($fields as $name => $field) {
                if(!in_array('unused',$field))
                    $arr[] = "`$tbl`.`$name`";
            }
            $this->select = implode(', ', $arr);
        }
        if (is_array($query))
            $query = implode(' ', $query);
        $this->join = $query;
        return $this;
    }

    public function page($query = '1') {
        if (!is_numeric($query))
            return $this;
        //TODO: class may by stdClass
        $m = $this->class;
        if ($this->limit <= 0)
            $this->limit = isset($m->limit) ? $m->limit : 10;
        $query = ((int) $query) - 1;
        if ($query < 0)
            $query = 0;
        $this->offset = $query * $this->limit;
        $m->setPage($query);
        return $this;
    }

    public function where($query = '1') {
        //TODO: check if wrong query;
        if (is_array($query)) {
            $query = array_shift($query);
        }
        if ($query == '')
            $query = '1';
        $this->condition = $query;
        return $this;
    }

    public function update($field, $value = null) {
        $this->action = "UPDATE";
        $m = $this->class;
        $class = $m;
        foreach ($class->_fields as $fld) {
            if(in_array('unused', $fld)) continue;
            if (isset($fld['ref'])) {
                if (isset($fld['ref']['onupdate'])) {
                    if (is_array($fld['ref']['onupdate'])) {
                        $function = array_slice($fld['ref']['onupdate'], 0, 2);
                        call_user_func_array($function, $params);
                    } elseif (is_callable($fld['ref']['onupdate'])) {
                        //PHP >= 5.3
                        $params = array('class' => $class);
                        call_user_func_array($fld['ref']['onupdate'], $params);
                    }else
                        switch (strtoupper($fld['ref']['onupdate'])) {
                            case "CASCADE":
                                die('В разработке...');
                                break;
                            case "RESTRICT":
                                die('В разрвботке...');
                                break;
                            case "SET DEFAULT":
                                //TODO: check if related table.field have default
                                break;
                            case "SET NULL":
                                //TODO: check if related table.field can be null
                                break;
                        }
                }
            }
        }
        $values = array();
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                $k = trim($k, "`");
                if(in_array('unused', $class->_fields[$k])) continue;
                $v = trim($v, "'");

                if (is_null($field[$k]))
                    $values[] = "`$k`=NULL";
                else{
                    $v = mysql_real_escape_string($v);
                    $values[] = "`$k`='$v'";
                }
            }
            $this->values = implode(', ', $values);
        }else {
            $field = trim($field, "`");
            $value = trim($value, "'");
            if ($value === null)
                $this->values = "`$field`=NULL";
            else{
                $value = mysql_real_escape_string($value);
                $this->values = "`$field`='$value'";
            }
        }
        return $this;
    }

    /**
     *
     * @param array $fields e.g. array('title','date','text')
     * @param array $values e.g. array('hello','9.07.2011','world')
     */
    public function insert($fields, $values = null) {
        $this->action = "INSERT";
        if (is_array($fields)) {
            if (!is_numeric(key($fields))) {
                $values = array_values($fields);
                $fields = array_keys($fields);
            }
            foreach ($fields as $i=>$k) {
                if(in_array('unused', $this->class->_fields[$k])){
                    unset($fields[$i]);
                    unset($values[$i]);
                }
            }
            $fields = "`" . implode("`, `", $fields) . "`";
            if (is_array($values[0])) {
                $_val = $values;
                $values = array();
                foreach ($_val as $v)
                    foreach ($v as $i => $l)
                        if (!is_null($l)){
                            $l = mysql_real_escape_string($l);
                            $v[$i] = "'$l'";
                        }
                $values[] = "(" . implode(", ", $v) . ")";
                $values = implode(',', $values);
            }else {
                foreach ($values as $i => $val) {
                    if (is_null($val))
                        $values[$i] = "NULL";
                    else{
                        $val = mysql_real_escape_string($val);
                        $values[$i] = "'$val'";
                    }
                }
                $values = "(" . implode(", ", $values) . ")";
            }
        }
        $this->select = $fields;
        $this->values = $values;
        return $this;
    }

    public function delete() {
        $this->action = "DELETE";
        $class = $this->class;
        foreach ($class->_fields as $field) {
            if(in_array('unused', $field)) continue;
            if (isset($field['ref'])) {
                if (isset($field['ref']['ondelete'])) {
                    if (is_array($field['ref']['ondelete'])) {
                        $function = array_slice($field['ref']['ondelete'], 0, 2);
                        call_user_func_array($function, $params);
                    } elseif (is_callable($field['ref']['ondelete'])) {
                        //PHP >= 5.3
                        $params = array('class' => $class);
                        call_user_func_array($field['ref']['ondelete'], $params);
                    }else
                        switch (strtoupper($field['ref']['ondelete'])) {
                            case "CASCADE":
                                die('ДОДЕЛАЙ MySQL QUERY!!!');
                                break;
                            case "RESTRICT":
                                die('ДОДЕЛАЙ MySQL QUERY!!!');
                                break;
                            case "SET DEFAULT":
                                //TODO: check if related table.field have default
                                break;
                            case "SET NULL":
                                //TODO: check if related table.field can be null
                                break;
                        }
                }
            }
        }
        return $this;
    }

    public function render($view, $data = null, $return = false) {
        /* if(is_array($data))
          $data=array_merge($data,array('models'=>self::$_models[$this->tableName]));
          else
          $data = array('models'=>self::$_models[$this->tableName]);
          parent::render($view, $data, $return); */
    }

    public function asArray($single = false) {
        //TODO: fetch as an array
        $sql = $this->buildSQL();
        if ($single) {
            return X3::app()->db->fetch($sql);
        }
        return X3::app()->db->fetchAll($sql);
    }

    public function asObject($single = false) {
        //TODO: fetch as object
        $models = $this->asArray($single);
        $module = $this->class;
        if ($single) {
            if (empty($models)) {
                return NULL;
            }
            $this->fire('beforeGet', array(&$models));
            $module->push(X3_Model::create($module)->acquire($models));
            $this->fire('afterGet', array(&$module));
            return $module;
        }
        if (empty($models))
            return $module;
        foreach ($models as $i => $model) {
            $class = X3::app()->db->modelClass;
            $tmp = new $class($module->tableName, $module);
            $this->fire('beforeGet', array(&$model));
            $tmp->acquire($model);
            $module->push($tmp);
            $this->fire('afterGet', array(&$module));
        }
        return $module;
    }

    public function asJSON($single = false) {
        $sql = $this->buildSQL();
        $result = $this->asArray($single);
        return json_encode($result);
    }

//WHAT FOR THIS???
    public function asNumeric() {
        $sql = $this->buildSQL();
        $cnt = X3::app()->db->fetch($sql);
        if (is_array($cnt))
            $cnt = (int) array_pop($cnt);
        else
            $cnt = 0;
        return $cnt;
    }

    public function count() {
        $this->select = 'COUNT(0) AS `cnt`';
        $sql = $this->buildSQL();
        $cnt = X3::app()->db->fetch($sql);
        //TODO: ...FROM `table` USE INDEX(`index_set`) WHERE..., where `index_set` might be a set of fields by one index to ccelerate
        return $cnt['cnt'];
    }

    public function execute() {
        $sql = $this->buildSQL();
        return X3::app()->db->query($sql);
    }

    public function formQuery($params = array()) {
        if (count($params) == 0) {
            $this->where('1');
            return $this;
        }
        if ($this->class != null && $this->class instanceof X3_Module_Table) {
            $table = $this->class->tableName;
            $this->select("{$table}.*");
        }
        $key = strtolower(key($params));
        if (!in_array($key, array('@join', '@condition', '@limit', '@order', '@offset','@select'))) {
            $tmp = $params;
            $params = array();
            $params['@condition'] = $tmp;
            unset($tmp);
        }
        foreach ($params as $key => $array) {
            $key = strtolower($key);
            switch ($key) {
                case "@select":
                    $this->select($array);
                    break;
                case "@join":
                    $this->join($this->formJoin($array));
                    break;
                case "@condition":
                    $this->where($this->formCondition($array));
                    break;
                case "@limit":
                    $this->limit($array);
                    break;
                case "@offset":
                    $this->offset($array);
                    break;
                case "@group":
                    $this->group($array);
                    break;
                case "@order":
                    if (is_array($array))
                        $this->order('`' . implode('`, `', $array) . '`');
                    else
                        $this->order($array);
                    break;
            }
        }
        return $this;
    }

    public function formCondition($params = array()) {
        $query = "";
        if (is_string($params))
            return $params;
        $dub = $params;
        for ($v = current($params); $v !== false; $v = next($params)) {
            $nv = next($dub);
            $k = key($params);
            $v = current($params);
            if (is_array($v) && is_integer($k)) {
                if ($nv !== false) {
                    if (is_array($nv))
                        $query .= "(" . $this->formCondition($v) . ") OR ";
                    else
                        $query .= "(" . $this->formCondition($v) . ") AND ";
                }else
                    $query .= "(" . $this->formCondition($v) . ")";
            }else {
                $oper = '=';
                if (is_numeric($k)) {
                    $k = $v;
                    $v = '1';
                }
                if (is_array($v)) {
                    $oper = key($v);
                    $v = current($v);
                }
                if($oper=='@@'){
                    $s = $v;
                }else{
                    if (strpos($k, '.') > 0)
                        $k = str_replace('.', '`.`', $k);
                    if ($v === 'NULL' || is_null($v) || $v == 'NOT NULL') {
                        if (is_null($v))
                            $v = 'NULL';
                        $s = "`$k` IS $v";
                    }elseif ($oper == '=')
                        $s = "`$k` $oper " . (is_string($v) ? "'$v'" : $v);
                    else
                        $s = "`$k` $oper $v";
                }
                if ($nv !== false)
                    $query .= "$s AND ";
                else
                    $query .= "$s";
            }
        }
        return $query;
    }

    public function formJoin($params = array()) {
        if (is_string($params))
            return $params;
        if (!is_array(current($params)))
            $paramz = array($params);
        else
            $paramz = $params;
        $query = "";
        foreach ($paramz as $key => $params)
            if (!empty($params)) {
                //$key = key($params);
                if (is_numeric($key)) {
                    $query = "INNER JOIN ";
                }else
                    $query = strtoupper($key) . " JOIN ";
                $query .= $params['table'];
                $on = '1';
                if (is_string($params['on'])) {
                    $on = $params['on'];
                } elseif (is_array($params['on'])) {
                    $on = $this->formCondition($params['on']);
                }
                $query .= " ON $on";
            }
        return $query;
    }

}

//array(array('id'=>'1','user'=>'LOLO',array(array('a'=>'1'),array('b'=>'1')),'status'),array('hello'=>'world','bye'=>'world'))
?>
