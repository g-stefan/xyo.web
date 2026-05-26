<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Driver-agnostic row operations exposed by table.php:
// insert / save / load / tryLoad / loadIsValid / loadHasNext / loadNext /
// count / update / delete / select / clear / isEmpty / empty / atomic*.
//
// Included by each driver's "0002.*.php" after its _bootstrap.php, which
// provides mock_connection(), the mock descriptors and the assert harness.

$connection = mock_connection();
$user = new MockUserTable($connection);
$user->recreateStorage(); // clean slate (the DB may be persistent across runs)

t_section("insert + count");
check("seed Ada", seed_user($user, "Ada", "ada@example.com", 10));
check("seed Linus", seed_user($user, "Linus", "linus@example.com", 20));
check("seed Grace", seed_user($user, "Grace", "grace@example.com", 30));
// count()/load() filter by the currently populated fields, so clear the row
// state first to count every row.
$user->clear();
check_eq("count() == 3", 3, intval($user->count()));

t_section("empty() / isEmpty()");
$user->empty();
check("all fields empty after empty()", $user->isEmpty("name") && $user->isEmpty("email") && $user->isEmpty("score"));

t_section("load() by field value");
$user->empty();
$user->email = "linus@example.com";
check("load() finds the row", $user->load() === true);
check("loadIsValid() true", $user->loadIsValid() === true);
check_eq("loaded name", "Linus", $user->name);
check_eq("loaded score", "20", (string) $user->score);

t_section("load() with no match");
$user->empty();
$user->email = "nobody@example.com";
check("load() returns false for no rows", $user->load() === false);
check("loadIsValid() false after empty result", $user->loadIsValid() === false);

t_section("tryLoad()");
$user->empty();
$user->name = "Grace";
check("tryLoad() finds the row", $user->tryLoad() === true);
check_eq("tryLoad score", "30", (string) $user->score);

t_section("save() updates an existing row");
$user->empty();
$user->email = "ada@example.com";
$user->load();
$adaId = $user->id;
$user->score = 99;
check("save() existing row", $user->save() === true);

$user->empty();
$user->id = $adaId;
$user->load();
check_eq("score persisted via save()", "99", (string) $user->score);

t_section("save() inserts when no primary key set");
$user->empty();
$user->name = "Margaret";
$user->email = "margaret@example.com";
$user->score = 5;
check("save() falls back to insert", $user->save() === true);
$user->clear();
check_eq("count() == 4 after save-insert", 4, intval($user->count()));

t_section("loadHasNext() / loadNext() iteration");
$user->empty();
$user->setOrder("id", \XYO\Web\DataSource\Order::ASCENDENT);
$rows = [];
if ($user->load()) {
    do {
        $rows[] = $user->name;
    } while ($user->loadHasNext() && $user->loadNext());
}
check_eq("iterated over all 4 rows", 4, count($rows));
check("first row is Ada (ascending id)", $rows[0] === "Ada");

t_section("select() restricts returned columns");
$user->clear();
$user->select(["name"]);
$user->email = "linus@example.com";
check("load() with column subset", $user->load() === true);
check("selected column present", $user->name === "Linus");
check("unselected column stays empty", $user->isEmpty("score"));

t_section("update() with WHERE");
$user->clear();
$user->setOperator("score", ">=", 30);
check("update() by condition", $user->update(["score" => 1000]) === true);

$user->clear();
$user->setOperator("score", "=", 1000);
check("count() of updated rows", intval($user->count()) >= 1);

t_section("atomicAdd / atomicIncrement / atomicSub");
$user->clear();
$user->email = "margaret@example.com";
$user->load();
$mId = $user->id;

$user->empty();
$user->id = $mId;
check("atomicAdd(+10)", $user->atomicAdd("score", 10) === true);
$user->empty();
$user->id = $mId;
check("atomicIncrement()", $user->atomicIncrement("score") === true);
$user->empty();
$user->id = $mId;
check("atomicSub(-3)", $user->atomicSub("score", 3) === true);

$user->empty();
$user->id = $mId;
$user->load();
// started at 5, +10, +1, -3 = 13
check_eq("atomic operations net result", "13", (string) $user->score);

t_section("delete()");
$user->empty();
$user->email = "grace@example.com";
$user->load();
check("delete() existing row", $user->delete() === true);
$user->empty();
$user->email = "grace@example.com";
check("row gone after delete()", $user->load() === false);

$user->destroyStorage();
t_done();
