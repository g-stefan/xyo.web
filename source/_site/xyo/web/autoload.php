<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

function classNameToKebabCase($className)
{
    $parts = explode('\\', ltrim($className, '\\'));
    $kebabParts = array_map(
        fn($part) => strtolower(preg_replace(
            '/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|(?<=[a-zA-Z])(?=[0-9]+)/',
            '-',
            $part
        )),
        $parts
    );
    return implode('/', $kebabParts);
}

\spl_autoload_register(function ($class) {
    $fileKebab = \XYO\Web\classNameToKebabCase($class);
    $file = XYO_WEB_PATH . "_site/" . $fileKebab . ".php";
    if (file_exists($file)) {
        require_once($file);
    };
    $file = XYO_WEB_PATH . $fileKebab . ".php";
    if (file_exists($file)) {
        require_once($file);
    };
});
