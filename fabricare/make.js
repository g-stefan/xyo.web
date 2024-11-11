// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make [" + Project.name + "]");

Shell.removeDirRecursivelyForce("output");
Shell.mkdirRecursivelyIfNotExists("output");
Shell.mkdirRecursivelyIfNotExists("temp");

// ---

exitIf(!Shell.copyDirRecursively("source", "output"));

Shell.remove("output/_site/web/info.php");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/_site/web/info.php --file-out=output/_site/web/info.php"));
Shell.remove("output/_site/web/release/web.header.js");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/_site/web/release/web.header.js --file-out=output/_site/web/release/web.header.js"));
Shell.remove("output/_site/web/release/web.header.php");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/_site/web/release/web.header.php --file-out=output/_site/web/release/web.header.php"));

// ---

Fabricare.include("make.tailwind");
