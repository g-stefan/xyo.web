<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Driver-agnostic mock descriptors shared by every driver's test suite.
// They are real subclasses of the framework Table/Query, exactly as an
// application would declare them; only descriptor() is overridden.
//
// Requires the framework table.php / query.php to be loaded first (the
// per-driver _bootstrap.php does that before including this file).
//
// Field tuple: [ type, default, length/attr, autoIncrement/attr ]
//   [0] type     "int" | "bigint" | "varchar" | "text" | "datetime" | ...
//   [1] default  value, null, or the sentinel "DEFAULT" (let DB decide / skip)
//   [2] varchar length, or an extra column attribute for other types
//   [3] "autoIncrement", or another trailing attribute

class MockUserTable extends \XYO\Web\DataSource\Table
{
    public static function descriptor($info)
    {
        $info->name = "user";
        $info->primaryKey = "id";
        $info->fields = [
            "id"      => ["int", null, null, "autoIncrement"],
            "name"    => ["varchar", "", 64],
            "email"   => ["varchar", null, 128],
            "score"   => ["int", 0],
            "created" => ["datetime", null],
        ];
        $info->indexes = ["email"];
    }
}

// Same table after a schema upgrade: "created" removed, "status" added,
// "score" retyped to bigint, index moved from email -> name.
class MockUserTableV2 extends \XYO\Web\DataSource\Table
{
    public static function descriptor($info)
    {
        $info->name = "user";
        $info->primaryKey = "id";
        $info->fields = [
            "id"     => ["int", null, null, "autoIncrement"],
            "name"   => ["varchar", "", 64],
            "email"  => ["varchar", null, 128],
            "score"  => ["bigint", 0],
            "status" => ["varchar", "new", 16],
        ];
        $info->indexes = ["name"];
    }
}

class MockPostTable extends \XYO\Web\DataSource\Table
{
    public static function descriptor($info)
    {
        $info->name = "post";
        $info->primaryKey = "id";
        $info->fields = [
            "id"     => ["int", null, null, "autoIncrement"],
            "userId" => ["int", 0],
            "title"  => ["varchar", "", 128],
        ];
        $info->indexes = ["userId"];
    }
}

// Joined read: posts left-joined to their author. Explicit field maps avoid
// name collisions and exercise the array-spec path of prepareFields().
// join-spec: [ outerField, [baseAlias, baseField] ]  ->  u_.id = p_.userId
class MockUserPostsQuery extends \XYO\Web\DataSource\Query
{
    public static function descriptor($info)
    {
        $info->base = ["p", MockPostTable::class, ["postId" => "id", "postTitle" => "title", "postUserId" => "userId"]];
        $info->outer = [
            "u" => [MockUserTable::class, ["authorId" => "id", "authorName" => "name"], ["id", ["p", "userId"]]],
        ];
    }
}

// Shared row seeder for the user table.
function seed_user($user, $name, $email, $score)
{
    $user->empty();
    $user->name = $name;
    $user->email = $email;
    $user->score = $score;
    return $user->insert();
}
