<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Request
 *
 * @author Soul_man
 */
class X3_Request extends X3_Component {

    public $url = '';
    public $uri = array();
    public $get = array();
    public $post = array();
    public $suffix = '';

    public function __construct($config) {
        if (isset($config['suffix']))
            $this->suffix = $config['suffix'];        
        $this->get = array_extend($this->get, $_GET);
        $this->post = array_extend($this->post, $_POST);
    }

    protected function parse_query() {
        $var = parse_url($this->url, PHP_URL_QUERY);
        $this->url = str_replace(".$this->suffix", '', $this->url);
        $this->url = str_replace("?$var", '', $this->url);
        $var = html_entity_decode($var);
        $var = explode('&', $var);
        $arr = array();       
        foreach ($var as $val) {
            if($val=='') continue;
            $x = explode('=', $val);
            if(!isset($x[1])) $x[1]=true;
            $arr[$x[0]] = $x[1];
        }
        unset($val, $x, $var);
        return $arr;
    }

    public function resolveURI($uri) {
        $this->url = trim($uri, '/');
        $this->parse_query();
        $this->uri = $uri = explode('/', $this->url);
        $controller = (!empty($uri[0])) ? array_shift($uri) : 'site';
        $action = (!empty($uri[0])) ? array_shift($uri) : 'index';
        if (strpos($action, '.') !== false)
            $action = substr($action, 0, strrpos($action, '.'));
        if (!empty($uri)) {
            for ($i = 0; $i < sizeof($uri); $i+=2) {
                $key = $uri[$i];
                $val = isset($uri[$i + 1]) ? $uri[$i + 1] : null;
                $_GET[$key] = $val;
            }
        }
        $this->get = array_extend($this->get, $_GET);

        return array($controller, $action);
    }

    public function safeRequest() {
        foreach ($this->get as $key => $var) {
            if (X3::app()->controller->fields
                )1;
            //TODO: parse safe request
        }
    }

    public function isActive($url,$strict=false) {
        if($strict && $url == $_SERVER['REQUEST_URI']) return true;
        elseif($strict) return false;
        $trueurl = trim($url,'/');
        $trueurl = str_replace(".$this->suffix", '', $trueurl);
        $trueurl = preg_replace("#\?(.*)#", '', $trueurl);
        if($this->url==$trueurl) return true;
        return false;
    }

}
?>
