<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Client
    {
        public static function init()
        {
            $info = \XYO\Web\Info::instance();
            $site = $info->sitePath;
            $view = \XYO\Web\View::instance();
            $view->jsLinks->set("xyo.web", $site."site/web/client/web.js","defer");
            $view->jsLinks->set("xyo.web", $site."site/web/client/script.js","defer");
            $view->jsLinks->set("xyo.web", $site."site/web/client/style.js","defer");
            $view->jsLinks->set("xyo.web", $site."site/web/client/html.js","defer");
            $view->jsLinks->set("xyo.web", $site."site/web/client/ajax.js","defer");
            $view->jsLinks->set("xyo.web", $site."site/web/client/component.js","defer");
        }
    }

}
