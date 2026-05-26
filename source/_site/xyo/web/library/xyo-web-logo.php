<?php
// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\Library;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/web.php");

class XYOWebLogo extends \XYO\Web\Component
{
    protected static $name = "xyo-web-logo";

    public function init($options = null)
    {
        $this->view->cssLinks->set(self::$name, $this->site . "_site/xyo/web/library/xyo-web-logo.css");
    }

    public function render($options = null)
    { ?>
        <div class="xyo-web-logo"></div>
    <?php }

}
