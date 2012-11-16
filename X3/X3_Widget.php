<?php
/**
 * X3_Widget
 * 
 * @author Soul_man
 */
class X3_Widget extends X3_Renderer{
    
    private $filename = false;
    private $buffer = false;
    private $_cache = null;


    public $widgets_dir = '';
    public $cache = false;
    public $cacheClass = 'X3_Cache_File';
    public $cacheConfig = array();


    public function init() {
        if($this->cache){
            $this->_cache = $cacheFile = $this->cacheClass;
            if(is_string($cacheFile)){
                $name = pathinfo($this->filename,PATHINFO_FILENAME);
                $this->cacheConfig = array_extend(array('filename'=>$name,'expire'=>'+1 day','noTriggers'=>true),$this->cacheConfig);
                $this->_cache = $cacheFile = new $cacheFile($this->cacheConfig);
            }

            if(FALSE!==($buf = $cacheFile->readCache(null,null,false))){
                $this->buffer = $buf;
            }
        }
    }
    
    public static function run($filename,$data=array(),$config=array()) {
        $widget = new self($config);
        $widget->filename = X3::app()->getPathFromAlias($filename);
        $widget->init();
        return $widget->renderPartial($widget->filename,$data);
    }
    
    public function renderPartial($view, $data = null) {
        if($this->buffer === FALSE){
            if($this->cache){
                $buffer = parent::renderPartial($view, $data);
                $this->_cache->writeCache($buffer);
                return $buffer;
            }else
                return parent::renderPartial($view, $data);
        }else
            return $this->buffer;
    }
}

?>
