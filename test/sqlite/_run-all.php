<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

// Runs every "[index].[name].php" test in this folder as its own process.
//   php _run-all.php

require(__DIR__ . "/../_lib/run-folder.php");
exit(run_folder(__DIR__));
