<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Runs every driver sub-suite (test/sqlite, test/mysql, ...).
//   php _run-all.php
//
// A driver folder that cannot connect (e.g. no MySQL server) reports as
// FAILED; run its own _run-all.php directly to see the connection error.

require(__DIR__ . "/_lib/run-folder.php");

$drivers = glob(__DIR__ . "/*", GLOB_ONLYDIR);
sort($drivers);

$overall = 0;
foreach ($drivers as $dir) {
    if (basename($dir) === "_lib") {
        continue;
    }
    echo "\n############################################################\n";
    echo "## " . basename($dir) . "\n";
    echo "############################################################\n";
    $code = run_folder($dir);
    if ($code !== 0) {
        $overall = 1;
    }
}

echo "\n";
echo ($overall === 0) ? "==> ALL DRIVER SUITES PASSED\n" : "==> SOME DRIVER SUITES FAILED\n";
exit($overall);
