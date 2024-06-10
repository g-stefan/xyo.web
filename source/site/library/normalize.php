<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Library {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web.php");

    class Normalize extends \XYO\Web\Component
    {

        protected static $name = "normalize";

        public function init(){
            $this->view->cssLinks->set(self::$name, $this->site."site/library/normalize.min.css");
        }

    }

}
