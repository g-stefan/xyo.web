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
    require_once ("./site/web/request.php");

    class Module
    {
        protected $view;
        protected $request;

        protected $components;
        protected $componentIndex;
        public $id;
        public $site;

        public function __construct()
        {
            $this->view = \XYO\Web\View::instance();
            $this->request = \XYO\Web\Request::instance();
            $this->components = array();
            $this->componentIndex = 0;
            $this->id = "";
            $info = \XYO\Web\Info::instance();
            $this->site = $info->sitePath;
        }

        public static function instance()
        {
            return new static();
        }

        public function render(&$options = null)
        {
        }

        public function &registerComponent($classLibrary, $id = null, $isUnique = false)
        {
            if (is_null($id)) {
                ++$this->componentIndex;
                $id = "_" . $this->componentIndex;
            }
            if ($isUnique) {
                if (array_key_exists($id, $this->components)) {
                    return $this->components[$id];
                }
            }
            $this->components[$id] = $classLibrary::instance();
            $this->components[$id]->id = (strlen($this->id) > 0) ? $this->id . "." . $id : $id;
            return $this->view->setComponent($this->components[$id]->id, $this->components[$id]);
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

        public function init()
        {
        }

        public function process()
        {
            $this->render();
        }

        public function initComponents()
        {
            foreach ($this->components as &$component) {
                $component->init();
                $component->initComponents();
            }
        }

        public function initComponent($id)
        {
            $idList = explode(".", $id);
            if (count($idList) == 1) {
                if (!array_key_exists($id, $this->components)) {
                    return;
                }
                $this->components[$id]->init();
                $this->components[$id]->initComponents();
                return;
            }
            $id = array_shift($idList);
            if (!array_key_exists($id, $this->components)) {
                return;
            }
            $this->components[$id]->init();
            $this->components[$id]->initComponent(implode(".", $idList));
        }

        public function isGet()
        {
            return $this->request->isGet();
        }

        public function isPost()
        {
            return $this->request->isPost();
        }

        public function isAJAX()
        {
            return $this->request->isComponentAJAX($this->id);
        }

        public function isJSON()
        {
            return $this->request->isComponentJSON($this->id);
        }

        public function isAPI()
        {
            $info = \XYO\Web\Info::instance();
            return ($info->routeType == $info->routeTypeAPI);
        }

        protected function processAJAXPayload($payload = null, $payloadJs = null)
        {
            $payloadArray = "[";
            if (!is_null($payload)) {
                foreach ($payload as $key => $value) {
                    $payloadArray .= "[\"" . $key . "\",\"" . $value . "\"]";
                }
            }
            if (!is_null($payloadJs)) {
                foreach ($payloadJs as $key => $value) {
                    $payloadArray .= "[\"" . $key . "\"," . $value . "]";
                }
            }
            $payloadArray .= "]";
            return $payloadArray;
        }

        protected function renderAJAXRequestPost($payload = null, $payloadJs = null)
        {
            $payloadArray = $this->processAJAXPayload($payload, $payloadJs);
            echo "XYO.Web.Component.AJAX.post(\"" . $this->id . "\", " . $payloadArray . ");";
        }

        protected function renderAJAXRequestGet($payload = null, $payloadJs = null)
        {
            $payloadArray = $this->processAJAXPayload($payload, $payloadJs);
            echo "XYO.Web.Component.AJAX.get(\"" . $this->id . "\", " . $payloadArray . ");";
        }

        protected function sessionSet($name, $value)
        {
            $key = "component_" . $this->id;
            if (!array_key_exists($key, $_SESSION)) {
                $_SESSION[$key] = array();
            }
            $_SESSION[$key][$name] = $value;
        }
        protected function sessionGet($name, $defaultValue = null)
        {
            $key = "component_" . $this->id;
            if (!array_key_exists($key, $_SESSION)) {
                return $defaultValue;
            }
            if (!array_key_exists($name, $_SESSION[$key])) {
                return $defaultValue;
            }
            return $_SESSION[$key][$name];
        }

    }
}
