<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// SQLite schema-upgrade primitives:
//   storageRemoveField  (must drop dependent index first)
//   storageRenameField
//   storageUpdateField  (no ALTER MODIFY -> table rebuild, data preserved)
//   storageUpdateTable  (descriptor -> live schema, all-in-one)
//   storageCheckTable   (true iff live schema matches the descriptor)

require_once(__DIR__ . "/_bootstrap.php");

// ---------------------------------------------------------------------------
t_section("storageRemoveField drops an indexed column");
// "email" is indexed in MockUserTable -> a naive DROP COLUMN would fail.
$connection = mock_connection();
$user = new MockUserTable($connection);
$user->recreateStorage();
seed_user($user, "Ada", "ada@example.com", 10);
seed_user($user, "Linus", "linus@example.com", 20);

check("email column exists before remove", db_has_column($connection, "user", "email"));
check("storageRemoveField('email') succeeds despite index", $user->storageRemoveField("email") === true);
check("email column gone after remove", db_has_column($connection, "user", "email") === false);
$user->clear();
check_eq("rows preserved after remove", 2, intval($user->count()));

// ---------------------------------------------------------------------------
t_section("storageRenameField keeps data");
$connection = mock_connection();
$user = new MockUserTable($connection);
$user->recreateStorage();
seed_user($user, "Grace", "grace@example.com", 30);

check("storageRenameField(name -> fullName)", $user->storageRenameField("name", "fullName") === true);
check("old column gone", db_has_column($connection, "user", "name") === false);
check("new column present", db_has_column($connection, "user", "fullName"));
check_eq("data survived the rename", "Grace", db_first_value($connection, "user", "fullName"));

// ---------------------------------------------------------------------------
t_section("storageUpdateField rebuilds (no ALTER MODIFY on SQLite)");
$connection = mock_connection();
$user = new MockUserTable($connection);   // score is "int"
$userV2 = new MockUserTableV2($connection); // score is "bigint" + other changes
$user->recreateStorage();
seed_user($user, "Margaret", "margaret@example.com", 42);

check("storageUpdateField('score') succeeds (rebuild)", $userV2->storageUpdateField("score") === true);
check_eq("name preserved through rebuild", "Margaret", db_first_value($connection, "user", "name"));
check_eq("score preserved through rebuild", "42", (string) db_first_value($connection, "user", "score"));

// ---------------------------------------------------------------------------
t_section("storageCheckTable / storageUpdateTable full upgrade V1 -> V2");
$connection = mock_connection();
$user = new MockUserTable($connection);
$user->recreateStorage();
seed_user($user, "Ada", "ada@example.com", 10);
seed_user($user, "Linus", "linus@example.com", 20);
seed_user($user, "Grace", "grace@example.com", 30);

$userV2 = new MockUserTableV2($connection);
check("V1 storage matches V1 descriptor", $user->storageCheckTable() === true);
check("V1 storage does NOT match V2 descriptor", $userV2->storageCheckTable() === false);

check("storageUpdateTable() applies V2", $userV2->storageUpdateTable() === true);
check("V2 descriptor now matches storage", $userV2->storageCheckTable() === true);
check("storageUpdateTable() is idempotent (already in sync)", $userV2->storageUpdateTable() === true);

check("V2 added 'status' column", db_has_column($connection, "user", "status"));
check("V2 removed 'created' column", db_has_column($connection, "user", "created") === false);
check("kept 'email' column", db_has_column($connection, "user", "email"));

$userV2->clear();
check_eq("row count preserved through updateTable", 3, intval($userV2->count()));
$userV2->clear();
$userV2->email = "linus@example.com";
check("reload migrated row", $userV2->load() === true);
check_eq("name preserved", "Linus", $userV2->name);
check_eq("score preserved", "20", (string) $userV2->score);
check_eq("new column took its default", "new", (string) $userV2->status);

// ---------------------------------------------------------------------------
t_section("index reconciliation");
// V1 indexes "email"; V2 indexes "name". SQLite names indexes "<table>_<field>".
check_eq("only the V2 index 'user_name' exists", ["user_name"], db_secondary_indexes($connection, "user"));

// ---------------------------------------------------------------------------
t_section("storageUpdateTable creates a missing table");
$connection = mock_connection();
$user = new MockUserTable($connection);
check("table absent initially", $user->storageCheckTable() === false);
check("storageUpdateTable() creates it", $user->storageUpdateTable() === true);
check("schema matches after create-via-update", $user->storageCheckTable() === true);

t_done();
