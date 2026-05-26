<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

use Exception;
use stdClass;

defined("XYO_WEB") or die("Forbidden");

class Router
{
    protected $web;
    protected $info;
    protected $firewall;
    protected $request;
    protected $config;

    protected $view;
    protected $dsConnection;

    public function __construct($web, $firewall)
    {
        $this->web = $web;
        $this->firewall = $firewall;
        $this->request = $web->get(\XYO\Web\Request::class);
        $this->info = $web->get(\XYO\Web\Info::class);
        $this->info->path = XYO_WEB_PATH;
        $this->info->location = "";
        $this->info->site = "";
        $this->info->routeType = $this->info->routeTypeUnknown;
        $this->info->routeFile = "";
        $this->info->authorization = null;

        $this->config = $web->get(\XYO\Web\Config::class);
        $this->view = $web->get(\XYO\Web\View::class);
        $this->dsConnection = $web->get(\XYO\Web\DataSource\Connection::class);
    }

    public function run()
    {
        try {
            if (!$this->siteFromRequestURI()) {
                $this->renderError("400");
                return;
            }
            if (!$this->requestPathCheckEnding()) {
                $this->renderRedirect();
                return;
            }
            if (!$this->pathCheckBounds($this->info->path)) {
                $this->renderError("400");
                return;
            }

            $this->resolve();

            if (!$this->dsConnection->init($this->config)) {
                $this->renderError("501");
                return;
            }

            $reason = "";
            if (!$this->authorize($reason)) {
                $this->renderError($reason);
                return;
            }

            if ($this->request->isOPTIONS()) {
                return;
            }

            $this->render();
        } catch (\Throwable $e) {
            $this->renderError("501");
            \XYO\Web\Log::logMessage("router", ["datetime" => date("Y-m-d H:i:s"), "message" => $e->getMessage()]);
        }
    }

    public function resolve()
    {
        if ($this->findPage($this->info->path, $this->info->routeFile)) {
            $this->info->routeType = $this->info->routeTypePage;
        } elseif ($this->findAPI($this->info->path, $this->info->routeFile)) {
            $this->info->routeType = $this->info->routeTypeAPI;
        } elseif ($this->findSlug($this->info->path, $this->info->routeFile)) {
            $this->info->routeType = $this->info->routeTypeSlug;
        } elseif (defined("XYO_WEB_SERVICE")) {
            if ($this->findService($this->info->path, $this->info->routeFile)) {
                $this->info->routeType = $this->info->routeTypeService;
            }
        }
    }

    public function authorize(&$reason)
    {
        $this->info->authorization = new \XYO\Web\Authorization(
            $this->info,
            $this->config,
            $this->request,
            $this->dsConnection
        );
        if ($this->info->routeType != $this->info->routeTypeUnknown) {
            $authorizationFile = $this->findAuthorization($this->info->path);
            if (!empty($authorizationFile)) {
                $authorizationClass = require($authorizationFile);
                if (!class_exists($authorizationClass, false)) {
                    $reason = "501";
                    return false;
                }
                if (!is_subclass_of($authorizationClass, \XYO\Web\Authorization::class)) {
                    $reason = "501";
                    return false;
                }
                $this->info->authorization = new $authorizationClass(
                    $this->info,
                    $this->config,
                    $this->request,
                    $this->dsConnection
                );
            }
        }

        if (!$this->firewall->run()) {
            $reason = "401";
            return false;
        }
        return true;
    }

    public function render()
    {
        if ($this->info->routeType == $this->info->routeTypePage) {
            $this->renderPage($this->info->routeFile, $this->info->path);
            return;
        }
        if ($this->info->routeType == $this->info->routeTypeAPI) {
            $this->renderAPI($this->info->routeFile, $this->info->path);
            return;
        }
        if ($this->info->routeType == $this->info->routeTypeSlug) {
            $this->renderSlug($this->info->routeFile, $this->info->path);
            return;
        }
        if ($this->info->routeType == $this->info->routeTypeService) {
            $this->renderService($this->info->routeFile, $this->info->path);
            return;
        }

        $this->renderError("404");
    }

