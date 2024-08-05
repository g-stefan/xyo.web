<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {
    defined("XYO_WEB") or die("Forbidden");

    class Authorization
    {

        public static function instance()
        {
            return new static();
        }

        public function checkOPTIONS()
        {
            return false;
        }

        public function checkGET()
        {
            return true;
        }

        public function checkPOST()
        {
            return true;
        }

        public function setHeaders()
        {

        }

        public function checkBearerToken($bearerToken)
        {
            $config = \XYO\Web\Config::instance();
            $authorizationToken = $config->get("authorizationBearerToken");
            if (!empty($authorizationToken)) {
                return strcmp($authorizationToken, $bearerToken) == 0;
            }
            return true;
        }

        public function checkCSRF()
        {
            if (strcmp($_SERVER["REQUEST_METHOD"], "POST") == 0) {
                return true;
            }

            return false;
        }

    }
}