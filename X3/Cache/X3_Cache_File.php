<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Cache_File
 *
 * @author Soul_man
 */
class X3_Cache_File extends X3_Component {
    
    private $cache = false;
    private $file = '';
    public $directory = '@app:cache';
    public $filename = '<controller>.<action>';
    public $expire = '+1 day';

    public function __construct($config=array()) {
        foreach($config as $var=>$val){
            if(property_exists($this, $var))
                $this->$var = $val;
        }
        $this->directory = X3::app()->getPathFromAlias($this->directory);
        if(!file_exists($this->directory))
            if(!mkdir($this->directory))
                throw new X3_Exception("cache directory does not exist!",500);
            else{
                //chown($this->directory, 'www-data');
                chmod($this->directory,0644);
            }
        $this->addTrigger('onRender');
    }

    public function readCache($controller,$action) {
        $filename = str_replace('<controller>', $controller, $this->filename);
        $filename = str_replace('<action>', $action, $filename);
        $this->file = $file = $this->directory . DIRECTORY_SEPARATOR . $filename;
        if(!is_file($file)){
            $this->cache = true;
            return false;
        }
        $filesize = filesize($file);
        if(false === ($f = fopen($file,'r'))){
            X3::log("Cache file '$file' could not be read.");
            return false;
        }
        $header = (int)fread($f, 16);
        //if cache file expired regenerate
        if(time() > $header){
            $this->cache = true;
            return false;
        }
        $buf = '';
        while(!feof($f)){
            $buf .= fread($f, $filesize-32);
        }
        fclose($f);
        header('HTTP/1.1 304 Not Modified');
        header('Expires: ' . gmdate('D, d M Y H:i:s', $header) . ' GMT');
        return $buf;
    }

    public function clearCache() {
        //TODO: if cache exist unlink
    }

    public function onRender(&$output) {
        if($this->cache){
            $expire = sprintf('%16s',strtotime($this->expire));
            if(false === ($f = fopen($this->file,'w'))){
                X3::log("Can't write cache file '$this->file'");
                return false;
            }
            if(flock($f, LOCK_EX)){
                fwrite($f, $expire,16);
                fwrite($f, $output);
                flock($f, LOCK_UN);
            }
            fclose($f);
        }
    }


}
?>
