<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {

    defined("XYO_WEB") or die("Forbidden");

    class Info
    {
        private static $instance = null;
        public $path;
        public $location;

        public $routeTypeUnknown;
        public $routeTypePage;
        public $routeTypeAPI;
        public $routeTypeSlug;
        public $routeType;
        public $routeFile;

        public $sitePath;

        public $version;

        protected function __construct()
        {
            $this->path = null;
            $this->location = null;
            $this->sitePath = null;
            $this->version = "${VERSION_VERSION}";

            $this->routeTypeUnknown = 0;
            $this->routeTypePage = 1;
            $this->routeTypeAPI = 2;
            $this->routeTypeSlug = 3;
            $this->routeType = $this->routeTypeUnknown;
            $this->routeFile = null;
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Info();
        }

    }
}