    public function isModuleClass($className)
    {
        if (!class_exists($className, false)) {
            $this->renderError("501");
            return false;
        }
        if (!is_subclass_of($className, \XYO\Web\Module::class)) {
            $this->renderError("501");
            return false;
        }
        return true;
    }

    public function renderPage($page, $path)
    {
        $this->info->path = $path;

        if ($this->request->isAJAX() || $this->request->isJSON()) {
            $component = $this->request->get("_component", "");
            if (strlen($component) > 0) {
                $pageClass = require($page);
                if (!$this->isModuleClass($pageClass)) {
                    return;
                }
                $this->info->page = new $pageClass($this->web);
                $this->info->page->init();
                $this->info->page->initComponent($component);
                $this->info->page->process();
                session_write_close();
                $this->info->page->renderComponent($component);
                return;
            }
            $componentList = $this->request->get("_batch", "");
            if (strlen($componentList) > 0) {
                $idList = array_filter(array_map("trim", explode(",", $componentList)));
                $pageClass = require($page);
                if (!$this->isModuleClass($pageClass)) {
                    return;
                }
                $this->info->page = new $pageClass($this->web);
                $this->info->page->init();
                foreach ($idList as $id) {
                    $this->info->page->initComponent($id);
                }
                $this->info->page->process();
                session_write_close();
                $out = [];
                foreach ($idList as $id) {
                    $out[] = [$id, $this->info->page->strRenderComponent($id)];
                }
                header("Content-Type: application/json");
                echo json_encode($out);
                return;
            }
        }

        Client::init($this->view, $this->info->site);

        $layout = $this->findLayout($path);
        $layoutClass = require($layout);
        if (!$this->isModuleClass($layoutClass)) {
            return;
        }
        $this->info->layout = new $layoutClass($this->web);
        $pageClass = require($page);
        if (!$this->isModuleClass($pageClass)) {
            return;
        }
        $this->info->page = new $pageClass($this->web);
        $this->info->layout->init();
        $this->info->layout->initComponents();

        $this->info->page->init();
        $this->info->page->initComponents();
        $this->info->page->process();

        $this->info->layout->process();
        session_write_close();
        $this->info->layout->renderLayout($this->info->page);
    }

    public function renderAPI($apiFile, $path)
    {
        $this->info->path = $path;

        $apiClass = require($apiFile);
        if (!$this->isModuleClass($apiClass)) {
            return;
        }
        $this->info->api = new $apiClass($this->web);
        $this->info->api->init();
        $this->info->api->process();
        session_write_close();
        $this->info->api->render();
    }

    public function renderSlug($slug, $path)
    {
        $this->renderPage($slug, $path);
    }

    public function renderService($serviceFile, $path)
    {
        $this->info->path = $path;

        $serviceClass = require($serviceFile);
        if (!$this->isModuleClass($serviceClass)) {
            return;
        }
        $this->info->api = new $serviceClass($this->web);
        $this->info->api->init();
        $this->info->api->process();
        session_write_close();
        $this->info->api->render();
    }

    public function requestPathCheckEnding()
    {
        $request = "";
        $path = $this->request->getQuery("__", null);
        if (!is_null($path)) {
            $path = trim($path);
            //
            // Redirect .../page/ to .../page
            //
            if (substr($path, -1) == "/") {

                $location = substr($_SERVER["REQUEST_URI"], 0, -1);
                $pos = strpos($_SERVER["REQUEST_URI"], "?", 0);
                if ($pos !== false) {
                    $location = substr($_SERVER["REQUEST_URI"], 0, $pos - 1) . substr($_SERVER["REQUEST_URI"], $pos);
                }

                $this->info->location = $location;
                return false;
            }
            if (strlen($path)) {
                $cwd = str_replace("\\", "/", getcwd());
                if (!($path === $cwd)) {
                    $request = $path;
                }
            }
        }
        $this->info->path = $request;
        return true;
    }

