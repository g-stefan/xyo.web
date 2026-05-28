<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Module
{
    protected $web;
    protected $config;
    protected $language;
    protected $request;
    protected $view;
    protected $info;
    protected $session;
    protected $_dsConnection;

    protected $_components;
    protected $_componentOptions;
    protected $_componentIndex;
    public $id;
    public $idRequest;
    protected $_selector = null;
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
        $this->_dsConnection = $web->get(\XYO\Web\DataSource\Connection::class);

        $this->_components = [];
        $this->_componentOptions = [];
        $this->_componentIndex = 0;
        $this->id = "";
        $this->idRequest = "";
        $this->_selector = null;
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
        ++$this->_componentIndex;
        return "_" . $this->_componentIndex;
    }

    public function registerComponent($classLibrary, $id = null, $options = null)
    {
        if (is_null($id)) {
            $id = $this->generateComponentId();
        }       
        if (!is_subclass_of($classLibrary, Module::class)) {
            return null;
        }
        $this->_components[$id] = new $classLibrary($this->web);
        $this->_components[$id]->id = (strlen($this->id) > 0) ? $this->id . "." . $id : $id;
        $this->_components[$id]->idRequest = (strlen($this->id) > 0) ? $this->getIdRequest() . "/" . $id : $id;
        $this->_components[$id]->setParent($this);
        $this->_componentOptions[$id] = $options;
        return $this->_components[$id];
    }

    public function renderComponent($id, $options = null)
    {
        $idList = explode(".", $id);
        $id = array_shift($idList);
        if (!array_key_exists($id, $this->_components)) {
            return;
        }
        if (count($idList) == 0) {
            $this->_components[$id]->render($options);
            return;
        }
        $this->_components[$id]->renderComponent(implode(".", $idList));
    }

    public function initComponents()
    {
        foreach ($this->_components as $key => $component) {
            $component->init($this->_componentOptions[$key]);
            $component->initComponents();
            $component->process($this->_componentOptions[$key]);
        }
    }

    public function initComponent($id)
    {
        $idList = explode(".", $id);
        $id = array_shift($idList);
        if (!array_key_exists($id, $this->_components)) {
            return;
        }
        $this->_components[$id]->init($this->_componentOptions[$id]);

        if (count($idList) == 0) {
            $this->_components[$id]->initComponents();
        } else {
            $this->_components[$id]->initComponent(implode(".", $idList));
        }

        $this->_components[$id]->process($this->_componentOptions[$id]);
    }

    public function initComponentFromRequest($idList)
    {
        $idSelector = array_shift($idList);
        $id = $idSelector[0];
        if (!array_key_exists($id, $this->_components)) {
            return;
        }
        $this->_components[$id]->setSelector($idSelector[1]);
        $this->_components[$id]->init($this->_componentOptions[$id]);
        if (count($idList) == 0) {
            $this->_components[$id]->initComponents();
        } else {
            $this->_components[$id]->initComponentFromRequest($idList);
        }
        $this->_components[$id]->process($this->_componentOptions[$id]);
    }

    public function getComponent($id)
    {
        $retV = null;
        if (!array_key_exists($id, $this->_components)) {
            return $retV;
        }
        return $this->_components[$id];
    }

    public function getComponentId($id)
    {
        if (!array_key_exists($id, $this->_components)) {
            return null;
        }
        return $this->_components[$id]->id;
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
            foreach ($payload as $key => $value) {
                $payloadArray .= $comma . "[\"" . addslashes($key) . "\",\"" . addslashes($value) . "\"]";
                $comma = ",";
            }
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

    public function getIdRequest($selector = null)
    {
        if (!is_null($selector)) {
            return $this->idRequest . "." . $selector;
        }
        if (!is_null($this->_selector)) {
            return $this->idRequest . "." . $this->_selector;
        }
        return $this->idRequest;
    }

    public function renderJSRequestGet($payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.get(\"" . addslashes($idRequest) . "\", \"" . addslashes($this->id) . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestPost($payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.post(\"" . addslashes($idRequest) . "\", \"" . addslashes($this->id) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestPostForm($formId, $payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.postForm(\"" . addslashes($idRequest) . "\", \"" . addslashes($formId) . "\", \"" . addslashes($this->id) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestGetToElement($id, $payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.get(\"" . addslashes($idRequest) . "\",\"" . addslashes($id) . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestPostToElement($id, $payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.post(\"" . addslashes($idRequest) . "\", \"" . addslashes($id) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestPostFormToElement($formId, $id, $payload = null, $payloadJs = null, $selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.postForm(\"" . addslashes($idRequest) . "\",\"" . addslashes($formId) . "\",\"" . addslashes($id) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestBatchPost($idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchPost(\"" . addslashes(implode(";", $idList)) . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderJSRequestBatchGet($idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchGet(\"" . addslashes(implode(";", $idList)) . "\", " . $payloadArray . ");";
    }

    public function renderJSRequestBatchPostForm($formId, $idList, $payload = null, $payloadJs = null)
    {
        $payloadArray = $this->processJSPayload($payload, $payloadJs);
        echo "XYO.Web.Component.AJAX.batchPostForm(\"" . addslashes(implode(";", $idList)) . "\",\"" . $formId . "\",  " . $payloadArray . ",\"" . $this->view->token . "\");";
    }

    public function renderComponentFormRequiredFields($selector = null)
    {
        $idRequest = $this->getIdRequest($selector);
        echo "<input type=\"hidden\" name=\"_component\" value=\"" . addslashes($idRequest) . "\"></input>";
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

    public function hasParent()
    {
        return (!is_null($this->parent));
    }

    public function getDataSource($className, $connectionName = null)
    {
        return $this->_dsConnection->getDataSource($className, $connectionName);
    }

    public function setSelector($selector)
    {
        $this->_selector = $selector;
    }

    public function getSelector($default = null)
    {
        if (is_null($this->_selector)) {
            return $default;
        }
        return $this->_selector;
    }

    public function hasSelector()
    {
        return (!is_null($this->_selector));
    }

}
