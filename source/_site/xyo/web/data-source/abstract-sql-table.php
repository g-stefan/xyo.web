<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

use XYO\Web\DataSource\Order;

// Shared single-table WHERE / SELECT / CRUD logic for the SQL drivers.
// Everything dialect-specific is expressed through a handful of hooks:
//   quoteIdentifier()   `x` / "x" / [x]
//   limitClause()       LIMIT a,b  /  LIMIT b OFFSET a
//   autoIncrementType() AUTO_INCREMENT / SERIAL / AUTOINCREMENT
//   lastInsertIdQuery() how to read the generated id back
// plus the DDL methods, which differ enough per dialect to stay overridden.

abstract class AbstractSQLTable
{
    protected $connection = null;
    protected $table = null;
    protected $info = null;
    protected $name = null;
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

    // Raise a DataSourceException carrying the most recent database error. The
    // query-executing operations (count/insert/save/delete/update/atomic*) use
    // this so a genuine database failure reaches the caller as an exception,
    // exactly like load(), instead of being flattened into a false/0 return.
    // (tryLoad() stays the explicit non-throwing escape hatch.)
    protected function fail($context = "DataSource operation failed")
    {
        throw new \XYO\Web\DataSource\DataSourceException($this->connection->lastError() ?: $context);
    }
    private const FUNCTIONS = ["COUNT", "SUM", "AVG", "MIN", "MAX"];

    // --- dialect hooks

    abstract protected function quoteIdentifier($name);

    abstract protected function limitClause($start, $length);

    abstract protected function autoIncrementType();

    abstract protected function lastInsertIdQuery();

    abstract public function createStorage();

    abstract public function createStorageIndex();

    abstract public function storageRemoveField($name);

    abstract public function storageRenameField($oldName, $newName);

    abstract public function storageUpdateField($name);

    // --- construction

