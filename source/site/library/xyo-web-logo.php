<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Library {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web.php");

    class XYOWebLogo extends \XYO\Web\Component
    {

        protected static $name = "xyo-web-logo";

        public function init(){
            $this->view->cssLinks->set(self::$name, $this->site."site/library/xyo-web-logo.css");
        }

        public function render(&$options = null)
        { ?>
            <div class="xyo-web-logo <?php echo $options; ?> dark:drop-shadow-[0_0_16px_#FFFFFF80] dark:invert">
                <span>xyo</span><span>.</span><span>web</span>
            </div>
        <?php }

    }

}
