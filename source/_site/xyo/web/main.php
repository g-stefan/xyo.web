<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\Main {

	define("XYO_WEB", 1);
	require_once("./_site/xyo/web/config.php");
	require_once("./_site/xyo/web/info.php");
	require_once("./_site/xyo/web/view.php");
	require_once("./_site/xyo/web/request.php");
	require_once("./_site/xyo/web/firewall.php");
	require_once("./_site/xyo/web/router.php");
	require_once("./_site/xyo/web/language.php");

	function run()
	{
		define("XYO_WEB_RUN", 1);
		\XYO\Web\Config::init();
		$config = \XYO\Web\Config::instance();
		$config->includeByPattern("./config.*.php", "./config.php");
		\XYO\Web\Info::init();
		\XYO\Web\View::init();
		\XYO\Web\Request::init();
		\XYO\Web\Firewall::init();
		\XYO\Web\Router::init();
		\XYO\Web\Language::init();
		$router = \XYO\Web\Router::instance();
		$router->run();
	}

	function service($route)
	{
		define("XYO_WEB_SERVICE", 1);
		$_SERVER = array();
		$_SERVER["HTTPS"] = "on";
		$_SERVER["HTTP_HOST"] = "127.0.0.1";
		$_SERVER["REQUEST_URI"] = "/" . $route;
		$_SERVER["REQUEST_METHOD"] = "GET";

		$_GET = array();
		$_GET["__"] = $route;

		run();
	}

	function serviceRun($filename)
	{
		define("XYO_WEB_SERVICE_RUN", $filename);

		service(basename(__FILE__, ".php"));
	}

}
