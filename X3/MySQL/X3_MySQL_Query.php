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

    public function __construct($tableName,$classname='stdClass') {
        $this->class = $classname;
        $params = array('tables'=>$tableName);
        parent::__construct($params);
    }

    public function select($query='*') {
        $this->action = "SELECT";        
        $this->select = $query;
        return $this;
    }

    public function limit($query='-1') {
        if(!is_numeric($query)) return $this;
        $this->limit = (int)$query;
        return $this;
    }

    public function offset($query='1') {
        if(!is_numeric($query)) return $this;
        $this->offset = (int)$query;
        return $this;
    }

    public function order($query='id') {
        $this->order = $query;
        return $this;
    }

    public function group($query='id') {
        $this->group = $query;
        return $this;
    }

    public function page($query='1') {
        if(!is_numeric($query)) return $this;
        //TODO: class may by stdClass
        $m = $this->class;
        if($this->limit<=0) $this->limit=isset(X3_Module::getInstance($m)->limit)?X3_Module::getInstance($m)->limit:10;
        $query = ((int)$query)-1;
        if($query<0) $query=0;
        $this->offset = $query*$this->limit;
        X3_Module::getInstance($m)->setPage($query);
        return $this;
    }

    public function where($query='1') {
        //TODO: check if wrong query;
        if(is_array($query)){
            $query = array_shift($query);
        }
        if($query == '') $query = '1';
        $this->condition = $query;
        return $this;
    }

    public function update($field,$value = null) {
        $this->action = "UPDATE";
        $values = array();
        if(is_array($field)){
            foreach($field as $k=>$v){
                $k = trim($k,"`");
                $v = trim($v,"'");
                
                if($field[$k]===null)
                    $values[] = "`$k`=NULL";
                else
                    $values[] = "`$k`='$v'";
            }
            $this->values = implode(', ',$values);
        }else{
            $field = trim($field,"`");
            $value = trim($value,"'");
            if($value===null)
                $this->values = "`$field`=NULL";
            else
                $this->values = "`$field`='$value'";
        }
        return $this;
    }

    /**
     *
     * @param array $fields e.g. array('title','date','text')
     * @param array $values e.g. array('hello','9.07.2011','world')
     */
    public function insert($fields,$values=null) {
        $this->action = "INSERT";
        if(is_array($fields)){
            if(!is_numeric(key($fields))){
                $values = array_values($fields);
                $fields = array_keys($fields);

            }
            $fields = "`".implode("`, `",$fields)."`";
            if(is_array($values[0])){
                $_val = $values;
                $values=array();
                foreach($_val as $v)
                    $values[] = "('".implode("', '",$v)."')";
                $values = implode(',',$values);
            }else{
                foreach ($values as $i=>$val){
                    if($val===null)
                        $values[$i] = "NULL";
                    else
                        $values[$i] = "'$val'";
                }
                $values = "(".implode(", ",$values).")";
            }
        }
        $this->select = $fields;
        $this->values = $values;
        return $this;
    }

    public function delete() {
        $this->action = "DELETE";
        return $this;
    }

    public function  render($view, $data = null, $return = false) {
        if(is_array($data))
            $data=array_merge($data,array('models'=>self::$_models[$this->tableName]));
        else
            $data = array('models'=>self::$_models[$this->tableName]);
        parent::render($view, $data, $return);
    }

    public function asArray($single = false) {
        //TODO: fetch as an array
        $sql = $this->buildSQL();
        if($single) {
            return X3::app()->db->fetch($sql);
        }
        return X3::app()->db->fetchAll($sql);
    }

    public function asObject($single = false) {
        //TODO: fetch as object
        $models = $this->asArray($single);
        $module = $this->class;
        if($single) {
            if(empty($models)) {
                return NULL;
            }
            $module = X3_Module::getInstance($module);
            $module->table->accuire($models);
            return $module;
        }
        if(empty($models)) return array();
        $module = X3_Module::getInstance($module);
        foreach ($models as $i=>$model) {
            $tmp = new X3_Model($module->tableName,$module);
            $tmp->accuire($model);
            $module->push($tmp);
            if($i==0)
                $module->table->accuire($tmp);
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
        if(is_array($cnt))
            $cnt = (int)array_pop($cnt);
        else $cnt = 0;
        return $cnt;
    }

    public function count() {
        $this->select = 'COUNT(0) AS `cnt`';
        $sql = $this->buildSQL();
        $cnt = X3::app()->db->fetch($sql);
        //TODO: ...FROM `table` USE INDEX(`index_set`) WHERE..., where `index_set` might be a set of fields by one index to accelerate
        return $cnt['cnt'];
    }

    public function execute() {
        $sql = $this->buildSQL();
        return X3::app()->db->query($sql);
    }

    public function formQuery($params=array()){
        $query = "";
        $dub = $params;
        for($v = current($params);$v!==false;$v = next($params)){
            $nv = next($dub);
            $k = key($params);
            $v = current($params);
            if(is_array($v)){
                if($nv!==false){
                    if(is_array($nv))
                        $query .= "(" . $this->formQuery($v) . ") OR ";
                    else
                        $query .= "(" . $this->formQuery($v) . ") AND ";
                }else
                    $query .= "(" . $this->formQuery($v) . ")";
            }else{
                if(is_numeric($k)){
                    $k = $v;
                    $v = '1';
                }
                if($nv!=false)
                    $query .= "`$k`='$v' AND ";
                else
                    $query .= "`$k`='$v'";
            }
        }
        return $query;
    }
}
array(array('id'=>'1','user'=>'LOLO',array(array('a'=>'1'),array('b'=>'1')),'status'),array('hello'=>'world','bye'=>'world'))
?>
