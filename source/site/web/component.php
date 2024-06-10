<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web/module.php");

    class Component extends Module
    {        
        public static function &register(&$render, $id = null, $isUnique = false) {
            return $render->registerComponent(static::class, $id, $isUnique);            
        }        
    }
}
