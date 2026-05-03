<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once("./_site/xyo/web/module.php");

    class Layout extends Module
    {

        public function renderHead()
        {
            $this->view->renderMeta();
            $this->view->renderLinks();
            $this->view->renderTitle();
            $this->view->renderCSSLinks();
            $this->view->renderCSSSource();
        }

        public function renderScripts()
        {
            $this->view->renderJSLinks();
            $this->view->renderJSSource();
        }

        public function renderLayout(&$page = null)
        {

        }

        public function renderPage(&$page, $options = null)
        {
            $page->render($options);
        }

        public function renderHTMLClasses()
        {
            $this->view->renderHTMLClasses();
        }

        public function renderLanguage()
        {
            $this->view->renderLanguage();
        }

        public function renderBodyClasses()
        {
            $this->view->renderBodyClasses();
        }

    }
}