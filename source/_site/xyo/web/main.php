<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\Main;

require_once(XYO_WEB_PATH . "_site/xyo/web/registry.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/config.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/info.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/view.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/request.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/session.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/firewall.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/router.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/language.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/datasource/connection.php");

function run()
{
    define("XYO_WEB_RUN", 1);

    $web = new \XYO\Web\Registry();
    $config = new \XYO\Web\Config();
    $config->includeByPattern(XYO_WEB_PATH . "config.*.php", XYO_WEB_PATH . "config.php");
    $web->set(\XYO\Web\Config::class, $config);
    $info = new \XYO\Web\Info();
    $web->set(\XYO\Web\Info::class, $info);
    $view = new \XYO\Web\View($config);
    $web->set(\XYO\Web\View::class, $view);
    $request = new \XYO\Web\Request($info);
    $web->set(\XYO\Web\Request::class, $request);
    $session = new \XYO\Web\Session($request);
    $web->set(\XYO\Web\Session::class, $session);
    $web->set(\XYO\Web\Language::class, new \XYO\Web\Language());
    $web->set(\XYO\Web\DataSource\Connection::class, new \XYO\Web\DataSource\Connection());
    $firewall = new \XYO\Web\Firewall($info, $view, $session);

    $router = new \XYO\Web\Router($web, $firewall);

    $router->run();
}

function service($route)
{
    define("XYO_WEB_SERVICE", 1);
    $_SERVER = [];
    $_SERVER["HTTPS"] = "on";
    $_SERVER["HTTP_HOST"] = "127.0.0.1";
    $_SERVER["REQUEST_URI"] = "/" . $route;
    $_SERVER["REQUEST_METHOD"] = "GET";

    $_GET = [];
    $_GET["__"] = $route;

    run();
}

function serviceRun($filename)
{
    define("XYO_WEB_SERVICE_RUN", $filename);

    service("service");
}
