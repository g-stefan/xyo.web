<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type\MySQL;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized MySQL Driver

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/abstract-sql-query.php");

class Query extends \XYO\Web\DataSource\Type\AbstractSQLQuery
{
    protected function quoteIdentifier($name)
    {
        return "`" . str_replace("`", "``", $name) . "`";
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
