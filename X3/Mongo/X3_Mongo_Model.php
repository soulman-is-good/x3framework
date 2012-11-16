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
class X3_MySQL_Model extends X3_Model implements ArrayAccess{
    
    public static $db;
    private $module = null;
    private static $_queries = array();
    public static $_tables = array();
    protected $_errors = array();

    private static $_columns = array();
    private $_dataTypes = array();
    private $tableName = null;
    protected $attributes = array(); //array stores table values
    public $_PK = null; //initializes as array e.g. ('id'=>1) or ('name'=>'about') with PRIMARY attribute
    private $_alter_stack = array();   

    public function __construct($tableName,$module=null) {
        self::$db = X3::app()->db;
        $this->tableName = $tableName;
        if(empty(self::$_tables)){
            $tables = self::$db->fetchRecords();
            foreach($tables as $table)
                self::$_tables[] = array_shift($table);
        }
        if($module!=null){
            //TODO: if $module != NULL!!! Must be either way. With or without $module!!!
            $this->module=$module;
            self::$_queries[$tableName] = new X3_Query($tableName,$module);
            if (!in_array($tableName, self::$_tables)) {
                if($this->createTable()){
                    self::$_tables[] = $tableName;                    
                }else
                    throw new X3_Exception ("$tableName creation failed! Try manualy...", '500');
            } else {
                if(empty(self::$_columns[$tableName]))
                    self::$_columns[$tableName] = self::$db->fetchAttributes($tableName);
                $this->verifyTable();
                $this->applyStack();
                $class = get_class($module);
                //$this->module->_modules[$class] = $this;
            }
        }
        //...till here. Maybe we can leave ONE non static variable for X3_Query???
        
    }

    public function getQueries($name=null) {
        if($name==null)
            return self::$_queries;
        return self::$_queries[$name];
    }

