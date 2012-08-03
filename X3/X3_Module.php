<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @since v0.1.0b
 * X3_Module class that extands X3_Model and X3_Controller by the chain
 * 
 * @since v1.0.0b
 * implementation where separated. And permanent links where created $controller and $table
 * 
 * @since v1.1.0b
 * X3_Module were divided in to X3_Module and X3_Module_Table to separate database logic from standart controller
 * 
 * @since v2.0
 * X3_Module must become a Mediator-like class to extend
 *
 * @author Soul_man
 */
class X3_Module extends X3_Component implements X3_Interface_Controller {

    /**
     * @since v 1.0
     * @var X3_Controller controller
     */
    private $controller = null;
    private $template = null;
    protected static $_modules = array();
    private $_page = 0;

    public function __construct($action=null) {
        $this->addTrigger('beforeAction',array(&$action));
        if($action!==null && $action!=''){
            $this->addTrigger('afterAction');
            $this->controller = new X3_Controller($action,$this);
        }
        $this->template = new X3_Renderer($this);
        return $this;
    }
    
    public function moduleTitle() {
        return get_class($this);
    }
    
    public static function getInstance($class=null) {
        if($class == null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию getInstance(\$class=__CLASS__)");
        else
        if($class==null)
            $class = get_called_class();
        if(!isset(self::$_modules[$class])){
            self::$_modules[$class] = new $class();
        }
        return self::$_modules[$class];
    }

    public static function newInstance($class=null) {
        if($class==null && PHP_VERSION_ID<50300)
            throw new X3_Exception("Для PHP<5.3 вам необходимо наследовать функцию newInstance(\$class=__CLASS__)");
        else
        if($class==null)
            $class = get_called_class();
        if(is_object($class))
            return $class;
        return self::$_modules[$class] = new $class();
    }
    /**
     * Returns array of definitions. Role->set of actions
     * @return array(
     *       'allow'=>array(
     *           '*'=>array('index','show'),
     *           'creator'=>array('insert'),
     *           'editor'=>array('insert','update'),
     *           'admin'=>array('insert','update','delete')
     *       ),
     *       'handle'=>'redirect:site/index'
     *   );
     */
    public function filter() {
        return array();
    }

    public function route() {
        return array();
    }

    public function cache() {
        return array();
    }

    public function getController() {
        return $this->controller;
    }

    public function getTemplate() {
        return $this->template;
    }

    public function getId() {
        return strtolower(get_class());
    }


    public function getErrors(){
        return $this->_errors;
    }


    public function init() {

    }

    /*public function beforeAction() {
        return true;
    }

    public function afterAction() {
        return true;
    }*/
    
    public function setPage($page) {
        $this->_page = intval($page);
        X3_Session::getInstance()->write("X3-" . $this->id . "-" . $this->action . "-page",$page);
    }

    public function getPage() {
        $this->_page = X3_Session::getInstance()->read("X3-" . $this->id . "-" . $this->action . "-page");
        return $this->_page;
    }

    /**
     * Magic
     */
    public function __call($name, $parameters) {
        if(method_exists($this->controller, $name))
            return call_user_func_array (array($this->controller,$name), $parameters);

        return parent::__call($name, $parameters);
    }

}
?>
