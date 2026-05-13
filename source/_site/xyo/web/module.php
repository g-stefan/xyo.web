<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once("./_site/xyo/web/info.php");
    require_once("./_site/xyo/web/view.php");
    require_once("./_site/xyo/web/request.php");
    require_once("./_site/xyo/web/state.php");

    class Module
    {
        protected $view;
        protected $request;
        protected $language;

        protected $components;
        protected $componentOptions;
        protected $componentIndex;
        public $id;
        public $site;
        protected $parent;

        public function __construct()
        {
            $this->view = \XYO\Web\View::instance();
            $this->request = \XYO\Web\Request::instance();
            $this->language = \XYO\Web\Language::instance();
            $this->components = array();
            $this->componentOptions = array();
            $this->componentIndex = 0;
            $this->id = "";
            $info = \XYO\Web\Info::instance();
            $this->site = $info->sitePath;
            $this->parent = null;
        }

        public static function instance()
        {
            return new static();
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

        public function &registerComponent($classLibrary, $id = null, $options = null)
        {
            if (is_null($id)) {
                $id = $this->generateComponentId();
            }
            $this->components[$id] = $classLibrary::instance();
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
            foreach ($this->components as $key => &$component) {
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

        public function &getComponent($id)
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

        public function &registerAndInitComponent($classLibrary, $id = null, $options = null)
        {
            if (is_null($id)) {
                $id = $this->generateComponentId();
            }
            $component = $this->registerComponent($classLibrary, $id, $options);
            $this->initComponent($id);
            return $component;
        }

        public function isOPTIONS()
        {
            return $this->request->isOPTIONS();
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
            $info = \XYO\Web\Info::instance();
            return ($info->routeType == $info->routeTypeAPI);
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
            $state = \XYO\Web\State::instance();            

            $payloadArray = "[";
            $payloadArray .= "[\"_state\",\"" . $state->encode() . "\"]";

            if (!is_null($payload)) {
                foreach ($payload as $key => $value) {                    
                    $payloadArray .= ",[\"" . $key . "\",\"" . $value . "\"]";
                }
            }

            if (!is_null($payloadJs)) {
                $coma = false;
                foreach ($payloadJs as $key => $value) {
                    $payloadArray .= ",[\"" . $key . "\"," . $value . "]";
                }
            }

            $payloadArray .= "]";
            return $payloadArray;
        }

        public function renderJSRequestPost($payload = null, $payloadJs = null)
        {            
            $payloadArray = $this->processJSPayload($payload, $payloadJs);
            echo "XYO.Web.Component.AJAX.post(\"" . $this->id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
        }

        public function renderJSRequestGet($payload = null, $payloadJs = null)
        {
            $payloadArray = $this->processJSPayload($payload, $payloadJs);
            echo "XYO.Web.Component.AJAX.get(\"" . $this->id . "\", " . $payloadArray . ");";
        }

        public function renderJSRequestPostForm($payload = null, $payloadJs = null)
        {
            $payloadArray = $this->processJSPayload($payload, $payloadJs);
            echo "XYO.Web.Component.AJAX.postForm(\"" . $this->id . "\", " . $payloadArray . ",\"" . $this->view->token . "\");";
        }

        public function renderComponentFormRequiredFields()
        {
            $state = \XYO\Web\State::instance();
            echo "<input type=\"hidden\" name=\"_component\" value=\"" . $this->id . "\"></input>";
            echo "<input type=\"hidden\" name=\"_state\" value=\"" . $state->encode() . "\"></input>";
            echo "<input type=\"hidden\" name=\"_token\" value=\"" . $this->view->token . "\"></input>";
        }

        public function sessionSet($key, $value)
        {
            $_SESSION[$key] = $value;
        }

        public function sessionGet($key, $defaultValue = null)
        {
            if (!array_key_exists($key, $_SESSION)) {
                return $defaultValue;
            }
            return $_SESSION[$key];
        }

        public function setLanguage($type)
        {
            $this->view->language = $type;
        }

        public function loadLanguage($force = false)
        {
            $info = \XYO\Web\Info::instance();
            $languageFile = $info->path . "/language/" . strtolower($this->view->language) . ".php";
            if ($force) {
                $this->language->includeFile($languageFile);
            } else {
                $this->language->includeOnceFile($languageFile);
            }
        }

        public function loadLanguageFromPath($path, $tag, $force = false)
        {
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

        public function setState($name, $value)
        {
            $state = \XYO\Web\State::instance();
            $state->set($this->id, $name, $value);
        }

        public function getState($name, $default = null)
        {
            $state = \XYO\Web\State::instance();
            return $state->get($this->id, $name, $default);
        }

        public function hasState($name)
        {
            $state = \XYO\Web\State::instance();
            return $state->has($this->id, $name);
        }

        public function setParent($parent) {
            $this->parent = $parent;
        }

        public function getParent() {
            return $this->parent;
        }

    }
}
