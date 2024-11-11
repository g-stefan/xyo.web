// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make amalgam-js [" + Project.name + "]");

jsSourceFiles = [
	"web.js",
	"script.js",
	"style.js",
	"html.js",
	"ajax.js",
	"component.js"
];

jsSource = Shell.fileGetContents("./output/_site/web/release/web.header.js");
for (var i = 0; i < jsSourceFiles.length; ++i) {
	content = Shell.fileGetContents("./output/_site/web/client/" + jsSourceFiles[i]);
	content = content.replace("/*!\r\n", "/*\r\n");
	content = content.replace("/*!\n", "/*\n");
	jsSource += content;
};

Shell.filePutContents("./temp/web.amalgam.js", jsSource);
Shell.system("uglifyjs -c -m -o output/_site/web.js --comments \"/^!/\" temp/web.amalgam.js");

Shell.removeDirRecursivelyForce("output/_site/web/client");

Shell.remove("output/_site/web/client.php");
Shell.copyFile("output/_site/web/release/client.php", "output/_site/web/client.php");
