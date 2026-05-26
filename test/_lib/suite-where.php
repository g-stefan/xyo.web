<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Driver-agnostic WHERE / ordering / grouping / aggregate building:
// setOperator (all operators), pushOperator (and/or/parens), setOrder,
// setGroup, setFunctionAs, plus multi-value (IN-like) field filtering.
//
// Included by each driver's "0003.*.php" after its _bootstrap.php.

$connection = mock_connection();
$user = new MockUserTable($connection);
$user->recreateStorage();

seed_user($user, "Ada", "ada@example.com", 10);
seed_user($user, "Linus", "linus@example.com", 20);
seed_user($user, "Grace", "grace@example.com", 30);
seed_user($user, "Margaret", "margaret@example.com", 40);
seed_user($user, "Dennis", "dennis@elsewhere.org", 50);

function count_with($user, $build)
{
    $user->clear();
    $build($user);
    return intval($user->count());
}

t_section("comparison operators");
check_eq("score = 30", 1, count_with($user, function ($u) {
    $u->setOperator("score", "=", 30);
}));
check_eq("score > 30", 2, count_with($user, function ($u) {
    $u->setOperator("score", ">", 30);
}));
check_eq("score >= 30", 3, count_with($user, function ($u) {
    $u->setOperator("score", ">=", 30);
}));
check_eq("score < 30", 2, count_with($user, function ($u) {
    $u->setOperator("score", "<", 30);
}));
check_eq("score <= 30", 3, count_with($user, function ($u) {
    $u->setOperator("score", "<=", 30);
}));
check_eq("score != 30", 4, count_with($user, function ($u) {
    $u->setOperator("score", "!=", 30);
}));

t_section("between / not-between");
check_eq("score BETWEEN 20 AND 40", 3, count_with($user, function ($u) {
    $u->setOperator("score", "between", 20, 40);
}));
check_eq("score NOT BETWEEN 20 AND 40", 2, count_with($user, function ($u) {
    $u->setOperator("score", "not-between", 20, 40);
}));

t_section("like");
check_eq("email LIKE %example.com%", 4, count_with($user, function ($u) {
    $u->setOperator("email", "like", "example.com");
}));
check_eq("name LIKE %a% (Ada,Grace,Margaret)", 3, count_with($user, function ($u) {
    $u->setOperator("name", "like", "a");
}));

t_section("is-null / is-not-null");
// every row has an email, but "created" was never set -> NULL
check_eq("created IS NULL (all rows)", 5, count_with($user, function ($u) {
    $u->setOperator("created", "is-null");
}));
check_eq("created IS NOT NULL (none)", 0, count_with($user, function ($u) {
    $u->setOperator("created", "is-not-null");
}));

t_section("AND / OR via pushOperator");
check_eq("score > 10 AND score < 50", 3, count_with($user, function ($u) {
    $u->setOperator("score", ">", 10);
    $u->pushOperator("and");
    $u->setOperator("score", "<", 50);
}));
check_eq("score = 10 OR score = 50", 2, count_with($user, function ($u) {
    $u->setOperator("score", "=", 10);
    $u->pushOperator("or");
    $u->setOperator("score", "=", 50);
}));

t_section("parenthesised grouping");
// (score = 10 OR score = 50) AND email LIKE %example.com%  -> only Ada(10)
check_eq("(=10 OR =50) AND like example.com", 1, count_with($user, function ($u) {
    $u->pushOperator("(");
    $u->setOperator("score", "=", 10);
    $u->pushOperator("or");
    $u->setOperator("score", "=", 50);
    $u->pushOperator(")");
    $u->pushOperator("and");
    $u->setOperator("email", "like", "example.com");
}));

t_section("multi-value field filter (IN-like)");
$user->clear();
$user->score = [10, 50];
check_eq("score IN (10,50)", 2, intval($user->count()));

t_section("setOrder ascending / descending");
$user->clear();
$user->setOrder("score", \XYO\Web\DataSource\Order::DESCENDENT);
$names = [];
if ($user->load()) {
    do {
        $names[] = $user->name;
    } while ($user->loadHasNext() && $user->loadNext());
}
check("descending by score: Dennis first", $names[0] === "Dennis");
check("descending by score: Ada last", end($names) === "Ada");

t_section("setFunctionAs validation (API)");
$user->clear();
check("setFunctionAs(MAX) accepted", $user->setFunctionAs("score", "MAX", "maxScore") === true);
check("setFunctionAs rejects unknown function", $user->setFunctionAs("score", "BOGUS", "x") === false);
check("setFunctionAs rejects bad alias", $user->setFunctionAs("score", "MIN", "1bad alias") === false);

t_section("aggregate over GROUP BY");
// NOTE: PostgreSQL enforces the SQL standard - every non-aggregated selected
// column must appear in GROUP BY. The query builder always emits the selected
// columns, so an aggregate is only portable when the SELECT list is restricted
// to the grouped column(s). MySQL/SQLite are lenient and also accept the
// unrestricted form.
$user->clear();
$user->select(["score"]);
$user->setGroup("score", true);
$user->setFunctionAs("score", "MAX", "maxScore");
$user->setOrder("score", \XYO\Web\DataSource\Order::DESCENDENT);
$groups = 0;
$firstMax = null;
if ($user->load()) {
    do {
        if ($firstMax === null) {
            $firstMax = (string) $user->maxScore;
        }
        $groups++;
    } while ($user->loadHasNext() && $user->loadNext());
}
check_eq("GROUP BY score yields 5 groups", 5, $groups);
check_eq("MAX(score) of the top group == 50", "50", $firstMax);

$user->destroyStorage();
t_done();
