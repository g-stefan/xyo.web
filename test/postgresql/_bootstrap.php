<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// PostgreSQL test bootstrap: framework environment + a real connection to a
// local test database + the introspection helpers the PostgreSQL tests need.
//
// Expects a reachable server with database "xyo-web-test-0002". Override the
// connection via environment variables if your setup differs:
//   XYO_PGSQL_SERVER, XYO_PGSQL_PORT, XYO_PGSQL_DATABASE,
//   XYO_PGSQL_USER, XYO_PGSQL_PASSWORD
//
// NOTE: this database is PERSISTENT. Every test recreates the tables it uses
// at the start and drops them at the end.

error_reporting(E_ALL);
ini_set("display_errors", "1");

if (!defined("XYO_WEB")) {
    define("XYO_WEB", true);
}
if (!defined("XYO_WEB_PATH")) {
    // <base>/test/postgresql  ->  <base>/source/
    $root = realpath(__DIR__ . "/../../source");
    define("XYO_WEB_PATH", str_replace("\\", "/", $root) . "/");
}

require_once(XYO_WEB_PATH . "_site/xyo/web/autoload.php");

require_once(__DIR__ . "/../_lib/harness.php");
require_once(__DIR__ . "/../_lib/descriptors.php");

define("XYO_TEST_DRIVER", "postgresql");

function mock_connection()
{
    $env = function ($name, $default) {
        $v = getenv($name);
        return ($v === false || $v === "") ? $default : $v;
    };

    $connection = new \XYO\Web\DataSource\PostgreSQLConnection([
        "type"     => "postgresql",
        "server"   => $env("XYO_PGSQL_SERVER", "localhost"),
        "port"     => $env("XYO_PGSQL_PORT", "5432"),
        "database" => $env("XYO_PGSQL_DATABASE", "xyo-web-test-0002"),
        "user"     => $env("XYO_PGSQL_USER", "postgres"),
        "password" => $env("XYO_PGSQL_PASSWORD", "Password2026"),
        "prefix"   => "",
    ]);
    if (!$connection->open()) {
        fwrite(STDERR, "Could not connect to PostgreSQL test database (see source/_log)\n");
        exit(2);
    }
    return $connection;
}

// --- introspection helpers (PostgreSQL-specific) ---------------------------

function db_has_column($connection, $table, $column)
{
    $stmt = $connection->queryPrepare(
        "SELECT 1 FROM \"information_schema\".\"columns\" WHERE \"table_schema\"=CURRENT_SCHEMA() AND \"table_name\"=? AND \"column_name\"=? LIMIT 1;",
        [$table, $column]
    );
    if ($stmt) {
        return (bool) $stmt->fetch(\PDO::FETCH_NUM);
    }
    return false;
}

function db_first_value($connection, $table, $column)
{
    $q = "\"" . str_replace("\"", "\"\"", $column) . "\"";
    $t = "\"" . str_replace("\"", "\"\"", $table) . "\"";
    $stmt = $connection->queryPrepare("SELECT " . $q . " FROM " . $t . " LIMIT 1;");
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        }
    }
    return null;
}

// Secondary index names (PRIMARY KEY index excluded), sorted.
function db_secondary_indexes($connection, $table)
{
    $names = [];
    $stmt = $connection->queryPrepare(
        "SELECT c.relname FROM pg_index i" .
        " JOIN pg_class c ON c.oid=i.indexrelid" .
        " JOIN pg_class t ON t.oid=i.indrelid" .
        " JOIN pg_namespace n ON n.oid=t.relnamespace" .
        " WHERE n.nspname=CURRENT_SCHEMA() AND t.relname=? AND i.indisprimary=false;",
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

// The information_schema data_type for one column (e.g. "integer", "bigint",
// "character varying", "timestamp without time zone").
function db_column_data_type($connection, $table, $column)
{
    $stmt = $connection->queryPrepare(
        "SELECT \"data_type\" FROM \"information_schema\".\"columns\" WHERE \"table_schema\"=CURRENT_SCHEMA() AND \"table_name\"=? AND \"column_name\"=? LIMIT 1;",
        [$table, $column]
    );
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return strtolower((string) $row[0]);
        }
    }
    return null;
}
