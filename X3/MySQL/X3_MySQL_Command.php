<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_MySQL_Command
 *
 * @author Soul_man
 */
class X3_MySQL_Command extends X3_Component {

    /**
     * @var string <p>Using for define what kind of a query this command will be.
     * Can be "SELECT", "UPDATE", "INSERT", "DROP" or "TRUNCATE"</p>
     */
    public $action = "SELECT";

    /**
     * @var string <p>This is a private variable for storing SQL query</p>
     */
    private $_sql = "";
    
    public $recreate = false;
    /**
     * @var string
     * <p>tables which will be used in query.</p>
     * can be defined in this way:
     * <ul>
     *      <li>`table1` or `table1` AS `t1` - for single table</li>
     *      <li>`table1`, `table2` AS `t2` - for multitable query</li>
     * </ul>
     */
    public $tables = "";
    public $as = array();

    /**
     * @var string <p>variable for SELECT action.
     * a set of columns one can need may be defined here
     * in such way: <b>`id`, `login`, `password`</b>
     * or for use in ALTER <b>`id` `ID`</b>
     * e.g.: <i>ALTER TABLE `users` CHANGE `id` `ID`</i></p>
     */
    public $select = "*";

    /**
     * @var string <p>for use in UPDATE or INSERT actions.
     * must be defined in array it such a way:</p>
     * <p>example columns (`id`,`title`,`date`)<br/>
     * <b>array</b>(<br/>
     * "1,'hello worldy!','22-05-2011'",<br/>
     * "2,'second record','23-05-2011'"<br/>
     * )
     * </p>
     */
    public $values = "";

    /**
     *
     * @var string use for ALTER only.
     */
    public $dataType = "";

    /**
     * @var string <p>WHERE condition such as it is</p>
     */
    public $condition = "1";

    /**
     * @var string <p>ORDER BY conditions</p>
     */
    public $order = "";

    /**
     * @var string <p>GROUP BY conditions</p>
     */
    public $group = "";

    /**
     * @var string <p>using for LIMIT query<br/> can be used in pair with $offset</p>
     */
    public $limit = -1;

    /**
     * @var string <p>using in LIMIT $offset,$limit</p>
     */
    public $offset = 0;
    public $join = "";
    
    public $defaultCharset = 'utf8';
    public $defaultCollation = 'utf8_general_ci';

    public function __construct($tableName,$params=array()) {
        $this->tables = $tableName;
        $this->formQuery($params);
    }
    
    public static function parseMySQLField($field){
        $dataType = trim(strtolower($field['Type']));
        $matches = array();
        $arg = false;
        $unsigned = false;
        $unique = false;
        $isnull = false;
        $autoinc = false;
        $default = false;
        if (preg_match('/\((.+?)\)/', $dataType, $matches) > 0) {
            $rep = array_shift($matches);
            $arg = array_shift($matches);
            $dataType = trim(str_replace($rep, "", $dataType));
        }
        if(strpos($dataType,' ')>0){
            $dataType = explode(" ",$dataType);
            if(count($dataType)>1 && array_pop($dataType) == 'unsigned')
                $unsigned = true;
            $dataType = array_shift($dataType);
        }
        switch ($dataType) {
            case "int":
            case "bigint":
            case "mediumint":
            case "smallint":
            case "tinyint":
                if($arg==false)
                    $arg = 11;
                $dataType = "integer[$arg]";
            break;
            case "serial":
                $dataType = "integer[11]";
                $unsigned = true;
                $unique = true;
                $autoinc = true;
            break;
            case "double":
            case "real":
            case "float":
            case "decimal":
                if($arg==false)
                    $arg = '7,2';
                $dataType = "$dataType[$arg]";
            break;
            case "bit":
            case "bool":
            case "boolean":
                $dataType = "boolean";
            break;
            case "date":
            case "datetime":
            case "timestamp":
            case "time":
            case "year":
            break;
            case "binary":
            case "char":
                $dataType = "string[255]";
            break;
            case "varbinary":
            case "varchar":
                if($arg == false)
                    $arg = 255;
                $dataType = "string[$arg]";
            break;
            case "tinytext":
            case "text":
            case "mediumtext":
            case "longtext":
            case "tinyblob":
            case "mediumblob":
            case "longblob":
            case "blob":
                $dataType = "text";
            break;
            case "enum":
            case "set":
                $dataType = "{$dataType}[$arg]";
            break;
        }
        $out_field = array();
        $out_field[0] = $dataType;
        $unique = $unique || $field['Key']=='UNI';
        $autoinc = $autoinc || $field['Extra']=='auto_increment';
        $index = $field['Key']=='MUL';
        $primary = $field['Key']=='PRI';
        if($unsigned)
            $out_field[] = "unsigned";
        if($unique)
            $out_field[] = "unique";
        elseif($index)
            $out_field[] = "index";
        if($primary)
            $out_field[] = "primary";
        if($autoinc)
            $out_field[] = "autoincrement";
        if(!is_null($field['Default']))
            $out_field['default'] = $field['Default'];
        if($field['Null'] == 'YES')
            $out_field['default'] = "NULL";
        return $out_field;
    }

