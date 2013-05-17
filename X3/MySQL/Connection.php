<?php
namespace X3\MySQL {

    /**
     * DbConnection for MySQL
     *
     * @author Maxim <i@soulman.kz> Savin
     */
    class Connection extends \X3\DbConnection {

        protected $_db = NULL;
        protected $_query = NULL;
        protected $config = array(
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => null,
        );
        protected $sql = NULL;
        protected static $_schema = array();
        private $bTransaction = false;
        private $transaction = array();
        public $query_num = 0;
        public $queryClass = "X3_MySQL_Query";

        public function __construct($config = null) {
            if ($config != null && is_array($config))
                $this->config = $config;
        }

        public function connect($config = array()) {
            if ($this->_db !== NULL)
                return false;
            if (empty($config))
                $config = $this->config;
            $server = (isset($config['host'])) ? $config['host'] : 'localhost';
            $username = (isset($config['user'])) ? $config['user'] : 'root';
            $password = (isset($config['password'])) ? $config['password'] : '';
            $dbname = (isset($config['database'])) ? $config['database'] : 'information_schema';
            $this->_db = @mysql_connect($server, $username, $password);
            if ($this->_db === false) {
                throw new X3_Exception("Could not connect to mysql server", 500);
            }if (!mysql_select_db($dbname, $this->_db)) {
                throw new X3_Exception("Could not connect to database", 500);
            }
            self::$_schema[$dbname] = array('tables' => array());
            @mysql_query("SET NAMES utf8");
            @mysql_query("SET lc_time_names = 'ru_RU'");
            return true;
        }

        /**
         * @deprecated since version 3.0 use validateQuery instead
         * @see validateQuery
         */
        public function validateSQL($sql = null) {
            return $this->validateQuery($sql);
        }

        /**
         * @param string $sql sql query
         * @return string if escape success or null if doesn't
         */
        public function validateQuery($sql = null) {
            if (is_null($sql))
                return null;
            return mysql_real_escape_string($sql);
        }

        public function fetch($sql = null, $cache = false, $duaration = 84600) {
            if ($cache && X3::app()->hasComponent('cache') && false != ($data = X3::cache()->get(md5($sql))))
                return $data;
            $res = $this->query($sql);
            if (is_resource($res)) {
                $data = mysql_fetch_assoc($res);
                if ($cache && X3::app()->hasComponent('cache')) {
                    X3::cache()->set(md5($sql), $data, $duaration);
                }
                return $data;
            }
            return false;
        }

        public function fetchAll($sql = null, $cache = false, $duaration = 84600) {
            $this->connect();
            $data = array();
//            if ($cache && X3::app()->hasComponent('cache') && false != ($data = X3::cache()->get(md5($sql))))
//                return $data;
            if (!($query = $this->query($sql)))
                return NULL;
            $query = mysql_query($this->sql, $this->_db);
            if (!$query) {
                throw new X3_Exception($this->getErrors(), 500);
                return false;
            }
            while ($data[] = mysql_fetch_assoc($query)) {
                
            }
            array_pop($data);
            if ($cache && X3::app()->hasComponent('cache')) {
                X3::cache()->set(md5($sql), $data, $duaration);
            }
            return $data;
        }

        public function query($sql = null, $transact = true) {
            $this->connect();
            if (empty($sql) && empty($this->sql))
                throw new X3_Exception("Empty query", "500");
            if ($this->bTransaction && $transact && (strpos($sql, "INSERT") !== false || strpos($sql, "UPDATE") !== false || strpos($sql, "ALTER") !== false)) {
                $this->transaction[] = $sql;
                return true;
            }
            if (!empty($sql)) {
                $this->sql = $sql;
//                if (X3_DEBUG) {
//                    X3::log($sql, 'db');
//                }
                $this->validateSql();
            }
            $this->query_num++;
            //TODO:Handling mysql_unbuffered_query
            return mysql_query($this->sql, $this->_db);
        }

        public function fetchFields($entity) {
            $dbname = $this->config['database'];
            if (isset(self::$_schema[$dbname]['tables'][$entity]) && !empty(self::$_schema[$dbname]['tables'][$entity]))
                return self::$_schema[$dbname]['tables'][$entity];
            $query = new X3_MySQL_Query("SHOW COLUMNS FROM `$entity`", null, $this);
            if ($query->valid())
                foreach ($query as $i => $field) {
                    $fields[$field['Field']] = $field;
                }
            return self::$_schema[$dbname]['tables'][$entity] = $fields;
        }

