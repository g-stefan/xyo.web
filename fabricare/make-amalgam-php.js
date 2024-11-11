// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make amalgam-php [" + Project.name + "]");

function processAmalgam(path, phpSourceFiles, file) {

	phpSource = Shell.fileGetContents("./output/_site/web/release/web.header.php");
	for (i = 0; i < phpSourceFiles.length; ++i) {

		Shell.system("php \"fabricare/make-amalgam-php.php\" \"./output/" + path + "/" + phpSourceFiles[i] + "\" \"temp/temp.php\"");
		content = Shell.fileGetContents("./temp/temp.php");

		content = content.replace("defined(\"XYO_WEB\") or die(\"Forbidden\");", "");
		content = content.replace("<?php", "");

		for (j = 0; j < phpSourceFiles.length; ++j) {
			search = "require_once (\"./" + path + "/" + phpSourceFiles[j] + "\");";
			content = content.replace(search, "");
		};

		phpSource += content;
	};

	phpSource += "\r\n";

	Shell.filePutContents(file, phpSource);
};

phpSourceFiles = [
	"config.php",
	"info.php",
	"unique-list.php",
	"grouped-list.php",
	"view.php",
	"request.php",
	"authorization.php",
	"firewall.php",
	"client.php",
	"log.php",
	"datasource/empty-field.php",
	"datasource/table-info.php",
	"datasource/query-info.php",
	"datasource/query.php",
	"datasource/table.php",
	"datasource/connections.php",
	"router.php",
	"module.php",
	"component.php",
	"component-ajax.php",
	"page.php",
	"layout.php",
	"main.php"
];

processAmalgam("_site/web", phpSourceFiles, "./output/_site/web.php");

// ---

phpSourceFiles = [
	"mysql-table.php",
	"mysql-query.php",
	"mysql-connection.php"
];

processAmalgam("_site/web.ds", phpSourceFiles, "./output/_site/web.ds/mysql-connection.php");
Shell.remove("./output/_site/web.ds/mysql-table.php");
Shell.remove("./output/_site/web.ds/mysql-query.php");

// ---

phpSourceFiles = [
	"sqlite-table.php",
	"sqlite-query.php",
	"sqlite-connection.php"
];

processAmalgam("_site/web.ds", phpSourceFiles, "./output/_site/web.ds/sqlite-connection.php");
Shell.remove("./output/_site/web.ds/sqlite-table.php");
Shell.remove("./output/_site/web.ds/sqlite-query.php");

// ---

Shell.removeDirRecursivelyForce("output/_site/web");
