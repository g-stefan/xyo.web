<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Shared runner: executes every "[index].[name].php" file in a folder as a
// separate PHP process and prints an aggregate result. Returns an exit code.

function run_folder($dir)
{
    $files = glob($dir . "/[0-9][0-9][0-9][0-9].*.php");
    sort($files);

    $php = PHP_BINARY;
    $failed = [];

    foreach ($files as $file) {
        $name = basename($file);
        $output = [];
        $code = 0;
        exec(escapeshellarg($php) . " " . escapeshellarg($file) . " 2>&1", $output, $code);

        $summary = "";
        foreach ($output as $line) {
            if (strpos($line, "RESULT:") === 0) {
                $summary = $line;
            }
        }
        $status = ($code === 0) ? "OK" : "FAIL";
        if ($code !== 0) {
            $failed[] = $name;
        }
        echo str_pad($status, 5) . " " . str_pad($name, 32) . " " . $summary . "\n";
        if ($code !== 0) {
            echo "      ---- output ----\n";
            foreach ($output as $line) {
                echo "      " . $line . "\n";
            }
        }
    }

    echo "\n";
    if (count($failed) === 0) {
        echo "ALL TEST FILES PASSED (" . count($files) . " files in " . basename($dir) . ")\n";
        return 0;
    }
    echo count($failed) . " test file(s) FAILED in " . basename($dir) . ": " . implode(", ", $failed) . "\n";
    return 1;
}
