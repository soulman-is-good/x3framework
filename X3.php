<?php

/**
 * X3 Framework v3
 *
 * PHP Version >= 5.3.0
 * @author Maxim Savin <i@soulman.kz>, Eugeniy Mineyev <eugeniy.mineyev@gmail.com>
 * @version 3.0
 * @copyright (c) 2012, Maxim Savin
 */

namespace X3 {

    defined('X3_DEBUG') or define('X3_DEBUG', FALSE);
    defined('X3_DEBUG_LEVEL') or define('X3_DEBUG_LEVEL', 5);
    defined('X3_ENABLE_EXCEPTION_HANDLER') or define('X3_ENABLE_EXCEPTION_HANDLER', TRUE);
    defined('X3_ENABLE_ERROR_HANDLER') or define('X3_ENABLE_ERROR_HANDLER', TRUE);
    defined('IS_AJAX') or define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    defined('IS_FLASH') or define('IS_FLASH', isset($_SERVER['HTTP_X_FLASH_VERSION']) || stripos($_SERVER['HTTP_USER_AGENT'], 'Flash') > 0);
    defined('IS_IE') or define('IS_IE', isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false));
    //IS_SAME_DOMAIN is depricated of no use
    //IS_SAME_AJAX is depricated of no use

    /**
     * defining framework base path
     */
    define('X3_BASE_PATH', dirname(__FILE__));

    if (X3_DEBUG) {
        ini_set('display_errors', 1);
        error_reporting(E_ALL & ~E_NOTICE);
    }

    /**
     * @return string version of X3 framework
     */
    function getVersion() {
        return '3.0.0a';
    }

    function autoload($file) {
        $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);
        $filepath = X3_BASE_PATH . DIRECTORY_SEPARATOR . $file . '.php';
        if (file_exists($filepath)) {
            require_once($filepath);
        }
    }

    \spl_autoload_register('X3\autoload');
}