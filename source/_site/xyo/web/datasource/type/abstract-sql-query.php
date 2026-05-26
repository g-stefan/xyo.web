<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type;

defined("XYO_WEB") or die("Forbidden");

use XYO\Web\DataSource\Order;

// Shared multi-table (joined) read logic for the SQL drivers.
// Dialect specifics are limited to:
//   quoteIdentifier()  `x` / "x" / [x]
//   limitClause()      LIMIT a,b  /  LIMIT b OFFSET a

abstract class AbstractSQLQuery
{
    protected $connection = null;
    protected $query = null;    
    protected $info = null;
    protected $infoList = null;
    protected $operator = null;
    protected $order = null;
    protected $_select = null;

    protected $_function = null;
    protected $group = null;

    protected $result = null;
    protected $nextFields = null;
    protected $autoIncrement = null;
    protected $_loadIsValid = false;
    protected $params = [];
    public function getQueryParams()
    {
        $p = $this->params;
        $this->params = [];
        return $p;
    }

    // Raise a DataSourceException carrying the most recent database error, so a
    // genuine database failure reaches the caller as an exception (like load())
    // instead of being flattened into a false/0 return. tryLoad() stays the
    // explicit non-throwing escape hatch.
    protected function fail($context = "DataSource operation failed")
    {
        throw new \XYO\Web\DataSource\DataSourceException($this->connection->lastError() ?: $context);
    }
    private const FUNCTIONS = ["COUNT", "SUM", "AVG", "MIN", "MAX"];

    protected $_fields = null;
    protected $_base = null;
    protected $_outer = null;

    // --- dialect hooks

    abstract protected function quoteIdentifier($name);

    abstract protected function limitClause($start, $length);

    // alias "." field, both quoted for the active dialect
    protected function qualifiedField($alias, $field)
    {
        return $this->quoteIdentifier($alias) . "." . $this->quoteIdentifier($field);
    }

    // --- construction

    public function __construct($connection, $query)
    {
        $this->connection = $connection;
        $this->query = $query;        
        $this->operator = [];
        $this->_select = [];
        $this->_function = [];
        $this->group = [];
        $this->order = [];
        $this->autoIncrement = null;

        $this->result = null;
        $this->nextFields = null;
        $this->_loadIsValid = false;

        $this->infoList = [];

        $this->info = new \XYO\Web\DataSource\QueryInfo();
        $query::descriptor($this->info);

        $this->infoList[$this->info->base[1]] = new \XYO\Web\DataSource\TableInfo();
        $this->info->base[1]::descriptor($this->infoList[$this->info->base[1]]);
        foreach ($this->info->outer as $key => $value) {
            $this->infoList[$value[0]] = new \XYO\Web\DataSource\TableInfo();
            $value[0]::descriptor($this->infoList[$value[0]]);
        }

        $this->_fields = [];
        $this->_base = [$this->connection->getPrefix() . ($this->infoList[$this->info->base[1]])->name, $this->info->base[0] . "_"];
        $this->_outer = [];

        $this->prepareFields($this->info->base[0], [$this->info->base[1], $this->info->base[2]]);

        foreach ($this->info->outer as $key => $value) {
            $this->_outer[] = [
                $this->connection->getPrefix() . ($this->infoList[$value[0]])->name,
                $key . "_",
                $this->qualifiedField($key . "_", $value[2][0]) . "=" . $this->qualifiedField($value[2][1][0] . "_", $value[2][1][1])
            ];
            $this->prepareFields($key, $value);
        }

        $this->empty();

    }

    public function prepareFields($key, $value)
    {
        if (is_string($value[1])) {
            $info = $this->infoList[$value[0]];
            if ($value[1] == "*") {
                foreach ($info->fields as $fieldAs => $fieldInfo) {
                    $this->_fields[$fieldAs] = [$this->qualifiedField($key . "_", $fieldAs), $fieldInfo[0], $fieldInfo[1]];
                }
            }
            return;
        }
        if (is_array($value[1])) {
            $info = $this->infoList[$value[0]];
            foreach ($value[1] as $fieldAs => $fieldName) {
                $this->_fields[$fieldAs] = [$this->qualifiedField($key . "_", $fieldName), $info->fields[$fieldName][0], $info->fields[$fieldName][1]];
            }
        }
    }

    public function empty()
    {
        foreach ($this->_fields as $key => $value) {
            $this->query->$key = $this->connection->_empty;
        }
    }

    public function setOrder($key, $value)
    {
        if (array_key_exists($key, $this->_fields)) {
            $this->order[$key] = $value;
        }
    }

