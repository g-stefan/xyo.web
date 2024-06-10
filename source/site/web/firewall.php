<?php
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

namespace XYO\Web {
    defined("XYO_WEB") or die("Forbidden");

    require_once ("./site/web/info.php");
    require_once ("./site/web/view.php");
    require_once ("./site/web/request.php");

    class Firewall
    {
        private static $instance = null;
        protected $info;
        protected $view;
        protected $request;
        protected $clientIP;

        protected function __construct()
        {
            $this->info = \XYO\Web\Info::instance();
            $this->view = \XYO\Web\View::instance();
            $this->request = \XYO\Web\Request::instance();
            $this->clientIP = null;
        }

        public static function instance()
        {
            return self::$instance;
        }

        public static function init()
        {
            self::$instance = new Firewall();
        }

        public function prepare()
        {
            // start up session - cookie only
            ini_set("session.use_cookies", 1);
            ini_set("session.use_trans_sid", 0);
            session_start();

            $this->clientIP = $this->getClientIP();
            $this->cspInit();
        }

        public function run()
        {
            $this->csrfLoad();
            return $this->csrfCheck();
        }

        public function getClientIP()
        {
            if (array_key_exists("HTTP_CLIENT_IP", $_SERVER)) {
                return $_SERVER["HTTP_CLIENT_IP"];
            }
            if (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
                return $_SERVER["HTTP_X_FORWARDED_FOR"];
            }
            if (array_key_exists("HTTP_X_FORWARDED", $_SERVER)) {
                return $_SERVER["HTTP_X_FORWARDED"];
            }
            if (array_key_exists("HTTP_FORWARDED_FOR", $_SERVER)) {
                return $_SERVER["HTTP_FORWARDED_FOR"];
            }
            if (array_key_exists("HTTP_FORWARDED", $_SERVER)) {
                return $_SERVER["HTTP_FORWARDED"];
            }
            if (array_key_exists("REMOTE_ADDR", $_SERVER)) {
                return $_SERVER["REMOTE_ADDR"];
            }
            return null;
        }

        public function cspInit()
        {
            if (!array_key_exists("firewall_csp_nonce", $_SESSION)) {
                $_SESSION["firewall_csp_nonce"] = hash("sha256", $this->clientIP . "." . rand() . "." . time() . session_id() . "CSP_NONCE", false);
            }
            $nonce = $_SESSION["firewall_csp_nonce"];
            $header = "default-src 'self';";
            $header .= " script-src 'self' 'nonce-" . $nonce . "' 'strict-dynamic';";
            $header .= " style-src 'self' 'nonce-" . $nonce . "';";
            $header .= " img-src 'self' blob: data:;";
            $header .= " font-src 'self';";
            $header .= " object-src 'none';";
            $header .= " base-uri 'self';";
            $header .= " form-action 'self';";
            $header .= " frame-ancestors 'none';";
            $header .= " connect-src 'self';";
            $header .= " upgrade-insecure-requests;";

            header("Content-Security-Policy: " . $header);

            $this->view->nonce = $nonce;
        }

        public function csrfInit()
        {
            $extra = "";
            if (array_key_exists("firewall_csrf_extra", $_SESSION)) {
                $extra = $_SESSION["firewall_csrf_extra"];
            }
            $tokenCookie = hash("sha256", $this->clientIP . "." . rand() . "." . time() . session_id() . "CSRF_TOKEN_COOKIE" . $extra, false);
            $tokenPost = hash("sha256", $this->clientIP . "." . rand() . "." . time() . session_id() . "CSRF_TOKEN_COOKIE" . $tokenCookie . $extra, false);
            $_SESSION["firewall_csrf_token_cookie"] = $tokenCookie;
            $_SESSION["firewall_csrf_token_post"] = $tokenPost;

            setcookie("_token", $tokenCookie, [
                "path" => $this->info->sitePath,
                "httponly" => true,
                "samesite" => "Strict"
            ]);

            $this->view->token = $tokenPost;
        }

        public function csrfLoad()
        {
            if (!array_key_exists("firewall_csrf_token_post", $_SESSION)) {
                $this->csrfInit();
                return;
            }
            $this->view->token = $_SESSION["firewall_csrf_token_post"];
        }

        public function csrfCheck()
        {
            if (strcmp($_SERVER["REQUEST_METHOD"], "GET") == 0) {
                return true;
            }
            if (!(strcmp($_SERVER["REQUEST_METHOD"], "POST") == 0)) {
                return false;
            }
            if (!array_key_exists("_token", $_POST)) {
                return false;
            }
            if (!array_key_exists("_token", $_COOKIE)) {
                return false;
            }
            if (!array_key_exists("firewall_csrf_token_cookie", $_SESSION)) {
                return false;
            }
            if (!array_key_exists("firewall_csrf_token_post", $_SESSION)) {
                return false;
            }
            if (strlen($_COOKIE["_token"]) == 0) {
                return false;
            }
            if (strlen($_POST["_token"]) == 0) {
                return false;
            }
            if (!(strcmp($_COOKIE["_token"], $_SESSION["firewall_csrf_token_cookie"]) == 0)) {
                return false;
            }
            if (!(strcmp($_POST["_token"], $_SESSION["firewall_csrf_token_post"]) == 0)) {
                return false;
            }
            return true;
        }


    }
}

