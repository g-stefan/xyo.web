<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\_Default {
    defined("XYO_WEB") or die("Forbidden");

    require_once ("./_site/web.php");    
    require_once ("./_site/library/xyo-web-css.php");
    use \XYO\Library\XYOWebCSS;
    
    class Layout extends \XYO\Web\Layout
    {
        public function init()
        {            
            XYOWebCSS::register($this);         
        }

        public function render(&$page = null)
        { ?>

            <!DOCTYPE html>
            <html lang="en" <?php $this->renderHTMLClasses(); ?>>

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
}

