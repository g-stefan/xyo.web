<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// MySQL / MariaDB specific behaviours the driver must tolerate, plus a couple
// of remaining table.php / query.php paths.

require_once(__DIR__ . "/_bootstrap.php");

$connection = mock_connection();
echo "  (server: " . $connection->queryPrepare("SELECT VERSION();")->fetch(\PDO::FETCH_NUM)[0] . ")\n";

$user = new MockUserTable($connection);
$user->recreateStorage();

t_section("integer display-width is normalized for storageCheckTable");
// MariaDB reports "int(11)" / "bigint(20)"; MySQL 8.0.17+ reports "int" /
// "bigint". The driver strips the display width, so the check must hold
// regardless of which form the server uses.
$scoreType = strtolower((string) db_column_type($connection, "user", "score"));
check("server reports an integer score type", strpos($scoreType, "int") !== false);
check("storageCheckTable() true despite display-width quirk", $user->storageCheckTable() === true);

t_section("LAST_INSERT_ID round-trips through insert()");
$user->empty();
$user->name = "Ada";
$user->email = "ada@example.com";
$user->insert();
$id1 = intval($user->id);
check("insert() populated the auto id", $id1 >= 1);
$user->empty();
$user->name = "Linus";
$user->email = "linus@example.com";
$user->insert();
check("second insert id increments", intval($user->id) > $id1);

t_section("DATETIME / NULL default round-trips");
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
