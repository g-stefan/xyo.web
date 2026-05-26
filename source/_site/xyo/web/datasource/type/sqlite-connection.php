<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type\SQLite;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized SQLite Driver

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/abstract-sql-connection.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/sqlite-table.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/sqlite-query.php");

class Connection extends \XYO\Web\DataSource\Type\AbstractSQLConnection
{
    protected $database;

    public function __construct($configuration)
    {
        parent::__construct();

        $this->db = null;
        $this->prefix = "";
        $this->database = "";

        $this->loadConfiguration($configuration);
    }

    protected function dsn()
    {
        return "sqlite:" . $this->database;
    }

    protected function logChannel()
    {
        return "sqlite-connection";
    }

    protected function newTable($connector)
    {
        return new Table($this, $connector);
    }

    protected function newQuery($connector)
    {
        return new Query($this, $connector);
    }
}
