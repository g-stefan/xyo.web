<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

class Log
{
    public static function logMessage($type, $message)
    {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }
        $type = preg_replace("/[^a-z0-9_-]/i", "", $type);
        file_put_contents(XYO_WEB_PATH . "_log/" . date("Y-m-d") . "-" . $type . ".log", $message . "\r\n", FILE_APPEND | LOCK_EX);
    }
}
