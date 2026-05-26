<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// PostgreSQL storage lifecycle: createStorage / storageCheckTable /
// destroyStorage / recreateStorage, plus SERIAL auto-increment behaviour.
// (Regression guard: createStorage used to emit a broken auto-increment PK and
// duplicate the DATETIME column definition.)

require_once(__DIR__ . "/_bootstrap.php");

$connection = mock_connection();
$user = new MockUserTable($connection);
$user->destroyStorage(); // clean slate (persistent DB)

t_section("createStorage");
check("createStorage() succeeds (SERIAL PK + DATETIME column)", $user->createStorage() === true);
check("createStorage() is idempotent (table already exists)", $user->createStorage() === true);

t_section("storageCheckTable after fresh create");
check("schema matches descriptor right after create", $user->storageCheckTable() === true);
check("id is SERIAL-backed integer", db_column_data_type($connection, "user", "id") === "integer");
check("created is a timestamp", db_column_data_type($connection, "user", "created") === "timestamp without time zone");

t_section("SERIAL auto-increment behaves (last insert id)");
$user->empty();
$user->name = "Ada";
$user->email = "ada@example.com";
check("insert row 1", $user->insert() === true);
$firstId = $user->id;
check("auto id assigned on insert", is_numeric($firstId) && intval($firstId) >= 1);

$user->empty();
$user->name = "Linus";
$user->email = "linus@example.com";
check("insert row 2", $user->insert() === true);
check("second auto id greater than first", intval($user->id) > intval($firstId));

t_section("storageCheckTable detects an unrelated descriptor");
$userV2 = new MockUserTableV2($connection);
check("V2 descriptor does not match V1 storage", $userV2->storageCheckTable() === false);

t_section("destroyStorage");
check("destroyStorage() succeeds", $user->destroyStorage() === true);
check("destroyStorage() idempotent when missing", $user->destroyStorage() === true);
check("storageCheckTable() false when table missing", $user->storageCheckTable() === false);

t_section("recreateStorage");
check("recreateStorage() succeeds", $user->recreateStorage() === true);
check("schema matches after recreate", $user->storageCheckTable() === true);
$user->clear();
check_eq("recreate left the table empty", 0, intval($user->count()));

// leave the DB clean
$user->destroyStorage();
t_done();
