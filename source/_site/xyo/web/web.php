<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

define("XYO_WEB", 1);
define("XYO_WEB_PATH", realpath(__DIR__ . "/../../../") . "/");

require_once(XYO_WEB_PATH . "_site/xyo/web/autoload.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/main.php");
