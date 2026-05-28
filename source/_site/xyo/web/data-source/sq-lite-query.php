<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized SQLite Driver

class SQLiteQuery extends \XYO\Web\DataSource\AbstractSQLQuery
{
    protected function quoteIdentifier($name)
    {
        $name = str_replace("[", "[[", $name);
        $name = str_replace("]", "]]", $name);
        return "[" . $name . "]";
    }

    protected function limitClause($start, $length)
    {
        $start = max(0, intval($start));
        $clause = " LIMIT " . $start;
        if ($length) {
            $length = max(0, intval($length));
            $clause .= "," . $length;
        }
        return $clause;
    }
}
