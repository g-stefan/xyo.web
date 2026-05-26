<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\Library;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/web.php");

class XYOWebCSS extends \XYO\Web\Component
{
    protected static $name = "xyo-web-css";

    public function init($options = null)
    {
        $this->view->cssLinks->set(self::$name, $this->site . "_site/xyo/web/library/xyo-web.css");
    }

}
