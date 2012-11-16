<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of X3_Thread
 *
 * @author Soul_man
 */
class X3_Thread extends X3_Component {

    const GET_STREAM = 1;
    const CHECK_CONNECTION = 2;
    const HEADERS_ONLY = 3;
    const RESPONSE_CODE = 4;
    
    private $url = null;
    private $params = array();
    private $method = "POST";
    private $response = "";
    private $content_type = "text/html"; //application/x-www-form-urlencoded
    private static $_instance = null;
    public $timeout = 60;
    private $error = '';

    public function __construct($url, $params = array(), $method = "POST",$timeout = 60) {
        $this->url = $url;
        $this->params = $params;
        $this->method = $method;
        $this->timeout = $timeout;
        $this->addTrigger('onError');
    }

    public static function create($url, $params = array(), $method = "POST",$timeout = 60) {
        return new self($url, $params, $method);
    }

    public function run($check_only=false) {
        $url = $this->url;
        $parts = parse_url($url);
        $params = array();
        parse_str($parts['query'],$params);
        $params = array_merge($this->params, $params);
        $err = '';
        if(empty($parts['host']))
            throw new X3_Exception('Host is not defined!');
        try{
            if (!$fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80,$errno,$err,$this->timeout)) {
                $this->error = $err;
                return false;
            }
        }catch(Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
        if($check_only == self::CHECK_CONNECTION) {
            fclose($fp);
            return true;
        }
        $result = "";
        $data = http_build_query($params, '', '&');
        if (isset($parts['user']))
            fwrite($fp, "USER " . $parts['user'] . "\r\n");
        if (isset($parts['pass']))
            fwrite($fp, "PASS " . $parts['pass'] . "\r\n");        
        fwrite($fp, "$this->method " . (!empty($parts['path']) ? $parts['path'] : '/') . ($this->method=="GET"?"?$data":'') . " HTTP/1.1\r\n");
        fwrite($fp, "Host: " . $parts['host'] . "\r\n");
        fwrite($fp, "Content-Type: $this->content_type\r\n");
        fwrite($fp, "Content-Length: " . strlen($data) . "\r\n");
        fwrite($fp, "User-Agent: kansha.kz\r\n");
        fwrite($fp, "Connection: Close\r\n\r\n");
        if($this->method == "POST")
            fwrite($fp, $data);
        if($check_only == self::GET_STREAM) 
            return $fp;
        if($check_only == self::RESPONSE_CODE){
            $result .= fread($fp, 56);
            fclose($fp);
            $result = explode(' ', $result);
            return (int)$result[1];
        }
        if($check_only == self::HEADERS_ONLY){
            $line = "";
            while(!($line == "\r\n")  && !feof($fp)){
                $result .= $line;
                $line = fread($fp, 128);
            }
            fclose($fp);    
            return $result;
        }
        while(!feof($fp))
            $result .= fread($fp, 1024);
        fclose($fp);
        $this->parseHttpResponse($result);
        return $this;
    }

    /**
     * Accepts provided http content, checks for a valid http response,
     * unchunks if needed, returns http content without headers on
     * success, false on any errors.
     * 
     */
    private function parseHttpResponse($content = null) {
        if (empty($content)) {
            return false;
        }
        // split into array, headers and content.
        $hunks = explode("\r\n\r\n", trim($content));
        if (!is_array($hunks) || count($hunks) < 2) {
            return false;
        }
        $header = array_shift($hunks);
        $body = array_shift($hunks);
        $headers = explode("\n", $header);
        unset($hunks);
        unset($header);
//        if (!verifyHttpResponse($headers)) {
//            return false;
//        }
//        if (in_array('Transfer-Coding: chunked', $headers)) {
//            return trim(unchunkHttpResponse($body));
//        } else {
//            return trim($body);
//        }
        $this->response = array('body' => $this->clearResponse($body), 'headers' => $headers);
    }

    private function clearResponse($str = null) {
        if (!is_string($str) or strlen($str) < 1) {
            return false;
        }
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $str;
        $str = '';
        do {
            $tmp = ltrim($tmp);
            $pos = strpos($tmp, $eol);
            if ($pos === false) {
                return $tmp;
            }
            $len = hexdec(substr($tmp, 0, $pos));
            if (!is_numeric($len) or $len < 0) {
                return $tmp;
            }
            $str .= substr($tmp, ($pos + $add), $len);
            $tmp = substr($tmp, ($len + $pos + $add));
            $check = trim($tmp);
        } while (!empty($check));
        unset($tmp);
        return trim($str);
    }
    
    public function getError() {
        return $this->error;
    }

    public function getBody() {
        return $this->response['body'];
    }
    
    public function getHeaders() {
        return $this->response['headers'];        
    }
    
    public function onError($code, $message, $file, $line, $throw){
        $throw = false;
        $this->error = $message;
        return true;
    }

}

?>
