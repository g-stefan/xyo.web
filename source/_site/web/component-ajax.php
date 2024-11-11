<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./_site/web/component.php");

    class ComponentAJAX extends Component
    {
        protected function renderAJAX()
        {
        }

        protected function renderContainer(&$options = null)
        {
        }

        public function render(&$options = null)
        {
            ($this->isAJAX()) ? $this->renderAJAX() : $this->renderContainer($options);
        }

        public function initComponents(){
            parent::initComponents();
            if(!$this->view->jsSources->hasGroup("xyo.web.component.token")){
                $this->view->jsSources->set("xyo.web.component.token", array("code" => "XYO.Web.Component.token=\"".$this->view->token."\";", "mode" => "load"));            
            };
        }
    }
}
