<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized SQLite Driver

class SQLiteConnection extends \XYO\Web\DataSource\AbstractSQLConnection
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
        return new SQLiteTable($this, $connector);
    }

    protected function newQuery($connector)
    {
        return new SQLiteQuery($this, $connector);
    }
}