    /**
     * create uncreated table
     * @param boolean $recreate true if you want to recreate your table <br><b>WARNING!!! All data will be ERASED!</b>
     * @return boolean true if successfull creation took place, false otherwise
     */
    public function createTable($recreate = false) {
        $select = array();
        //TODO: must be without _fields to function along without X3_Module
        $this->_alter_stack[$this->tableName] = self::$_queries[$this->tableName];
        foreach ($this->module->_fields as $name => $field) {
            //storing special attributes for any cases
            if(in_array('unused',$field)) continue;
            $dataType = $this->parseDataType($field);
            $select[] = "`$name` ".$this->compile($dataType);
        }
        $this->_alter_stack[$this->tableName]->action = "CREATE/TABLE";
        $this->_alter_stack[$this->tableName]->select = implode(', ', $select);
        if($res = $this->applyStack()){
            self::$_columns[$this->tableName] = self::$db->fetchAttributes($this->tableName);
            $this->verifyTable();
        }
        return $res;
    }
    protected function applyStack() {
        //$sql="";
        //TODO: logging!!!!
        foreach($this->_alter_stack as $stack){
            $sql = $stack->buildSQL();
            X3::log("Applying SQL '$sql' for ".get_class($this));
            $db = X3::app()->db;
            $db->query($sql);
            if(($msg = $db->getErrors())!==false){
                throw new X3_Exception($msg, 500);
            }
        }
        return true;
            //$sql .= $stack->buildSQL() . ';'.chr(13);
        //echo "<textarea>".$sql."</textarea>";
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function getAttribute($name='') {
        if(isset($this[$name]))
        return $this->attributes[$name];
    }

    public function verifyTable() {
        //Go through defined fields and manage database
        if(!isset(self::$_columns[$this->tableName]))
            self::$_columns[$this->tableName] = self::$db->fetchAttributes($this->tableName);
        $columns = self::$_columns[$this->tableName];
        if(is_null($columns)) return false;
        foreach ($this->module->_fields as $name => $field) {
            $found = false;
            $change = false;
            $k = current($columns);
            $langs = in_array('language', $field);
            $this[$name]=(isset($field['default']) && strtolower($field['default'])!='null')?$field['default']:null;
            //storing special attributes for any cases
            if(in_array('unused',$field)) continue;
            //TODO: if $langs && !in_array($lang,$this->_columns)
            $dataType = array();
            $dataType = $this->parseDataType($field);
            $dataType['Field'] = $name;
            $this->_dataTypes[$name] = $dataType;
            if($dataType['Key']=='PRI') $this->_PK = $name;
            $modifyField = '';
            do {
                $k['Field'] = trim($k['Field'],'`');
                if ($k['Field'] == $name) {
                    $diff = array_diff_assoc($dataType, $k);
                    if(!empty($diff))
                            $change = true;
                    $found = true;
                    $modifyField = $k['Field'];
                    if(isset($diff['Key']) && $diff['Key']!=''){
                        if($k['Key']=='MUL' || $k['Key']=='UNI'){
                            $Query = new X3_Query($this->tableName,$this->module);
                            $Query->action = 'ALTER/TABLE/DROP';
                            $Query->select = "INDEX `{$name}`";
                            $Query->execute();
                        }
                        $what = (($diff['Key']=='MUL')?'INDEX':(($diff['Key']=='UNI')?'UNIQUE':false));
                        if($what!==false){
                            if(sizeof($diff)==1){
                                $found=true;
                                $change=false;
                            }
                            $Query = new X3_Query($this->tableName,$this->module);
                            $Query->action = 'ALTER/TABLE/ADD';
                            $Query->select = "$what (`{$name}`)";
                            $this->_alter_stack[$name.'_INDEX'] = $Query;
                        }
                    }
                    unset($columns[key($columns)]);
                }
                if (strcasecmp($k['Field'], $name) === 0 && !$found) $change = true;
            }while(($k = next($columns)) && !$found && !$change);
            reset($columns);

            //check if field is multilanguage
            if (!$found || $change) {
                $Query = new X3_Query($this->tableName,$this->module);
                $Query->action = 'ALTER/TABLE';
                $Query->tables = $this->tableName;
                $this->_alter_stack[$name] = $Query;
                $dataType = $this->compile($dataType);
                //If the field has same name but different case we do this
                if ($change) {
                    //TODO: ALTER TABLE `tableName` CHANGE `$k[Field]` `$name` `$dataType`;
                    $this->_alter_stack[$name]->action.= "/CHANGE";
                    $this->_alter_stack[$name]->select = "`{$modifyField}` `$name`";
                    $this->_alter_stack[$name]->dataType = $dataType;
                    //if($langs)
                } else { // !$found
                    //TODO: ALTER TABLE `tableName` ADD `$name` `$dataType`;
                    $this->_alter_stack[$name]->action .= "/ADD";
                    $this->_alter_stack[$name]->select = "`$name`";
                    $this->_alter_stack[$name]->dataType = $dataType;
                }
            }
        }
        foreach($columns as $column){
            $name = $column['Field'];
            $this->_alter_stack[$name] = new X3_MySQL_Command(array('action' => 'ALTER/TABLE', 'tables' => $this->tableName));
            $this->_alter_stack[$name]->action.= "/DROP";
            $this->_alter_stack[$name]->select = "`{$name}`";
        }
    }

    public function alterTable($tableName) {
        //TODO: Alter table
    }

    public function parseDataType($field) {
        if(!is_array($field)) throw new X3_Exception ('$field variable must be an array', 500);
        $result=array(
            'Field'=>'',
            'Type'=>'',
            'Null'=>'',
            'Key'=>'',
            'Default'=>'',
            'Extra'=>''
        );
        $null = "NULL";
        $dataType = array_shift($field);
        $matches=array();
        $arg = false;
        if(preg_match('/\[(.+?)\]/', $dataType, $matches)>0){
            $rep = array_shift($matches);
            $arg = array_shift($matches);
            $dataType = str_replace($rep, "", $dataType);
        }
        if($arg) {
            if(strpos($arg,'|')!==false){
                $arg = array_pop(explode('|',$arg));
            }
        }
        if(strpos($dataType, '*')!==false){
            $dataType = str_replace('*', '', $dataType);
            $null="NOT NULL";
        }
        switch ($dataType) {
            case 'float':
                if(!$arg) $arg = "7,2";
                $mantisa = explode(',',$arg);
                if(sizeof($mantisa)==1){
                    $mantisa = 0;
                    $dataType = "float";
                }else{
                    $mantisa = (int)array_pop($mantisa);
                    $dataType = "float($arg)";
                }
                if(isset($field['default'])){
                    $def = substr($field['default'],strpos($field['default'],'.')+1);
                    if(strpos($field['default'],'.')==false || strlen($def)!=$mantisa){
                        $field['default'] = sprintf("%.0{$mantisa}f",$field['default']);
                    }
                }
                if(in_array('unsigned',$field))
                    $dataType .= " unsigned";
                break;
            case 'integer':
            case 'datetime':
                if(!$arg) $arg = 11;
                $dataType = "int($arg)";
                if(in_array('unsigned',$field))
                    $dataType .= " unsigned";
                break;
            case 'email':
            case 'string':
            case 'file':
                if(!$arg) $arg = 255;
                $dataType = "varchar($arg)";
                break;
            case 'html':
            case 'content':
            case 'text':
                $dataType = "text";
                break;
            case 'fulltext':
                $dataType = "mediumtext";
                break;
            case 'boolean':
                $dataType = "tinyint(1)";//For other MySQL's
                break;

            default:
                $dataType = "int(11)";
                break;
        }
        $result['Type'] = $dataType;
        //TODO: UNIQUE AND INDEX indexes

        $result['Null'] = "NO";
        if(in_array('null',$field) || (isset($field['default']) && $field['default']=='NULL')){
            unset($field['default']);
            $result['Null'] = "YES";
        }
        if(isset($field['default'])){
            $result['Default'] = $field['default'];
        }
        if(in_array('auto_increment',$field))
            $result['Extra'] = "auto_increment";
        if(in_array('primary',$field)){
            $result['Key'] = 'PRI';
        }
        if(in_array('index',$field)){
            $result['Key'] = 'MUL';
        }
        if(in_array('unique',$field)){
            $result['Key'] = 'UNI';
        }

        return $result;
    }
    /**
     *
     * @param array $data of column definition
     * @return string SQL string
     */
    public function compile($data) {
        if($data['Null']=='YES')
            $null = "NULL";
        else
            $null = "NOT NULL";
        if(isset($data['Default']) && $data['Default']!='')
            $default = "DEFAULT '".$data['Default']."'";
        else $default = '';
        $key = "";
        switch ($data['Key']) {
            case "PRI":
                $key = "PRIMARY KEY";
                break;
            default:
                break;
        }
        $sql = strtoupper($data['Type'])." ".$null." ".$default." ".strtoupper($data['Extra'])." ".$key;
        return $sql;
    }

    public function acquire($array=array()) {
        foreach($array as $key=>$value){
            if(isset($this[$key])){
                $this[$key] = $value;
            }
        }
    }

    public function getTables() {
        return self::$_tables;
    }

    public function isEmpty() {
        //TODO: Normalize function
        $isset = false;
        foreach($this->attributes as  $attr){
            $isset = $isset || isset($attr);
        }
        return (empty($this->attributes) || !$isset);
    }

    public function addError($name,$message) {
        $this->_errors[$name][]=$message;
    }

    public function validate(){
        $this->module->beforeValidate();
        foreach ($this->module->_fields as $name => $field) {
            if(in_array('auto_increment', $field)) continue;
            $isset = isset($this[$name]);
            if(!in_array('null',$field) && !isset($field['default']) && (!$isset || ($isset && empty($this[$name])))){
                    $this->addError($name,(isset($field['errors']['notnull']))?$field['errors']['notnull']:X3::translate('Поле {attribute} не должно быть пустым', array('attribute'=>$this->module->fieldName($name))));
                    continue;
//                if(isset($field['default']) && ((isset($this[$name]) && $this[$name]=='')||(!isset($this[$name]))))
//                    $this[$name] = $field['default'];
//                elseif(!isset($this[$name]) || (isset($this[$name]) && $this[$name]=='')){
//                    $this->addError($name,(isset($field['errors']['notnull']))?$field['errors']['notnull']:X3::translate('Поле {attribute} не должно быть пустым', array('attribute'=>$this->fieldName($name))));
//                    continue;
//                }
            }

            $dataType = array_shift($field);
            $matches=array();
            $arg = false;
            if(preg_match('/\[(.+?)\]/', $dataType, $matches)>0){
                $rep = array_shift($matches);
                $arg = array_shift($matches);
                $dataType = str_replace($rep, "", $dataType);
            }
            if(strpos($dataType, '*')!==false){
                $dataType = str_replace('*', '', $dataType);
            }
            if($dataType=='datetime') $arg=11;
            if($dataType=='email') $arg=255;
            if($arg && $dataType!='float') {
                if(strpos($arg,'|')!==false){
                    $arg = explode('|',$arg);
                    $min = array_shift($arg);
                    $max = array_shift($arg);
                }else{
                    $max = $arg;
                    $min = 0;
                }
                if($isset && mb_strlen($this[$name],X3::app()->encoding)>$max)
                    $this->addError($name,(isset($field['errors']['length-max']))?$field['errors']['length-max']:X3::translate('Поле {attribute} не должно превышать {length} символов', array('attribute'=>$this->module->fieldName($name),'length'=>$max)));
                if($isset && isset($min) && mb_strlen($this[$name],X3::app()->encoding)<$min)
                    $this->addError($name,(isset($field['errors']['length-min']))?$field['errors']['length-min']:X3::translate('Поле {attribute} должно быть более {length} символов', array('attribute'=>$this->module->fieldName($name),'length'=>$min)));
            }
            $default = false;
            if(isset($field['default']))
                $default = $field['default'];
            if(in_array('null',$field)){
                $default = 'NULL';
            }
            switch ($dataType) {
                case 'file':
                    $src = $name."_source";
                    if(isset($this[$src]) && !$isset)
                        $this[$name] = $this[$src];
                    elseif(isset($this[$src]))
                        unset($this[$src]);
                break;
                case 'email':
                    if($isset)
                        if(preg_match("/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD", $this[$name])==0)
                            $this->addError($name,(isset($field['errors']['email']))?$field['errors']['email']:X3::translate('Поле {attribute} не является верным e-mail адресом', array('attribute'=>$this->module->fieldName($name))));
                break;
                case 'string':
                    if($isset && $this[$name]!==null)
                        $this[$name]=strip_tags($this[$name]);
                break;
                case 'text':
                    $this[$name] = mysql_real_escape_string($this[$name]);
                break;
                case 'content':
                    if($isset && $this[$name]!==null)
                    $this[$name]=strip_tags($this[$name]);
                break;
                case 'boolean':
                    if($isset && ($this[$name]==='on' || $this[$name]==='true' || $this[$name]==1))
                        $this[$name]='1';
                    else
                        $this[$name]='0';
                break;
                case 'datetime':
                case 'integer':
                    if($default!='NULL' && preg_match('/[^0-9]/', $this[$name])>0)
                        $this->addError($name,(isset($field['errors']['integer']))?$field['errors']['integer']:X3::translate('Поле {attribute} должно быть целым числом', array('attribute'=>$this->module->fieldName($name))));
                break;
                case 'float':
                    $this[$name] = str_replace(",",".",$this[$name]);
                    if($default!='NULL' && preg_match('/(^[-0-9]+?[.][0-9]+$)|(^[0-9]+$)/', $this[$name])==0)
                        $this->addError($name,(isset($field['errors']['float']))?$field['errors']['float']:X3::translate('Поле {attribute} должно быть вещественным числом', array('attribute'=>$this->module->fieldName($name))));
                break;
                default:
                break;
            }

            if(in_array('unique',$field)){
                $value = $this[$name];
                $id = $this[$this->_PK];
                $q = "";
                if(isset($id) && $id!=''){
                    $q = " AND `$this->_PK`<>'$id'";
                }
                $count = self::$db->fetch("SELECT COUNT(0) AS `cnt` FROM `$this->tableName` WHERE `$name`='$value' $q");
                if($count['cnt']>0){
                        $this->addError($name,(isset($field['errors']['unique']))?$field['errors']['unique']:X3::translate('Поле "{attribute}" уже используется с таким значением', array('attribute'=>$this->module->fieldName($name))));
                }
            }
        }
        $this->module->afterValidate();
        return empty($this->errors);
    }

    public function save() {
        $this->module->beforeSave();
        if(!$this->validate()) {
            return false;
        }
        if(isset($this->attributes['id']) && $this->attributes['id']>0){ 
            //TODO: primary key attribute orienatation
            //TODO: Can't UPDATE without primary key if no WHERE;
            //TODO: Optimization if module->tables != empty -> onEnd -> query all updates : else realtime
            $rez = $this->update($this->attributes)->where("`id`='".$this->attributes['id']."'")->execute();
            $this->module->afterSave();
            return $rez;
        }else{
            $rez = $this->insert($this->attributes)->execute();
            $this->attributes['id'] = mysql_insert_id();
            $this->module->afterSave(true);
            return $rez;
        }
        
    }

    public function getErrors() {
        return $this->_errors;
    }

    public function getModule() {
        return $this->module;
    }

/**
 * Getters setters and other routine
 */

    public function __get($name) {
        if(isset($this[$name]))
            return $this[$name];
        return parent::__get($name);
    }

    public function __set($name,$value) {
        if(isset($this[$name]))
            $this[$name] = $value;
        parent::__set($name,$value);
    }
    public function offsetExists($offset) {
        return array_key_exists($offset, $this->attributes);
    }

    public function offsetSet($offset, $value) {
        if (isset($this->module->_fields[$offset])) {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        if (isset($this->module->_fields[$offset]))
            unset($this->attributes[$offset]);
    }

    public function offsetGet($offset) {
        return array_key_exists($offset,$this->attributes) ? $this->attributes[$offset] : null;
    }

    public function __call($name, $parameters) {
        if(isset($this->module->_fields[$name])){
            if(!isset($this->module->_fields[$name]['ref']))
                throw new X3_Exception("Field '$name' must have reference to be called");
            $class = $this->module->_fields[$name]['ref'][0];
            $key = $this->module->_fields[$name]['ref'][1];
            $class = new $class();
            return $class->getTable()->select('*')->where("$key={$this->$name}")->asObject(true);
        }
        $obj = self::$_queries[$this->tableName];
        return call_user_func_array(array($obj,$name), $parameters);
        if(method_exists($obj, $name)){
        /* Parent fieldset
        }elseif(isset($this->module->_fields[$name]) && isset($this->module->_fields[$name]['parent'])){
            if(empty($this->attributes[$name])) $this->attributes[$name] = null;
            $prop = $this->module->_fields[$name]['parent'];
            $class = explode('.',$prop['link']);
            $attr = array_pop($class);
            $class = array_pop($class);
            $class = new $class;
            if(!isset($prop['select'])) $prop['select']='*';
            if(!isset($prop['condition'])) $prop['condition']='1';
            return $class->select($prop['select'])->where("({$prop['condition']}) AND `$attr`='{$this->$name}'");*/
        }else
            return parent::__call($name, $parameters);
    }
}
?>
