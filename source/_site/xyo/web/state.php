<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class State
    {
        private static $instance = null;
        protected $state;

        protected function __construct()
        {
            $this->state = array();
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new State();
        }

        public function set($id, $name, $value)
        {
            if (!array_key_exists($id, $this->state)) {
                $this->state[$id] = array();
            }
            $this->state[$id][$name] = $value;
        }

        public function get($id, $name, $default = null)
        {
            if (!array_key_exists($id, $this->state)) {
                return $default;
            }
            if (!array_key_exists($name, $this->state[$id])) {
                return $default;
            }
            return $this->state[$id][$name];
        }

        public function remove($id, $name = null)
        {
            if (!array_key_exists($id, $this->state)) {
                return;
            }
            if (!is_null($name)) {
                unset($this->state[$id][$name]);
                return;
            }
            unset($this->state[$id]);
        }

        public function clear()
        {
            $this->state = array();
        }

        public function has($id, $name)
        {
            if (!array_key_exists($id, $this->state)) {
                return false;
            }
            return array_key_exists($name, $this->state[$id]);
        }

        public function encode()
        {
            return base64_encode(json_encode($this->state));
        }

        public function decode($encodedState)
        {
            $this->state = array();
            $decoded = base64_decode($encodedState);
            if ($decoded === false) {
                return false;
            }
            $this->state = json_decode($decoded, true);
            if (is_null($this->state)) {
                $this->state = array();
                return false;
            }
            return true;
        }
        
    }
}
