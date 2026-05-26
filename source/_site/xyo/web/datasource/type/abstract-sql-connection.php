<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource\Type;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/log.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/empty-field.php");

use XYO\Web\DataSource\EmptyField;

// Shared PDO connection logic for the SQL drivers.
// Subclasses provide only the dialect specifics: the DSN, the PDO
// credentials/options, the log channel name and the concrete Table/Query.

abstract class AbstractSQLConnection
{
    protected $db;
    protected $prefix;
    protected $error = null;
    public $_empty = null;

    public function __construct()
    {
        $this->_empty = new EmptyField();
    }

    // --- dialect hooks

    abstract protected function dsn();

    abstract protected function logChannel();

    abstract protected function newTable($connector);

    abstract protected function newQuery($connector);

    protected function connectUser()
    {
        return "";
    }

    protected function connectPassword()
    {
        return "";
    }

    protected function connectOptions()
    {
        return [];
    }

    // Hook for per-key configuration handling beyond plain property
    // assignment (e.g. MySQL's nested "ssl" block).
    protected function applyConfigurationEntry($key, $value)
    {
    }

    // --- shared configuration

    protected function loadConfiguration($configuration)
    {
        foreach ($configuration as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
            $this->applyConfigurationEntry($key, $value);
        }
    }

    // --- shared connection lifecycle

    public function open()
    {
        if ($this->db) {
            return true;
        }
        try {
            $this->db = new \PDO($this->dsn(), $this->connectUser(), $this->connectPassword(), $this->connectOptions());
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->logError($e);
            $this->db = null;
            return false;
        }
        return true;
    }

    public function isOpen()
    {
        return $this->db !== null;
    }

    public function close()
    {
        if ($this->db) {
            $this->db = null;
        }
    }

    // --- shared query helpers

    public function query($query)
    {
        try {
            $stmt = $this->db->query($query);
            if (!$stmt) {
                return null;
            }
        } catch (\PDOException $e) {
            $this->logError($e);
            return null;
        }
        return $stmt;
    }

    public function queryPrepare($query, $params = [])
    {
        if (empty($params)) {
            return $this->query($query);
        }
        try {
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                return null;
            }
            if (!$stmt->execute($params)) {
                return null;
            }
        } catch (\PDOException $e) {
            $this->logError($e);
            return null;
        }
        return $stmt;
    }

    public function queryValue($query, $default = null)
    {
        $result = $this->query($query);
        if ($result) {
            $data = $result->fetch(\PDO::FETCH_NUM);
            if ($data) {
                return $data[0];
            }
        }
        return $default;
    }

    // --- shared transactions

    public function beginTransaction()
    {
        try {
            return $this->db->beginTransaction();
        } catch (\PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    public function commit()
    {
        try {
            return $this->db->commit();
        } catch (\PDOException $e) {
            $this->logError($e);
            return false;
        }
    }

    public function rollBack()
    {
        try {
            if ($this->db->inTransaction()) {
                return $this->db->rollBack();
            }
        } catch (\PDOException $e) {
            $this->logError($e);
        }
        return false;
    }

    // --- shared connectors

    public function connectTable($connector = null)
    {
        return $this->newTable($connector);
    }

    public function connectQuery($connector = null)
    {
        return $this->newQuery($connector);
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    // Last database error message, or null if none has occurred. The SQL
    // drivers read this to build a meaningful DataSourceException.
    public function lastError()
    {
        return $this->error;
    }

    protected function logError($e)
    {
        $this->error = $e->getMessage();
        \XYO\Web\Log::logMessage($this->logChannel(), ["datetime" => date("Y-m-d H:i:s"), "message" => $e->getMessage()]);
    }
}
