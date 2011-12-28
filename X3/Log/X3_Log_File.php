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
            $file = explode('.', $props['filename']);
            $ext = '.log';
            if(sizeof($file)>1)
                $ext = '.' . array_pop($file);
            $file = implode('.', $file);
            $date = array();
            if(preg_match("#\{(.+)?\}#",$file,$date)){
                $date[1] = date($date[1]);
                $file = str_replace($date[0],$date[1],$file);
            }
            $this->filename = $file . $ext;
        }else
            $this->filename = date('app-d_m_Y') . '.log';
        if(isset($props['prefix']))
            $this->prefix = $props['prefix'];
    }
    public function  processLog($log) {
        if($this->directory == null)
            throw new X3_Exception("log directory does not exist!",500);
        if(!is_writable($this->directory))
            throw new X3_Exception("log directory is not writable!",500);

        @file_put_contents($this->directory . DIRECTORY_SEPARATOR . $this->filename, date($this->prefix) . $log . PHP_EOL, FILE_APPEND);

        parent::processLog($log);
    }
}
?>
