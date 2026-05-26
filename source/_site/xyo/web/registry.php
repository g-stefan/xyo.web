<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Registry
{
    protected $value = null;

    public function __construct()
    {
        $this->value = [];
    }

    public function set($className, $value)
    {
        $this->value[$className] = $value;
    }

    public function get($className)
    {
        if (!array_key_exists($className, $this->value)) {
            throw new \Exception("registry.unknown.class: ".$className);
        }
        return $this->value[$className];
    }

}
