<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Cache_Mem
 *
 * @author Soul_man
 */
class X3_Cache_Mem extends X3_Component implements X3_Interface_Cache {
    
    private $_servers = array();
    private $_cache = null;
    
    public function __construct($params = array()) {
        $cache = $this->getCache();
        if(isset($params['hosts'])){
            foreach($params['hosts'] as $host){
                $data = explode(':',$host);
                if(!isset($data[1])) $data[1] = 0;
                if(!isset($data[2])) $data[2] = 10;
                $cache->addServer($data[0],$data[1],$data[2]);
                $this->_servers[] = (object)array('host'=>$data[0],'port'=>$data[1],'weight'=>$data[2]);
            }
        }else
            $cache->addServer('localhost',11211);
    }
    
    public function getCache() {
        return $this->_cache = $this->_cache==null?new Memcached:$this->_cache;
    }
    
    public function set($key,$value,$expitre = 0,$dependency=null){
        if($expire>0)
                $expire+=time();
        else
                $expire=0;        
        return $this->getCache()->set($key,$value, $expitre);
    }

    public function add($id, $value, $expire = 0, $dependency = null) {
        if($expire>0)
                $expire+=time();
        else
                $expire=0;        
        return $this->getCache()->set($id,$value,$expire);
    }

    public function delete($id) {
        return $this->getCache()->delete($id);
    }

    public function flush() {
        return $this->getCache()->flush();
    }

    public function get($id) {
        return $this->getCache()->get($id);
    }

    public function mget($ids) {
        return $this->getCache()->getMulti($ids);
    }
}

?>
