<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Log_File
 *
 * @author Soul_man
 */
class X3_Log_File extends X3_Log {

    private $directory = 'logs';
    private $filename = null;
    private $prefix = '[d.m.Y H:i:s] ';
    private $_category = '*';
    /**
     *
     * @var boolean flashes log file each application run;
     */
    public $recreate = false;

    public function __construct($props) {
        parent::__construct($props['category']);
        foreach ($props as $key => $value) {
            if(property_exists($this, $key))
                $this->$key = $value;
        }
        if(isset($props['directory']))
            $this->directory = X3::app()->getPathFromAlias($props['directory']);
        if(!file_exists($this->directory))
            if(!mkdir($this->directory))
                throw new X3_Exception("log directory does not exist!",500);
        if(isset($props['filename'])){
            $file = $props['filename'];
            $file = str_replace("{%category}",$this->_category,$file);
            $date = array();
            if(preg_match("#\{(.+)?\}#",$file,$date)){
                $date[1] = date($date[1]);
                $file = str_replace($date[0],$date[1],$file);
            }
            $this->filename = $file;
        }else
            $this->filename = date('app-d_m_Y') . '.log';
        if(isset($props['prefix']))
            $this->prefix = $props['prefix'];
    }
    public function  processLog($log,$category = '*') {
        $this->_category = $category;
        if($this->directory == null)
            throw new X3_Exception("log directory does not exist!",500);
        if(!is_writable($this->directory))
            throw new X3_Exception("log directory is not writable!",500);
        if($this->recreate){
            @file_put_contents($this->directory . DIRECTORY_SEPARATOR . $this->filename, "");
            $this->recreate = false;
        }
        @file_put_contents($this->directory . DIRECTORY_SEPARATOR . $this->filename, date($this->prefix) . $log . PHP_EOL, FILE_APPEND);

        parent::processLog($log);
    }
}
?>