    public function __construct($connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->operator = [];
        $this->_select = [];
        $this->_function = [];
        $this->group = [];
        $this->order = [];
        $this->autoIncrement = null;
        $this->result = null;
        $this->nextFields = null;
        $this->_loadIsValid = false;

        $this->info = new \XYO\Web\DataSource\TableInfo();
        $table::descriptor($this->info);
        $this->name = $this->connection->getPrefix() . $this->info->name;

        foreach ($this->info->fields as $key => $value) {
            if (count($value) > 3) {
                if ($value[3] == "autoIncrement") {
                    $this->autoIncrement = $key;
                }
            }
        }

        $this->empty();

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

    public function setFunctionAs($key, $fn, $as)
    {
        $fn = strtoupper($fn);
        if (!array_key_exists($key, $this->info->fields)) {
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
        return $this->quoteIdentifier($fieldAs) . "=" . $this->strQueryValue($value);
    }

    public function strQueryWhereClauseForField($fieldAs, $fieldThis)
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

                    $where .= $this->quoteIdentifier($value[1]);
                    if ($value[2] == 1) {
                        if ($value[7]) {
                            $where .= "," . $this->strQueryValue($value[5]) . ")";
                        }
                    }

                    $where .= $value[3];

                    if ($value[2] == 0) {
                    } elseif ($value[2] == 1) {
                        if ($value[6]) {
                            $where .= $this->quoteIdentifier($value[4]);
                        } else {
                            $where .= $this->strQueryValue($value[4]);
                        }
                    } elseif ($value[2] == 2) {
                        if ($value[6]) {
                            $where .= $this->quoteIdentifier($value[4]);
                        } else {
                            $where .= $this->strQueryValue($value[4]);
                        }

                        $where .= " AND ";

                        if ($value[7]) {
                            $where .= $this->quoteIdentifier($value[5]);
                        } else {
                            $where .= $this->strQueryValue($value[5]);
                        }
                    } elseif ($value[2] == 3) {
                        if (is_array($value[4])) {
                            $idx = 0;
                            $cnt = count($value[4]);
                            foreach ($value[4] as $valueX_) {
                                if ($value[6]) {
                                    $where .= $this->quoteIdentifier($valueX_);
                                } else {
                                    $where .= "?";
                                    $this->params[] = "%" . addcslashes($valueX_, "%_\\") . "%";
                                }
                                ++$idx;
                                if ($idx < $cnt) {
                                    $where .= " OR " . $this->quoteIdentifier($value[1]) . " LIKE ";
                                }
                            }
                        } else {
                            if ($value[6]) {
                                $where .= $this->quoteIdentifier($value[4]);
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
                    if (array_key_exists($key, $this->info->fields)) {
                        if ($query) {
                            $query .= "," . $this->quoteIdentifier($key);
                        } else {
                            $query = "SELECT " . $this->quoteIdentifier($key);
                        }
                    }
                }
            } else {
                foreach ($this->info->fields as $key => $value) {
                    if ($query) {
                        $query .= "," . $this->quoteIdentifier($key);
                    } else {
                        $query = "SELECT " . $this->quoteIdentifier($key);
                    }
                }
            }

            foreach ($this->_function as $key => $value) {
                $query .= "," . $value[0] . "(" . $this->quoteIdentifier($key) . ") AS " . $this->quoteIdentifier($value[1]);
            }
        }

        $query .= " FROM " . $this->quoteIdentifier($this->name);

        $query .= $this->strWhereQuery();

        $group = false;
        foreach ($this->group as $key => $value) {
            if ($value) {
                if ($group) {
                    $group .= "," . $this->quoteIdentifier($key);
                } else {
                    $group = "GROUP BY " . $this->quoteIdentifier($key);
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
                        $order .= "," . $this->quoteIdentifier($key);
                    } else {
                        $order = "ORDER BY " . $this->quoteIdentifier($key);
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
        if (!is_null($this->info->primaryKey)) {
            if (is_null($this->table->{$this->info->primaryKey})) {
                if (
                    ($this->info->fields[$this->info->primaryKey][0] === "int") ||
                    ($this->info->fields[$this->info->primaryKey][0] === "bigint")
                ) {
                    $this->table->{$this->info->primaryKey} = $this->connection->_empty;
                }
            }
        }
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
                    $this->table->$key = $value;
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
    // caller can tell an error apart from an empty table.
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
                $this->table->$key = $value;
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

    public function empty()
    {
        foreach ($this->info->fields as $key => $value) {
            $this->table->$key = $this->connection->_empty;
        }
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
                $this->table->$key = $value;
            }
            $this->nextFields = null;
            $this->_loadIsValid = true;
            return true;
        }
        if ($this->result) {
            $fields = $this->result->fetch(\PDO::FETCH_ASSOC);
            if ($fields) {
                foreach ($fields as $key => $value) {
                    $this->table->$key = $value;
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
            if (array_key_exists($key, $this->info->fields)) {
                $this->table->$key = $this->connection->_empty;
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

    public function insert()
    {
        $query = false;
        $queryV = false;
        foreach ($this->info->fields as $key => $value) {
            // Let the database assign the auto-increment key when the caller
            // left it empty. Omitting the column (rather than inserting NULL)
            // is the form that works across all dialects: a PostgreSQL SERIAL
            // is declared NOT NULL and rejects an explicit NULL, whereas
            // SQLite/MySQL only auto-assign on NULL or on omission.
            if (($key === $this->autoIncrement) && $this->table->isEmpty($key)) {
                continue;
            }

            $value = $this->table->$key;
            if (is_array($value)) {
                $value = null;
            }
            if ($this->table->isEmpty($key)) {
                $value = $this->info->fields[$key][1];
                if ($value === "DEFAULT") {
                    continue;
                }
            }

            if ($query) {
                $query .= "," . $this->quoteIdentifier($key);
            } else {
                $query = "INSERT INTO " . $this->quoteIdentifier($this->name) . " (" . $this->quoteIdentifier($key);
            }

            if ($queryV) {
                $queryV .= "," . $this->strQueryValue($value);
            } else {
                $queryV = "VALUES (" . $this->strQueryValue($value);
            }

        }
        $query .= ") " . $queryV . ");";

        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result === null) {
            $this->fail("DataSource insert failed");
        }
        if ($this->autoIncrement) {
            $this->table->{$this->autoIncrement} = $this->connection->queryValue($this->lastInsertIdQuery(), null);
        }
        return true;
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
                        $query .= "," . $this->quoteIdentifier($key) . "=" . $this->strQueryValue($this->table->$key);
                    } else {
                        $query = "UPDATE " . $this->quoteIdentifier($this->name) . " SET " . $this->quoteIdentifier($key) . "=" . $this->strQueryValue($this->table->$key);
                    }
                }

                $query .= " WHERE " . $this->quoteIdentifier($this->info->primaryKey) . "=" . $this->strQueryValue($tablePrimaryKeyValue) . ";";

                $result = $this->connection->queryPrepare($query, $this->getQueryParams());
                if ($result === null) {
                    $this->fail("DataSource save failed");
                }
                return true;
            }
        }
        return $this->insert();
    }

    public function delete()
    {
        $query = false;

        if ($this->info->primaryKey) {
            if (!$this->table->isEmpty($this->info->primaryKey)) {
                $query = "DELETE FROM " . $this->quoteIdentifier($this->name) . " WHERE " . $this->strQueryWhereClauseForField($this->info->primaryKey, $this->info->primaryKey) . ";";
                $result = $this->connection->queryPrepare($query, $this->getQueryParams());
                if ($result === null) {
                    $this->fail("DataSource delete failed");
                }
                return true;
            }
        }

        foreach ($this->info->fields as $key => $value) {
            if ($this->table->isEmpty($key)) {
                continue;
            }
            if ($query) {
                $query .= " AND " . $this->strQueryWhereClauseForField($key, $key);
            } else {
                $query = "DELETE FROM " . $this->quoteIdentifier($this->name) . " WHERE " . $this->strQueryWhereClauseForField($key, $key);
            }
        }

        $query .= ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result === null) {
            $this->fail("DataSource delete failed");
        }
        return true;
    }

    public function update($what = [])
    {
        if (count($what)) {

            $query = false;
            foreach ($what as $key => $value) {
                if (!array_key_exists($key, $this->info->fields)) {
                    return false;
                }
                if (is_array($value)) {
                    continue;
                }
                if ($value instanceof \XYO\Web\DataSource\EmptyField) {
                    continue;
                }

                if ($query) {
                    $query .= "," . $this->quoteIdentifier($key) . "=" . $this->strQueryValue($value);
                } else {
                    $query = "UPDATE " . $this->quoteIdentifier($this->name) . " SET " . $this->quoteIdentifier($key) . "=" . $this->strQueryValue($value);
                }

            }

            $query .= $this->strWhereQuery();

            $result = $this->connection->queryPrepare($query, $this->getQueryParams());
            if ($result === null) {
                $this->fail("DataSource update failed");
            }
            return true;

        }
        // No fields to update (empty $what) is a caller precondition, not a
        // database error, so it stays a false return.
        return false;
    }

    public function select($what = [])
    {
        $this->_select = $what;
    }

    public function atomicAdd($field, $value)
    {
        if (!array_key_exists($field, $this->info->fields)) {
            return false;
        }
        $value = max(0, intval($value));

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
                $tablePrimaryKeyValue = max(0, intval($this->table->{$this->info->primaryKey}));
            }

            if ($tablePrimaryKeyValue) {

                if (!$this->connection->beginTransaction()) {
                    $this->fail("DataSource atomicAdd failed");
                }

                $queryUpdate = "UPDATE " . $this->quoteIdentifier($this->name) . " SET " . $this->quoteIdentifier($field) . " = " . $this->quoteIdentifier($field) . " + ? WHERE " . $this->quoteIdentifier($this->info->primaryKey) . " = ?;";

                if ($this->connection->queryPrepare($queryUpdate, [$value, $tablePrimaryKeyValue])) {
                    if ($this->connection->commit()) {
                        return true;
                    }
                }

                $this->connection->rollBack();
                $this->fail("DataSource atomicAdd failed");
            }
        }

        // No usable primary-key value is a caller precondition, not an error.
        return false;
    }

