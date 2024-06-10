// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make [" + Project.name + "]");

Shell.removeDirRecursivelyForce("output");
Shell.mkdirRecursivelyIfNotExists("output");
Shell.mkdirRecursivelyIfNotExists("temp");

// ---

runInPath("temp", function() {
	if (!Shell.directoryExists("node_modules")) {
		exitIf(Shell.system("7z x -aoa ../archive/vendor.7z"));
	};
});

// ---

exitIf(!Shell.copyDirRecursively("source", "output"));

Shell.remove("output/site/web/info.php");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/site/web/info.php --file-out=output/site/web/info.php"));
Shell.remove("output/site/web/release/web.header.js");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/site/web/release/web.header.js --file-out=output/site/web/release/web.header.js"));
Shell.remove("output/site/web/release/web.header.php");
exitIf(Shell.system("xyo-version --no-bump --project=xyo.web --version-file=version.json --file-in=source/site/web/release/web.header.php --file-out=output/site/web/release/web.header.php"));

// ---
Shell.copyFile("tailwind.config.js", "temp/tailwind.config.js");
Shell.copyFile("uno.config.ts", "temp/uno.config.ts");

Shell.remove("output/site/library/tailwind.css");
runInPath("temp", function() {
	Shell.system("npx tailwindcss -i ./../source/site/library/tailwind.css -o ./../output/site/library/tailwind.css --minify");
});

// ---
