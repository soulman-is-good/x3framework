<?php
/**
 * X3_Model
 *
 * @since v3.0
 * This is an ORM data model
 * 
 * @author Maxim <i@soulman.kz> Savin
 */
class X3_Model extends X3_Component implements ArrayAccess {

    protected $class = null;
    protected $model = null;
    /**
     * @var array list of model's fields
     */
    protected $fields = array();
    protected $validators = array();
    protected $modelName = false;

    /**
     * @param string $tableName could be either a path to a data file or table|collection name
     * @param array $fields data structure as follows:
     * <pre>
     * array(
     *  "fieldName"=>array("dataType","options..."),
     *  ...
     * )
     * </pre>
     */
    public function __construct($tableName,$fields = array()) {
        $db = X3::db();
        $this->class = 'stdClass';
        //If there is model file specified we'll parse it, else we'll get existing model from DB
        if((!is_array($fields) || empty($fields)) && is_file($tableName)){
            $fields = $this->parseFile($tableName);
            if(X3_DEBUG){
                if(!$db->entityExists($this->modelName)){
                    $add = new X3_MySQL_Command($this->modelName,array('@create'=>$fields));
                    if($add->execute())
                        X3::log("Creating entity `$this->modelName` succeeded.");
                    else
                        X3::log("Creating entity `$this->modelName` failed. " . $db->getErrors());
                }else
                    $this->compareStructure($fields);
            }
        }else{
            $this->modelName = $tableName;
            if(!is_array($fields) || empty($fields))
                $fields = $this->fetchFields($tableName);
            else if(X3_DEBUG){
                $this->compareStructure($fields);
            }
        }
        $this->fields = $fields;
    }
    
    /**
     * Parses data file
     * now applies to xml, json, php, inc
     * 
     * @param string $file path to file
     * @return array data structure
     * @throws X3_Exception
     */
    protected function parseFile($file) {
        $ext = pathinfo($file,PATHINFO_EXTENSION);
        $data = null;
        switch($ext){
            case 'xml':
                $_data = X3_File_XML::fromFile($file,true);
                var_dump($_data);exit;
                $data['name'] = (string)$_data->name;
                foreach($_data->fields as $name=>$field){
                    $data[$name][0] = $field->type;
                    //foreach ($field)
                }
                var_dump($data);exit;
            break;
            case 'json':
                $data = X3_File_JSON::fromFile($file,true);
            break;
            case 'inc':
            case 'php':
                $data = include($file);
            break;
            default:
                throw new X3_Exception("Data model file '*.$ext' not supported yet",500);
        }
        $data = $this->normalizeDataStruct($data);
        return $data;
    }
    
    /**
     * Normalizes data structure array if there any errors or mistakes
     * 
     * @param array $data data struct
     * @return array normalized data struct
     */
    protected function normalizeDataStruct($data){
        $this->modelName = $data['name'];
        $this->validators = isset($data['validators'])?$data['validators']:array();
        return $data['fields'];
    }
    
    /**
     * 
     * @param array $fields data structure that SHOULD be!
     * @return array data structure
     */
    protected function compareStructure($fields){
        //let's get the current data structure
        $data = X3::db()->fetchFields($this->modelName);
        //Nothing to compare to. Return 
        if(empty($fields))
            return false;
        //see if we have a primary key
        $hasPK = array_reduce($fields, create_function('&$res,$item', 'return $res=$res || in_array("primary",$item);'), FALSE);
        X3::db()->startTransaction();
        $pks = array();
        foreach($fields as $name=>$field){
            $_field = X3_MySQL_Command::parseField($field);
            $_field['Field'] = $name;
            if(isset($data[$name])){
                $test = $data[$name];
                if($test['Key']=='PRI' && $_field['Key']!='PRI'){
                    if(X3::db()->addTransaction("ALTER TABLE `$this->modelName` DROP PRIMARY KEY"))
                        $hasPK = false;
                    else
                        throw new X3_Exception("Failed to drop primery key at `$this->modelName`.`$name`",500);
                }
                if($test['Key']!='PRI' && $_field['Key']=='PRI'){
                    if($hasPK)
                        X3::db()->addTransaction("ALTER TABLE `$this->modelName` DROP PRIMARY KEY");
                    $pks[] = $name;
                }
                $diff = array_diff($_field, $test);
                if(!empty($diff)){
                    $key = isset($diff['Key'])?$diff['Key']:false;
                    unset($diff['Key']);
                    if(!empty($diff))
                        X3::db()->addTransaction("ALTER TABLE `$this->modelName` MODIFY COLUMN `$name` ".X3_MySQL_Command::compile($_field));
                    if($key && $key!='PRI'){
                        if($key == 'MUL' || $key == 'UNI'){
                            var_dump($field);exit;
                            $idx = "ADD " . ($key == 'MUL'?'INDEX':'UNIQUE') . " `{$name}_index` ";
                            if(isset($field['index_type'])){
                                $idx .= "USING " . $field['index_type'];
                            }
                            $idx .= "(`$name`";
                            if(isset($field['index_with'])){
                                if(is_string($field['index_with'])){
                                    $idx .= ',`'.$field['index_with'].'`';
                                }else if(is_array($field['index_with'])){
                                    foreach ($field['index_with'] as $val) {
                                        $idx .= ",`$val`";
                                    }
                                }
                            }
                            $idx .=  ")";
                            if(isset($field['index_option'])){
                                $idx .= $field['index_option'];
                            }
                            X3::db()->addTransaction("ALTER TABLE `$this->modelName` $idx");
                        }
                    }
                }
                unset($data[$name]);
            }else{
                X3::db()->addTransaction("ALTER TABLE `$this->modelName` ADD COLUMN `$name` ".X3_MySQL_Command::compile($_field));
            }
        }
        if(!empty($pks))
            X3::db()->addTransaction("ALTER TABLE `$this->modelName` ADD PRIMARY KEY (`".  implode(`,`, $pks)."`)");
        if(count($data)>0){
            foreach($data as $field){
                //X3::db()->addTransaction("ALTER TABLE `$this->modelName` DROP COLUMN `$name`");
            }
        }
        if(!X3::db()->commit()){
            X3::log("compareStructure transaction failed! ".X3::db()->getErrors());
            X3::db()->rollback();
        }
        return false;
    }
    
    public function getFields(){
        return $this->fields;
    }
    
    /**
     * Retreaves data structure from database.
     * 
     * @return array data structure from database
     */
    protected function fetchFields(){
        $fields = X3::db()->fetchFields($this->modelName);
        $_res = array();
        foreach($fields as $name=>$field){
            $dataType = X3_MySQL_Command::parseMySQLField($field);
            $_res[$name] = $dataType;
        }
        return $_res;
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
            return parent::__call($name, $parameters);
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
