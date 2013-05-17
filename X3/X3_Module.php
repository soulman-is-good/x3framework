<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @since v0.1.0b
 * X3_Module class that extends X3_Model and X3_Controller by the chain
 * 
 * @since v1.0.0b
 * implementation where separated. And permanent links where created $controller and $table
 * 
 * @since v1.1.0b
 * X3_Module were divided in to X3_Module and X3_Module_Table to separate database logic from standart controller
 * 
 * @since v2.0
 * X3_Module is Mediator-like class to extend
 * 
 * @since v3.0
 * X3_Module will take controll of all things extending from X3_Controller
 *
 * @author Maxim <i@soulman.kz> Savin
 */
class X3_Module extends X3_Controller implements X3_Interface_Controller {

    /**
     * @since v 1.0
     * @deprecated since version 3.0
     * @var X3_Controller controller
     */
    private $controller = null;

    /**
     * Stores X3_Model instance if a linked data file exists
     * @var X3_Model 
     */
    private $model = null;

    /**
     * @var array static modules storage for quick access by getInstance method etc.
     */
    protected static $_modules = array();

    public function __construct($action = null) {
        if (NULL === $this->tableName) {
            $model_file = X3::app()->getPathFromAlias('@models') . DIRECTORY_SEPARATOR . get_class($this);
            if (!is_file($model_file . '.xml')) {
                if (!is_file($model_file . '.json')){
                    if (is_file($model_file . '.php'))
                        $this->model = new X3_Model($model_file . '.php');
                }else
                    $this->model = new X3_Model($model_file . '.json');
            }else {
                $this->model = new X3_Model($model_file . '.xml');
            }
        }else{
            $this->model = new X3_Model($this->tableName,$this->get_fields());
        }
        parent::__construct($action);
    }
    
    /**
     * Returns data schema. 
     * If empty array returns then we try geting schema from DB
     * 
     * @return array schema
     */
    public function get_fields(){
        return isset($this->model)?$this->model->getFields():array();
    }

    /**
     * Returns SQL table name or a collection name
     * @return string document|table name
     */
    public function getTableName() {
        if(!is_null($this->model)){
            return $this->model->getModelName();
        }
        return null;
    }
    
    public function getId(){
        static $class = false;
        return $class!=false?$class:$class=X3_String::create(get_class($this))->lcfirst();
    }

    /**
     * @return string module's UI title
     */
    public function moduleTitle() {
        return get_class($this);
    }

    /**
     * @param string $class name of a class [optional just for PHP version 5.3 and above]
     * @return X3_Module instance
     * @throws X3_Exception
     */
    public static function getInstance($class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getInstance(\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        if (isset(self::$_modules[$class])) {
            return self::$_modules[$class];
        }
        return self::$_modules[$class] = new $class();
    }

    /**
     * @param string $class name of a class [optional just for PHP version 5.3 and above]
     * @return X3_Module instance
     * @throws X3_Exception
     */
    public static function newInstance($class = null) {
        if ($class == null && PHP_VERSION_ID < 50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию newInstance(\$class=__CLASS__)");
        elseif ($class == null)
            $class = get_called_class();
        return self::$_modules[$class] = new $class();
    }

    /**
     * Magic
     */
    public function __call($name, $parameters) {
        if (method_exists($this->controller, $name))
            return call_user_func_array(array($this->controller, $name), $parameters);

        return parent::__call($name, $parameters);
    }

}

?>
