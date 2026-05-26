<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Component extends Module
{    
    public static function register($render, $id = null, $options = null)
    {
        return $render->registerComponent(static::class, $id, $options);
    }

    public static function registerAndInit($render, $id = null, $options = null)
    {
        return $render->registerAndInitComponent(static::class, $id, $options);
    }

    public function renderAJAX($options = null) {}

    public function renderContainer($options = null) {}

    public function render($options = null)
    {
        ($this->isComponentAJAX()) ? $this->renderAJAX($options) : $this->renderContainer($options);
    }
}