    public function setGroup($key, $value)
    {
        if (array_key_exists($key, $this->_fields)) {
            $this->group[$key] = $value;
        }
    }

    public function setFunctionAs($key, $fn, $as)
    {
        $fn = strtoupper($fn);
        if (!array_key_exists($key, $this->_fields)) {
            return false;
        }
        if (!in_array($fn, self::FUNCTIONS, true)) {
            return false;
        }
        if (!preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $as)) {
            return false;
        }
        $this->_function[$key] = [$fn, $as];
        return true;
    }

    public function pushOperator($mode)
    {
        $opList1 = [
            "and" => " AND ",
            "or" => " OR "
        ];

        $opList2 = [
            "(" => "(",
            ")" => ")"
        ];

        if (array_key_exists($mode, $opList1)) {
            $idx = count($this->operator);
            if ($idx) {
                if (in_array($this->operator[$idx - 1][1], $opList1)) {
                    $idx = $idx - 1;
                }
            }
            $this->operator[$idx] = [0 => 1, 1 => $opList1[$mode]];
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
            $this->operator[$idx] = [0 => 1, 1 => $opList2[$mode]];
        }
    }

    public function setOperator($key, $operator, $v1 = null, $v2 = null, $v1x = false, $v2x = false)
    {
        if (!array_key_exists($key, $this->_fields)) {
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

        $opList = [
            "between" => [2, " BETWEEN "],
            "not-between" => [2, " NOT BETWEEN "],
            "is-null" => [0, " IS NULL "],
            "is-not-null" => [0, " IS NOT NULL "],
            "=" => [1, " = "],
            "<" => [1, " < "],
            ">" => [1, " > "],
            "<=" => [1, " <= "],
            ">=" => [1, " >= "],
            "!=" => [1, " != "],
            "like" => [3, " LIKE "]
        ];
        if (array_key_exists($operator, $opList)) {
            $this->operator[$idx] = [0 => 0, 1 => $key, 2 => $opList[$operator][0], 3 => $opList[$operator][1], 4 => $v1, 5 => $v2, 6 => $v1x, 7 => $v2x];
        }
    }

    public function strQueryValue($value)
    {
        $this->params[] = $value;
        return "?";
    }

    public function strQueryWhereClauseForFieldValue($fieldAs, $value)
    {
        return $this->_fields[$fieldAs][0] . "=" . $this->strQueryValue($value);
    }

    public function strQueryWhereClauseForField($fieldAs, $fieldThis)
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
                        $x .= " OR " . $this->strQueryWhereClauseForFieldValue($fieldAs, $v);
                    } else {
                        $x = $this->strQueryWhereClauseForFieldValue($fieldAs, $v);
                    }
                }

                $where .= $x;
                $where .= ")";
                return $where;
            }
        }

        return $this->strQueryWhereClauseForFieldValue($fieldAs, $value);
    }

    public function strWhereQuery()
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
                        // Starting the WHERE clause on a grouping token: a
                        // dangling AND/OR is dropped, but a leading "(" must be
                        // kept or the parentheses end up unbalanced.
                        $where = " WHERE ";
                        if ($value[1] === "(") {
                            $where .= "(";
                        }
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

                    $where .= $this->_fields[$value[1]][0];
                    if ($value[2] == 1) {
                        if ($value[7]) {
                            $where .= "," . $this->strQueryValue($value[5]) . ")";
                        }
                    }

                    $where .= $value[3];

                    if ($value[2] == 0) {
                    } elseif ($value[2] == 1) {
                        if ($value[6]) {
                            $where .= $this->_fields[$value[4]][0];
                        } else {
                            $where .= $this->strQueryValue($value[4]);
                        }
                    } elseif ($value[2] == 2) {
                        if ($value[6]) {
                            $where .= $this->_fields[$value[4]][0];
                        } else {
                            $where .= $this->strQueryValue($value[4]);
                        }

                        $where .= " AND ";

                        if ($value[7]) {
                            $where .= $this->_fields[$value[5]][0];
                        } else {
                            $where .= $this->strQueryValue($value[5]);
                        }
                    } elseif ($value[2] == 3) {
                        if (is_array($value[4])) {
                            $idx = 0;
                            $cnt = count($value[4]);
                            foreach ($value[4] as $valueX_) {
                                if ($value[6]) {
                                    $where .= $this->_fields[$valueX_][0];
                                } else {
                                    $where .= "?";
                                    $this->params[] = "%" . addcslashes($valueX_, "%_\\") . "%";
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
                                $where .= "?";
                                $this->params[] = "%" . addcslashes($value[4], "%_\\") . "%";
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

    public function strSelectQuery($query = false, $inCount = false)
    {

        if ($query == false) {

            if (count($this->_select)) {
                foreach ($this->_select as $key) {
                    if ($query) {
                        $query .= "," . $this->_fields[$key][0] . " AS " . $this->quoteIdentifier($key);
                    } else {
                        $query = "SELECT " . $this->_fields[$key][0] . " AS " . $this->quoteIdentifier($key);
                    }
                }
            } else {
                foreach ($this->_fields as $key => $value) {
                    if ($query) {
                        $query .= "," . $value[0] . " AS " . $this->quoteIdentifier($key);
                    } else {
                        $query = "SELECT " . $value[0] . " AS " . $this->quoteIdentifier($key);
                    }
                }
            }

            foreach ($this->_function as $key => $value) {
                $query .= "," . $value[0] . "(" . $this->_fields[$key][0] . ") AS " . $this->quoteIdentifier($value[1]);
            }
        }

        $query .= " FROM " . $this->quoteIdentifier($this->_base[0]) . " AS " . $this->quoteIdentifier($this->_base[1]);
        foreach ($this->_outer as $key => $value) {
            $query .= " LEFT OUTER JOIN " . $this->quoteIdentifier($value[0]) . " AS " . $this->quoteIdentifier($value[1]) . " ON " . $value[2];
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

                    if ($value == Order::ASCENDENT) {
                        $order .= " ASC";
                    } elseif ($value == Order::DESCENDENT) {
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

    public function strQueryCode($start = null, $length = null)
    {
        $query = $this->strSelectQuery();
        if (isset($start)) {
            $query .= $this->limitClause($start, $length);
        }
        $query .= ";";
        return $query;
    }

    // Non-throwing variant: returns false on both a database error and an
    // empty result. Use loadCode()/load() when an error must be told apart
    // from an empty result.
    public function tryLoadCode($query)
    {
        $this->_loadIsValid = false;
        $this->result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($this->result) {
            $fields = $this->result->fetch(\PDO::FETCH_ASSOC);
            if ($fields) {
                $this->empty();
                foreach ($fields as $key => $value) {
                    $this->query->$key = $value;
                }
                $this->_loadIsValid = true;
                return true;
            }
        }
        $this->result = null;
        return false;
    }

    // Throws DataSourceException when the query fails at the database level;
    // returns false only when the query succeeded but matched no rows, so the
    // caller can tell an error apart from an empty result.
    public function loadCode($query)
    {
        $this->_loadIsValid = false;
        $this->empty();
        $this->result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($this->result === null) {
            $this->fail("DataSource load failed");
        }
        $fields = $this->result->fetch(\PDO::FETCH_ASSOC);
        if ($fields) {
            foreach ($fields as $key => $value) {
                $this->query->$key = $value;
            }
            $this->_loadIsValid = true;
            return true;
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

    public function loadIsValid()
    {
        return $this->_loadIsValid;
    }

    public function count()
    {
        $query = $this->strSelectQuery("SELECT COUNT(*)", true);
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result === null) {
            $this->fail("DataSource count failed");
        }
        $data = $result->fetch(\PDO::FETCH_NUM);
        if ($data) {
            return $data[0];
        }
        return 0;
    }

    public function loadHasNext()
    {
        if ($this->result) {
            $this->nextFields = $this->result->fetch(\PDO::FETCH_ASSOC);
            if ($this->nextFields) {
                return true;
            }
        }
        return false;
    }

    public function loadNext()
    {
        $this->_loadIsValid = false;
        $this->empty();
        if ($this->nextFields) {
            foreach ($this->nextFields as $key => $value) {
                $this->query->$key = $value;
            }
            $this->nextFields = null;
            $this->_loadIsValid = true;
            return true;
        }
        if ($this->result) {
            $fields = $this->result->fetch(\PDO::FETCH_ASSOC);
            if ($fields) {
                foreach ($fields as $key => $value) {
                    $this->query->$key = $value;
                }
                $this->_loadIsValid = true;
                return true;
            }
        }
        return false;
    }

    public function clear($key = false)
    {
        if ($key) {
            if (array_key_exists($key, $this->_fields)) {
                $this->query->$key = $this->connection->_empty;
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
        $this->_loadIsValid = false;
        $this->group = [];
        $this->order = [];
        $this->_function = [];
        $this->operator = [];
        $this->_select = [];
        $this->params = [];
    }

}
