<?php
// XYO.Web
// Copyright (c) 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
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
            $request = \XYO\Web\Request::instance();
            if ($request->isAPI()) {
                $config = \XYO\Web\Config::instance();
                if ($config->has("api")) {
                    if ($config->get("api")->has("authorizationBearerToken")) {
                        $authorizationToken = $config->get("api")->get("authorizationBearerToken");
                        if (!empty($authorizationToken)) {
                            return strcmp($authorizationToken, $bearerToken) == 0;
                        }
                    }
                }
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

        public function sessionSet($key, $value)
        {
            $_SESSION[$key] = $value;
        }

        public function sessionGet($key, $defaultValue = null)
        {
            if (!array_key_exists($key, $_SESSION)) {
                return $defaultValue;
            }
            return $_SESSION[$key];
        }

    }
}