// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make [" + Project.name + ".tailwind]");

// ---

runInPath("temp", function() {
	if (!Shell.directoryExists("node_modules")) {
		exitIf(Shell.system("7z x -aoa ../archive/vendor.7z"));
	};
});

// ---

Shell.copyFile("tailwind.config.js", "temp/tailwind.config.js");

Shell.remove("output/site/library/tailwind.css");
runInPath("temp", function() {
	Shell.system("npx tailwindcss -i ./../source/site/library/tailwind.css -o ./../output/site/library/tailwind.css --minify");
});

// ---
