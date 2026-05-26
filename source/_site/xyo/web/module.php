<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/info.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/view.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/request.php");

class Module
{
    protected $web;
    protected $config;
    protected $language;
    protected $request;
    protected $view;
    protected $info;
    protected $session;
    protected $dsConnection;

    protected $components;
    protected $componentOptions;
    protected $componentIndex;
    public $id;
    public $site;
    protected $parent;

    public function __construct($web)
    {
        $this->web = $web;
        $this->config = $web->get(\XYO\Web\Config::class);
        $this->language = $web->get(\XYO\Web\Language::class);
        $this->request = $web->get(\XYO\Web\Request::class);
        $this->view = $web->get(\XYO\Web\View::class);
        $this->info = $web->get(\XYO\Web\Info::class);
        $this->session = $web->get(\XYO\Web\Session::class);
        $this->dsConnection = $web->get(\XYO\Web\DataSource\Connection::class);

        $this->components = [];
        $this->componentOptions = [];
        $this->componentIndex = 0;
        $this->id = "";
        $this->site = $this->info->site;
        $this->parent = null;
    }

    public function init($options = null)
    {
    }

    public function process($options = null)
    {
    }

    public function render($options = null)
    {
    }

    public function generateComponentId()
    {
        ++$this->componentIndex;
        return "_" . $this->componentIndex;
    }

    public function registerComponent($classLibrary, $id = null, $options = null)
    {
        if (is_null($id)) {
            $id = $this->generateComponentId();
        }
        if (!class_exists($classLibrary, false)) {
            return null;
        }
        if (!is_subclass_of($classLibrary, Module::class)) {
            return null;
        }
        $this->components[$id] = new $classLibrary($this->web);
        $this->components[$id]->id = (strlen($this->id) > 0) ? $this->id . "." . $id : $id;
        $this->components[$id]->setParent($this);
        $this->componentOptions[$id] = $options;
        return $this->components[$id];
    }

    public function renderComponent($id, $options = null)
    {
        $idList = explode(".", $id);
        if (count($idList) == 1) {
            if (!array_key_exists($id, $this->components)) {
                return;
            }
            $this->components[$id]->render($options);
            return;
        }
        $id = array_shift($idList);
        if (!array_key_exists($id, $this->components)) {
            return;
        }
        $this->components[$id]->renderComponent(implode(".", $idList));
    }

    public function initComponents()
    {
        foreach ($this->components as $key => $component) {
            $component->init($this->componentOptions[$key]);
            $component->initComponents();
            $component->process($this->componentOptions[$key]);
        }
    }

    public function initComponent($id)
    {
        $idList = explode(".", $id);
        if (count($idList) == 1) {
            if (!array_key_exists($id, $this->components)) {
                return;
            }
            $this->components[$id]->init($this->componentOptions[$id]);
            $this->components[$id]->initComponents();
            $this->components[$id]->process($this->componentOptions[$id]);
            return;
        }
        $id = array_shift($idList);
        if (!array_key_exists($id, $this->components)) {
            return;
        }
        $this->components[$id]->init($this->componentOptions[$id]);
        $this->components[$id]->initComponent(implode(".", $idList));
        $this->components[$id]->process($this->componentOptions[$id]);
    }

    public function getComponent($id)
    {
        $retV = null;
        if (!array_key_exists($id, $this->components)) {
            return $retV;
        }
        return $this->components[$id];
    }

    public function getComponentId($id)
    {
        if (!array_key_exists($id, $this->components)) {
            return null;
        }
        return $this->components[$id]->id;
    }

    public function registerAndInitComponent($classLibrary, $id = null, $options = null)
    {
        if (is_null($id)) {
            $id = $this->generateComponentId();
        }
        $component = $this->registerComponent($classLibrary, $id, $options);
        $this->initComponent($id);
        return $component;
    }

    public function isGET()
    {
        return $this->request->isGET();
    }

    public function isPOST()
    {
        return $this->request->isPOST();
    }

    public function isAJAX()
    {
        return $this->request->isAJAX();
    }

    public function isJSON()
    {
        return $this->request->isJSON();
    }

    public function isAPI()
    {
        return $this->request->isAPI();
    }

    public function isComponentAJAX()
    {
        return $this->request->isComponentAJAX($this->id);
    }

    public function isComponentJSON()
    {
        return $this->request->isComponentJSON($this->id);
    }

    public function processJSPayload($payload = null, $payloadJs = null)
    {
        $payloadArray = "[";
        $comma = "";
        if (!is_null($payload)) {
            $payloadArray .= json_encode($payload);
            $comma = ",";
        }

        if (!is_null($payloadJs)) {
            foreach ($payloadJs as $key => $value) {
                $payloadArray .= $comma . "[\"" . addslashes($key) . "\"," . $value . "]";
                $comma = ",";
            }
        }

        $payloadArray .= "]";
        return $payloadArray;
    }

    public function renderJSRequestPost($payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.post(\"" . $this->id . "\", \"" . $this->id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestGet($payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.get(\"" . $this->id . "\", \"" . $this->id . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestPostForm($formId, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.postForm(\"" . $this->id . "\", \"" . $formId . "\", \"" . $this->id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestPostToElement($id, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.post(\"" . $this->id . "\", \"" . $id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestGetToElement($id, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.get(\"" . $this->id . "\",\"" . $id . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestPostFormToElement($formId, $id, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.postForm(\"" . $this->id . "\",\"" . $formId . "\",\"" . $id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestBatchPost($idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchPost(\"" . implode(",", $idList) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestBatchGet($idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchGet(\"" . implode(",", $idList) . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestBatchPostForm($formId, $idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchPostForm(\"" . implode(",", $idList) . "\",\"" . $formId . "\",  " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderComponentFormRequiredFields()
    {
        echo "<input type=\"hidden\" name=\"_component\" value=\"" . $this->id . "\"></input>";
        echo "<input type=\"hidden\" name=\"_token\" value=\"" . $this->view->token . "\"></input>";
    }

    public function setLanguage($type)
    {
        $this->view->language = $type;
    }

    public function loadLanguage($force = false)
    {
        if (!preg_match("/^[a-z]{2}(-[a-z]{2})?$/", strtolower($this->view->language))) {
            return false;
        }
        $languageFile = $this->info->path . "/language/" . strtolower($this->view->language) . ".php";
        if ($force) {
            $this->language->includeFile($languageFile);
        } else {
            $this->language->includeOnceFile($languageFile);
        }
    }

    public function loadLanguageFromPath($path, $tag, $force = false)
    {
        if (!preg_match("/^[a-z]{2}(-[a-z]{2})?$/", strtolower($this->view->language))) {
            return false;
        }
        $languageFile = $path . "/language/" . strtolower($this->view->language) . "/" . $tag . ".php";
        if ($force) {
            $this->language->includeFile($languageFile);
        } else {
            $this->language->includeOnceFile($languageFile);
        }
    }

    public function strRenderComponent($id, $options = null)
    {
        ob_start();
        $this->renderComponent($id, $options);
        $str = ob_get_contents();
        ob_end_clean();
        return $str;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getDataSource($className, $connectionName = null)
    {
        return $this->dsConnection->getDataSource($className, $connectionName);
    }

}
