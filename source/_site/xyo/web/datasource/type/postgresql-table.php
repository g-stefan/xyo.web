<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type\PostgreSQL;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized PostgreSQL Driver

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/abstract-sql-table.php");

class Table extends \XYO\Web\DataSource\Type\AbstractSQLTable
{
    protected function quoteIdentifier($name)
    {
        return "\"" . str_replace("\"", "\"\"", $name) . "\"";
    }

    protected function limitClause($start, $length)
    {
        $start = max(0, intval($start));
        if ($length) {
            $length = max(0, intval($length));
            return " LIMIT " . $length . " OFFSET " . $start;
        }
        return " LIMIT " . $start;
    }

    protected function autoIncrementType()
    {
        return "SERIAL";
    }

    protected function lastInsertIdQuery()
    {
        return "SELECT CURRVAL(pg_get_serial_sequence('" . $this->name . "','" . $this->autoIncrement . "'));";
    }

    // The PostgreSQL type the descriptor field maps onto (without constraints).
    protected function pgType($value)
    {
        $type = strtolower($value[0]);
        if ($type === "varchar") {
            return "VARCHAR" . ((count($value) > 2) ? "(" . intval($value[2]) . ")" : "");
        }
        if ($type === "int") {
            return "INTEGER";
        }
        if ($type === "bigint") {
            return "BIGINT";
        }
        if ($type === "datetime") {
            return "TIMESTAMP";
        }
        return strtoupper($type);
    }

    // The DDL fragment for one column, without a leading comma and without the
    // PRIMARY KEY clause (declared separately). Shared by createStorage() and
    // storageUpdateTable() (ADD COLUMN).
    protected function buildColumnDefinition($key, $value)
    {
        // Auto-increment integer keys use SERIAL/BIGSERIAL, which already imply
        // NOT NULL plus a sequence-backed default — no extra constraints.
        if ((count($value) > 3) && ($value[3] === "autoIncrement")) {
            $serial = ($value[0] === "bigint") ? "BIGSERIAL" : "SERIAL";
            return $this->quoteIdentifier($key) . " " . $serial;
        }

        if ($value[0] === "varchar") {
            $def = $this->quoteIdentifier($key) . " VARCHAR";
            if (count($value) > 2) {
                $def .= "(" . intval($value[2]) . ")";
            }
            if (count($value) > 1) {
                if (!is_null($value[1])) {
                    $def .= " DEFAULT '" . addcslashes($value[1], "'\\") . "'";
                }
            }
            return $def;
        }

        if ($value[0] === "datetime") {
            $def = $this->quoteIdentifier($key) . " TIMESTAMP";
            if ((count($value) > 1) && !(is_null($value[1]) || ($value[1] === "DEFAULT"))) {
                $def .= " DEFAULT '" . addcslashes($value[1], "'\\") . "'";
            }
            return $def;
        }

        $def = $this->quoteIdentifier($key) . " " . strtoupper($value[0]);
        if ((count($value) > 2) && !is_null($value[2])) {
            $def .= " " . strtoupper($value[2]);
        }
        if (($value[0] === "int") || ($value[0] === "bigint")) {
            $def .= " NOT NULL";
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
        if ($this->tableExists()) {
            return true;
        }

        $before = false;
        $query = "CREATE TABLE " . $this->quoteIdentifier($this->name) . " (";
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
            $query = "CREATE INDEX IF NOT EXISTS " . $this->quoteIdentifier($index) . " ON " . $this->quoteIdentifier($this->name) . " (" . $this->quoteIdentifier($index) . ")";
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
        // PostgreSQL drops any index that referenced the column along with it.
        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " DROP COLUMN " . $this->quoteIdentifier($name) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function storageRenameField($oldName, $newName)
    {
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
        $value = $this->info->fields[$name];

        // SERIAL/BIGSERIAL columns are sequence-backed; their type and default
        // must not be rewritten through this path.
        if ((count($value) > 3) && ($value[3] === "autoIncrement")) {
            return true;
        }

        $tbl = $this->quoteIdentifier($this->name);
        $col = $this->quoteIdentifier($name);
        $type = $this->pgType($value);

        // PostgreSQL changes a column's type with a dedicated sub-command and
        // an explicit USING cast (it will not implicitly coerce, e.g. int->text).
        $query = "ALTER TABLE " . $tbl . " ALTER COLUMN " . $col . " TYPE " . $type . " USING " . $col . "::" . $type . ";";
        if (!$this->connection->queryPrepare($query, $this->getQueryParams())) {
            return false;
        }

        // Default: declared -> SET, otherwise DROP.
        if ((count($value) > 1) && !(is_null($value[1]) || ($value[1] === "DEFAULT"))) {
            if (is_int($value[1])) {
                $default = (string) $value[1];
            } else {
                $default = "'" . addcslashes($value[1], "'\\") . "'";
            }
            if (!$this->connection->queryPrepare("ALTER TABLE " . $tbl . " ALTER COLUMN " . $col . " SET DEFAULT " . $default . ";")) {
                return false;
            }
        } else {
            $this->connection->queryPrepare("ALTER TABLE " . $tbl . " ALTER COLUMN " . $col . " DROP DEFAULT;");
        }

        // Integer columns are declared NOT NULL by createStorage(); keep parity.
        if (($value[0] === "int") || ($value[0] === "bigint")) {
            if (!$this->connection->queryPrepare("ALTER TABLE " . $tbl . " ALTER COLUMN " . $col . " SET NOT NULL;")) {
                return false;
            }
        }

        return true;
    }

    // True when a base table with this name exists in the current schema.
    protected function tableExists()
    {
        $stmt = $this->connection->queryPrepare(
            "SELECT 1 FROM " . $this->quoteIdentifier("information_schema") . "." . $this->quoteIdentifier("tables") .
            " WHERE " . $this->quoteIdentifier("table_schema") . "=CURRENT_SCHEMA() AND " . $this->quoteIdentifier("table_name") . "=? LIMIT 1;",
            [$this->name]
        );
        if ($stmt) {
            return (bool) $stmt->fetch(\PDO::FETCH_NUM);
        }
        return false;
    }

    // Map of live column name => [ data_type, character_maximum_length ].
    protected function readColumns()
    {
        $columns = [];
        $stmt = $this->connection->queryPrepare(
            "SELECT " . $this->quoteIdentifier("column_name") . "," . $this->quoteIdentifier("data_type") . "," . $this->quoteIdentifier("character_maximum_length") .
            " FROM " . $this->quoteIdentifier("information_schema") . "." . $this->quoteIdentifier("columns") .
            " WHERE " . $this->quoteIdentifier("table_schema") . "=CURRENT_SCHEMA() AND " . $this->quoteIdentifier("table_name") . "=?;",
            [$this->name]
        );
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
                $columns[$row[0]] = [strtolower((string) $row[1]), is_null($row[2]) ? null : intval($row[2])];
            }
        }
        return $columns;
    }

