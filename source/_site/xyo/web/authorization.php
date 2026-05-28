<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Authorization
{
    protected $web;
    protected $info;
    protected $config;
    protected $request;
    protected $_dsConnection;

    public function __construct($web)
    {
        $this->web = $web;

        $this->info = $web->get(\XYO\Web\Info::class);
        $this->config = $web->get(\XYO\Web\Config::class);
        $this->request = $web->get(\XYO\Web\Request::class);
        $this->_dsConnection = $web->get(\XYO\Web\DataSource\Connection::class);

    }

    public function checkOPTIONS()
    {
        return true;
    }

    public function checkGET()
    {
        return true;
    }

    public function checkPOST()
    {
        return true;
    }

    public function checkPUT()
    {
        return true;
    }

    public function checkPATCH()
    {
        return true;
    }

    public function checkDELETE()
    {
        return true;
    }

    public function setHeaders()
    {
    }

    public function checkBearerToken($bearerToken)
    {
        if ($this->request->isAPI()) {
            if (is_null($bearerToken)) {
                return false;
            }
            if ($this->config->has("api")) {
                if ($this->config->get("api")->has("authorizationBearerToken")) {
                    $authorizationToken = $this->config->get("api")->get("authorizationBearerToken");
                    if (!empty($authorizationToken)) {
                        return hash_equals($authorizationToken, $bearerToken);
                    }
                }
            }
            return false;
        }
        return true;
    }

    public function checkCSRF()
    {

        if ($this->request->isPOST()) {
            return true;
        }
        if ($this->request->isPUT()) {
            return true;
        }
        if ($this->request->isPATCH()) {
            return true;
        }
        if ($this->request->isDELETE()) {
            return true;
        }

        return false;
    }

    public function sessionSet($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function sessionGet($key, $defaultValue = null)
    {
        if (!array_key_exists($key, $_SESSION)) {
            return $defaultValue;
        }
        return $_SESSION[$key];
    }

    public function getDataSource($className, $connectionName = null)
    {
        return $this->_dsConnection->getDataSource($className, $connectionName);
    }

    public function requireTokenReset()
    {
        return false;
    }


}