        public function fetchEntities() {
            static $result_tables = array();
            if (!empty($result_tables))
                return $result_tables;
            $tables = new X3_MySQL_Query("SHOW TABLES");
            $dbname = $this->getDatabase();
            $key = "Tables_in_" . $dbname;
            foreach ($tables as $table) {
                if (!isset(self::$_schema[$dbname]['tables'][$table[$key]]))
                    self::$_schema[$dbname]['tables'][$table[$key]] = array();
                $result_tables[] = $table[$key];
            }
            return $result_tables;
        }

        public function entityExists($modelName) {
            $tables = $this->fetchEntities();
            $modelName = trim($modelName, "`'\" ");
            return in_array($modelName, $tables);
        }

        public function count($sql = NULL) {
            if ($sql == null && $this->sql != null)
                $sql = $this->sql;
            elseif ($sql == null)
                return null;
            $q = $this->query($sql, false);

            if (!is_resource($q))
                return false;
            return mysql_num_rows($q);
        }

        public function fetchAttributes($table) {
            return $this->fetchAll("SHOW COLUMNS FROM `{$table}`");
        }

        public function fetchRecords() {
            if (empty(self::$_schema['tables']))
                $this->updateSchema('tables');
            return $this->getSchema('tables');
        }

        public function getSchema($param = null, $param2 = null) {
            if ($param2 == null && empty(self::$_schema[$param])) {
                $this->updateSchema($param);
                return self::$_schema[$param];
            } elseif ($param2 == null && !empty(self::$_schema[$param])) {
                return self::$_schema[$param];
            } elseif ($param2 != null && empty(self::$_schema[$param][$param2])) {
                $this->updateSchema($param, $param2);
                return self::$_schema[$param][$param2];
            } elseif (!empty(self::$_schema[$param][$param2]))
                return self::$_schema[$param][$param2];
            return self::$_schema;
        }

        private function updateSchema($case, $param = null) {
            switch ($case) {
                case 'tables':
                    $tables = $this->fetchAll("SHOW TABLES");
                    foreach ($tables as $table)
                        self::$_schema['tables'][] = array_shift($table);
                    break;
                case 'columns':
                    if ($param == null) {
                        //TODO: SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='$this->config[\'database\']';                    
                    } else {
                        self::$_schema['columns'][$param] = $this->fetchAll("SHOW COLUMNS FROM `$param`");
                    }
                    break;
            }
        }

        public function startTransaction() {
            $this->bTransaction = true;
            $this->transaction = array();
        }

        public function pauseTransaction() {
            $this->bTransaction = false;
        }

        public function resumeTransaction() {
            $this->bTransaction = true;
        }

        public function isTransaction() {
            return $this->bTransaction;
        }

        public function addTransaction($sql) {
            $this->transaction[] = $sql;
        }

        public function getTransaction() {
            return $this->transaction;
        }

        public function commit() {
            if (empty($this->transaction)) {
                if (X3_DEBUG)
                    X3::log('Nothing to commit. Skiping.');
                return true;
            }
            $this->connect();
            $this->bTransaction = false;
            if (!mysql_query("START TRANSACTION", $this->_db)) {
                if (X3_DEBUG)
                    X3::log('Error in transaction: ' . $this->getErrors(), 'db');
                return false;
            }
            try {
                if (X3_DEBUG)
                    X3::log('Starting transaction', 'db');
                foreach ($this->transaction as $trans) {
                    mysql_query($trans, $this->_db);
                    if (X3_DEBUG)
                        X3::log("\t" . $trans, 'db');
                }
                if (X3_DEBUG)
                    X3::log('Going for commit', 'db');
                return $this->query("COMMIT");
            } catch (Exception $e) {
                throw new X3_Exception($e->getMessage(), '500');
                X3::log('Transaction failure: ' . $e->getMessage(), 'db');
                return false;
            }
        }

        public function rollback() {
            $this->bTransaction = false;
            return $this->query("ROLLBACK");
        }

        public function getErrors() {
            if (($msg = mysql_error($this->_db)) == "")
                $msg = false;
            return $msg;
        }

        public function getUser() {
            return $this->config['user'];
        }

        public function getPassword() {
            return $this->config['password'];
        }

        public function getDatabase() {
            return $this->config['database'];
        }

    }

}

?>
