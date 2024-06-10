<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web/view.php");
    require_once ("./site/web/module.php");    
    require_once ("./site/web/component.php");

    class Page extends Module
    {
        protected function setTitle($title)
        {
            $this->view->title = $title;
        }
    }
}
