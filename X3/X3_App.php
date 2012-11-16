<?php

/**
 * X3_App
 *
 * @author soulman
 *
 * 21.11.2010 0:00:57
 */
class X3_App extends X3_Component {
    /**
     * Defines how much lines will be shown before and after broken line in error handlers
     */

    const ERROR_OUTPUT_RADIUS = 5;

    public $user = null;
    public $cs = null;
    private $request = null;
    private static $_components = array(
        'log' => array(
            'class' => 'X3_LogRouter'
        ),
        'db' => array(
            'class' => 'X3_MySQLConnection',
        ),
        'cache' => array(
            'class' => 'X3_Cache_File'
        ),
        'router' => array(
            'class' => 'X3_Router'
        ),
    );
    public $APPLICATION_DIR = 'application';
    public $HELPERS_DIR = 'helpers';
    public $MODULES_DIR = 'modules';
    public $VIEWS_DIR = 'views';
    public $LAYOUTS_DIR = 'layouts';
    public $MODELS_DIR = 'models';
    public $basePath = '';
    public $timezone = 'Asia/Almaty';
    public $locale = 'ru_RU';
    public $encoding = 'UTF-8';
    public $languages = array();

    /**
     * @var X3_Module class exemplar
     */
    private $module = null;
    
    public $global = array();

    /**
     * @var X3_Controller error handling Controller/Action variable
     */
    public $errorController = null;
    protected $errorHandler = null;
    protected $errorAction = 'actionIndex';

    /**
     *
     * @var string Default application name
     */
    public $name = 'X3 Web Application';

    public function __construct($config) {
        defined('IS_CONSOLE') or define('IS_CONSOLE', FALSE);
        X3::setApp($this);
        $module = 'site';
        $action = 'index';
        foreach ($config as $name => $value) {
            if (property_exists($this, $name))
                $this->$name = $value;
            else
                switch ($name) {
                    case 'uri':
                        $this->request = new X3_Request($value);
                        list($module, $action) = $this->request->resolveURI($_SERVER['REQUEST_URI']);
                        break;
                    default:
                        $this->global[$name] = $value;
                        break;
                }
        }
        mb_internal_encoding($this->encoding);
        setlocale(LC_ALL, "$this->locale." . str_replace('-', '', $this->encoding));
        setlocale(LC_NUMERIC, 'C');
        date_default_timezone_set($this->timezone);
        $this->initSystemHandlers();
        $this->cs = new X3_ClientScript();
        $this->user = new X3_User();
        if (isset($config['components']))
            $this->initComponents($config['components']);
        $this->fire('onStartApp', array(&$module, &$action));

        //$module = (string) X3_String::create($module)->lcfirst();
        $module = ucfirst($module);
        if (class_exists($module))
            $this->module = new $module($action);
        else {
            $c_path = $this->basePath . DIRECTORY_SEPARATOR .
                    $this->APPLICATION_DIR . DIRECTORY_SEPARATOR .
                    $this->MODULES_DIR . DIRECTORY_SEPARATOR .
                    $module . '.php'; //Path to the module
            if (!is_file($c_path))
                $c_path = $this->basePath . DIRECTORY_SEPARATOR .
                        $this->APPLICATION_DIR . DIRECTORY_SEPARATOR .
                        $this->MODULES_DIR . DIRECTORY_SEPARATOR .
                        $module . DIRECTORY_SEPARATOR . $module . '.php'; //Path to the module
            if (!is_file($c_path))
                if (X3_DEBUG)
                    throw new X3_Exception('No module "' . $module . '" found!', 500);
                else
                    throw new X3_404();
            require_once($c_path);
            $this->module = new $module($action);
        }
        return $this;
    }

    public function run() {
        if($this->module->controller != null)
            $this->module->controller->run();
        $this->fire('onEndApp');
    }

    private function initComponents($config) {
        $components = array();
        if (!is_array($config))
            $components = self::$_components;
        else
            $components = array_extend(self::$_components, $config);

        foreach ($components as $name => $component) {
            if (isset($component['class']) && class_exists($component['class'])) {
                $class = $component['class'];
                unset($component['class']);
                self::$_components[$name] = new $class($component);
                self::$_components[$name]->init();
            }
        }
    }

