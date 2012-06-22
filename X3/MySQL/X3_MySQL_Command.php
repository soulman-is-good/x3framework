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

    public function __construct($params) {
        if(is_array($params)){
            foreach($params as $key=>$value) {
                if(property_exists($this, $key) && !empty($value)) {
                        $this->$key = $value;
                }
            }
        }elseif(is_string($params)) {
            $this->condition = $params;
        }
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
}
?>
