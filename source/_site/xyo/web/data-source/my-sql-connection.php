<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

// This is part of optimized MySQL Driver

class MySQLConnection extends \XYO\Web\DataSource\AbstractSQLConnection
{
    protected $user;
    protected $password;
    protected $server;
    protected $port;
    protected $database;

    // SSL
    protected $sslOn;
    protected $sslKey;
    protected $sslCertificate;
    protected $sslCACertificate;
    protected $sslCAPath;
    protected $sslCipherAlgos;

    public function __construct($configuration)
    {
        parent::__construct();

        $this->db = null;
        $this->prefix = "";
        $this->user = "";
        $this->password = "";
        $this->server = "localhost";
        $this->port = "3306";
        $this->database = "";

        $this->sslOn = false;
        $this->sslKey = null;
        $this->sslCertificate = null;
        $this->sslCACertificate = null;
        $this->sslCAPath = null;
        $this->sslCipherAlgos = null;

        $this->loadConfiguration($configuration);
    }

    protected function applyConfigurationEntry($key, $value)
    {
        if ($key == "ssl") {
            if (array_key_exists("on", $value)) {
                if (($value["on"] === "true") || ($value["on"] == 1)) {
                    $this->sslOn = true;
                }
            }
            if (array_key_exists("key", $value)) {
                $this->sslKey = $value["key"];
            }
            if (array_key_exists("certificate", $value)) {
                $this->sslCertificate = $value["certificate"];
            }
            if (array_key_exists("ca_certificate", $value)) {
                $this->sslCACertificate = $value["ca_certificate"];
            }
            if (array_key_exists("ca_path", $value)) {
                $this->sslCAPath = $value["ca_path"];
            }
            if (array_key_exists("cipher_algos", $value)) {
                $this->sslCipherAlgos = $value["cipher_algos"];
            }
        }
    }

    protected function dsn()
    {
        $dsn = "mysql:host=" . $this->server;
        if (strlen($this->port)) {
            $dsn .= ";port=" . $this->port;
        }
        $dsn .= ";dbname=" . $this->database;
        $dsn .= ";charset=utf8mb4";
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

    protected function connectOptions()
    {
        $options = [];
        $options[\PDO::ATTR_EMULATE_PREPARES] = false;
        if ($this->sslOn) {
            if ($this->sslKey)
                $options[\PDO::MYSQL_ATTR_SSL_KEY] = $this->sslKey;
            if ($this->sslCertificate)
                $options[\PDO::MYSQL_ATTR_SSL_CERT] = $this->sslCertificate;
            if ($this->sslCACertificate)
                $options[\PDO::MYSQL_ATTR_SSL_CA] = $this->sslCACertificate;
            if ($this->sslCAPath)
                $options[\PDO::MYSQL_ATTR_SSL_CAPATH] = $this->sslCAPath;
            if ($this->sslCipherAlgos)
                $options[\PDO::MYSQL_ATTR_SSL_CIPHER] = $this->sslCipherAlgos;
        }
        return $options;
    }

    protected function logChannel()
    {
        return "mysql-connection";
    }

    protected function newTable($connector)
    {
        return new MySQLTable($this, $connector);
    }

    protected function newQuery($connector)
    {
        return new MySQLQuery($this, $connector);
    }
}
