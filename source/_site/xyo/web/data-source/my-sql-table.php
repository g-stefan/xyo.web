<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized MySQL Driver

class MySQLTable extends \XYO\Web\DataSource\AbstractSQLTable
{
    protected function quoteIdentifier($name)
    {
        return "`" . str_replace("`", "``", $name) . "`";
    }

    protected function limitClause($start, $length)
    {
        $start = max(0, intval($start));
        $clause = " LIMIT " . $start;
        if ($length) {
            $length = max(0, intval($length));
            $clause .= "," . $length;
        }
        return $clause;
    }

    protected function autoIncrementType()
    {
        return "AUTO_INCREMENT";
    }

    protected function lastInsertIdQuery()
    {
        return "SELECT LAST_INSERT_ID();";
    }

    // The DDL fragment for one column, without a leading comma and without the
    // PRIMARY KEY clause (which MySQL declares separately). Shared by
    // createStorage(), storageUpdateField() (MODIFY) and storageUpdateTable()
    // (ADD COLUMN) so every code path emits an identical definition.
    protected function buildColumnDefinition($key, $value)
    {
        $def = $this->quoteIdentifier($key) . " " . strtoupper($value[0]);

        if ($value[0] == "varchar") {
            if (count($value) > 2) {
                $def .= "(" . strtoupper($value[2]) . ")";
            }
            if (count($value) > 1) {
                if (!is_null($value[1])) {
                    $def .= " DEFAULT '" . addcslashes($value[1], "'\\") . "'";
                }
            }
            return $def;
        }

        if (count($value) > 2) {
            $def .= " " . strtoupper($value[2]);
        }
        if (($value[0] == "int") || ($value[0] == "bigint")) {
            $def .= " NOT NULL";
        }
        if (count($value) > 3) {
            if ($value[3] == "autoIncrement") {
                $def .= " " . $this->autoIncrementType();
            } else {
                $def .= " " . strtoupper($value[3]);
            }
        }
        if (count($value) > 1) {
            if (!(is_null($value[1]) || ($value[1] === "DEFAULT"))) {
                if (is_int($value[1])) {
                    $def .= " DEFAULT " . $value[1];
                } else {
                    $def .= " DEFAULT '" . addcslashes($value[1], "'\\") . "'";
                }
            }
        }

        return $def;
    }

