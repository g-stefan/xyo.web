<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource {

    defined("XYO_WEB") or die("Forbidden");
    require_once("./_site/xyo/web/config.php");

    class Connection
    {
        private static $instance = null;

        protected $connection;
        protected $sources;
        protected $_null;

        protected function __construct()
        {
            $this->connection = array();
            $this->sources = array();
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Connection();
            self::$instance->_null = null;
            self::processConfig();
        }

        public static function processConfig()
        {
            $config = \XYO\Web\Config::instance();
            if ($config->has("dataSource")) {
                $connection = $config->get("dataSource")->getArray("connection");
                foreach ($connection as $connection => $config) {
                    self::set($connection, $config);
                }
            }
        }

        public static function set($name, $configuration)
        {
            if (!array_key_exists("type", $configuration)) {
                return false;
            }
            $typeSource = "./_site/xyo/web/datasource/type/" . $configuration["type"] . "-connection.php";
            if (!file_exists($typeSource)) {
                return false;
            }
            if (!array_key_exists($typeSource, self::$instance->sources)) {
                $typeClass = require_once($typeSource);
                self::$instance->sources[$typeSource] = $typeClass;
            } else {
                $typeClass = self::$instance->sources[$typeSource];
            }
            self::$instance->connection[$name] = new $typeClass($configuration);
        }

        public static function has($name)
        {
            return array_key_exists($name, self::$instance->connection);
        }

        public static function &get($name = null)
        {
            if (is_null($name)) {
                $name = "db";
            }
            if (!array_key_exists($name, self::$instance->connection)) {
                return self::$instance->_null;
            }
            self::$instance->connection[$name]->open();
            return self::$instance->connection[$name];
        }

    }
}
