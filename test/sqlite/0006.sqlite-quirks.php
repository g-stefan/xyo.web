<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Documents and pins the SQLite-specific behaviours the driver depends on,
// plus a couple of remaining table.php / query.php paths.

require_once(__DIR__ . "/_bootstrap.php");

$connection = mock_connection();
$user = new MockUserTable($connection);
$user->createStorage();

t_section("stored CREATE TABLE is verbatim (no trailing ';')");
$row = $connection->queryPrepare("SELECT [sql] FROM [sqlite_master] WHERE [type]='table' AND [name]='user';")->fetch(\PDO::FETCH_NUM);
check("stored SQL has no trailing semicolon", substr(rtrim($row[0]), -1) !== ";");
check("PRIMARY KEY ASC AUTOINCREMENT accepted", strpos($row[0], "PRIMARY KEY ASC AUTOINCREMENT") !== false);

t_section("storageCheckTable survives a rebuild (RENAME re-quotes identifiers)");
seed_user($user, "Ada", "ada@example.com", 10);
check("schema matches before rebuild", $user->storageCheckTable() === true);
check("storageUpdateField triggers a rebuild", $user->storageUpdateField("score") === true);
check("schema STILL matches after rebuild", $user->storageCheckTable() === true);

t_section("INTEGER PRIMARY KEY is a rowid alias (last_insert_rowid)");
$user->empty();
$user->name = "Linus";
$user->email = "linus@example.com";
$user->insert();
check("insert returned an auto id", intval($user->id) >= 1);

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
$post->createStorage();
$post->empty();
$post->userId = intval($user->id);
$post->title = "Hello";
$post->insert();

$q = new MockUserPostsQuery($connection);
$q->clear();
$q->setOperator("postTitle", "=", "Hello");
check("Query::tryLoad() finds the row", $q->tryLoad() === true);
check_eq("tryLoad joined value", "Hello", $q->postTitle);
$q->clear();
check("Query::clear() empties fields", $q->isEmpty("postTitle"));

t_done();