    public function pathCheckBounds(&$path)
    {
        $path = str_replace("\\", "/", $path);
        // Prevent wrappers and absolute paths
        if (preg_match('/^([a-zA-Z]:|\/|\\\\)/', $path) || strpos($path, '://') !== false) {
            return false;
        }

        $scan = explode("/", $path);
        $pathList = [];
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
        $pathSearchList = [];
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
            if (file_exists(XYO_WEB_PATH . $path . $item)) {
                return XYO_WEB_PATH . $path . $item;
            }
        }
        return null;
    }

    public function findPage($path, &$page)
    {
        if (strlen($path) == 0) {
            $path = ".";
        }
        $page = XYO_WEB_PATH . $path . "/page.php";
        if (!file_exists($page)) {
            if (!($path === ".")) {
                return false;
            }
            $page = XYO_WEB_PATH . "_site/xyo/web/default/page.php";
        }
        return true;
    }

    public function findAPI($path, &$api)
    {
        if (strlen($path) == 0) {
            $path = ".";
        }
        $api = XYO_WEB_PATH . $path . "/api.php";
        return file_exists($api);
    }

    public function findSlug($path, &$slug)
    {
        $pathSearchList = $this->buildPathSearch($path);
        $slug = $this->findItem($pathSearchList, "slug.php");
        if (is_null($slug)) {
            return false;
        }
        return true;
    }

    public function findService($path, &$service)
    {
        if (defined("XYO_WEB_SERVICE_RUN")) {
            $service = XYO_WEB_SERVICE_RUN;
            return true;
        }
        if (strlen($path) == 0) {
            $path = ".";
        }
        $filename = XYO_WEB_PATH . $path . "/service.php";
        if (!file_exists($filename)) {
            return false;
        }
        $service = $filename;
        return true;
    }

    public function findLayout($path)
    {
        $pathSearchList = $this->buildPathSearch($path);
        array_unshift($pathSearchList, $path . "/");
        $pathSearchList[] = "./_site/xyo/web/default/";
        return $this->findItem($pathSearchList, "layout.php");
    }

    public function findAuthorization($path)
    {
        $pathSearchList = $this->buildPathSearch($path);
        array_unshift($pathSearchList, $path . "/");
        return $this->findItem($pathSearchList, "authorization.php");
    }

    public function renderRedirect()
    {
        if ($this->request->isJSON() || $this->request->isAPI() || $this->request->isAJAX()) {
            http_response_code(301);
            header("Location: " . $this->info->location);
            return;
        }

        $pathSearchList = [];
        $pathSearchList[] = "./";
        $pathSearchList[] = "./_site/xyo/web/default/";
        $page = $this->findItem($pathSearchList, "301.php");
        if (is_null($page)) {
            return;
        }
        $this->renderPage($page, "./");
    }

    public function renderError($error)
    {
        if ($this->request->isJSON() || $this->request->isAPI() || $this->request->isAJAX()) {
            http_response_code(intval($error));
            return;
        }

        $this->firewall->initToken();

        $pathSearchList = [];
        $pathSearchList[] = "./";
        $pathSearchList[] = "./_site/xyo/web/default/";
        $page = $this->findItem($pathSearchList, $error . ".php");
        if (is_null($page)) {
            return;
        }
        $this->renderPage($page, "./");
    }

    public function siteFromRequestURI()
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
            $queryIndex = strrpos($requestURI, "?", -1);
            if ($queryIndex !== false) {
                $requestURI = substr($requestURI, 0, $queryIndex);
            }
            $path = $this->request->getQuery("__", null);
            if (!is_null($path)) {
                if (strlen($path) > 0) {
                    $cwd = str_replace("\\", "/", getcwd());
                    if ($path === $cwd) {
                        if (substr($requestURI, -1) === "/") {
                            $this->info->site = $requestURI;
                            return true;
                        }
                        $this->info->site = $requestURI . "/";
                        return true;

                    }
                    $this->info->site = substr($requestURI, 0, strlen($requestURI) - strlen($path));
                    return true;
                }
            }
            $this->info->site = $requestURI;
            $index = strpos($this->info->site, ".php", 0);
            if ($index === false) {
                return true;
            }
            $index = strrpos($this->info->site, "/", -1);
            if ($index === false) {
                $this->info->site = "/";
                return true;
            }
            $this->info->site = substr($this->info->site, 0, $index + 1);
            return true;
        }
        return false;
    }

}
