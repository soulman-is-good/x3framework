<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3 Framework
 *
 * PHP Version >= 5.2.0
 * @author soulman, darell
 */

/**
 * defining Debug constant
 *     [HTTP_X_FLASH_VERSION] => 10,0,2,54
    [HTTP_USER_AGENT] => Shockwave Flash
 */
//TODO: playout $_SERVER for console app
defined('X3_DEBUG') or define('X3_DEBUG', FALSE);
defined('X3_ENABLE_EXCEPTION_HANDLER') or define('X3_ENABLE_EXCEPTION_HANDLER', TRUE);
defined('X3_ENABLE_ERROR_HANDLER') or define('X3_ENABLE_ERROR_HANDLER', TRUE);
defined('IS_AJAX') or define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
defined('IS_SAME_DOMAIN') or define('IS_SAME_DOMAIN', !isset($_SERVER['HTTP_REFERER']) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0));
defined('IS_SAME_AJAX') or define('IS_SAME_AJAX', IS_AJAX && IS_SAME_DOMAIN);
/**
 * defining framework base path
 */
define('BASE_PATH', dirname(__FILE__));
class X3 {

    private static $_app;

    /**
     * Error codes gose further down
     */
    const ERROR = 0;
    const FILE_IO_ERROR = 1;
    const SYNTAX_ERROR = 2;
    const DB_ERROR = 3;

    /**
     * @return string version of X3 framework
     */
    public static function getVersion() {
        return '1.1.0b';
    }

    /**
     * Creates X3 application
     * @param string $config is a path to a config file
     * @return X3_Application;
     */
    public static function init($config=NULL) {        
        if(X3_DEBUG) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL & ~E_NOTICE);
        }
        defined('IS_FLASH') or define('IS_FLASH', isset($_SERVER['HTTP_X_FLASH_VERSION']) || stripos($_SERVER['HTTP_USER_AGENT'],'Flash') > 0);
        defined('IS_IE') or define('IS_IE', isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false));
            $version = explode('.', PHP_VERSION);        
        //TODO: multiple load variants, not only JSON
        if (is_string($config) && is_file($config))
            $config = include($config); //taking configuration array
        
        if(!is_array($config))
            throw new X3_Exception('Could not create application from a given config!');

        return new X3_App($config);
    }
    
    public static function console($config) {
        if (is_string($config) && is_file($config))
            $config = include($config); //taking configuration array
        
        if(!is_array($config))
            throw new X3_Exception('Could not create application from a given config!');

        return new X3_Console($config);        
    }

    /**
     *
     * @return object Returns web application
     */
    public static function app() {
        return self::$_app;
    }
    
    public static function db() {
        return self::$_app->db;
    }
    
    public static function user() {
        return self::$_app->user;
    }

    public static function setApp($app) {
        if($app!==null) {
            self::$_app = $app;
        }
        else throw new X3_Exception('Could not create application "' .  get_class($app) . '"!');
    }
    /**
     * Class autoload loader.
     * This method is provided to be invoked within an __autoload() magic method.
     * @param string class name
     * @return boolean whether the class has been loaded successfully
     */
    public static function autoload($className) {
        $path = explode('_',$className);
        if(sizeof($path)>1) {
            array_pop($path);
            $path = implode(DIRECTORY_SEPARATOR , $path) . DIRECTORY_SEPARATOR . $className . '.php';
            $file=BASE_PATH . DIRECTORY_SEPARATOR . $path;
            if(is_file($file)){
               include($file);
               return true;
            }else
               $path = str_replace ('_', DIRECTORY_SEPARATOR, $className) . '.php';
        }else
            $path = $className . '.php';
        //echo "$className<br/>";
        $file = self::$_app->basePath . DIRECTORY_SEPARATOR . self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->MODULES_DIR . DIRECTORY_SEPARATOR . $path;
        if(!file_exists($file) && self::$_app instanceof X3_Console)
            $file = self::$_app->basePath . DIRECTORY_SEPARATOR . self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->COMMANDS_DIR . DIRECTORY_SEPARATOR . $path;
        if(!file_exists($file))
            $file = self::$_app->basePath . DIRECTORY_SEPARATOR . self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->HELPERS_DIR . DIRECTORY_SEPARATOR . $className
                                                                                                    . DIRECTORY_SEPARATOR . $path;
        if(!file_exists($file))
            $file = self::$_app->basePath . DIRECTORY_SEPARATOR . self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->HELPERS_DIR . DIRECTORY_SEPARATOR . $path;
        if(is_file($file))
            include($file);
        return true;
    }

    /**
     * Function to require path from alias or as is.
     *
     * @param string $path path or alias to the file to be imported
     */
    public static function import($path,$noautoload=false,$once=true) {
        $path = self::$_app->getPathFromAlias($path);
        if(is_file($path)) {
            /*if(($i=  strrpos($path, DIRECTORY_SEPARATOR))!==false){
                $dir = substr($path, 0,$i) . DIRECTORY_SEPARATOR;
                set_include_path ($dir);
            }*/
            if($noautoload) spl_autoload_unregister(array('X3', 'autoload'));
            if($once)
                return require_once $path;
            else
                return require $path;
            if($noautoload) spl_autoload_register(array('X3', 'autoload'));
        }
        else throw new X3_Exception ("Wrong import path. File '$path' does not exist!", X3::FILE_IO_ERROR);
    }

    /**
     * Registers a new class autoloader.
     * @param callback a valid PHP callback (function name or array($className,$methodName)).
     */
    public static function registerAutoloader($callback) {
        spl_autoload_unregister(array('X3', 'autoload'));
        spl_autoload_register($callback);
        spl_autoload_register(array('X3', 'autoload'));
    }

    public static function log($msg,$category='application') {
        self::$_app->log->processLog($msg,$category);
       //echo "<pre>X3 LOG UNDONE!!! : ".$msg."</pre>";
    }

    public static function trace($class,$msg) {
       //TODO: TRACE Class Time: Message;
    }

    public static function translate($message,$substitude=array()) {
       //TODO: i18n
//        $dirData = pathinfo($_SERVER['SCRIPT_NAME']);
//        $pathToStrings =  X3::app()->basePath
//                 . $dirData['dirname']
//                 . '/strings/'
//                 . $dirData['filename'] . '.str.php';
        if(!empty($substitude))
            foreach ($substitude as $key => $value) {
                $message = str_replace("{".$key."}", $value, $message);
            }
        X3::app()->fire('onTranslate',array(&$message));
        return $message;
    }

    public static function __callStatic($name, $arguments) {        
        if(property_exists(self::$_app,$name) || self::$_app->hasComponent($name))
            return self::$_app->$name;
        else 
            throw new X3_Exception("No such component '$name'");
    }

}

