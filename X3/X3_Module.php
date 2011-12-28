<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Module
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
        if($action!==null && $action!=''){
            $this->addTrigger('beforeAction');
            $this->addTrigger('afterAction');
            $this->controller = new X3_Controller($action,$this);
        }
        $this->template = new X3_Renderer($this);
        return $this;
    }

    public static function getInstance($class=__CLASS__) {
        if(!isset(self::$_modules[$class])){
            self::$_modules[$class] = new $class();
        }
        return self::$_modules[$class];
    }

    public static function newInstance($class=__CLASS__) {
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

    public function beforeAction() {
        return true;
    }

    public function afterAction() {
        return true;
    }
    
    public function setPage($page) {
        $this->_page = intval($page);
        X3_Session::getInstance()->write("X3-" . $this->id . "-" . $this->action . "-page",$page);
    }

    public function getPage() {
        $this->_page = X3_Session::getInstance()->read("X3-" . $this->id . "-" . $this->action . "-page");
        return $this->_page;
    }
    

}
?>
