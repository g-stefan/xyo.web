<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource {

    defined("XYO_WEB") or die("Forbidden");
    require_once("./_site/xyo/web/config.php");

    class Connections
    {
        private static $instance = null;

        protected $connections;
        protected $sources;
        protected $_null;

        protected function __construct()
        {
            $this->connections = array();
            $this->sources = array();
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Connections();
            self::$instance->_null = null;
            self::processConfig();
        }

        public static function processConfig()
        {
            $config = \XYO\Web\Config::instance();
            if ($config->has("dataSource")) {
                $connections = $config->get("dataSource")->getArray("connections");
                foreach ($connections as $connection => $config) {
                    self::set($connection, $config);
                }
            }
        }

        public static function set($name, $configuration)
        {
            if (!array_key_exists("type", $configuration)) {
                return false;
            }
            $typeSource = "./_site/xyo/web/datasource/types/" . $configuration["type"] . "-connection.php";
            if (!file_exists($typeSource)) {
                return false;
            }
            if (!array_key_exists($typeSource, self::$instance->sources)) {
                $typeClass = require_once($typeSource);
                self::$instance->sources[$typeSource] = $typeClass;
            } else {
                $typeClass = self::$instance->sources[$typeSource];
            }
            self::$instance->connections[$name] = new $typeClass($configuration);
        }

        public static function has($name)
        {
            return array_key_exists($name, self::$instance->connections);
        }

        public static function &get($name = null)
        {
            if (is_null($name)) {
                $name = "db";
            }
            if (!array_key_exists($name, self::$instance->connections)) {
                return self::$instance->_null;
            }
            self::$instance->connections[$name]->open();
            return self::$instance->connections[$name];
        }

    }
}
