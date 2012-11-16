<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Renderer
 *
 * @author Soul_man
 */
class X3_Renderer extends X3_Component {

    protected static $_renderer;
    public $module = null;
    public $layout = 'main';
    protected $data = array();
    protected $_process = array();

    public static function getInstance($renew = false) {
        if (!isset(self::$_renderer) || $renew) {
            self::$_renderer = new self;
        }
        return self::$_renderer;
    }

    public function __construct($class = null) {
        if (is_object($class))
            $this->module = $class;
        elseif (is_string($class) && class_exists($class))
            $this->module = new $class;
        else
            parent::__construct ($class);
    }

    public function addData($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_extend($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    public function removeData($key) {
        unset($this->data[$key]);
    }

    public function render($view, $data = null, $return = false, $processOutput = true) {
        if (($viewFile = $this->resolveViewFile($view)) !== false) {
            $output = $this->renderPartial($viewFile, $data, true);
        }else
            throw new X3_Exception(get_class($this) . ' не может найти представление "' . $view . '".');
        if (is_array($data)) {
            $data = array_merge(array('content' => $output), $data);
        } else {
            $data = array('content' => $output);
        }
        if (($layoutFile = $this->resolveLayoutFile($this->layout)) !== false) {
            $output = $this->renderFile($layoutFile, $data, true);
        }
        $this->fire('onRender', array(&$output));
        if ($return)
            return $output;
        else
            echo $output;
    }

    public function renderPartial($view, $data = null) {
        if (!is_file($view))
            $view = $this->resolveViewFile($view);
        $output = $this->renderFile($view, $data, true);
        $output = $this->processOutput($output);
        return $output;
    }

    public function renderFile($viewFile, $data = null, $return = false) {
        $content = $this->renderInternal($viewFile, $data, $return);
        return $content;
    }

    public function renderInternal($_viewFile_, $_data_ = null, $_return_ = false) {
        // we use special variable names here to avoid conflict when extracting data
        if (is_array($_data_)) {
            $_data_ = $_data_ + $this->data;
            extract($_data_, EXTR_PREFIX_SAME, 'data');
            //extract($this->data, EXTR_PREFIX_SAME, 'data');
        }else
            $data = $_data_;
        if ($_return_) {
            ob_start();
            ob_implicit_flush(false);
            require($_viewFile_);
            return ob_get_clean();
        }
        else
            require($_viewFile_);
    }

    public function resolveViewFile($viewFile) {
        $path = X3::app()->getPathFromAlias($viewFile,true);
        if (!is_file($path))
            $viewPath = X3::app()->basePath . DIRECTORY_SEPARATOR . X3::app()->APPLICATION_DIR
                    . DIRECTORY_SEPARATOR . X3::app()->VIEWS_DIR . DIRECTORY_SEPARATOR . $this->module->controller->id
                    . DIRECTORY_SEPARATOR . $path . '.php';
        else
            $viewPath = $path;
        if (!is_file($viewPath))
            throw new X3_Exception("Could not open view file '$viewPath'!", X3::FILE_IO_ERROR);
        return $viewPath;
    }

    public function resolveLayoutFile($viewFile) {
        $path = X3::app()->getPathFromAlias($viewFile,true);
        if (!is_file($path))
            $viewPath = X3::app()->basePath . DIRECTORY_SEPARATOR . X3::app()->APPLICATION_DIR
                    . DIRECTORY_SEPARATOR . X3::app()->VIEWS_DIR . DIRECTORY_SEPARATOR
                    . X3::app()->LAYOUTS_DIR . DIRECTORY_SEPARATOR . $path . '.php';
        else
            $viewPath = $path;
        if (!is_file($viewPath))
            return false;
        //throw new X3_Exception ("Could not open layout file '$viewPath'!", X3::FILE_IO_ERROR);
        return $viewPath;
    }

    public function setOutput($func) {
        $this->_process[] = $func;
    }

    private function processOutput($output) {
        foreach ($this->_process as $func){
            $output = call_user_func_array($func, array($output));
        }
        $this->_process = array();
        return $output;
    }

    public function __get($name) {
        if (isset($this->module->$name))
            return $this->module->$name;
        return parent::__get($name);
    }

    public function __call($name, $params) {
        if (method_exists($this->module, $name))
            return call_user_func_array(array($this->module, $name), $params);
        return parent::__call($name, $params);
    }

}

?>
