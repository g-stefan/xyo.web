<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Client
{
    public static function init($view, $site)
    {
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/web.js", "defer");
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/script.js", "defer");
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/style.js", "defer");
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/html.js", "defer");
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/ajax.js", "defer");
        $view->jsLinks->set("xyo.web", $site . "_site/xyo/web/client/component.js", "defer");
    }

}
