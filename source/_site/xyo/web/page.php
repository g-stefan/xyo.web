<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

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
