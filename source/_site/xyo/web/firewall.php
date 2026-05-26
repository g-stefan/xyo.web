<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Firewall
{
    protected $info;
    protected $view;
    protected $session;
    protected $initToken;

    public function __construct($info, $view, $session)
    {
        $this->info = $info;
        $this->view = $view;
        $this->initToken = false;
        $this->session = $session;
    }

    public function run()
    {
        if (!array_key_exists("REQUEST_METHOD", $_SERVER)) {
            return false;
        }

        if (!$this->requestCheckMethod()) {
            return false;
        }

        $this->info->authorization->setHeaders();

        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            return true;
        }

        if (!$this->info->authorization->checkBearerToken($this->getBearerToken())) {
            return false;
        }

        if ($this->info->routeType == $this->info->routeTypeAPI) {
            return true;
        }

        $this->initToken();

        if ($this->info->authorization->checkCSRF()) {
            if (!$this->csrfCheck()) {
                return false;
            }
        }

        return true;
    }

    public function initToken()
    {
        if ($this->initToken) {
            return;
        }

        ini_set("session.use_cookies", 1);
        ini_set("session.use_trans_sid", 0);
        ini_set("session.cookie_httponly", 1);
        ini_set("session.cookie_samesite", "strict");
        ini_set("session.cookie_secure", !empty($_SERVER["HTTPS"]));
        session_start();

        if ($this->info->authorization->requireTokenReset()) {
            $this->tokenReset();
        }

        $this->cspInit();
        $this->csrfLoad();

        $this->initToken = true;

        $this->session->init();
    }

    public function tokenReset()
    {
        session_regenerate_id(true);
        unset($_SESSION["firewall_csrf_token_post"]);
        unset($_SESSION["firewall_csrf_token_cookie"]);
        unset($_SESSION["firewall_csrf_extra"]);
    }

    public function cspInit()
    {
        $nonce = hash("sha256", bin2hex(random_bytes(32)) . "." . time() . session_id() . "CSP_NONCE", false);
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
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
        if (!empty($_SERVER["HTTPS"])) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        $this->view->setNonce($nonce);
    }

    public function csrfInit()
    {
        $extra = "";
        if (array_key_exists("firewall_csrf_extra", $_SESSION)) {
            $extra = $_SESSION["firewall_csrf_extra"];
        }
        $tokenCookie = hash("sha256", bin2hex(random_bytes(32)) . "." . time() . session_id() . "CSRF_TOKEN_COOKIE" . $extra, false);
        $tokenPost = hash("sha256", bin2hex(random_bytes(32)) . "." . time() . session_id() . "CSRF_TOKEN_POST" . $tokenCookie . $extra, false);
        $_SESSION["firewall_csrf_token_cookie"] = $tokenCookie;
        $_SESSION["firewall_csrf_token_post"] = $tokenPost;

        // Can't use __Host- prefix, the app can be in /app/
        setcookie("_token", $tokenCookie, [
            "path" => $this->info->site,
            "httponly" => true,
            "samesite" => "Strict",
            "secure" => !empty($_SERVER["HTTPS"])
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

    public function getAuthorizationHeader()
    {
        if (isset($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
            return trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
        }
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
            return trim($_SERVER["HTTP_AUTHORIZATION"]);
        }
        return null;
    }

    public function getBearerToken()
    {
        $header = $this->getAuthorizationHeader();
        if (!empty($header)) {
            if (substr($header, 0, 7) === "Bearer ") {
                return substr($header, 7);
            }
        }
        return null;
    }

    public function requestCheckMethod()
    {
        if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
            return $this->info->authorization->checkOPTIONS();
        }
        if ($_SERVER["REQUEST_METHOD"] === "GET") {
            return $this->info->authorization->checkGET();
        }
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            return $this->info->authorization->checkPOST();
        }
        if ($_SERVER["REQUEST_METHOD"] === "PUT") {
            return $this->info->authorization->checkPUT();
        }
        if ($_SERVER["REQUEST_METHOD"] === "PATCH") {
            return $this->info->authorization->checkPATCH();
        }
        if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
            return $this->info->authorization->checkDELETE();
        }
        return false;
    }

    public function csrfCheck()
    {
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
        if (!hash_equals($_COOKIE["_token"], $_SESSION["firewall_csrf_token_cookie"])) {
            return false;
        }
        if (!hash_equals($_POST["_token"], $_SESSION["firewall_csrf_token_post"])) {
            return false;
        }
        return true;
    }

}