    public static function parseField($field) {
        if (!is_array($field))
            throw new X3_Exception('$field variable must be an array', 500);
        $result = array(
            'Field' => '',
            'Type' => '',
            'Null' => 'NO',
            'Key' => '',
            'Default' => NULL,
            'Extra' => ''
        );
        $null = "NULL";
        $dataType = array_shift($field);
        $matches = array();
        $arg = false;
        if (preg_match('/\[(.+?)\]/', $dataType, $matches) > 0) {
            $rep = array_shift($matches);
            $arg = array_shift($matches);
            $dataType = str_replace($rep, "", $dataType);
        }
        if ($arg) {
            if (strpos($arg, '|') !== false) {
                $arg = array_pop(explode('|', $arg));
            }
        }
        if (strpos($dataType, '*') !== false) {
            $dataType = str_replace('*', '', $dataType);
            $null = "NOT NULL";
        }
        switch ($dataType) {
            case 'double':
            case 'decimal':
            case 'real':
            case 'float':
                if (!$arg)
                    $arg = "7,2";
                $mantisa = explode(',', $arg);
                if (sizeof($mantisa) == 1) {
                    $mantisa = 0;
                } else {
                    $mantisa = (int) array_pop($mantisa);
                    $dataType = "$dataType($arg)";
                }
                if (isset($field['default'])) {
                    $def = substr($field['default'], strpos($field['default'], '.') + 1);
                    if (strpos($field['default'], '.') == false || strlen($def) != $mantisa) {
                        $field['default'] = sprintf("%.0{$mantisa}f", $field['default']);
                    }
                }
                if (in_array('unsigned', $field))
                    $dataType .= " unsigned";
                break;
            case 'integer':
            case 'datetime':
                if (!$arg)
                    $arg = 11;
                $dataType = "int($arg)";
                if (in_array('unsigned', $field))
                    $dataType .= " unsigned";
                break;
            case 'email':
            case 'string':
            case 'file':
                if (!$arg)
                    $arg = 255;
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
                $dataType = "tinyint(1)"; //For other MySQL's
                break;
            case 'enum':
                //we must handle error if there is no enum argumnts defined
                if(!$arg){
                    throw new X3_Exception("Enum must take arguments like `enum['apple','banana','pineapple']` on ".get_class($this));
                }
                $arg = str_replace('"',"'",$arg);
                $dataType = "enum($arg)";
                break;
            default:
                $dataType = "int(11)";
                break;
        }
        $result['Type'] = $dataType;

        $result['Null'] = "NO";
        if (in_array('null', $field) || (isset($field['default']) && ($field['default'] == 'NULL' || is_null($field['default'])))) {
            unset($field['default']);
            $result['Null'] = "YES";
        }
        if (isset($field['default']) && $dataType != "text") {
            $result['Default'] = $field['default'];
        }
        if (in_array('autoincrement', $field) || in_array('auto_increment', $field))
            $result['Extra'] = "auto_increment";
        if (in_array('primary', $field)) {
            $result['Key'] = 'PRI';
        }
        if (in_array('index', $field)) {
            $result['Key'] = 'MUL';
        }
        if (in_array('unique', $field)) {
            $result['Key'] = 'UNI';
        }

        return $result;
    }
    
