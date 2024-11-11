<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\Main {

	define("XYO_WEB", 1);
	require_once ("./_site/web/config.php");
	require_once ("./_site/web/info.php");
	require_once ("./_site/web/view.php");
	require_once ("./_site/web/request.php");
	require_once ("./_site/web/firewall.php");
	require_once ("./_site/web/router.php");

	function run()
	{
		\XYO\Web\Config::init();
		$config = \XYO\Web\Config::instance();
		$config->includeFile("./config.php");
		\XYO\Web\Info::init();
		\XYO\Web\View::init();
		\XYO\Web\Request::init();
		\XYO\Web\Firewall::init();
		\XYO\Web\Router::init();
		$router = \XYO\Web\Router::instance();
		$router->run();
	}

}
