<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Request
{
    protected $_cookie;
    protected $_query;
    protected $_post;
    protected $_requestMethod;
    protected $info;
    protected $request;

    public function __construct($info)
    {
        $this->info = $info;
        $this->_cookie = $_COOKIE;
        $this->_query = $_GET;
        $this->_post = $_POST;
        $this->_requestMethod = $_SERVER["REQUEST_METHOD"] ?? "GET";
    }

    // ---

    public function setCookie($name, $value)
    {
        $this->_cookie[$name] = $value;
    }

    public function getCookie($name, $default = null)
    {
        if (!array_key_exists($name, $this->_cookie)) {
            return $default;
        }
        return $this->_cookie[$name];
    }

    public function removeCookie($name)
    {
        unset($this->_cookie[$name]);
    }

    public function clearCookie()
    {
        $this->_cookie = [];
    }

    public function hasCookie($name)
    {
        return array_key_exists($name, $this->_cookie);
    }

    // ---

    public function setQuery($name, $value)
    {
        $this->_query[$name] = $value;
    }

    public function getQuery($name, $default = null)
    {
        if (!array_key_exists($name, $this->_query)) {
            return $default;
        }
        return $this->_query[$name];
    }

    public function removeQuery($name)
    {
        unset($this->_query[$name]);
    }

    public function clearQuery()
    {
        $this->_query = [];
    }

    public function hasQuery($name)
    {
        return array_key_exists($name, $this->_query);
    }

    // ---

    public function setPost($name, $value)
    {
        $this->_post[$name] = $value;
    }

    public function getPost($name, $default = null)
    {
        if (!array_key_exists($name, $this->_post)) {
            return $default;
        }
        return $this->_post[$name];
    }

    public function removePost($name)
    {
        unset($this->_post[$name]);
    }

    public function clearPost()
    {
        $this->_post = [];
    }

    public function hasPost($name)
    {
        return array_key_exists($name, $this->_post);
    }

    // ---

    public function get($name, $default = null)
    {
        $value = $this->getQuery($name, $default);
        $value = $this->getPost($name, $value);
        return $value;
    }

    // ---

    public function isOPTIONS()
    {
        return ($this->_requestMethod === "OPTIONS");
    }

    public function isGET()
    {
        return ($this->_requestMethod === "GET");
    }

    public function isPOST()
    {
        return ($this->_requestMethod === "POST");
    }

    public function isPUT()
    {
        return ($this->_requestMethod === "PUT");
    }

    public function isPATCH()
    {
        return ($this->_requestMethod === "PATCH");
    }

    public function isDELETE()
    {
        return ($this->_requestMethod === "DELETE");
    }

    public function isAJAX()
    {
        if ($this->getPost("_ajax", "0") === "1") {
            return true;
        }
        if ($this->getQuery("_ajax", "0") === "1") {
            return true;
        }

        return false;
    }

    public function isJSON()
    {
        if (array_key_exists("CONTENT_TYPE", $_SERVER)) {
            if (strtolower($_SERVER["CONTENT_TYPE"]) === "application/json") {
                return true;
            }
        }
        if ($this->getPost("_json", "0") === "1") {
            return true;
        }
        if ($this->getQuery("_json", "0") === "1") {
            return true;
        }

        return false;
    }

    public function isAPI()
    {
        return ($this->info->routeType == $this->info->routeTypeAPI);
    }

    public function isComponent($id)
    {
        $component = $this->get("_component", "");
        return ($component === $id);
    }

    public function isComponentAJAX($id)
    {
        if (!$this->isAJAX()) {
            return false;
        }
        return $this->isComponent($id);
    }

    public function isComponentJSON($id)
    {
        if (!$this->isJSON()) {
            return false;
        }
        return $this->isComponent($id);
    }

    public function getTabId()
    {
        $tab = preg_replace("/[^a-z0-9]/i", "", (string) $this->get("_tab", ""));
        return substr($tab, 0, 32) ?: "_default";
    }
    
}
