<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Info
{    
    public $path;
    public $location;

    public $routeTypeUnknown;
    public $routeTypePage;
    public $routeTypeAPI;
    public $routeTypeSlug;
    public $routeTypeService;
    public $routeType;
    public $routeFile;
    public $authorization;

    public $site;

    public $version;

    public $layout;
    public $page;
    public $api;

    public function __construct()
    {
        $this->path = null;
        $this->location = null;
        $this->site = null;
        $this->version = "#{VERSION_VERSION}";

        $this->routeTypeUnknown = 0;
        $this->routeTypePage = 1;
        $this->routeTypeAPI = 2;
        $this->routeTypeSlug = 3;
        $this->routeTypeService = 4;
        $this->routeType = $this->routeTypeUnknown;
        $this->routeFile = null;
        $this->authorization = null;

        $this->layout = null;
        $this->page = null;
        $this->api = null;
    }

}
