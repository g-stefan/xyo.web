<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource\Types\MySQL {

    defined("XYO_WEB") or die("Forbidden");

    class Table
    {

        protected $connection = null;
        protected $table = null;
        protected $info = null;
        protected $name = null;
        protected $operator = null;
        protected $order = null;
        protected $_order = null;

        protected $_select = null;

        protected $_function = null;
        protected $group = null;

        protected $result = null;
        protected $nextFields = null;
        protected $autoIncrement = null;
        protected $_loadValid = false;
        public function __construct(&$connection, &$table)
        {
            $this->connection = $connection;
            $this->table = $table;
            $this->info = &$table->_class::$_registry[$table->_class];
            $this->name = $this->connection->getPrefix() . $this->info->name;
            $this->_order = &$table->_class::$_order;
            $this->operator = array();
            $this->_select = array();
            $this->_function = array();
            $this->group = array();
            $this->order = array();
            $this->autoIncrement = null;

            $this->result = null;
            $this->nextFields = null;
            $this->_loadValid = false;

            foreach ($this->info->fields as $key => &$value) {
                if (count($value) > 3) {
                    if ($value[3] == "autoIncrement") {
                        $this->autoIncrement = $key;
                    }
                }
            }

        }

        public function setOrder($key, $value)
        {
            if (array_key_exists($key, $this->info->fields)) {
                $this->order[$key] = $value;
            }
        }

        public function setGroup($key, $value)
        {
            if (array_key_exists($key, $this->info->fields)) {
                $this->group[$key] = $value;
            }
        }

        public function setFunctionAs($key, $_function, $as)
        {
            if (array_key_exists($key, $this->info->fields)) {
                $this->_function[$key] = array($_function, $as);
            }
        }

        public function pushOperator($mode)
        {
            $opList1 = array(
                "and" => " AND ",
                "or" => " OR "
            );

            $opList2 = array(
                "(" => "(",
                ")" => ")"
            );

            if (array_key_exists($mode, $opList1)) {
                $idx = count($this->operator);
                if ($idx) {
                    if (in_array($this->operator[$idx - 1][1], $opList1)) {
                        $idx = $idx - 1;
                    }
                }
                $this->operator[$idx] = array(0 => 1, 1 => $opList1[$mode]);
                return;
            }
            if (array_key_exists($mode, $opList2)) {
                $idx = count($this->operator);
                if ($idx) {
                    if (in_array($this->operator[$idx - 1][1], $opList2)) {
                        if ($this->operator[$idx - 1][1] == "(" && $opList2[$mode] == ")") {
                            unset($this->operator[$idx - 1]);
                            return;
                        }
                    }
                }
                $this->operator[$idx] = array(0 => 1, 1 => $opList2[$mode]);
            }
        }

        public function setOperator($key, $operator, $v1 = null, $v2 = null, $v1x = false, $v2x = false)
        {
            if (!array_key_exists($key, $this->info->fields)) {
                return;
            }

            $idx = count($this->operator);
            if ($idx) {
                if ($this->operator[$idx - 1][0] == 0) {
                    $idx = $idx - 1;
                }
            } else {
                $this->pushOperator("and");
                $idx = count($this->operator);
            }

            $opList = array(
                "between" => array(2, " BETWEEN "),
                "not-between" => array(2, " NOT BETWEEN "),
                "is-null" => array(0, " IS NULL "),
                "is-not-null" => array(0, " IS NOT NULL "),
                "=" => array(1, " = "),
                "<" => array(1, " < "),
                ">" => array(1, " > "),
                "<=" => array(1, " <= "),
                ">=" => array(1, " >= "),
                "!=" => array(1, " != "),
                "like" => array(3, " LIKE ")
            );
            if (array_key_exists($operator, $opList)) {
                $this->operator[$idx] = array(0 => 0, 1 => $key, 2 => $opList[$operator][0], 3 => $opList[$operator][1], 4 => $v1, 5 => $v2, 6 => $v1x, 7 => $v2x);
            }
        }

        function strQueryValue($key, $value)
        {
            return $this->connection->safeTypeValue($this->info->fields[$key][0], $value);
        }

        function strQueryWhereClauseForFieldValue($fieldAs, $fieldThis, $value)
        {
            return "`" . $fieldAs . "`=" . $this->strQueryValue($fieldThis, $value);
        }

        function strQueryWhereClauseForField($fieldAs, $fieldThis)
        {
            $value = $this->table->$fieldThis;
            if (is_array($value)) {
                if (count($value) == 1) {
                    $value = $value[0];
                } else {
                    $where = "(";

                    $x = null;
                    foreach ($value as $v) {
                        if ($x) {
                            $x .= " OR " . $this->strQueryWhereClauseForFieldValue($fieldAs, $fieldThis, $v);
                        } else {
                            $x = $this->strQueryWhereClauseForFieldValue($fieldAs, $fieldThis, $v);
                        }
                    }

                    $where .= $x;
                    $where .= ")";
                    return $where;
                }
            }

            return $this->strQueryWhereClauseForFieldValue($fieldAs, $fieldThis, $value);
        }

        function strWhereQuery()
        {
            $where = null;
            foreach ($this->info->fields as $key => $value) {
                if ($this->table->isEmpty($key)) {
                    continue;
                }
                if ($where) {
                    $where .= " AND " . $this->strQueryWhereClauseForField($key, $key);
                } else {
                    $where = " WHERE " . $this->strQueryWhereClauseForField($key, $key);
                }
            }

            if (count($this->operator)) {
                foreach ($this->operator as $key => $value) {
                    if ($value[0] == 1) {

                        if ($where) {
                            $where .= $value[1];
                        } else {
                            $where = " WHERE ";
                        }

                    } else {

                        if ($value[2] == 3) {
                            if (is_array($value[4])) {
                                $where .= "(";
                            }
                        }

                        if ($value[2] == 1) {
                            if ($value[7]) {
                                $where .= "COALESCE(";
                            }
                        }

                        $where .= "`" . $value[1] . "`";
                        if ($value[2] == 1) {
                            if ($value[7]) {
                                $where .= "," . $this->strQueryValue($value[1], $value[5]) . ")";
                            }
                        }

                        $where .= $value[3];

                        if ($value[2] == 0) {
                        } else if ($value[2] == 1) {
                            if ($value[6]) {
                                $where .= "`" . $value[4] . "`";
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[4]);
                            }
                        } else if ($value[2] == 2) {
                            if ($value[6]) {
                                $where .= "`" . $value[4] . "`";
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[4]);
                            }

                            $where .= " AND ";

                            if ($value[7]) {
                                $where .= "`" . $value[5] . "`";
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[5]);
                            }
                        } else if ($value[2] == 3) {
                            if (is_array($value[4])) {
                                $idx = 0;
                                $cnt = count($value[4]);
                                foreach ($value[4] as $valueX_) {
                                    if ($value[6]) {
                                        $where .= "`" . $valueX_ . "`";
                                    } else {
                                        $where .= "'%" . $this->connection->safeLikeValue($valueX_) . "%'";
                                    }
                                    ++$idx;
                                    if ($idx < $cnt) {
                                        $where .= " OR `" . $value[1] . "` LIKE ";
                                    }
                                }
                            } else {
                                if ($value[6]) {
                                    $where .= "`" . $value[4] . "`";
                                } else {
                                    $where .= "'%" . $this->connection->safeLikeValue($value[4]) . "%'";
                                }
                            }
                        }


                        if ($value[2] == 3) {
                            if (is_array($value[4])) {
                                $where .= ")";
                            }
                        }

                    }
                }
            }

            return $where;
        }

        function strSelectQuery($query = false, $inCount = false)
        {

            if ($query == false) {

                if (count($this->_select)) {
                    foreach ($this->_select as $key) {
                        if ($query) {
                            $query .= ",`" . $key . "`";
                        } else {
                            $query = "SELECT `" . $key . "`";
                        }
                    }
                } else {
                    foreach ($this->info->fields as $key => &$value) {
                        if ($query) {
                            $query .= ",`" . $key . "`";
                        } else {
                            $query = "SELECT `" . $key . "`";
                        }
                    }
                }

                foreach ($this->_function as $key => &$value) {
                    $query .= "," . $value[0] . "(`" . $key . "`) AS `" . $value[1] . "`";
                }
            }

            $query .= " FROM `" . $this->name . "`";

            $query .= $this->strWhereQuery();

            $group = false;
            foreach ($this->group as $key => $value) {
                if ($value) {
                    if ($group) {
                        $group .= ",`" . $key . "`";
                    } else {
                        $group = "GROUP BY `" . $key . "`";
                    }
                }
            }

            if ($group) {
                $query .= " " . $group;
            }

            if (!$inCount) {
                $order = false;
                foreach ($this->order as $key => $value) {
                    if ($value) {
                        if ($order) {
                            $order .= ",`" . $key . "`";
                        } else {
                            $order = "ORDER BY `" . $key . "`";
                        }

                        if ($value == $this->_order->ascendent) {
                            $order .= " ASC";
                        } else if ($value == $this->_order->descendent) {
                            $order .= " DESC";
                        }
                    }
                }

                if ($order) {
                    $query .= " " . $order;
                }
            }

            return $query;
        }

        function strQueryCode($start = null, $length = null)
        {
            if (!is_null($this->info->primaryKey)) {
                if (is_null($this->table->{$this->info->primaryKey})) {
                    if (
                        ($this->info->fields[$this->info->primaryKey][0] === "int") ||
                        ($this->info->fields[$this->info->primaryKey][0] === "bigint")
                    ) {
                        $this->table->{$this->info->primaryKey} = $this->table->_empty;
                    }
                }
            }
            $query = $this->strSelectQuery();
            if (isset($start)) {
                $query .= " LIMIT " . $start;

                if ($length) {
                    $query .= "," . $length;
                }
            }
            $query .= ";";
            return $query;
        }

        function tryLoadCode($query)
        {
            $this->_loadValid = false;
            $this->result = $this->connection->query($query);
            if ($this->result) {
                $fields = $this->result->fetch_assoc();
                if ($fields) {
                    $this->table->empty();
                    foreach ($fields as $key => $value) {
                        $this->table->$key = $value;
                    }
                    $this->_loadValid = true;
                    return true;
                }
            }
            $this->result = null;
        }

        function loadCode($query)
        {
            $this->_loadValid = false;
            $this->table->empty();
            $this->result = $this->connection->query($query);
            if ($this->result) {
                $fields = $this->result->fetch_assoc();
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $this->table->$key = $value;
                    }
                    $this->_loadValid = true;
                    return true;
                }
            }
            $this->result = null;
            return false;
        }

        public function load($start = null, $length = null)
        {
            return $this->loadCode($this->strQueryCode($start, $length));
        }

        public function tryLoad($start = null, $length = null)
        {
            return $this->tryLoadCode($this->strQueryCode($start, $length));
        }

        public function loadValid()
        {
            return $this->_loadValid;
        }

        public function count()
        {
            $query = $this->strSelectQuery("SELECT COUNT(*)", true);
            return $this->connection->queryValue($query, 0);
        }

        public function hasNext()
        {
            if ($this->result) {
                $this->nextFields = $this->result->fetch_assoc();
                if ($this->nextFields) {
                    return true;
                }
            }
            return false;
        }

        public function loadNext()
        {
            $this->_loadValid = false;
            $this->table->empty();
            if ($this->nextFields) {
                foreach ($this->nextFields as $key => $value) {
                    $this->table->$key = $value;
                }
                $this->nextFields = null;
                $this->_loadValid = true;
                return true;
            }
            if ($this->result) {
                $fields = $this->result->fetch_assoc();
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $this->table->$key = $value;
                    }
                    $this->_loadValid = true;
                    return true;
                }
            }
            return false;
        }

        public function clear($key = false)
        {
            if ($key) {
                if (array_key_exists($key, $this->info->fields)) {
                    $this->table->$key = $this->table->_empty;
                }
                if (array_key_exists($key, $this->group)) {
                    unset($this->group[$key]);
                }
                if (array_key_exists($key, $this->order)) {
                    unset($this->order[$key]);
                }
                if (array_key_exists($key, $this->_function)) {
                    unset($this->_function[$key]);
                }
                return;
            }
            $this->table->empty();
            $this->_loadValid = false;
            $this->group = array();
            $this->order = array();
            $this->_function = array();
            $this->operator = array();
            $this->_select = array();
        }

        public function insert()
        {
            $query = false;
            $queryV = false;
            foreach ($this->info->fields as $key => $value) {
                $value = $this->table->$key;
                if (is_array($value)) {
                    $value = null;
                }
                if ($this->table->isEmpty($key)) {
                    $value = $this->info->fields[$key][1];
                }

                if ($query) {
                    $query .= ",`" . $key . "`";
                } else {
                    $query = "INSERT INTO `" . $this->name . "` (`" . $key . "`";
                }

                if ($queryV) {
                    $queryV .= "," . $this->strQueryValue($key, $value);
                } else {
                    $queryV = "VALUES (" . $this->strQueryValue($key, $value);
                }

            }
            $query .= ") " . $queryV . ");";

            $result = $this->connection->query($query);
            if ($result) {
                if ($this->autoIncrement) {
                    $query = "SELECT LAST_INSERT_ID();";
                    $this->table->{$this->autoIncrement} = $this->connection->queryValue($query, null);
                }
                return true;
            }
            return false;
        }

        public function save()
        {

            if ($this->info->primaryKey) {

                $tablePrimaryKeyValue = $this->table->{$this->info->primaryKey};
                if (is_array($tablePrimaryKeyValue)) {
                    $tablePrimaryKeyValue = null;
                }

                if ($tablePrimaryKeyValue === $this->info->fields[$this->info->primaryKey][1]) {
                    $tablePrimaryKeyValue = null;
                }

                if ($this->table->isEmpty($this->info->primaryKey)) {
                    $tablePrimaryKeyValue = null;
                }

                if ($tablePrimaryKeyValue) {

                    $query = false;

                    foreach ($this->info->fields as $key => $value) {
                        if (is_array($this->table->$key)) {
                            continue;
                        }
                        if ($this->table->isEmpty($key)) {
                            continue;
                        }

                        if ($query) {
                            $query .= ",`" . $key . "`=" . $this->strQueryValue($key, $this->table->$key);
                        } else {
                            $query = "UPDATE `" . $this->name . "` SET `" . $key . "`=" . $this->strQueryValue($key, $this->table->$key);
                        }
                    }

                    $query .= " WHERE `" . $this->info->primaryKey . "`=" . $this->strQueryValue($this->info->primaryKey, $tablePrimaryKeyValue) . ";";


                    $result = $this->connection->query($query);
                    if ($result) {
                        return true;
                    }
                    return false;
                }
            }
            return $this->insert();
        }

        public function delete()
        {
            $query = false;

            if ($this->info->primaryKey) {
                if (!$this->table->isEmpty($this->info->primaryKey)) {
                    $query = "DELETE FROM `" . $this->name . "` WHERE " . $this->strQueryWhereClauseForField($this->info->primaryKey, $this->info->primaryKey) . ";";
                    $result = $this->connection->query($query);
                    if ($result) {
                        return true;
                    }
                    return false;
                }
            }

            foreach ($this->info->fields as $key => $value) {
                if ($this->table->isEmpty($key)) {
                    continue;
                }
                if ($query) {
                    $query .= " AND " . $this->strQueryWhereClauseForField($key, $key);
                } else {
                    $query = "DELETE FROM `" . $this->name . "` WHERE " . $this->strQueryWhereClauseForField($key, $key);
                }
            }

            $query .= ";";
            $result = $this->connection->queryDirect($query);
            if ($result) {
                return true;
            }
            return false;
        }

        public function update($what = array())
        {
            if (count($what)) {

                $query = false;
                foreach ($what as $key => $value) {
                    if (is_array($value)) {
                        continue;
                    }
                    if ($value instanceof EmptyField) {
                        continue;
                    }

                    if ($query) {
                        $query .= ",`" . $key . "`=" . $this->strQueryValue($key, $value);
                    } else {
                        $query = "UPDATE `" . $this->name . "` SET `" . $key . "`=" . $this->strQueryValue($key, $value);
                    }

                }

                $query .= $this->strWhereQuery();

                $result = $this->connection->query($query);
                if ($result) {
                    return true;
                }

            }
            return false;
        }

        public function select($what = array())
        {
            $this->_select = $what;
        }

        public function atomicAdd($field, $value)
        {

            if ($this->info->primaryKey) {

                $tablePrimaryKeyValue = $this->table->{$this->info->primaryKey};
                if (is_array($tablePrimaryKeyValue)) {
                    $tablePrimaryKeyValue = null;
                }
                if ($tablePrimaryKeyValue === $this->info->fields[$this->info->primaryKey][1]) {
                    $tablePrimaryKeyValue = null;
                }
                if ($this->table->isEmpty($this->info->primaryKey)) {
                    $tablePrimaryKeyValue = null;
                }

                if ($tablePrimaryKeyValue) {

                    $query = "BEGIN;";
                    $query .= "SELECT `" . $field . "` FROM `" . $this->name . "` WHERE `" . $this->info->primaryKey . "` = " . $tablePrimaryKeyValue . " FOR UPDATE;";
                    $query .= "UPDATE `" . $this->name . "` SET `" . $field . "` = `" . $field . "`+" . $value . " WHERE `" . $this->info->primaryKey . "` = " . $tablePrimaryKeyValue . ";";
                    $query .= "COMMIT;";

                    $result = $this->connection->multiQuery($query);
                    if ($result) {
                        return true;
                    }
                }
            }

            return false;
        }

        public function atomicIncrement($field)
        {
            return $this->atomicAdd($field, 1);
        }

        public function atomicSub($field, $value)
        {

            if ($this->info->primaryKey) {

                $tablePrimaryKeyValue = $this->table->{$this->info->primaryKey};
                if (is_array($tablePrimaryKeyValue)) {
                    $tablePrimaryKeyValue = null;
                }
                if ($tablePrimaryKeyValue === $this->info->fields[$this->info->primaryKey][1]) {
                    $tablePrimaryKeyValue = null;
                }
                if ($this->table->isEmpty($this->info->primaryKey)) {
                    $tablePrimaryKeyValue = null;
                }

                if ($tablePrimaryKeyValue) {

                    $query = "BEGIN;";
                    $query .= "SELECT `" . $field . "` FROM `" . $this->name . "` WHERE `" . $this->info->primaryKey . "` = " . $tablePrimaryKeyValue . " FOR UPDATE;";
                    $query .= "UPDATE `" . $this->name . "` SET `" . $field . "` = `" . $field . "`-" . $value . " WHERE `" . $this->info->primaryKey . "` = " . $tablePrimaryKeyValue . ";";
                    $query .= "COMMIT;";

                    $result = $this->connection->multiQuery($query);
                    if ($result) {
                        return true;
                    }
                }
            }

            return false;
        }

        public function destroyStorage()
        {
            $query = "DROP TABLE IF EXISTS `" . $this->name . "`;";
            $result = $this->connection->query($query);
            if ($result) {
                return true;
            }
            return false;
        }

        public function createStorage()
        {
            $before = false;
            $query = "CREATE TABLE IF NOT EXISTS `" . $this->name . "` (";
            foreach ($this->info->fields as $key => $value) {

                if ($before) {
                    $query .= ",";
                } else {
                    $before = true;
                }

                $query .= "`" . $key . "` " . strtoupper($value[0]);
                if ($value[0] == "varchar") {
                    if (count($value) > 2) {
                        $query .= "(" . strtoupper($value[2]) . ")";
                    }
                    if (count($value) > 1) {
                        if (!is_null($value[1])) {
                            $query .= " DEFAULT '" . $value[1] . "'";
                        }
                    }
                    continue;
                }

                if (count($value) > 2) {
                    $query .= " " . strtoupper($value[2]);
                }
                if (($value[0] == "int") || ($value[0] == "bigint")) {
                    $query .= " NOT NULL";
                }
                if (count($value) > 3) {
                    if ($value[3] == "autoIncrement") {
                        $query .= " AUTO_INCREMENT";
                    } else {
                        $query .= " " . strtoupper($value[3]);
                    }
                }

                if (count($value) > 1) {
                    if (!(is_null($value[1]) || (strcmp($value[1], "DEFAULT") == 0))) {
                        if (is_int($value[1])) {
                            $query .= " DEFAULT " . $value[1];
                        } else {
                            $query .= " DEFAULT '" . $value[1] . "'";
                        }
                    }
                }
            }

            if ($this->info->primaryKey) {
                if ($before) {
                    $query .= ",";
                } else {
                    $before = true;
                }
                $query .= "PRIMARY KEY(`" . $this->info->primaryKey . "`)";
            }

            $query .= ");";

            $result = $this->connection->query($query);
            if ($result) {
                return $this->createStorageIndex();
            }
            return false;
        }

        public function createStorageIndex()
        {
            if (count($this->info->indexes) == 0) {
                return true;
            }
            foreach ($this->info->indexes as $index) {
                $query = "CREATE INDEX `" . $index . "` ON `" . $this->name . "` (`" . $index . "`)";
                $result = $this->connection->query($query);
                if ($result) {
                    continue;
                }
                return false;
            }
            return true;
        }

        public function recreateStorage()
        {
            $this->destroyStorage();
            return $this->createStorage();
        }

        public function storageRemoveField($name)
        {
            $query = "ALTER TABLE `" . $this->name . "` DROP COLUMN `" . $name . "`;";
            $result = $this->connection->query($query);
            if ($result) {
                return true;
            }
            return false;
        }

        public function storageRenameField($oldName, $newName)
        {
            $query = "ALTER TABLE `" . $this->name . "` CHANGE COLUMN `" . $oldName . "` `" . $newName . "`;";
            $result = $this->connection->query($query);
            if ($result) {
                return true;
            }
            return false;
        }

        public function storageUpdateField($name)
        {
            $query = "ALTER TABLE `" . $this->name . "` MODIFY ";

            $key = $name;
            $value = $this->info->fields[$name];

            $query .= "`" . $key . "` " . strtoupper($value[0]);
            if ($value[0] == "varchar") {
                if (count($value) > 2) {
                    $query .= "(" . strtoupper($value[2]) . ")";
                }
                if (count($value) > 1) {
                    if (!is_null($value[1])) {
                        $query .= " DEFAULT '" . $value[1] . "'";
                    }
                }
                $query .= ";";
                $result = $this->connection->query($query);
                if ($result) {
                    return true;
                }
                return false;
            }

            if (count($value) > 2) {
                $query .= " " . strtoupper($value[2]);
            }
            if (($value[0] == "int") || ($value[0] == "bigint")) {
                $query .= " NOT NULL";
            }
            if (count($value) > 3) {
                if ($value[3] == "autoIncrement") {
                    $query .= " AUTO_INCREMENT";
                } else {
                    $query .= " " . strtoupper($value[3]);
                }
            }

            if (count($value) > 1) {
                if (!(is_null($value[1]) || (strcmp($value[1], "DEFAULT") == 0))) {
                    if (is_int($value[1])) {
                        $query .= " DEFAULT " . $value[1];
                    } else {
                        $query .= " DEFAULT '" . $value[1] . "'";
                    }
                }
            }

            $query .= ";";
            $result = $this->connection->query($query);
            if ($result) {
                return true;
            }
            return false;
        }
    }

}
