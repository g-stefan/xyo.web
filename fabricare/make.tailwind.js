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

Shell.remove("output/_site/library/tailwind.css");
Shell.copy("source/_site/library/tailwind.css", "temp/tailwind.css");
runInPath("temp", function() {
	Shell.system("npx @tailwindcss/cli  -i ./tailwind.css -o ./../output/_site/library/tailwind.css --minify");
});

// ---
