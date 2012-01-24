<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * X3 Framework
 *
 * PHP Version >= 4.3.0
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
defined('IS_SAME_HOST') or define('IS_SAME_HOST', isset($_SERVER['HTTP_HOST']) && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0);
defined('IS_SAME_AJAX') or define('IS_SAME_AJAX', IS_AJAX && IS_SAME_HOST);
defined('IS_FLASH') or define('IS_FLASH', isset($_SERVER['HTTP_X_FLASH_VERSION']) || stripos($_SERVER['HTTP_USER_AGENT'],'Flash') > 0);
defined('IS_IE') or define('IS_IE', isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false));
    $version = explode('.', PHP_VERSION);
defined('PHP_VER') or define('PHP_VER', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
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
        return '1.0.0b';
    }

    /**
     * Creates X3 application
     * @param string $config is a path to a config file
     * @return X3_Application;
     */
    public static function init($config=NULL) {
        if(X3_DEBUG) {
            ini_set('display_errors', 1);
            error_reporting(E_ALL | ~E_NOTICE);
        }
        //TODO: multiple load variants, not only JSON
        if (is_string($config) && is_file($config))
            $config = include($config); //taking configuration array
        
        if(!is_array($config))
            throw new X3_Exception('Could not create application from a given config!');

        return new X3_App($config);
    }

    /**
     *
     * @return object Returns web application
     */
    public static function app() {
        return self::$_app;
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
        if(sizeof($path)==1) {
            $file = self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->MODULES_DIR . DIRECTORY_SEPARATOR . $className . '.php';
            if(is_file($file))
                include($file);
            $file = self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR . self::$_app->HELPERS_DIR . DIRECTORY_SEPARATOR . $className . '.php';
            if(is_file($file))
                include($file);
        }else {
            array_pop($path);
            $className=BASE_PATH . DIRECTORY_SEPARATOR .
                implode(DIRECTORY_SEPARATOR , $path) .
                DIRECTORY_SEPARATOR . $className . '.php';
            if(!is_file($className))
                $className=self::$_app->APPLICATION_DIR . DIRECTORY_SEPARATOR .
                    implode(DIRECTORY_SEPARATOR , $path) .
                    DIRECTORY_SEPARATOR . $className . '.php';
            include($className);
        }
        return true;
    }

    /**
     * Function to require path from alias or as is.
     *
     * @param string $path path or alias to the file to be imported
     */
    public static function import($path,$noautoload=false) {
        $path = self::$_app->getPathFromAlias($path);
        if(is_file($path)) {
            /*if(($i=  strrpos($path, DIRECTORY_SEPARATOR))!==false){
                $dir = substr($path, 0,$i) . DIRECTORY_SEPARATOR;
                set_include_path ($dir);
            }*/
            if($noautoload) spl_autoload_unregister(array('X3', 'autoload'));
            require $path;
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
        self::app()->log->processLog($msg,$category);
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
        return $message;
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