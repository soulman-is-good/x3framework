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
    public $stack = array();
    public $get = array();
    public $post = array();
    public $suffix = '';
    public $hash = '';
    public $redirects = null;
    
    public static function getCodeName($code=null){
        $codes = array(
            '100'=>'Continue',
            '101'=>'Switching Protocols',
            '102'=>'Processing',
            '200'=>'OK',
            '201'=>'Created',
            '202'=>'Accepted',
            '203'=>'Non-Authoritative Information',
            '204'=>'No Content',
            '205'=>'Reset Content',
            '206'=>'Partial Content',
            '207'=>'Multi-Status',
            '208'=>'Already Reported',
            '226'=>'IM Used',
            '300'=>'Multiple Choices',
            '301'=>'Moved Permanently',
            '302'=>'Found',
            '303'=>'See Other',
            '304'=>'Not Modified',
            '305'=>'Use Proxy',
            '306'=>'Switch Proxy',
            '307'=>'Temporary Redirect',
            '308'=>'Permanent Redirect [12]',
            '400'=>'Bad Request',
            '401'=>'Unauthorized',
            '402'=>'Payment Required',
            '403'=>'Forbidden',
            '404'=>'Not Found',
            '405'=>'Method Not Allowed',
            '406'=>'Not Acceptable',
            '407'=>'Proxy Authentication Required',
            '408'=>'Request Timeout',
            '409'=>'Conflict',
            '410'=>'Gone',
            '411'=>'Length Required',
            '412'=>'Precondition Failed',
            '413'=>'Request Entity Too Large',
            '414'=>'Request-URI Too Long',
            '415'=>'Unsupported Media Type',
            '416'=>'Requested Range Not Satisfiable',
            '417'=>'Expectation Failed',
            '418'=>'I\'m a teapot',
            '420'=>'Enhance Your Calm',
            '422'=>'Unprocessable Entity',
            '423'=>'Locked',
            '424'=>'Failed Dependency',
            '424'=>'Method Failure [14]',
            '425'=>'Unordered Collection',
            '426'=>'Upgrade Required',
            '428'=>'Precondition Required',
            '429'=>'Too Many Requests',
            '431'=>'Request Header Fields Too Large',
            '444'=>'No Response',
            '449'=>'Retry With',
            '450'=>'Blocked by Windows Parental Controls',
            '451'=>'Unavailable For Legal Reasons',
            '451'=>'Redirect',
            '494'=>'Request Header Too Large',
            '495'=>'Cert Error',
            '496'=>'No Cert',
            '497'=>'HTTP to HTTPS',
            '499'=>'Client Closed Request',
            '500'=>'Internal Server Error',
            '501'=>'Not Implemented',
            '502'=>'Bad Gateway',
            '503'=>'Service Unavailable',
            '504'=>'Gateway Timeout',
            '505'=>'HTTP Version Not Supported',
            '506'=>'Variant Also Negotiates',
            '507'=>'Insufficient Storage',
            '508'=>'Loop Detected',
            '509'=>'Bandwidth Limit Exceeded',
            '510'=>'Not Extended',
            '511'=>'Network Authentication Required',
            '598'=>'Network read timeout error',
            '599'=>'Network connect timeout error'
        );
        if(is_null($code))
            return $codes;
        return isset($codes[$code])?$codes[$code]:false;
    }

    public function __construct($config) {
        if (isset($config['suffix']))
            $this->suffix = $config['suffix'];
        if(is_null($this->redirects))
            $this->redirects = 'redirects.txt';
        $redirects = X3::app()->getPathFromAlias($this->redirects);
        $this->get = array_extend($this->get, $_GET);
        $this->post = array_extend($this->post, $_POST);
        //var_dump($redirects);
        if(is_file($redirects)){
            $file = file($redirects,FILE_SKIP_EMPTY_LINES);
            foreach($file as $line){
                $attr = explode('==',$line);
                $attr[0] = str_replace('*','.*',$attr[0]);
                $m = array();
                $code = '301';
                $attr[1] = explode('>>',$attr[1]);
                if(count($attr[1])>1){
                    $code = (int)trim($attr[1][1],'][ ');
                }
                $attr[1] = $attr[1][0];
                if(!empty($attr[0]) && !empty($attr[1]) && preg_match("~{$attr[0]}~",$_SERVER['REQUEST_URI'])>0){
                    header("HTTP/1.1 $code ".self::getCodeName($code));
                    header("Location: ".X3::app()->baseUrl."/".$attr[1]);
                    exit;
                }
            }
        }
    }

    protected function parse_query(&$url) {
        $var = parse_url($url, PHP_URL_QUERY);
        $this->hash = parse_url($url, PHP_URL_FRAGMENT);
        $url = str_replace(".$this->suffix", '', $url);
        $url = str_replace("?$var", '', $url);
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
        $url = trim($uri, '/');
        $this->parse_query($url);        
        $this->uri = $uri = explode('/', $url);
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
        $url = trim($_SERVER['REQUEST_URI'],'/');
        $this->parse_query($url);
        $this->url = $url;
        return array($controller, $action);
    }

    public function safeRequest() {
        foreach ($this->get as $key => $var) {
            if (X3::app()->controller->fields
                )1;
            //TODO: parse safe request
        }
    }
    
    public function parseUrl($param=array(),$url=null,$absolute=false) {
        if($url === null)
            $url = $this->url;
        if(strpos($url,"http://")===0){
            $url = str_replace("http://", "", $url);
            $url = substr($url, 0, strpos($url,'/'));
        }
        $url = str_replace($this->suffix, "", $url);
        $url = trim($url,'./');
        $uri = explode('/',$url);
        foreach($param as $key=>$value){
            $i = array_search($key, $uri);
            if($i!==false){
                $uri[$i+1]=$value;
            }else{
                $uri[]=$key;
                $uri[]=$value;
            }
        }
        return ($absolute?"http://".$_SERVER['HTTP_HOST']:"")."/".implode('/',$uri).(!empty($this->suffix)?".$this->suffix":'');
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
