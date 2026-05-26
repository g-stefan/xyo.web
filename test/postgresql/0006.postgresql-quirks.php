<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// PostgreSQL-specific behaviours the driver must handle, plus a couple of
// remaining table.php / query.php paths.

require_once(__DIR__ . "/_bootstrap.php");

$connection = mock_connection();
echo "  (server: " . $connection->queryPrepare("SHOW server_version;")->fetch(\PDO::FETCH_NUM)[0] . ")\n";

$user = new MockUserTable($connection);
$user->recreateStorage();

t_section("reserved word \"user\" works as a quoted table name");
// "user" is a reserved keyword; the driver always quotes identifiers, so CRUD
// against a table literally named user must work.
$user->empty();
$user->name = "Ada";
$user->email = "ada@example.com";
check("insert into \"user\"", $user->insert() === true);
$id1 = intval($user->id);
check("SERIAL id populated via insert()", $id1 >= 1);

t_section("LAST insert id increments (CURRVAL of the serial sequence)");
$user->empty();
$user->name = "Linus";
$user->email = "linus@example.com";
$user->insert();
check("second insert id increments", intval($user->id) > $id1);

t_section("SERIAL column reports as integer for storageCheckTable");
check("id data_type is integer", db_column_data_type($connection, "user", "id") === "integer");
check("storageCheckTable() true despite nextval() default on the serial", $user->storageCheckTable() === true);

t_section("TIMESTAMP / NULL default round-trips");
$user->clear();
$user->email = "ada@example.com";
$user->load();
check("created is NULL (declared with no default)", $user->created === null || $user->isEmpty("created"));

t_section("clear(\$key) clears one field but keeps the rest");
$user->clear();
$user->name = "X";
$user->email = "Y";
$user->clear("name");
check("cleared field is empty", $user->isEmpty("name"));
check("other field retained", $user->email === "Y");

t_section("Query tryLoad() + clear()");
$post = new MockPostTable($connection);
$post->recreateStorage();
$post->empty();
$post->userId = $id1;
$post->title = "Hello";
$post->insert();

$q = new MockUserPostsQuery($connection);
$q->clear();
$q->setOperator("postTitle", "=", "Hello");
check("Query::tryLoad() finds the row", $q->tryLoad() === true);
check_eq("tryLoad joined value", "Hello", $q->postTitle);
$q->clear();
check("Query::clear() empties fields", $q->isEmpty("postTitle"));

// leave the DB clean
$post->destroyStorage();
$user->destroyStorage();
t_done();
