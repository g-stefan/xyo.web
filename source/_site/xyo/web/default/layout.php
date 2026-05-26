<?php
// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\_Default;

defined("XYO_WEB") or die("Forbidden");

require_once(XYO_WEB_PATH . "_site/xyo/web/web.php");
require_once(XYO_WEB_PATH . "_site/xyo/web/library/xyo-web-css.php");
use XYO\Web\Library\XYOWebCSS;

class Layout extends \XYO\Web\Layout
{
    public function init($options = null)
    {
        XYOWebCSS::register($this);
    }

    public function renderLayout($page = null)
    { ?>

        <!DOCTYPE html>
        <html <?php
        $this->renderLanguage();
        $this->renderHTMLClasses();
        ?>>

        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <?php $this->renderHead(); ?>
        </head>

        <body <?php $this->renderBodyClasses(); ?>>
            <?php $this->renderPage($page); ?>
            <?php $this->renderScripts(); ?>
        </body>

        </html>

    <?php }

}

return Layout::class;
