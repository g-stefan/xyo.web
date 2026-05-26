<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

define("XYO_WEB", 1);
define("XYO_WEB_PATH", realpath(__DIR__ . "/../../../") . "/");

require_once(XYO_WEB_PATH . "_site/xyo/web/main.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/component.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/component-form.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/page.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/layout.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/log.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/empty-field.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/table-info.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/query-info.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/query.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/table.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/connection.php");
