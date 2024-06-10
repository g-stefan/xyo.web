// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("vendor");

Shell.mkdirRecursivelyIfNotExists("archive");

if (Shell.fileExists("archive/vendor.7z")) {
	if (Shell.getFileSize("archive/vendor.7z") > 16) {
		return;
	};
	Shell.removeFile("archive/vendor.7z");
};

Shell.mkdirRecursivelyIfNotExists("archive/vendor");

runInPath("archive/vendor", function() {
	Shell.system("npm install tailwindcss");
	Shell.system("npx tailwindcss init");
	exitIf(Shell.system("7z a -mx9 -mmt4 -r- -sse -w. -y -t7z ../vendor.7z *"));
});

Shell.removeDirRecursively("archive/vendor");
