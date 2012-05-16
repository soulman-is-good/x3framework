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

    private $url = null;
    private $params = array();
    private static $_instance = null;

    public function __construct($url, $params = array()) {
        $this->url = $url;
        $this->params = $params;
    }

    public function create($url, $params = array()) {
        return new self($url,$params);
    }

    public function run() {
        $url = $this->url;
        $params = $this->params;
        $parts = parse_url($url);

        if (!$fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80)) {
            return false;
        }

        $data = http_build_query($params, '', '&');
        if(isset($parts['user']))
            fwrite($fp, "USER " . $parts['user'] . "\r\n");
        if(isset($parts['pass']))
            fwrite($fp, "PASS " . $parts['pass'] . "\r\n");

        fwrite($fp, "POST " . (!empty($parts['path']) ? $parts['path'] : '/') . " HTTP/1.1\r\n");
        fwrite($fp, "Host: " . $parts['host'] . "\r\n");
        fwrite($fp, "Content-Type: application/x-www-form-urlencoded\r\n");
        fwrite($fp, "Content-Length: " . strlen($data) . "\r\n");
        fwrite($fp, "Connection: Close\r\n\r\n");
        fwrite($fp, $data);
        echo fread($fp, 10240);
        fclose($fp);

        return true;
    }

}

?>
