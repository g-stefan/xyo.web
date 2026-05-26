<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type\SQLite;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized SQLite Driver

class Table extends \XYO\Web\DataSource\Type\AbstractSQLTable
{
    protected function quoteIdentifier($name)
    {
        $name = str_replace("[", "[[", $name);
        $name = str_replace("]", "]]", $name);
        return "[" . $name . "]";
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
        return "AUTOINCREMENT";
    }

    protected function lastInsertIdQuery()
    {
        return "SELECT last_insert_rowid();";
    }

    // True when a *table* (not an index/view/trigger) with this name exists.
    // The lookup is parameterized on purpose: SQLite treats "x" as an
    // identifier and only falls back to a string literal as a deprecated
    // quirk, so binding the value avoids relying on that misfeature.
    protected function tableExists()
    {
        $stmt = $this->connection->queryPrepare(
            "SELECT 1 FROM " . $this->quoteIdentifier("sqlite_master") .
            " WHERE " . $this->quoteIdentifier("type") . "='table' AND " . $this->quoteIdentifier("name") . "=?;",
            [$this->name]
        );
        if ($stmt) {
            return (bool) $stmt->fetch(\PDO::FETCH_NUM);
        }
        return false;
    }

    // The DDL fragment for one column, e.g. `[id] INTEGER PRIMARY KEY ASC
    // AUTOINCREMENT` or `[name] VARCHAR(64) DEFAULT 'x'`. Shared by
    // createStorage() and the rebuild path so the produced schema is always
    // identical to what storageCheckTable() expects.
    protected function buildColumnDefinition($key, $value)
    {
        // In SQLite an INTEGER PRIMARY KEY is an alias for the rowid; this is
        // the only form that may carry AUTOINCREMENT. Both "int" and "bigint"
        // map onto it (SQLite integers are already 64-bit).
        if ($this->info->primaryKey === $key) {
            if (($value[0] === "int") || ($value[0] === "bigint")) {
                $def = $this->quoteIdentifier($key) . " INTEGER PRIMARY KEY ASC";
                if (count($value) > 3) {
                    if ($value[3] == "autoIncrement") {
                        $def .= " " . $this->autoIncrementType();
                    } else {
                        $def .= " " . strtoupper($value[3]);
                    }
                }
                return $def;
            }
        }

        $def = $this->quoteIdentifier($key) . " " . strtoupper($value[0]);

        if ($value[0] == "varchar") {
            if (count($value) > 2) {
                $def .= "(" . strtoupper($value[2]) . ")";
            }
            if (count($value) > 1) {
                if (!is_null($value[1])) {
                    $def .= " DEFAULT '" . $value[1] . "'";
                }
            }
            return $def;
        }

        if (count($value) > 2) {
            $def .= " " . strtoupper($value[2]);
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
                    $def .= " DEFAULT '" . $value[1] . "'";
                }
            }
        }

        return $def;
    }

    // Full CREATE TABLE statement for the descriptor, without trailing ";".
    // Parameterized on the table name so the rebuild path can target a
    // scratch table.
    protected function buildCreateTableSQL($tableName)
    {
        $before = false;
        $query = "CREATE TABLE " . $this->quoteIdentifier($tableName) . " (";
        foreach ($this->info->fields as $key => $value) {
            if ($before) {
                $query .= ",";
            } else {
                $before = true;
            }
            $query .= $this->buildColumnDefinition($key, $value);
        }
        $query .= ")";
        return $query;
    }

    public function createStorage()
    {
        if ($this->tableExists()) {
            return true;
        }

        $result = $this->connection->queryPrepare($this->buildCreateTableSQL($this->name) . ";", $this->getQueryParams());
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
            $query = "CREATE INDEX IF NOT EXISTS " . $this->quoteIdentifier($this->name . "_" . $index) . " ON " . $this->quoteIdentifier($this->name) . " (" . $this->quoteIdentifier($index) . ")";
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
        // SQLite refuses to DROP a column that an index still references
        // ("error in index ... after drop column"). The descriptor names its
        // single-column indexes "<table>_<field>", so drop that one first.
        $this->connection->queryPrepare("DROP INDEX IF EXISTS " . $this->quoteIdentifier($this->name . "_" . $name) . ";");

        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " DROP COLUMN " . $this->quoteIdentifier($name) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function storageRenameField($oldName, $newName)
    {
        // RENAME COLUMN keeps the data and lets SQLite rewrite any index that
        // referenced the column. The index *name* is not updated, so a later
        // storageUpdateTable() reconciles index names against the descriptor.
        $query = "ALTER TABLE " . $this->quoteIdentifier($this->name) . " RENAME COLUMN " . $this->quoteIdentifier($oldName) . " TO " . $this->quoteIdentifier($newName) . ";";
        $result = $this->connection->queryPrepare($query, $this->getQueryParams());
        if ($result) {
            return true;
        }
        return false;
    }

    public function storageUpdateField($name)
    {
        // SQLite has no "ALTER TABLE ... MODIFY/ALTER COLUMN <type>": a
        // column's declared type or default can only be changed by rebuilding
        // the whole table. The descriptor already carries the new definition
        // for $name, so a descriptor-driven rebuild applies it while keeping
        // the existing rows.
        if (!array_key_exists($name, $this->info->fields)) {
            return false;
        }
        return $this->rebuildStorage();
    }

    // Current column names in storage order, read from PRAGMA table_info.
    protected function readColumns()
    {
        $columns = [];
        $stmt = $this->connection->queryPrepare("PRAGMA table_info(" . $this->quoteIdentifier($this->name) . ");");
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $columns[] = $row["name"];
            }
        }
        return $columns;
    }

    // The official SQLite "rebuild" migration: create a scratch table with the
    // target schema, copy over the columns that exist in both schemas, drop
    // the old table and rename the scratch table into its place, then recreate
    // the descriptor's indexes. Wrapped in a transaction so a failure leaves
    // the original table untouched.
    //
    // Note: this framework declares no foreign keys, so PRAGMA foreign_keys is
    // left alone. If FKs are ever added, they must be disabled *before* the
    // transaction (the pragma is a no-op inside one) per the SQLite manual.
    protected function rebuildStorage()
    {
        if (!$this->tableExists()) {
            return $this->createStorage();
        }

        $existing = $this->readColumns();
        if (count($existing) === 0) {
            return false;
        }

        // Columns present in BOTH the descriptor and the live table. Data is
        // carried over only for these: dropped columns are discarded and newly
        // added columns take their declared default.
        $common = [];
        foreach ($this->info->fields as $key => $value) {
            if (in_array($key, $existing, true)) {
                $common[] = $key;
            }
        }

        $tmpName = $this->name . "_xyo_rebuild";

        if (!$this->connection->beginTransaction()) {
            return false;
        }

        // Clear any scratch table left behind by an aborted previous run.
        if (!$this->connection->queryPrepare("DROP TABLE IF EXISTS " . $this->quoteIdentifier($tmpName) . ";")) {
            $this->connection->rollBack();
            return false;
        }

        if (!$this->connection->queryPrepare($this->buildCreateTableSQL($tmpName) . ";")) {
            $this->connection->rollBack();
            return false;
        }

        if (count($common) > 0) {
            $cols = "";
            foreach ($common as $columnName) {
                $cols .= ($cols === "" ? "" : ",") . $this->quoteIdentifier($columnName);
            }
            $copy = "INSERT INTO " . $this->quoteIdentifier($tmpName) . " (" . $cols . ") SELECT " . $cols . " FROM " . $this->quoteIdentifier($this->name) . ";";
            if (!$this->connection->queryPrepare($copy)) {
                $this->connection->rollBack();
                return false;
            }
        }

        // DROP TABLE also drops the table's indexes; they are recreated below.
        if (!$this->connection->queryPrepare("DROP TABLE " . $this->quoteIdentifier($this->name) . ";")) {
            $this->connection->rollBack();
            return false;
        }

        if (!$this->connection->queryPrepare("ALTER TABLE " . $this->quoteIdentifier($tmpName) . " RENAME TO " . $this->quoteIdentifier($this->name) . ";")) {
            $this->connection->rollBack();
            return false;
        }

        if (!$this->connection->commit()) {
            $this->connection->rollBack();
            return false;
        }

        return $this->createStorageIndex();
    }

    // The CREATE TABLE text SQLite stored for this table, or null if the table
    // does not exist. SQLite keeps this text verbatim from CREATE time (only
    // re-quoting identifiers on RENAME), which makes it a faithful record of
    // the live column structure.
    protected function storedTableSQL()
    {
        $stmt = $this->connection->queryPrepare(
            "SELECT " . $this->quoteIdentifier("sql") . " FROM " . $this->quoteIdentifier("sqlite_master") .
            " WHERE " . $this->quoteIdentifier("type") . "='table' AND " . $this->quoteIdentifier("name") . "=?;",
            [$this->name]
        );
        if ($stmt) {
            $row = $stmt->fetch(\PDO::FETCH_NUM);
            if ($row) {
                return $row[0];
            }
        }
        return null;
    }

    // Canonical form for comparing two CREATE TABLE statements: strip all
    // identifier quoting (SQLite may store [x], "x" or `x` interchangeably
    // after a RENAME), collapse whitespace and drop a trailing ";". String
    // literal contents (single-quoted defaults) are preserved.
    protected function normalizeSchemaSQL($sql)
    {
        $sql = (string) $sql;
        $sql = str_replace(["[", "]", "\"", "`"], "", $sql);
        $sql = preg_replace("/\\s+/", " ", $sql);
        $sql = trim($sql);
        $sql = rtrim($sql, ";");
        return trim($sql);
    }

    // True when the live column structure matches the descriptor (compared via
    // the regenerated vs. stored CREATE TABLE text).
    protected function columnsMatchDescriptor()
    {
        $stored = $this->storedTableSQL();
        if ($stored === null) {
            return false;
        }
        return $this->normalizeSchemaSQL($stored) === $this->normalizeSchemaSQL($this->buildCreateTableSQL($this->name));
    }

    // Index names SQLite currently has for this table, excluding the internal
    // auto-indexes it creates for UNIQUE/PRIMARY KEY constraints.
    protected function currentIndexNames()
    {
        $names = [];
        $stmt = $this->connection->queryPrepare(
            "SELECT " . $this->quoteIdentifier("name") . " FROM " . $this->quoteIdentifier("sqlite_master") .
            " WHERE " . $this->quoteIdentifier("type") . "='index' AND " . $this->quoteIdentifier("tbl_name") . "=? AND " . $this->quoteIdentifier("name") . " NOT LIKE 'sqlite_%';",
            [$this->name]
        );
        if ($stmt) {
            foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
                $names[] = $row[0];
            }
        }
        return $names;
    }

    // Index names the descriptor expects, using the "<table>_<field>" scheme.
    protected function expectedIndexNames()
    {
        $names = [];
        foreach ($this->info->indexes as $index) {
            $names[] = $this->name . "_" . $index;
        }
        return $names;
    }

    // Drop indexes that the descriptor no longer declares and create the ones
    // that are missing. Leaves the column structure untouched.
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
        if (!$this->columnsMatchDescriptor()) {
            return false;
        }
        $actual = $this->currentIndexNames();
        $expected = $this->expectedIndexNames();
        sort($actual);
        sort($expected);
        return $actual === $expected;
    }

    public function storageUpdateTable()
    {
        if (!$this->tableExists()) {
            return $this->createStorage();
        }

        // A column-structure difference (added/removed/retyped field) can only
        // be applied by rebuilding; rebuildStorage() also recreates exactly the
        // descriptor's indexes, so the table is fully in sync afterwards.
        if (!$this->columnsMatchDescriptor()) {
            return $this->rebuildStorage();
        }

        // Columns already match; only the index set may need reconciling.
        return $this->reconcileIndexes();
    }
}