    /**
     *
     * @param array $data of column definition
     * @return string SQL string
     */
    public static function compile($data,$create = false) {
        if ($data['Null'] == 'YES')
            $null = "NULL";
        else
            $null = "NOT NULL";
        if (isset($data['Default']) && $data['Default'] != '')
            $default = "DEFAULT '" . $data['Default'] . "'";
        else
            $default = '';
        $key = "";
        if($create){
            $name = $data['Field'];
            switch ($data['Key']) {
                case "PRI":
                        $key = "PRIMARY KEY";
                    break;
                case "MUL":
                        //$key = "INDEX (`{$name}`)";
                    break;
                case "UNI":
                        //$key = "UNIQUE (`{$name}`)";
                    break;
                default:
                    break;
            }
        }
        $dataType = strtoupper($data['Type']);
        if(strpos($dataType,'CHAR')!==false || strpos($dataType,'TEXT')!==false || strpos($dataType,'ENUM')!==false || strpos($dataType,'SET')!==false)
                $dataType .= " CHARACTER SET '$this->defaultCharset' COLLATE '$this->defaultCollation'";
        $sql = $dataType . " " . $null . " " . $default . " " . strtoupper($data['Extra']) . " " . $key;
        return $sql;
    }
    
    public function create($fields=array()){
        $this->action = 'CREATE/TABLE';
        $this->select = "\r\n";
        foreach ($fields as $name=>$field){
            $result = self::parseField($field);
            $this->select .= "`$name` " . self::compile($result,true) . ",";
        }
        $this->select = trim($this->select,',');
        $this->select .= "\r\n";
    }
    
    public function buildSQL(){
        $tables = '';
        if(is_array($this->tables))
            $tables = '`'.implode('`, `', $this->tables).'`';
        else 
            $tables = "`$this->tables`";
        foreach($this->as as $table=>$as){
            $tables = str_replace("`$table`", "`$table` AS $as", $this->tables);
        }
        if(strpos($this->action, '/')!==false){
            $actions = explode('/',$this->action);
            $this->action = array_shift($actions);
        }
        //TODO apostrofy all fields - "`"
        switch ($this->action) {
            case "CREATE":
                if(!empty($actions))
                    $what = array_shift($actions);
                else
                    $what = "TABLE";
                $do = false;
                if(!empty($actions))
                    $do = array_shift($actions);
                
                if($this->recreate && !$do)
                    $do = "DROP IF EXISTS";
                else
                    $do = "";
                $attributes = '';
                if($what == 'TABLE')
                    $attributes = "($this->select)";
                $sql = "CREATE $what $do " . $tables . " $attributes";
                break;
            case "SELECT":
                $sql = "SELECT " . $this->select . " FROM " . $tables . " " . $this->join .
                    " WHERE " . $this->condition .
                    ((empty($this->group))?"":" GROUP BY ".$this->group).
                    ((empty($this->order))?"":" ORDER BY ".$this->order);
                if($this->limit > 0)
                    $sql .= " LIMIT " . (($this->offset>0)?"$this->offset,$this->limit":"$this->limit");
                break;
            case "UPDATE":
                $sql = "UPDATE $tables SET $this->values WHERE $this->condition";
                break;
            case "INSERT":
                $sql = "INSERT INTO $tables ($this->select) VALUES $this->values";
                break;
            case "DELETE":
                if($this->condition=="")
                        throw new X3_Exception ('Need a condition to delete a row from a table. Use TRUNCATE action to empty table.', DB_ERROR);
                $this->fire('onDelete',array($tables,$this->condition));
                $sql = "DELETE FROM $tables WHERE $this->condition";
                break;
            case "TRUNCATE":
                $sql = "TRUNCATE $tables";
                break;
            case "ALTER":
                if(is_array($actions)){
                    $what = array_shift($actions);
                    $do = array_shift($actions);
                }
                if(empty($what)) $what = 'TABLE';
                if(empty($do) && empty($what))
                    $do = 'SET';
                elseif(empty($do) && ($what=='SET' || $what == 'CHANGE' || $what == "ADD" || $what == 'REMOVE')){
                    $do = $what;
                    $what = "TABLE";
                }
                if((empty($this->select) || $this->select === "*") && $what == "TABLE") {
                    //TODO: more logging, tracing
                    throw new X3_Exception ("Fields to ALTER is not specified", 500);
                }elseif($what=="VIEW"){
                    $this->select = "";
                    $this->dataType = "";
                }
                $sql = "ALTER $what " . $tables . " $do $this->select $this->dataType";
                break;
            default:
                break;
        }
        return $this->_sql = $sql;
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
        $key = strtolower(key($params));
        if (!in_array($key, array('@join', '@condition', '@limit', '@order', '@offset','@select','@create'))) {
            $tmp = $params;
            $params = array();
            $params['@condition'] = $tmp;
            unset($tmp);
        }
        foreach ($params as $key => $array) {
            $key = strtolower($key);
            switch ($key) {
                case "@create":
                    $this->create($array);
                    break;
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
?>
