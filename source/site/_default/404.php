<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\_Default {
    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web.php");
    require_once ("./site/library/xyo-web-logo.php");
    use \XYO\Library\XYOWebLogo;

    class HTTP404 extends \XYO\Web\Page
    {
        public function init()
        {
            http_response_code(404);

            $this->setTitle("404 Not Found");

            XYOWebLogo::register($this, "logo");
        }

        public function render(&$options = null)
        { ?>
            <div class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-stone-300 py-6 sm:py-12">
                <div
                    class="relative bg-white px-6 pb-8 pt-10 shadow-xl ring-1 ring-gray-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
                    <div class="mx-auto max-w-md">
                        <?php $this->renderComponent("logo", "-small"); ?>
                        <div class="min-w-96 divide-y divide-gray-300/50">
                            <div class="space-y-6 py-8 text-center text-base leading-7 text-gray-600">
                                <p class="text-9xl">404</p>
                                <p class="text-2xl">Not Found</p>
                            </div>
                            <div class="pt-8 text-center text-base font-semibold leading-7">
                                <p class="text-gray-900">The requested URL was not found on this server.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
    }

    return HTTP404::class;
}
