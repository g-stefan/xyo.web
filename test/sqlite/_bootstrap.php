<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// SQLite test bootstrap: framework environment + an in-memory SQLite
// connection + the introspection helpers the SQLite tests need.

error_reporting(E_ALL);
ini_set("display_errors", "1");

if (!defined("XYO_WEB")) {
    define("XYO_WEB", true);
}
if (!defined("XYO_WEB_PATH")) {
    // <base>/test/sqlite  ->  <base>/source/
    $root = realpath(__DIR__ . "/../../source");
    define("XYO_WEB_PATH", str_replace("\\", "/", $root) . "/");
}

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/table.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/query.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/sqlite-connection.php");

require_once(__DIR__ . "/../_lib/harness.php");
require_once(__DIR__ . "/../_lib/descriptors.php");

define("XYO_TEST_DRIVER", "sqlite");

// A fresh in-memory database per connection: clean state for every run.
function mock_connection()
{
    $connection = new \XYO\Web\DataSource\Type\SQLite\Connection([
        "type"     => "sqlite",
        "database" => ":memory:",
        "prefix"   => "",
    ]);
    if (!$connection->open()) {
        fwrite(STDERR, "Could not open in-memory SQLite database\n");
        exit(2);
    }
    return $connection;
}

// --- introspection helpers (SQLite-specific) -------------------------------

function db_has_column($connection, $table, $column)
{
    $stmt = $connection->queryPrepare("PRAGMA table_info([" . $table . "]);");
    if ($stmt) {
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ($row["name"] === $column) {
                return true;
            }
        }
    }
    return false;
}

function db_first_value($connection, $table, $column)
{
    $stmt = $connection->queryPrepare("SELECT [" . $column . "] FROM [" . $table . "] LIMIT 1;");
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        }
    }
    return null;
}

// Secondary (non auto) index names for a table, sorted.
function db_secondary_indexes($connection, $table)
{
    $names = [];
    $stmt = $connection->queryPrepare(
        "SELECT [name] FROM [sqlite_master] WHERE [type]='index' AND [tbl_name]=? AND [name] NOT LIKE 'sqlite_%';",
        [$table]
    );
    if ($stmt) {
        foreach ($stmt->fetchAll(\PDO::FETCH_NUM) as $row) {
            $names[] = $row[0];
        }
    }
    sort($names);
    return $names;
}
