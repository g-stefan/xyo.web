<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource\Types\SQLite {

    defined("XYO_WEB") or die("Forbidden");
    require_once ("./site/web.ds/sqlite-table.php");
    require_once ("./site/web.ds/sqlite-query.php");

    class Connection
    {

        protected $db;
        protected $database;
        protected $prefix;

        public function __construct($configuration)
        {
            $this->db = null;
            $this->database = "";
            $this->prefix = "";

            foreach ($configuration as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }

        public function open()
        {
            if ($this->db) {
                return true;
            }
            try {
                $this->db = new \SQLite3($this->database);
            } catch (\Exception $e) {
                $this->db = null;
                return false;
            }            
            return true;
        }

        public function close()
        {
            if ($this->db) {
                $this->db->close();
                $this->db = null;
            }
        }

        public function query($query)
        {         
            $result = $this->db->query($query);
            if (!$result) {
                $result = null;
            }
            return $result;
        }       

        public function safeValue($value)
        {
            return \SQLite3::escapeString($value);
        }

        public function safeLikeValue($value)
        {
            return addcslashes(\SQLite3::escapeString($value), "%_");
        }

        public function safeTypeValue($type, $value)
        {
            if ($type == "int") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "NULL";
                }
                return $this->safeValue(1 * $value);
            } else if ($type == "bigint") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "NULL";
                }
                return $this->safeValue(1 * $value);
            } else if ($type == "float") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "NULL";
                }
                return $this->safeValue(1 * $value);
            } else if ($type == "text") {
                if (is_null($value)) {
                    return "NULL";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "varchar") {
                if (is_null($value)) {
                    return "NULL";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "date") {
                if (is_null($value)) {
                    return "NULL";
                }
                if ($value == "NOW") {
                    return "DATE('NOW','localtime')";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "time") {
                if (is_null($value)) {
                    return "NULL";
                }
                if ($value == "NOW") {
                    return "TIME('NOW','localtime')";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "datetime") {
                if (is_null($value)) {
                    return "NULL";
                }
                if ($value == "NOW") {
                    return "DATETIME('NOW','localtime')";
                }
                return "'" . $this->safeValue($value) . "'";
            }
            return null;
        }

        public function queryValue($query, $default = null)
        {
            $result = $this->db->querySingle($query);
            if ($result) {
                return $result;             
            }
            return $default;
        }

        public function queryAssoc($query)
        {
            $result = $this->query($query);
            if ($result) {
                $data = $result->fetchArray(SQLITE3_ASSOC);
                if ($data) {
                    return $data;
                }
            }
            return null;
        }

        public function &connectTable(&$connector = null)
        {
            $table = new \XYO\Web\DataSource\Types\SQLite\Table($this, $connector);
            return $table;
        }

        public function &connectQuery(&$connector = null)
        {
            $query = new \XYO\Web\DataSource\Types\SQLite\Query($this, $connector);
            return $query;
        }
        
        public function getPrefix()
        {
            return $this->prefix;
        }
    }

    return Connection::class;
}
