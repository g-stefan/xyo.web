<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/request.php");

class Session
{
    protected $tabId = null;
    public function __construct($request)
    {
        $this->tabId = $request->getTabId();
    }

    public function init()
    {
        if (!array_key_exists("_tab", $_SESSION)) {
            $_SESSION["_tab"] = [];
        }
        if (!array_key_exists("_tabTime", $_SESSION)) {
            $_SESSION["_tabTime"] = [];
        }
        if (!array_key_exists($this->tabId, $_SESSION["_tab"])) {
            $_SESSION["_tab"][$this->tabId] = [];
        }
        if (!array_key_exists($this->tabId, $_SESSION["_tabTime"])) {
            $_SESSION["_tabTime"][$this->tabId] = [];
        }
        $currentTime = time();
        $_SESSION["_tabTime"][$this->tabId] = $currentTime;
        foreach ($_SESSION["_tabTime"] as $key => $value) {
            if ($currentTime - $value > (3600 * 8)) {
                unset($_SESSION["_tab"][$key]);
                unset($_SESSION["_tabTime"][$key]);
            }
        }
    }

    public function tabSet($key, $value)
    {
        $_SESSION["_tab"][$this->tabId][$key] = $value;
    }

    public function tabGet($key, $default = null)
    {
        if (!array_key_exists($key, $_SESSION["_tab"][$this->tabId])) {
            return $default;
        }
        return $_SESSION["_tab"][$this->tabId][$key];
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null)
    {
        if (!array_key_exists($key, $_SESSION)) {
            return $default;
        }
        return $_SESSION[$key];
    }
}
