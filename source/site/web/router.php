<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {
	defined("XYO_WEB") or die("Forbidden");

	require_once ("./site/web/info.php");
	require_once ("./site/web/view.php");
	require_once ("./site/web/firewall.php");
	require_once ("./site/web/client.php");
	require_once ("./site/web/datasource/connections.php");

	class Router
	{
		private static $instance = null;
		protected $info;
		protected $firewall;
		protected $request;

		protected function __construct()
		{
			$this->firewall = \XYO\Web\Firewall::instance();
			$this->request = \XYO\Web\Request::instance();
			$this->info = \XYO\Web\Info::instance();
			$this->info->path = "./";
			$this->info->location = "";
			$this->info->sitePath = "";
			$this->info->routeType = $this->info->routeTypeUnknown;
			$this->info->routeFile = "";
		}

		public static function instance()
		{
			return self::$instance;
		}

		public static function init()
		{
			self::$instance = new Router();
		}

		public function run()
		{
			$this->firewall->prepare();
			if (!$this->sitePathFromRequestURI()) {
				$this->renderError("400");
				return;
			}
			if (!$this->requestPathCheckEnding()) {
				$this->renderRedirect($this->info->location);
				return;
			}
			if (!$this->pathCheckBounds($this->info->path)) {
				$this->renderError("400");
				return;
			}

			if ($this->findPage($this->info->path, $this->info->routeFile)) {
				$this->info->routeType=$this->info->routeTypePage;
			} else
			if ($this->findAPI($this->info->path, $this->info->routeFile)) {
				$this->info->routeType=$this->info->routeTypeAPI;
			} else 
			if (!$this->findSlug($this->info->path, $this->info->routeFile)) {
				$this->info->routeType=$this->info->routeTypeSlug;
			}

			\XYO\Web\DataSource\Connections::init();

			if (!$this->firewall->run()) {
				$this->renderError("401");
				return;
			}

			if($this->info->routeType==$this->info->routeTypePage){
				$this->renderPage($this->info->routeFile, $this->info->path);
				return;
			}
			if($this->info->routeType==$this->info->routeTypeAPI){
				$this->renderAPI($this->info->routeFile, $this->info->path);
				return;
			}
			if($this->info->routeType==$this->info->routeTypeSlug){
				$this->renderSlug($this->info->routeFile, $this->info->path);
				return;
			}

			$this->renderError("404");			
		}

		public function renderPage($page, $path)
		{
			$this->info->path = $path;
			
			if ($this->request->isAJAX() || $this->request->isJSON()) {
				$component = $this->request->get("_component", "");
				if (strlen($component) > 0) {
					$pageClass = require ($page);
					$page = $pageClass::instance();
					$page->init();
					$page->initComponent($component);
					$page->renderComponent($component);
					return;
				}
			}
			\XYO\Web\Client::init();
			$layout = $this->findLayout($path);
			$pageClass = require ($page);
			$page = $pageClass::instance();
			$layoutClass = require ($layout);
			$layout = $layoutClass::instance();
			$view = \XYO\Web\View::instance();
			$layout->init();
			$layout->initComponents();
			$page->init();
			$page->initComponents();
			$layout->render($page);
		}

		public function renderAPI($page, $path)
		{
			$this->info->path = $path;			

			$pageClass = require ($page);
			$page = $pageClass::instance();
			$page->init();
			$page->render();
		}

		public function requestPathCheckEnding()
		{
			$request = "";
			if (array_key_exists("__", $_GET)) {
				$path = trim($_GET["__"]);
				//
				// Redirect .../page/ to .../page
				//
				if (substr($path, -1) == "/") {
					$protocol = "http";
					if (array_key_exists("HTTPS", $_SERVER)) {
						if (strcmp(strtolower($_SERVER["HTTPS"]), "on") == 0) {
							$protocol = "https";
						} else
							if (strcmp($_SERVER["HTTPS"], "1") == 0) {
								$protocol = "https";
							}
					}

					$location = $protocol . "://" . $_SERVER["HTTP_HOST"] . substr($_SERVER["REQUEST_URI"], 0, -1);
					$pos = strpos($_SERVER["REQUEST_URI"], "?", 0);
					if ($pos !== FALSE) {
						$location = $protocol . "://" . $_SERVER["HTTP_HOST"] . substr($_SERVER["REQUEST_URI"], 0, $pos - 1) . substr($_SERVER["REQUEST_URI"], $pos);
					}

					$this->info->location = $location;
					return false;
				}
				if (strlen($path)) {
					$cwd = str_replace("\\", "/", getcwd());
					if (!(strcmp($path, $cwd) == 0)) {
						$request = $path;
					}
				}
			}
			$this->info->path = $request;
			return true;
		}

		public function pathCheckBounds(&$path)
		{
			$scan = explode("/", $path);
			$pathList = array();
			foreach ($scan as $dir) {
				if ($dir == ".") {
					continue;
				}
				if ($dir == "..") {
					if (count($pathList) == 0) {
						return false;
					}
					array_pop($pathList);
					continue;
				}
				$pathList[] = $dir;
			}
			$path = implode("/", $pathList);
			return true;
		}

		public function buildPathSearch($path)
		{
			$pathSearchList = array();
			$searchList = explode("/", $path);
			array_unshift($searchList, ".");
			array_pop($searchList);
			$count = count($searchList);
			while ($count > 0) {
				$pathSearchList[] = implode("/", $searchList) . "/";
				array_pop($searchList);
				--$count;
			}
			return $pathSearchList;
		}

		public function findItem($pathSearchList, $item)
		{
			foreach ($pathSearchList as $path) {
				if (file_exists($path . $item)) {
					return $path . $item;
				}
			}
			return null;
		}

		public function findPage($path, &$page)
		{
			if(strlen($path)==0){
				$path=".";
			};
			$page = $path . "/page.php";
			if (!file_exists($page)) {
				if (strlen($path) != 0) {
					return false;
				}
				$page = "./site/_default/page.php";
			}
			return true;
		}

		public function findAPI($path, &$pageAPI)
		{
			if(strlen($path)==0){
				$path=".";
			};
			$pageAPI = $path . "/api.php";
			return file_exists($pageAPI);
		}

		public function findSlug($path, &$slug)
		{
			$pathSearchList = $this->buildPathSearch($path);
			$slug = $this->findItem($pathSearchList, "slug.php");
			if (is_null($slug)) {
				return false;
			}
		}

		public function findLayout($path)
		{
			$pathSearchList = $this->buildPathSearch($path);
			array_unshift($pathSearchList, $path . "/");
			$pathSearchList[] = "./site/_default/";
			return $this->findItem($pathSearchList, "layout.php");
		}

		public function renderRedirect($location)
		{
			$pathSearchList = array();
			$pathSearchList[] = "./";
			$pathSearchList[] = "./site/_default/";
			$this->renderPage($this->findItem($pathSearchList, "301.php"), "./");
		}

		public function renderError($error)
		{
			$pathSearchList = array();
			$pathSearchList[] = "./";
			$pathSearchList[] = "./site/_default/";
			$this->renderPage($this->findItem($pathSearchList, $error . ".php"), "./");
		}

		public function renderSlug($slug, $path)
		{
			$this->renderPage($slug, $path);
		}

		public function sitePathFromRequestURI()
		{
			if (array_key_exists("REQUEST_URI", $_SERVER)) {
				$requestURI = $_SERVER["REQUEST_URI"];
				$tagIndex = strpos($requestURI, "?__=", 0);
				if ($tagIndex !== false) {
					return false;
				}
				$tagIndex = strpos($requestURI, "&__=", 0);
				if ($tagIndex !== false) {
					return false;
				}
				$queryIndex = @strrpos($requestURI, "?", -1);
				if ($queryIndex !== false) {
					$requestURI = substr($requestURI, 0, $queryIndex);
				}
				if (array_key_exists("__", $_GET)) {
					$path = $_GET["__"];
					if (strlen($path) > 0) {
						$cwd = str_replace("\\", "/", getcwd());
						if (strcmp($path, $cwd) == 0) {
							if (substr($requestURI, -1) == "/") {
								$this->info->sitePath = $requestURI;
								return true;
							}
							$this->info->sitePath = $requestURI . "/";
							return true;

						}
						$this->info->sitePath = substr($requestURI, 0, strlen($requestURI) - strlen($path));
						return true;
					}
				}
				$this->info->sitePath = $requestURI;
				$index = strpos($this->info->sitePath, ".php", 0);
				if ($index === false) {
					return true;
				}
				$index = strrpos($this->info->sitePath, "/", -1);
				if ($index === false) {
					$this->info->sitePath = "/";
					return true;
				}
				$this->info->sitePath = substr($this->info->sitePath, 0, $index + 1);
				return true;
			}
			return false;
		}

	}

}

