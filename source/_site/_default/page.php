<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web\_Default {

    defined("XYO_WEB") or die("Forbidden");

    require_once ("./_site/web.php");
    require_once ("./_site/library/xyo-web-logo.php");
    use \XYO\Library\XYOWebLogo;

    class Page extends \XYO\Web\Page
    {
        public function init()
        {
            $this->setTitle("xyo.web");
            XYOWebLogo::register($this, "logo");
        }

        public function render(&$options = null)
        { ?>
            <div class="relative flex min-h-screen flex-col justify-center overflow-hidden bg-slate-300 py-6 sm:py-12">
                <div
                    class="relative bg-white px-6 pb-8 pt-10 shadow-xl ring-1 ring-slate-900/5 sm:mx-auto sm:max-w-lg sm:rounded-lg sm:px-10">
                    <div class="mx-auto max-w-md">
                        <?php $this->renderComponent("logo"); ?>
                        <div class="min-w-96 divide-y divide-slate-300/50">
                            <div class="space-y-6 py-8 text-center text-base leading-7 text-gray-600">
                                <p class="text-4xl">Hello, World!</p>
                            </div>
                            <div class="pt-8 text-center text-base font-semibold leading-7">
                                <p class="text-gray-900">This is the default welcome page.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php }
    }

    return Page::class;
}
