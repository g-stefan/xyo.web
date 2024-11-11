<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Config extends \stdClass
    {
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
            if (!property_exists($this, $name)) {
                return $defaultValue;
            }
            return $this->$name;
        }

        public function has($name)
        {
            return property_exists($this, $name);
        }

        public function includeFile($name)
        {
            if (file_exists($name)) {
                $config = include ($name);
                if (!empty($config)) {
                    foreach ($config as $key => $value) {
                        $this->$key = $value;
                    }
                    return true;
                }
            }
            return false;
        }

    }

}
