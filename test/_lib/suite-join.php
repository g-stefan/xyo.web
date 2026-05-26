<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Driver-agnostic multi-table read path exposed by query.php: a base table
// LEFT JOINed to an outer table, with aliased fields, WHERE operators,
// ordering and counting.
//
// Included by each driver's "0005.*.php" after its _bootstrap.php.

$connection = mock_connection();

$user = new MockUserTable($connection);
$post = new MockPostTable($connection);
$user->recreateStorage();
$post->recreateStorage();

$ada = (function () use ($user) {
    seed_user($user, "Ada", "ada@example.com", 0);
    return intval($user->id);
})();
$linus = (function () use ($user) {
    seed_user($user, "Linus", "linus@example.com", 0);
    return intval($user->id);
})();

function addPost($post, $userId, $title)
{
    $post->empty();
    $post->userId = $userId;
    $post->title = $title;
    $post->insert();
}
addPost($post, $ada, "On Algorithms");
addPost($post, $ada, "The Analytical Engine");
addPost($post, $linus, "On Kernels");

$q = new MockUserPostsQuery($connection);

t_section("count() across the join");
$q->clear();
check_eq("3 posts total", 3, intval($q->count()));

t_section("load() resolves joined author fields");
$q->clear();
$q->setOrder("postId", \XYO\Web\DataSource\Order::ASCENDENT);
check("load() first joined row", $q->load() === true);
check_eq("base field postTitle", "On Algorithms", $q->postTitle);
check_eq("joined field authorName", "Ada", $q->authorName);

t_section("iterate all joined rows");
$titles = [];
$q->clear();
$q->setOrder("postId", \XYO\Web\DataSource\Order::ASCENDENT);
if ($q->load()) {
    do {
        $titles[] = $q->postTitle . " / " . $q->authorName;
    } while ($q->loadHasNext() && $q->loadNext());
}
check_eq("iterated 3 joined rows", 3, count($titles));
check("Linus' post joined to Linus", in_array("On Kernels / Linus", $titles, true));

t_section("WHERE on a joined (outer) field");
$q->clear();
$q->setOperator("authorName", "=", "Ada");
check_eq("2 posts by Ada", 2, intval($q->count()));

t_section("WHERE on a base field");
$q->clear();
$q->setOperator("postTitle", "like", "On ");
check_eq("2 posts starting with 'On '", 2, intval($q->count()));

t_section("LIMIT via load(start,length)");
$q->clear();
$q->setOrder("postId", \XYO\Web\DataSource\Order::ASCENDENT);
$seen = 0;
if ($q->load(0, 2)) {
    do {
        $seen++;
    } while ($q->loadHasNext() && $q->loadNext());
}
check_eq("load(0,2) returns 2 rows", 2, $seen);

$post->destroyStorage();
$user->destroyStorage();
t_done();
