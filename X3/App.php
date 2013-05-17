<?php

namespace X3 {
    
    /**
     * class X3_App
     * Web Application class
     *
     * @author Maxim Savin <i@soulman.kz>
     */
    class App {
                
        /**
         * Application timezone. By default set to Almaty, where framework was born
         * @var string
         */
        public $timezone = 'Asia/Almaty';
        
        /**
         * Application locale
         * @var string
         */
        public $locale = 'ru_RU';
        
        /**
         * Application encoding
         * @var string
         */
        public $encoding = 'UTF-8';
        
        /**
         * Array of language abbr. Must not contain locale e.g. array('en','kz','fr')
         * Could be set only through the config array on init.
         * @var array
         */
        private $languages = array();
        
        /**
         * Global associative array for storing global variables
         * Could be accessed through get and set public methods
         * @var array
         */
        private $global = array();
        
        /**
         * Associative array for components
         * Could be accessed through get and public method
         * @var array
         */
        public $components = array();

        /**
         * Creates X3 application
         * @param string $config is a path to a config file
         * @return  \X3\App Web or console application instance 
         * @throws X3_Exception 
         */
        static public function init($config = array()) {
            if (is_string($config) && is_file($config)){
                $ext = strtolower(pathinfo($config,PATHINFO_EXTENSION));
                switch ($ext){
                    case "ini":
                        $config = parse_ini_file($config, TRUE);
                        break;
                    case "js":
                    case "json":
                        if(function_exists('json_decode'))
                            $config = json_decode(file_get_contents($config),1);
                        else
                            throw new \Exception('json functions module isn\'t loaded.');
                        break;
                    case "xml":
                        if(function_exists('simplexml_load_file'))
                            $config = simplexml_load_file($config);
                        else
                            throw new \Exception('SimpleXml module isn\'t loaded.');
                        break;
                    case "php":
                    case "inc":
                        $config = include($config);
                        break;
                    default:
                        $config = array();
                }
            }
            
            if (!is_array($config))
                $config = array();

            return new self($config);
        }
        
        /**
         * Duh! A Constructor!
         * @param array $config array of configurative directives with bunch of components to load. Gears I say!
         */
        public function __construct($config = array()) {
            //We don't need anything but array
            if(!is_array($config))
                $config = array();
            
            $components = array();
            if(isset($config['components']) && is_array($config['components'])){
                $components = $config['components'];
                unset($config['components']);
            }
            
            //set globals and public variables
            foreach($config as $key=>$value){
                switch ($key){
                    case 'encoding':
                    case 'locale':
                    case 'timezone':
                        if(is_string($value))
                            $this->$key = $value;
                        break;
                    case 'languages':
                        if(is_array($value))
                            $this->$key = $value;
                        break;
                    default:
                        $this->set($key,$value);
                }
            }
            
            //components load
            foreach($components as $i=>$component){
                if(isset($component['class'])){
                    $class = $component['class'];
                    unset($component['class']);
                    $this->components[$i] = new $class($component);
                }else
                    foreach ($component as $j => $subcomp) {
                        if(isset($subcomp['class'])){
                            $class = $subcomp['class'];
                            unset($subcomp['class']);
                            $this->components[$i][$j] = new $class($subcomp);
                        }
                    }
            }
        }
        
        /**
         * Store global variables
         * 
         * @param string $key
         * @param mixed $value
         * @return \X3\App
         */
        public function set($key, $value){
            $this->global[$key] = $value;
            return $this;
        }
        
        /**
         * Returns previously stored key or null otherwise
         * 
         * @param string $key
         * @return mixed
         */
        public function get($key) {
            return isset($this->global[$key])?$this->global[$key]:(isset($this->components[$key])?$this->components[$key]:null);
        }
    }
}
?>
