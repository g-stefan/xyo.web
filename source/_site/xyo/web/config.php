<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Config
    {
        public $list = array();

        private static $instance = null;

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Config();
        }

        public function get($name, $defaultValue = null)
        {
            if (!array_key_exists($name, $this->list)) {
                if (is_null($defaultValue)) {
                    return $name;
                }
                return $defaultValue;
            }
            return $this->list[$name];
        }

        public function has($name)
        {
            return array_key_exists($name, $this->list);
        }

        public function setArray($list)
        {
            foreach ($list as $key => $value) {
                if (is_array($value)) {
                    $this->list[$key] = new Config();
                    $this->list[$key]->setArray($value);
                    continue;
                }
                $this->list[$key] = $value;
            }
        }

        public function includeFile($name)
        {
            if (file_exists($name)) {
                $config = include($name);
                if (!empty($config)) {
                    $this->setArray($config);
                    return true;
                }
            }
            return false;
        }

        public function includeByPattern($name, $default = null)
        {
            $list = glob($name);
            if (count($list) == 0) {
                if (!is_null($default)) {
                    $list = array($default);
                }
            }
            sort($list);
            foreach ($list as $configFile) {
                $this->includeFile($configFile);
            }
        }

        public function getArray($name = null)
        {
            $list = array();
            $thisList = $this->list;

            if (!is_null($name)) {
                if (!array_key_exists($name, $this->list)) {
                    return $list;
                }
                if (!($this->list[$name] instanceof self)) {
                    return $list;
                }
                $thisList = $this->list[$name]->list;
            }

            foreach ($thisList as $key => $value) {
                if ($value instanceof self) {
                    $list[$key] = $value->getArray();
                    continue;
                }
                $list[$key] = $value;
            }

            return $list;
        }

        public function getObject($name = null)
        {
            $list = new \stdClass();
            $thisList = $this->list;

            if (!is_null($name)) {
                if (!array_key_exists($name, $this->list)) {
                    return $list;
                }
                if (!($this->list[$name] instanceof self)) {
                    return $list;
                }
                $thisList = $this->list[$name]->list;
            }

            foreach ($thisList as $key => $value) {
                if ($value instanceof self) {
                    $list->{$key} = $value->getObject();
                    continue;
                }
                $list->{$key} = $value;
            }

            return $list;
        }

    }

}