    public function getPathFromAlias($path = "",$relative = false) {
        if (strpos($path, ':') > 1) { //1 - is Windows style drive type (C:\)
            $dirs = explode(':', $path);
            foreach ($dirs as $i => $dir) {
                if ($dir == "APPLICATION_DIR" || $dir == "@app")
                    $dirs[$i] = $this->APPLICATION_DIR;
                elseif ($dir == "MODULES_DIR" || $dir == "@modules")
                    $dirs[$i] = $this->APPLICATION_DIR . DIRECTORY_SEPARATOR . $this->MODULES_DIR;
                elseif ($dir == "LAYOUTS_DIR" || $dir == "@layouts")
                    $dirs[$i] = $this->APPLICATION_DIR . DIRECTORY_SEPARATOR . $this->VIEWS_DIR . DIRECTORY_SEPARATOR . $this->LAYOUTS_DIR;
                elseif ($dir == "VIEWS_DIR" || $dir == "@views")
                    $dirs[$i] = $this->APPLICATION_DIR . DIRECTORY_SEPARATOR . $this->VIEWS_DIR;
                elseif ($dir == "HELPERS_DIR" || $dir == "@helpers")
                    $dirs[$i] = $this->APPLICATION_DIR . DIRECTORY_SEPARATOR . $this->HELPERS_DIR;
                elseif ($dir == "MODELS_DIR" || $dir == "@models")
                    $dirs[$i] = $this->APPLICATION_DIR . DIRECTORY_SEPARATOR . $this->MODELS_DIR;
                elseif ($dir == "")
                    unset($dirs[$i]);
                
            }
            $path = implode(DIRECTORY_SEPARATOR, $dirs);
        }elseif ($path == "")
            return $this->basePath;
        return ($relative?'':($this->basePath . DIRECTORY_SEPARATOR)) . $path;
    }
    
    public function hasComponent($name) {
        return array_key_exists($name, self::$_components);
    }

    public function __call($name, $parameters) {
        parent::__call($name, $parameters);
    }