    public function atomicIncrement($field)
    {
        return $this->atomicAdd($field, 1);
    }

    public function atomicSub($field, $value)
    {
        if (!array_key_exists($field, $this->info->fields)) {
            return false;
        }
        $value = max(0, intval($value));

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
                $tablePrimaryKeyValue = max(0, intval($this->table->{$this->info->primaryKey}));
            }

            if ($tablePrimaryKeyValue) {

                if (!$this->connection->beginTransaction()) {
                    $this->fail("DataSource atomicSub failed");
                }

                $queryUpdate = "UPDATE " . $this->quoteIdentifier($this->name) . " SET " . $this->quoteIdentifier($field) . " = " . $this->quoteIdentifier($field) . " - ? WHERE " . $this->quoteIdentifier($this->info->primaryKey) . " = ?;";

                if ($this->connection->queryPrepare($queryUpdate, [$value, $tablePrimaryKeyValue])) {
                    if ($this->connection->commit()) {
                        return true;
                    }
                }

                $this->connection->rollBack();
                $this->fail("DataSource atomicSub failed");
            }
        }

        // No usable primary-key value is a caller precondition, not an error.
        return false;
    }

    public function destroyStorage()
    {
        $query = "DROP TABLE IF EXISTS " . $this->quoteIdentifier($this->name) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function recreateStorage()
    {
        $this->destroyStorage();
        return $this->createStorage();
    }

    // Schema-reconciliation helpers. They depend heavily on dialect-specific
    // catalog access (SQLite uses PRAGMA + a full table rebuild; MySQL and
    // PostgreSQL use information_schema + ALTER), so each driver overrides
    // them. Any future driver that does not inherits these guards: the call
    // fails loudly instead of silently reporting the wrong thing.

    // Return true when the live schema already matches the descriptor.
    public function storageCheckTable()
    {
        throw new \XYO\Web\DataSource\DataSourceException("storageCheckTable() is not implemented for this driver");
    }

    // Bring the live schema in line with the descriptor (create if missing,
    // otherwise apply column/index differences). A backup is the caller's
    // responsibility.
    public function storageUpdateTable()
    {
        throw new \XYO\Web\DataSource\DataSourceException("storageUpdateTable() is not implemented for this driver");
    }
}
