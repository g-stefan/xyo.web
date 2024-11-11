<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource {

    defined("XYO_WEB") or die("Forbidden");
    require_once ("./_site/web/datasource/empty-field.php");
    require_once ("./_site/web/datasource/table-info.php");
    require_once ("./_site/web/datasource/connections.php");

    class Table extends \stdClass
    {

        public $_class = null;
        public $_connector;

        public static $_registry = null;
        public static $_empty = null;
        public static $_order = null;
        public $_info = null;
        public function __construct($connectionName = null)
        {
            if (is_null(static::$_registry)) {
                static::$_registry = array();
                static::$_empty = new EmptyField();
                static::$_order = new \stdClass();
                static::$_order->none = 0;
                static::$_order->ascendent = 1;
                static::$_order->descendent = 2;
            }
            static::register();
            $this->_class = static::class;
            $this->_info =& static::$_registry[static::class];
            $this->_connector = &(Connections::get($connectionName))->connectTable($this);
            $this->empty();
        }

        public static function register()
        {
            if (!array_key_exists(static::class, static::$_registry)) {
                static::$_registry[static::class] = new TableInfo();
                static::descriptor(static::$_registry[static::class]);
            }
        }

        public static function descriptor(&$info)
        {

        }

        public function isEmpty($key)
        {
            return ($this->$key instanceof EmptyField);
        }

        public function empty()
        {
            foreach ($this->_info->fields as $key => &$value) {
                $this->$key = static::$_empty;
            }
        }

        public function load($start = null, $length = null)
        {
            return $this->_connector->load($start, $length);
        }

        public function tryLoad($start = null, $length = null)
        {
            return $this->_connector->tryLoad($start, $length);
        }

        public function loadValid()
        {
            return $this->_connector->loadValid();
        }

        public function count()
        {
            return $this->_connector->count();
        }

        public function hasNext()
        {
            return $this->_connector->hasNext();
        }

        public function loadNext()
        {
            return $this->_connector->loadNext();
        }

        public function clear($key = false)
        {
            return $this->_connector->clear($key);
        }

        public function insert()
        {
            return $this->_connector->insert();
        }

        public function save()
        {
            return $this->_connector->save();
        }

        public function update($what = array())
        {
            return $this->_connector->update($what);
        }

        public function select($what = array())
        {
            return $this->_connector->select($what);
        }

        public function atomicAdd($field, $value)
        {
            return $this->_connector->atomicAdd($field, $value);
        }

        public function atomicIncrement($field)
        {
            return $this->_connector->atomicIncrement($field);
        }

        public function atomicSub($field, $value)
        {
            return $this->_connector->atomicSub($field, $value);
        }
       
    }
}