    // The [ data_type, length ] signature the descriptor implies, matching how
    // PostgreSQL reports it in information_schema.columns.
    protected function expectedColumnSignature($value)
    {
        $type = strtolower($value[0]);
        if ($type === "varchar") {
            return ["character varying", (count($value) > 2) ? intval($value[2]) : null];
        }
        if (($type === "int") || ($type === "integer")) {
            return ["integer", null];
        }
        if ($type === "bigint") {
            return ["bigint", null];
        }
        if ($type === "datetime") {
            return ["timestamp without time zone", null];
        }
        return [$type, null];
    }

    // Index names the descriptor expects (named after the field, as
    // createStorageIndex does).
    protected function expectedIndexNames()
    {
        $names = [];
        foreach ($this->info->indexes as $index) {
            $names[] = $index;
        }
        return $names;
    }

    // Live secondary index names (the PRIMARY KEY index is excluded via
    // pg_index.indisprimary).
    protected function currentIndexNames()
    {
        $names = [];
        $stmt = $this->connection->queryPrepare(
            "SELECT c.relname FROM pg_index i" .
            " JOIN pg_class c ON c.oid=i.indexrelid" .
            " JOIN pg_class t ON t.oid=i.indrelid" .
            " JOIN pg_namespace n ON n.oid=t.relnamespace" .
            " WHERE n.nspname=CURRENT_SCHEMA() AND t.relname=? AND i.indisprimary=false;",
            [$this->name]
        );
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
                $names[] = $row[0];
            }
        }
        return $names;
    }

    protected function reconcileIndexes()
    {
        $expected = $this->expectedIndexNames();
        foreach ($this->currentIndexNames() as $name) {
            if (!in_array($name, $expected, true)) {
                if (!$this->connection->queryPrepare("DROP INDEX IF EXISTS " . $this->quoteIdentifier($name) . ";")) {
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

        // Compare data type (and varchar length). Defaults and NOT NULL are not
        // compared: their catalog representation is noisy (e.g. SERIAL adds a
        // nextval() default, char defaults carry a ::type cast).
        foreach ($this->info->fields as $key => $value) {
            if ($actual[$key] !== $this->expectedColumnSignature($value)) {
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

        // Add new columns and retype the drifted ones.
        foreach ($this->info->fields as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " ADD COLUMN " . $this->buildColumnDefinition($key, $value) . ";";
                if (!$this->connection->queryPrepare($query, $this->getQueryParams())) {
                    return false;
                }
                continue;
            }
            if ($actual[$key] !== $this->expectedColumnSignature($value)) {
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
