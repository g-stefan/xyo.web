<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type\PostgreSQL;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized PostgreSQL Driver

require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/abstract-sql-connection.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/postgresql-table.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/postgresql-query.php");

class Connection extends \XYO\Web\DataSource\Type\AbstractSQLConnection
{
    protected $user;
    protected $password;
    protected $server;
    protected $port;
    protected $database;

    public function __construct($configuration)
    {
        parent::__construct();

        $this->db = null;
        $this->prefix = "";
        $this->user = "";
        $this->password = "";
        $this->server = "";
        $this->port = "";
        $this->database = "";

        $this->loadConfiguration($configuration);
    }

    protected function dsn()
    {
        $dsn = "pgsql:host=" . $this->server;
        if (strlen($this->port)) {
            $dsn .= ";port=" . $this->port;
        }
        $dsn .= ";dbname=" . $this->database;
        return $dsn;
    }

    protected function connectUser()
    {
        return $this->user;
    }

    protected function connectPassword()
    {
        return $this->password;
    }

    protected function logChannel()
    {
        return "postgresql-connection";
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
