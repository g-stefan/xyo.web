<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// MySQL / MariaDB test bootstrap: framework environment + a real connection
// to a local test database + the introspection helpers the MySQL tests need.
//
// Expects a reachable server with database "xyo-web-test-0001". Override the
// connection via environment variables if your setup differs:
//   XYO_MYSQL_SERVER, XYO_MYSQL_PORT, XYO_MYSQL_DATABASE,
//   XYO_MYSQL_USER, XYO_MYSQL_PASSWORD
//
// NOTE: this database is PERSISTENT. Every test recreates the tables it uses
// at the start and drops them at the end, so a leftover/aborted run cannot
// poison the next one.

error_reporting(E_ALL);
ini_set("display_errors", "1");

if (!defined("XYO_WEB")) {
    define("XYO_WEB", true);
}
if (!defined("XYO_WEB_PATH")) {
    // <base>/test/mysql  ->  <base>/source/
    $root = realpath(__DIR__ . "/../../source");
    define("XYO_WEB_PATH", str_replace("\\", "/", $root) . "/");
}

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/table.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/query.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/mysql-connection.php");

require_once(__DIR__ . "/../_lib/harness.php");
require_once(__DIR__ . "/../_lib/descriptors.php");

define("XYO_TEST_DRIVER", "mysql");

function mock_connection()
{
    $env = function ($name, $default) {
        $v = getenv($name);
        return ($v === false || $v === "") ? $default : $v;
    };

    $connection = new \XYO\Web\DataSource\Type\MySQL\Connection([
        "type"     => "mysql",
        "server"   => $env("XYO_MYSQL_SERVER", "localhost"),
        "port"     => $env("XYO_MYSQL_PORT", "3306"),
        "database" => $env("XYO_MYSQL_DATABASE", "xyo-web-test-0001"),
        "user"     => $env("XYO_MYSQL_USER", "root"),
        "password" => $env("XYO_MYSQL_PASSWORD", ""),
        "prefix"   => "",
    ]);
    if (!$connection->open()) {
        fwrite(STDERR, "Could not connect to MySQL test database (see source/_log)\n");
        exit(2);
    }
    return $connection;
}

// --- introspection helpers (MySQL/MariaDB-specific) ------------------------

function db_has_column($connection, $table, $column)
{
    $stmt = $connection->queryPrepare(
        "SELECT 1 FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`=DATABASE() AND `TABLE_NAME`=? AND `COLUMN_NAME`=? LIMIT 1;",
        [$table, $column]
    );
    if ($stmt) {
        return (bool) $stmt->fetch(\PDO::FETCH_NUM);
    }
    return false;
}

function db_first_value($connection, $table, $column)
{
    $q = "`" . str_replace("`", "``", $column) . "`";
    $t = "`" . str_replace("`", "``", $table) . "`";
    $stmt = $connection->queryPrepare("SELECT " . $q . " FROM " . $t . " LIMIT 1;");
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        }
    }
    return null;
}

// Secondary index names (PRIMARY excluded), sorted.
function db_secondary_indexes($connection, $table)
{
    $names = [];
    $stmt = $connection->queryPrepare(
        "SELECT DISTINCT `INDEX_NAME` FROM `information_schema`.`STATISTICS` WHERE `TABLE_SCHEMA`=DATABASE() AND `TABLE_NAME`=? AND `INDEX_NAME`<>'PRIMARY';",
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

// The live COLUMN_TYPE string for one column (e.g. "int(11)", "bigint(20)").
function db_column_type($connection, $table, $column)
{
    $stmt = $connection->queryPrepare(
        "SELECT `COLUMN_TYPE` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`=DATABASE() AND `TABLE_NAME`=? AND `COLUMN_NAME`=? LIMIT 1;",
        [$table, $column]
    );
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return $row[0];
        }
    }
    return null;
}

// The EXTRA column flags (e.g. "auto_increment") for one column.
function db_column_extra($connection, $table, $column)
{
    $stmt = $connection->queryPrepare(
        "SELECT `EXTRA` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA`=DATABASE() AND `TABLE_NAME`=? AND `COLUMN_NAME`=? LIMIT 1;",
        [$table, $column]
    );
    if ($stmt) {
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row) {
            return (string) $row[0];
        }
    }
    return "";
}
