<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Driver-agnostic: a genuine database error must reach the caller as a
// DataSourceException (like load()), not be flattened into a false/0 return.
// tryLoad() stays the explicit non-throwing escape hatch, and caller
// preconditions (empty update, missing primary-key value) still return false.
//
// NOTE: this suite deliberately runs queries against a missing table, so the
// framework logs those (intentional) database errors to source/_log.

$connection = mock_connection();
$user = new MockUserTable($connection);

// True iff $fn() throws a DataSourceException.
function throws_dsx($fn)
{
    try {
        $fn();
    } catch (\XYO\Web\DataSource\DataSourceException $e) {
        return true;
    } catch (\Throwable $e) {
        return false;
    }
    return false;
}

t_section("data operations throw DataSourceException on a database error");
// Drop the table so every query fails at the database level.
$user->destroyStorage();

check("count() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->count();
}));
check("load() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->load();
}));
check("insert() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->empty();
    $user->name = "x";
    $user->email = "y@example.com";
    $user->insert();
}));
check("update() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->setOperator("score", ">", 0);
    $user->update(["score" => 1]);
}));
check("delete() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->id = 1;
    $user->delete();
}));
check("save() (update path) throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->id = 1;
    $user->name = "x";
    $user->save();
}));
check("atomicAdd() throws when the table is missing", throws_dsx(function () use ($user) {
    $user->clear();
    $user->id = 1;
    $user->atomicAdd("score", 1);
}));

t_section("tryLoad() stays non-throwing");
$threw = false;
try {
    $user->clear();
    $r = $user->tryLoad();
    check("tryLoad() returns false on a database error", $r === false);
} catch (\Throwable $e) {
    $threw = true;
}
check("tryLoad() did not throw", $threw === false);

t_section("caller preconditions still return false (not exceptions)");
$user->recreateStorage(); // table exists again
check("update([]) returns false (nothing to update)", $user->update([]) === false);
$user->clear();
check("atomicAdd() without a primary-key value returns false", $user->atomicAdd("score", 1) === false);

// leave storage clean
$user->destroyStorage();
t_done();
