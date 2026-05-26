<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/view.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/module.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/component.php");

class Page extends Module
{
    public function __construct($web)
    {
        parent::__construct($web);

        $this->loadLanguage();
    }

    public function setTitle($title)
    {
        $this->view->title = $title;
    }

}
