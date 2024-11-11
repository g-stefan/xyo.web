<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Request
    {
        private static $instance = null;
        protected $request;

        protected function __construct($defaultRequest = null)
        {
            $this->request = array();
            if (is_null($defaultRequest)) {
                return;
            }

            foreach (array_keys($defaultRequest) as $name) {
                $this->request[$name] = $defaultRequest[$name];
            }
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Request(array_merge($_COOKIE, $_GET, $_POST));
        }

        public function set($name, $value)
        {
            $this->request[$name] = $value;
        }

        public function get($name, $default = null)
        {
            if (!array_key_exists($name, $this->request)) {
                return $default;
            }
            return $this->request[$name];
        }

        public function remove($name)
        {
            unset($this->request[$name]);
        }

        public function clear()
        {
            $this->request = array();
        }

        public function has($name)
        {
            return array_key_exists($name, $this->request);
        }

        public function isOPTIONS(){
            return (strcmp($_SERVER["REQUEST_METHOD"], "OPTIONS") == 0);
        }

        public function isGET(){
            return (strcmp($_SERVER["REQUEST_METHOD"], "GET") == 0);
        }

        public function isPOST(){
            return (strcmp($_SERVER["REQUEST_METHOD"], "POST") == 0);
        }        

        public function isAJAX(){
            return (strcmp($this->get("_ajax", "0"),"1")==0);
        }

        public function isJSON(){
            return (strcmp($this->get("_json", "0"),"1")==0);
        }

        public function isAPI()
        {
            $info = \XYO\Web\Info::instance();
            return ($info->routeType == $info->routeTypeAPI);
        }

        public function isComponent($id) {
            $component=$this->get("_component","");
            return (strcmp($component,$id)==0);
        }
        
        public function isComponentAJAX($id) {
            if(!$this->isAJAX()){
                return false;   
            }            
            return $this->isComponent($id);
        }

        public function isComponentJSON($id) {
            if(!$this->isJSON()){
                return false;   
            }            
            return $this->isComponent($id);
        }

    }
}
