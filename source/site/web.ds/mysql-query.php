<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\DataSource\Types\MySQL {

    defined("XYO_WEB") or die("Forbidden");

    class Query
    {

        protected $connection = null;
        protected $query = null;
        protected $_order = null;
        protected $_empty = null;
        protected $info = null;
        protected $operator = null;
        protected $order = null;
        protected $_select = null;

        protected $_function = null;
        protected $group = null;

        protected $result = null;
        protected $nextFields = null;
        protected $autoIncrement = null;
        protected $_loadValid = false;
        protected $_fields = null;
        protected $_base = null;
        protected $_outer = null;
        public function __construct(&$connection, &$query)
        {
            $this->connection = $connection;
            $this->query = $query;
            $this->info = &$query->_class::$_registry[$query->_class];
            $this->_order = &$query->_class::$_order;
            $this->_empty = &$query->_class::$_empty;
            $this->operator = array();
            $this->_select = array();
            $this->_function = array();
            $this->group = array();
            $this->order = array();
            $this->autoIncrement = null;

            $this->result = null;
            $this->nextFields = null;
            $this->_loadValid = false;

            $this->info->base[1]::register();
            foreach ($this->info->outer as $key => $value) {
                $value[0]::register();
            }

            $this->_fields = array();
            $this->_base = array($this->connection->getPrefix() . ($this->info->base[1]::$_registry[$this->info->base[1]])->name, $this->info->base[0] . "_");
            $this->_outer = array();

            $this->prepareFields($this->info->base[0], array($this->info->base[1], $this->info->base[2]));

            foreach ($this->info->outer as $key => &$value) {
                $this->_outer[] = array($this->connection->getPrefix() . ($value[0]::$_registry[$value[0]])->name, $key . "_", "`" . $key . "_`.`" . $value[2][0] . "`=`" . $value[2][1][0] . "_`.`" . $value[2][1][1] . "`");
                $this->prepareFields($key, $value);
            }

        }

        public function prepareFields($key, $value)
        {
            if (is_string($value[1])) {
                $info = $value[0]::$_registry[$value[0]];
                if ($value[1] == "*") {
                    foreach ($info->fields as $fieldAs => $fieldInfo) {
                        $this->_fields[$fieldAs] = array("`" . $key . "_`.`" . $fieldAs . "`", $fieldInfo[0], $fieldInfo[1]);
                    }
                }
                return;
            }
            if (is_array($value[1])) {
                $info = $value[0]::$_registry[$value[0]];
                foreach ($value[1] as $fieldAs => $fieldName) {
                    $this->_fields[$fieldAs] = array("`" . $key . "_`.`" . $fieldName . "`", $info->fields[$fieldName][0], $info->fields[$fieldName][1]);
                }
            }
        }

        public function empty()
        {
            foreach ($this->_fields as $key => &$value) {
                $this->query->$key = $this->_empty;
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
            return $this->connection->safeTypeValue($this->_fields[$key][1], $value);
        }

        function strQueryWhereClauseForFieldValue($fieldAs, $fieldThis, $value)
        {
            return $this->_fields[$fieldAs][0] . "=" . $this->strQueryValue($fieldThis, $value);
        }

        function strQueryWhereClauseForField($fieldAs, $fieldThis)
        {
            $value = $this->query->$fieldThis;
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
            foreach ($this->_fields as $key => $value) {
                if ($this->query->isEmpty($key)) {
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

                        $where .= $this->_fields[$value[1]];
                        if ($value[2] == 1) {
                            if ($value[7]) {
                                $where .= "," . $this->strQueryValue($value[1], $value[5]) . ")";
                            }
                        }

                        $where .= $value[3];

                        if ($value[2] == 0) {
                        } else if ($value[2] == 1) {
                            if ($value[6]) {
                                $where .= $this->_fields[$value[4]][0];
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[4]);
                            }
                        } else if ($value[2] == 2) {
                            if ($value[6]) {
                                $where .= $this->_fields[$value[4]][0];
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[4]);
                            }

                            $where .= " AND ";

                            if ($value[7]) {
                                $where .= $this->_fields[$value[5]][0];
                            } else {
                                $where .= $this->strQueryValue($value[1], $value[5]);
                            }
                        } else if ($value[2] == 3) {
                            if (is_array($value[4])) {
                                $idx = 0;
                                $cnt = count($value[4]);
                                foreach ($value[4] as $valueX_) {
                                    if ($value[6]) {
                                        $where .= $this->_fields[$valueX_][0];
                                    } else {
                                        $where .= "'%" . $this->connection->safeLikeValue($valueX_) . "%'";
                                    }
                                    ++$idx;
                                    if ($idx < $cnt) {
                                        $where .= " OR " . $this->_fields[$value[1]][0] . " LIKE ";
                                    }
                                }
                            } else {
                                if ($value[6]) {
                                    $where .= $this->_fields[$value[4]][0];
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
                            $query .= "," . $this->_fields[$key][0] . " AS `" . $key . "`";
                        } else {
                            $query = "SELECT " . $this->_fields[$key][0] . " AS `" . $key . "`";
                        }
                    }
                } else {
                    foreach ($this->_fields as $key => &$value) {
                        if ($query) {
                            $query .= "," . $value[0] . " AS `" . $key . "`";
                        } else {
                            $query = "SELECT " . $value[0] . " AS `" . $key . "`";
                        }
                    }
                }

                foreach ($this->_function as $key => &$value) {
                    $query .= "," . $value[0] . "(" . $this->_fields[$key][0] . ") AS `" . $value[1] . "`";
                }
            }

            $query .= " FROM `" . $this->_base[0] . "` AS `" . $this->_base[1] . "`";
            foreach ($this->_outer as $key => &$value) {
                $query .= " LEFT OUTER JOIN `" . $value[0] . "` AS `" . $value[1] . "` ON " . $value[2];
            }

            $query .= $this->strWhereQuery();

            $group = false;
            foreach ($this->group as $key => $value) {
                if ($value) {
                    if ($group) {
                        $group .= "," . $this->_fields[$key][0];
                    } else {
                        $group = "GROUP BY " . $this->_fields[$key][0];
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
                            $order .= "," . $this->_fields[$key][0];
                        } else {
                            $order = "ORDER BY " . $this->_fields[$key][0];
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
                    $this->empty();
                    foreach ($fields as $key => $value) {
                        $this->query->$key = $value;
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
            $this->empty();
            $this->result = $this->connection->query($query);
            if ($this->result) {
                $fields = $this->result->fetch_assoc();
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $this->query->$key = $value;
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
            $this->empty();
            if ($this->nextFields) {
                foreach ($this->nextFields as $key => $value) {
                    $this->query->$key = $value;
                }
                $this->nextFields = null;
                $this->_loadValid = true;
                return true;
            }
            if ($this->result) {
                $fields = $this->result->fetch_assoc();
                if ($fields) {
                    foreach ($fields as $key => $value) {
                        $this->query->$key = $value;
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
                    $this->query->$key = $this->query->_empty;
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
            $this->empty();
            $this->_loadValid = false;
            $this->group = array();
            $this->order = array();
            $this->_function = array();
            $this->operator = array();
            $this->_select = array();
        }

    }

}
