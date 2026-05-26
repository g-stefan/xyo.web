<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");
class Connection
{
    protected $connection;

    public function __construct()
    {
        $this->connection = [];
    }

    public function init($config)
    {
        if ($config->has("dataSource")) {
            $connection = $config->get("dataSource")->getArray("connection");
            foreach ($connection as $name => $info) {
                if(!$this->set($name, $info)){
                    return false;
                }
            }
        }
        return true;
    }

    public function set($name, $configuration)
    {
        if (!array_key_exists("type", $configuration)) {
            return false;
        }
        $type = $configuration["type"];
        $typeClass = null;
        if($type==="sqlite") {
            require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/sqlite-connection.php");
            $typeClass = \XYO\Web\DataSource\Type\SQLite\Connection::class;
        }
        if($type==="mysql") {
            require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/mysql-connection.php");
            $typeClass = \XYO\Web\DataSource\Type\MySQL\Connection::class;
        }
        if($type==="postgresql") {
            require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/type/postgresql-connection.php");
            $typeClass = \XYO\Web\DataSource\Type\PostgreSQL\Connection::class;
        }
        if(is_null($typeClass)) {
            return false;
        }        
        $this->connection[$name] = new $typeClass($configuration);
        return true;
    }

    public function has($name)
    {
        return array_key_exists($name, $this->connection);
    }

    public function get($name = null)
    {
        if (is_null($name)) {
            $name = "db";
        }
        if (!array_key_exists($name, $this->connection)) {
            return null;
        }
        if(!$this->connection[$name]->open()){
            return null;
        }
        return $this->connection[$name];
    }

    public function getDataSource($className, $connectionName = null)
    {
        return new $className($this->get($connectionName));
    }

}
