<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web/module.php");

    class Layout extends Module
    {

        function renderHead()
        {
            $this->view->renderMeta();
            $this->view->renderLinks();
            $this->view->renderTitle();
            $this->view->renderCSSLinks();
            $this->view->renderCSSSource();
        }

        function renderScripts()
        {
            $this->view->renderJSLinks();
            $this->view->renderJSSource();
        }

        function renderPage(&$page, $options = null)
        {
            $page->render($options);
        }

        function renderHTMLClasses()
        {
            $this->view->renderHTMLClasses();
        }

        function renderBodyClasses()
        {
            $this->view->renderBodyClasses();
        }

    }
}