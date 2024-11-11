<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource\Types\MySQL {

    defined("XYO_WEB") or die("Forbidden");
    require_once ("./_site/web.ds/mysql-table.php");
    require_once ("./_site/web.ds/mysql-query.php");

    class Connection
    {

        protected $db;

        protected $user;
        protected $password;
        protected $server;
        protected $port;
        protected $database;
        protected $prefix;        
        protected $inUse;
        protected $forceUse;


        public function __construct($configuration)
        {
            $this->db = null;
            $this->user = "";
            $this->password = "";
            $this->server = "localhost";
            $this->port = "3306";
            $this->database = "";
            $this->prefix = "";            
            $this->inUse = false;
            $this->forceUse = false;

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
            $server = $this->server;
            if (strlen($this->port)) {
                $server .= ":" . $this->port;
            }
            $this->db = new \mysqli($server, $this->user, $this->password, $this->database);
            if (!$this->db) {
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
            $this->use();
            $result = $this->db->query($query);
            if (!$result) {
                $result = null;
            }
            return $result;
        }
        public function use()
        {
            if (!$this->forceUse) {
                if ($this->inUse) {
                    return true;
                }
                $this->inUse = true;
            }
            $query = "USE `" . $this->database . "`;";
            $result = $this->db->query($query);
            if (!$result) {
                $result = null;
            }
            return $result;
        }

        public function safeValue($value)
        {
            return $this->db->real_escape_string($value);
        }

        public function safeLikeValue($value)
        {
            return addcslashes($this->db->real_escape_string($value), "%_");
        }

        public function safeTypeValue($type, $value)
        {
            if ($type == "int") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "DEFAULT";
                }
                return $this->safeValue(1 * $value);
            } else if ($type == "bigint") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "DEFAULT";
                }
                return $this->safeValue(1 * $value);
            } else if ($type == "float") {
                if (strcmp($value, "DEFAULT") == 0) {
                    return "DEFAULT";
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
                    return "CURDATE()";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "time") {
                if (is_null($value)) {
                    return "NULL";
                }
                if ($value == "NOW") {
                    return "CURTIME()";
                }
                return "'" . $this->safeValue($value) . "'";
            } else if ($type == "datetime") {
                if (is_null($value)) {
                    return "NULL";
                }
                if ($value == "NOW") {
                    return "NOW()";
                }
                return "'" . $this->safeValue($value) . "'";
            }
            return null;
        }

        public function queryValue($query, $default = null)
        {
            $result = $this->query($query);
            if ($result) {
                $data = $result->fetch_row();
                if ($data) {
                    return $data[0];
                }
            }
            return $default;
        }

        public function queryAssoc($query)
        {
            $result = $this->query($query);
            if ($result) {
                $data = $result->fetch_assoc();
                if ($data) {
                    return $data;
                }
            }
            return null;
        }

        public function &connectTable(&$connector = null)
        {
            $table = new \XYO\Web\DataSource\Types\MySQL\Table($this, $connector);
            return $table;
        }

        public function &connectQuery(&$connector = null)
        {
            $query = new \XYO\Web\DataSource\Types\MySQL\Query($this, $connector);
            return $query;
        }

        public function multiQuery($query)
        {
            $this->use();
            $result = $this->db->multi_query($query);
            if (!$result) {
                $result = null;
            }
            return $result;
        }

        public function getPrefix(){
            return $this->prefix;
        }
    }

    return Connection::class;
}
