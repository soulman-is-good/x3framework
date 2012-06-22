<?php
/**
 * Exception class from X3 framefork
 *
 * @author soulman
 */
class X3_Exception extends Exception {

    public $statusCode;

    public function  __construct($message, $code=0,Exception $previous=NULL) {        
        $this->statusCode = $code;
        X3::log($message,'exception');
        if(PHP_VERSION_ID>=50300)
            parent::__construct($message, $code, $previous);
        else
            parent::__construct($message, $code);
    }
}
?>