    public function __get($name) {
        if ($name != 'handleError' && $name != 'handleException') {
            //return parent::__get($name);
            if (array_key_exists($name, self::$_components))
                return self::$_components[$name];
            elseif(isset($this->global[$name]))
                return $this->global[$name];
            else
                return $this->$name;
        }
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

    /*    public function getDb() {
      return self::$_db;
      } */

    public function getSession() {
        return X3_Session::getInstance();
    }

    public function getRequest() {
        return $this->request;
    }

    public function getModule() {
        return $this->module;
    }

    public function handleException($exception) {
        if (($trace = $this->getExactTrace($exception)) === null) {
            $fileName = $exception->getFile();
            $errorLine = $exception->getLine();
        } else {
            $fileName = '';
            $errorLine = '';
        }
        $data = array(
            'code' => ($exception instanceof X3_Exception) ? $exception->statusCode : 500,
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $fileName,
            'line' => $errorLine,
            'trace' => $exception->getTraceAsString(),
        );
        try{
            if ($this->errorHandler != null) {
                if (!headers_sent())
                    header("HTTP/1.1 404 Not Found ");
                $this->errorHandler = new $this->errorHandler($this->errorAction);
                $this->errorHandler->controller->run();
                exit(0);
            }else{
                if (!headers_sent())
                    header("HTTP/1.1 {$data['code']} " . get_class($exception));
                $this->displayException($exception);
            }
        }catch(Exception $e){
            echo "Error occured during handling exception!\n<br/>".$e->getMessage();
        }        
    }

    /**
     * Handles the PHP error.
     * @param CErrorEvent the PHP error event
     */
    public function handleError($code, $message, $file, $line) {
        $throw = true;
        $this->fire('onError',array($code,$message,$file,$line,&$throw));
        if(!$throw) return true;
        $trace = debug_backtrace();
        // skip the first 3 stacks as they do not tell the error position
        if (count($trace) > 3)
            $trace = array_slice($trace, 3);
        $traceString = '';
        foreach ($trace as $i => $t) {
            if (!isset($t['file']))
                $t['file'] = 'unknown';
            if (!isset($t['line']))
                $t['line'] = 0;
            if (!isset($t['function']))
                $t['function'] = 'unknown';
            $traceString.="#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object']))
                $traceString.=get_class($t['object']) . '->';
            $traceString.="{$t['function']}()\n";
        }

        $data = array(
            'code' => 500,
            'type' => 'PHP Error',
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'trace' => $traceString,
                //'source' => $this->getSourceLines($event->file, $event->line),
        );
        try{
            if ($this->errorHandler != null) {
                if (!headers_sent())
                    header("HTTP/1.0 404 Not Found");
                $this->errorHandler = new $this->errorHandler($this->errorAction);
                $this->errorHandler->run();
                exit(0);
            }else{
                if (!headers_sent())
                    header("HTTP/1.0 500 PHP Error");
                $this->displayError($code, $message, $file, $line);
            }
        }catch(Exception $e){
            echo "Error occured during handling an error!\n<br/>".$e->getMessage();
        }
    }

    /**
     * @param Exception the uncaught exception
     * @return array the exact trace where the problem occurs
     */
    protected function getExactTrace($exception) {
        $traces = $exception->getTrace();
        $result = null;
        foreach ($traces as $trace) {
            // property access exception
            if (isset($trace['function']) && ($trace['function'] === '__get' || $trace['function'] === '__set'))
                return $trace;
        }
        return null;
    }

    /**
     * Displays the captured PHP error.
     * This method displays the error in HTML when there is
     * no active error handler.
     * @param integer error code
     * @param string error message
     * @param string error file
     * @param string error line
     */
    public function displayError($code, $message, $file, $line) {
        if (X3_DEBUG) {
            echo "<h1>PHP Error [$code]</h1>\n";
            $trace = array_slice(debug_backtrace(), 2);
            array_unshift($trace, array('file' => $file, 'line' => $line, 'class' => $message));
            foreach ($trace as $debug) {
                $file = $debug['file'];
                $line = $debug['line'];
                $message = $debug['class'];
                $filename = pathinfo($file, PATHINFO_BASENAME);
                echo "<p>$message (<b>$filename</b>:$line)</p>\n";
                if (is_file($file)) {
                    $lines = file($file);
                    $total = count($lines);
                    $width = strlen((string) ($line + self::ERROR_OUTPUT_RADIUS)) * 10;
                    for ($i = $line - self::ERROR_OUTPUT_RADIUS; $i < $line + self::ERROR_OUTPUT_RADIUS; $i++) {
                        if (array_key_exists($i, $lines)) {
                            $lines[$i] = htmlspecialchars($lines[$i]);
                            echo "<div style=\"margin:0 0 0 40px;background-color:" . (($i == $line - 1) ? '#FCBFBF' : '#F28A8A') . ";border-left:2px solid #D11212\"><div style=\"float:left;width:{$width}px;background:#FFEDED;border-right:1px solid #D11212;color:#c1c1c1\">" . ($i + 1) . "</div><div>&nbsp;{$lines[$i]}</div></div>\n";
                        }
                    }
                }
            }
            exit;
        } else {
            echo "<h1>PHP Error [$code]</h1>\n";
            echo "<p>$message</p>\n";
        }
    }

    /**
     * Displays the uncaught PHP exception.
     * This method displays the exception in HTML when there is
     * no active error handler.
     * @param Exception the uncaught exception
     */
    public function displayException($exception) {
        $file = $exception->getFile();
        $line = $exception->getLine();
        if (X3_DEBUG) {
            $trace = $exception->getTrace();
            array_unshift($trace, array('file' => $file, 'line' => $line, 'class' => $exception->getMessage()));
            echo '<h1>' . get_class($exception) . "[" . $exception->getCode() . "]</h1>\n";
            foreach ($trace as $debug) {
                $file = $debug['file'];
                $line = $debug['line'];
                $message = $debug['class'];
                $filename = pathinfo($file, PATHINFO_BASENAME);
                echo "<p>$message (<b>$filename</b>:$line)</p>\n";
                if (is_file($file)) {
                    $lines = file($file);
                    $total = count($lines);
                    $width = strlen((string) ($line + self::ERROR_OUTPUT_RADIUS)) * 10;
                    for ($i = $line - self::ERROR_OUTPUT_RADIUS; $i < $line + self::ERROR_OUTPUT_RADIUS; $i++) {
                        if (array_key_exists($i, $lines)) {
                            $lines[$i] = htmlspecialchars($lines[$i]);
                            echo "<div style=\"margin:0 0 0 40px;background-color:" . (($i == $line - 1) ? '#FCBFBF' : '#F28A8A') . ";border-left:2px solid #D11212\"><div style=\"float:left;width:{$width}px;background:#FFEDED;border-right:1px solid #D11212;color:#c1c1c1\">" . ($i + 1) . "</div><div>&nbsp;{$lines[$i]}</div></div>\n";
                        }
                    }
                }
            }
            //echo '<pre>' . $exception->getTraceAsString() . '</pre>';
            exit;
        } else {
            echo '<h1>' . get_class($exception) . "</h1>\n";
            echo '<p>' . $exception->getMessage() . '</p>';
        }
    }

    /**
     * Initializes the class autoloader and error handlers.
     */
    protected function initSystemHandlers() {
        $ehandle = explode('/', $this->errorController);
        $econtroller = ucfirst($ehandle[0]);
        if (isset($ehandle[1]))
            $this->errorAction = (string) X3_String::create($ehandle[1])->lcfirst();
        $c_path = $this->basePath . DIRECTORY_SEPARATOR .
                $this->APPLICATION_DIR . DIRECTORY_SEPARATOR .
                $this->MODULES_DIR . DIRECTORY_SEPARATOR .
                $econtroller . '.php'; //Path to the controller
        if (!is_file($c_path))
            $this->errorHandler = null;
        else {
            $this->errorHandler = $econtroller;
            include($c_path);
        }
        if (X3_ENABLE_EXCEPTION_HANDLER)
            set_exception_handler(array($this, 'handleException'));
        if (X3_ENABLE_ERROR_HANDLER)
            set_error_handler(array($this, 'handleError'), error_reporting());
    }

}

?>