spl_autoload_register(array('X3','autoload'));

if(!function_exists('array_extend')){
function array_extend($a, $b) {
    if(!is_array($b)) return $a;
    foreach($b as $k=>$v) {
        if( is_array($v) ) {
            if( !isset($a[$k]) ) {
                $a[$k] = $v;
            } else {
                $a[$k] = array_extend($a[$k], $v);
            }
        } else {
            $a[$k] = $v;
        }
    }
    return $a;
}
}

if(!function_exists('array_insert')){
    /**
     * 
     * @param mixed $what what must be inserted. The behavior as array_splice
     * @param array $where source array
     * @param mixed $offset is key of the array where the insert should be
     * @param integer $how means how to insert element: -1 - before; 0 -replace; 1(or other)-after
     * @return array returns inserted array
     */
    function array_insert($what,&$where,$offset,$how=1) {
        if(!is_array($what))
            $what = (array)$what;
        $keys = array_keys($where);
        $values = array_values($where);
        $wk = array_keys($what);
        $wv = array_values($what);
        $offset = array_search($offset,$keys);
        $rep = 0;
        if($how == 1)
            $offset++;
        if($how == 0)
            $rep=1;   
        array_splice($keys,$offset,$rep,$wk);
        array_splice($values,$offset,$rep,$wv);        
        return $where = array_combine($keys, $values);
    } 
}

if(!function_exists('array_typecast')){
    function array_typecast($array,$tc = array()){
        if(empty($tc)) return $array;
        $from = key($tc);
        $to = $tc[$from];
        foreach($array as &$v){
            switch ($to) {
                case "string":
                $v = (string)$v;
                break;
                case "int":
                $v = (int)$v;
                break;
                default:
                break;
            }
        }
        return $array;
    }
}
/**
 * workaround until update to PHP 5.3 takes place
 * (Don't make more than one call in the same line, or it will break!!!).
 * @author "dsaa@dubli.com"
 * Thanx to "dsaa at dubli dot com"
 */
if (!function_exists('get_called_class')):
function get_called_class()
{
    $bt = debug_backtrace();
    $l = count($bt) - 1;
    $matches = array();
    while(empty($matches) && $l > -1){
        $lines = array();
        $callerLine = "";
        if(isset($bt[$l]['file']))
            $lines = file($bt[$l]['file']);
        if(isset($lines[$bt[$l]['line']-1]))
            $callerLine = $lines[$bt[$l]['line']-1];
        preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l--]['function'].'/',
        $callerLine,
        $matches);
    }
    if (!isset($matches[1])) $matches[1]=NULL; //for notices
    if ($matches[1] == 'self') {
        $line = 0;
        if(isset($bt[$l]['line']))
            $line = $bt[$l]['line']-1;
        while ($line > 0 && strpos($lines[$line], 'class') === false) {
            $line--;
        }
        if(isset($lines[$line]))
            preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
        else $matches = array();
    }
    return $matches[1];
}
endif;
if (!function_exists('is_a')):
    function is_a($object,$class){
        return $object instanceof $class;
    }
endif;
if(!function_exists('mb_strlen')){
    function mb_strlen($string,$encoding='UTF-8'){
        if($encoding == 'UTF-8')
            return preg_match_all("/.{1}/us",$string,$dummy);
        return strlen($string);
    }
}