    public function createStorage()
    {
        $before = false;
        $query = "CREATE TABLE IF NOT EXISTS " . $this->quoteIdentifier($this->name) . " (";
        foreach ($this->info->fields as $key => $value) {
            if ($before) {
                $query .= ",";
            } else {
                $before = true;
            }
            $query .= $this->buildColumnDefinition($key, $value);
        }

        if ($this->info->primaryKey) {
            if ($before) {
                $query .= ",";
            }
            $query .= "PRIMARY KEY(" . $this->quoteIdentifier($this->info->primaryKey) . ")";
        }

        $query .= ");";

        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
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
            $query = "SHOW INDEX FROM " . $this->quoteIdentifier($this->name) . " WHERE " . $this->quoteIdentifier("Key_name") . "='" . addcslashes($index, "'\\") . "';";
            $result = $this->connection->queryPrepare($query, $this->getQueryParams());
            if ($result) {
                $data = $result->fetch(\PDO::FETCH_NUM);
                if ($data) {
                    continue;
                }
            }
            $query = "CREATE INDEX " . $this->quoteIdentifier($index) . " ON " . $this->quoteIdentifier($this->name) . " (" . $this->quoteIdentifier($index) . ")";
            $result = $this->connection->queryPrepare($query, $this->getQueryParams());
            if ($result) {
                continue;
            }
            return false;
        }
        return true;
    }

    public function storageRemoveField($name)
    {
        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " DROP COLUMN " . $this->quoteIdentifier($name) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function storageRenameField($oldName, $newName)
    {
        // ALTER ... RENAME COLUMN needs MySQL 8.0+ / MariaDB 10.5+. For older
        // servers the portable form is CHANGE COLUMN <old> <full-definition>.
        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " RENAME COLUMN " . $this->quoteIdentifier($oldName) . " TO " . $this->quoteIdentifier($newName) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function storageUpdateField($name)
    {
        if (!array_key_exists($name, $this->info->fields)) {
            return false;
        }
        // MySQL changes a column's type/default in place with ALTER ... MODIFY.
        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " MODIFY " . $this->buildColumnDefinition($name, $this->info->fields[$name]) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    // True when a base table with this name exists in the current schema.
    protected function tableExists()
    {
        $stmt = $this->connection->queryPrepare(
            "SELECT 1 FROM " . $this->quoteIdentifier("information_schema") . "." . $this->quoteIdentifier("TABLES") .
            " WHERE " . $this->quoteIdentifier("TABLE_SCHEMA") . "=DATABASE() AND " . $this->quoteIdentifier("TABLE_NAME") . "=? LIMIT 1;",
            [$this->name]
        );
        if ($stmt) {
            return (bool) $stmt->fetch(\PDO::FETCH_NUM);
        }
        return false;
    }

    // Map of live column name => COLUMN_TYPE (e.g. "int(11)", "varchar(64)").
    protected function readColumns()
    {
        $columns = [];
        $stmt = $this->connection->queryPrepare(
            "SELECT " . $this->quoteIdentifier("COLUMN_NAME") . "," . $this->quoteIdentifier("COLUMN_TYPE") .
            " FROM " . $this->quoteIdentifier("information_schema") . "." . $this->quoteIdentifier("COLUMNS") .
            " WHERE " . $this->quoteIdentifier("TABLE_SCHEMA") . "=DATABASE() AND " . $this->quoteIdentifier("TABLE_NAME") . "=?;",
            [$this->name]
        );
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
                $columns[$row[0]] = $row[1];
            }
        }
        return $columns;
    }

    // Canonical type form for comparison. MySQL 8.0.17+ drops integer display
    // widths while MariaDB keeps them (int(11), bigint(20)); strip the width so
    // the two report the same base type.
    protected function normalizeType($type)
    {
        $type = strtolower(trim((string) $type));
        $type = preg_replace("/\\b(tinyint|smallint|mediumint|int|integer|bigint)\\s*\\(\\s*\\d+\\s*\\)/", "$1", $type);
        $type = str_replace("integer", "int", $type);
        $type = preg_replace("/\\s+/", " ", $type);
        return trim($type);
    }

    // The base column type the descriptor implies, before normalization.
    protected function expectedColumnType($value)
    {
        $type = strtolower($value[0]);
        if ($type === "varchar") {
            if (count($value) > 2) {
                return "varchar(" . intval($value[2]) . ")";
            }
            return "varchar";
        }
        if ($type === "integer") {
            return "int";
        }
        return $type;
    }

    // Index names the descriptor expects. The MySQL driver names each index
    // after its field (index names are scoped to the table in MySQL).
    protected function expectedIndexNames()
    {
        $names = [];
        foreach ($this->info->indexes as $index) {
            $names[] = $index;
        }
        return $names;
    }

    // Live secondary index names (the PRIMARY KEY index is excluded).
    protected function currentIndexNames()
    {
        $names = [];
        $stmt = $this->connection->queryPrepare(
            "SELECT DISTINCT " . $this->quoteIdentifier("INDEX_NAME") .
            " FROM " . $this->quoteIdentifier("information_schema") . "." . $this->quoteIdentifier("STATISTICS") .
            " WHERE " . $this->quoteIdentifier("TABLE_SCHEMA") . "=DATABASE() AND " . $this->quoteIdentifier("TABLE_NAME") . "=? AND " . $this->quoteIdentifier("INDEX_NAME") . "<>'PRIMARY';",
            [$this->name]
        );
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
                $names[] = $row[0];
            }
        }
        return $names;
    }

    // Drop indexes the descriptor no longer declares and create the missing
    // ones. Columns are left untouched.
    protected function reconcileIndexes()
    {
        $expected = $this->expectedIndexNames();
        foreach ($this->currentIndexNames() as $name) {
            if (!in_array($name, $expected, true)) {
                if (!$this->connection->queryPrepare("DROP INDEX " . $this->quoteIdentifier($name) . " ON " . $this->quoteIdentifier($this->name) . ";")) {
                    return false;
                }
            }
        }
        return $this->createStorageIndex();
    }

    public function storageCheckTable()
    {
        if (!$this->tableExists()) {
            return false;
        }

        $actual = $this->readColumns();

        $actualNames = array_keys($actual);
        $expectedNames = array_keys($this->info->fields);
        sort($actualNames);
        sort($expectedNames);
        if ($actualNames !== $expectedNames) {
            return false;
        }

        // Compare the base type of every column. Defaults and attributes
        // (NOT NULL, charset, UNSIGNED, ...) are intentionally not compared:
        // their information_schema representation differs between MySQL and
        // MariaDB and across versions.
        foreach ($this->info->fields as $key => $value) {
            if ($this->normalizeType($actual[$key]) !== $this->normalizeType($this->expectedColumnType($value))) {
                return false;
            }
        }

        $actualIdx = $this->currentIndexNames();
        $expectedIdx = $this->expectedIndexNames();
        sort($actualIdx);
        sort($expectedIdx);
        return $actualIdx === $expectedIdx;
    }

    public function storageUpdateTable()
    {
        if (!$this->tableExists()) {
            return $this->createStorage();
        }

        $actual = $this->readColumns();

        // Add columns the descriptor introduced and retype the ones that drifted.
        foreach ($this->info->fields as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " ADD COLUMN " . $this->buildColumnDefinition($key, $value) . ";";
                if (!$this->connection->queryPrepare($query, $this->getQueryParams())) {
                    return false;
                }
                continue;
            }
            if ($this->normalizeType($actual[$key]) !== $this->normalizeType($this->expectedColumnType($value))) {
                if (!$this->storageUpdateField($key)) {
                    return false;
                }
            }
        }

        // Drop columns the descriptor no longer declares.
        foreach (array_keys($actual) as $name) {
            if (!array_key_exists($name, $this->info->fields)) {
                if (!$this->storageRemoveField($name)) {
                    return false;
                }
            }
        }

        return $this->reconcileIndexes();
    }
}
