// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make amalgam-php [" + Project.name + "]");

function processAmalgam(path, phpSourceFiles, file, phpSourceFilesExtra, guard) {

	phpSource = Shell.fileGetContents("./output/_site/xyo/web/release/web.header.php");
	if (guard) {
		phpSource = Shell.fileGetContents("./output/_site/xyo/web/release/web.guard.php");
	}
	for (i = 0; i < phpSourceFiles.length; ++i) {

		Shell.system("php \"fabricare/make-amalgam-php.php\" \"./output/" + path + "/" + phpSourceFiles[i] + "\" \"temp/temp-"+i+".php\"");
		content = Shell.fileGetContents("./temp/temp-"+i+".php");

		content = content.replace("defined(\"XYO_WEB\") or die(\"Forbidden\");", "");
		content = content.replace("<?php", "");

		for (j = 0; j < phpSourceFiles.length; ++j) {
			search = "require_once(XYO_WEB_PATH . \"" + path + "/" + phpSourceFiles[j] + "\");";
			content = content.replace(search, "");
		};

		if (phpSourceFilesExtra) {
			for (j = 0; j < phpSourceFilesExtra.length; ++j) {
				search = "require_once(XYO_WEB_PATH . \"" + phpSourceFilesExtra[j] + "\");";
				content = content.replace(search, "");
			};
		};

		phpSource += content;
	};

	phpSource += "\r\n";

	Shell.filePutContents(file, phpSource);
};

phpSourceFiles = [
	"autoload.php",
	"registry.php",
	"config.php",
	"info.php",
	"unique-list.php",
	"grouped-list.php",
	"view.php",
	"request.php",
	"session.php",
	"authorization.php",
	"firewall.php",
	"client.php",
	"log.php",
	"data-source/empty-field.php",
	"data-source/table-info.php",
	"data-source/query-info.php",
	"data-source/order.php",
	"data-source/data-source-exception.php",
	"data-source/table.php",
	"data-source/query.php",	
	"data-source/connection.php",
	"data-source/abstract-sql-table.php",
	"data-source/abstract-sql-query.php",
	"data-source/abstract-sql-connection.php",		
	"router.php",
	"language.php",
	"module.php",
	"component.php",
	"component-form.php",
	"page.php",
	"layout.php",
	"main.php"
];

processAmalgam("_site/xyo/web", phpSourceFiles, "./output/_site/xyo/web/web.php");
for (file of phpSourceFiles) {
	Shell.remove("./output/_site/xyo/web/" + file);
};

// ---
phpSourceFilesExtra = [
	"_site/xyo/web/data-source/table-info.php",
	"_site/xyo/web/data-source/query-info.php",
	"_site/xyo/web/data-source/abstract-sql-table.php",
	"_site/xyo/web/data-source/abstract-sql-query.php",
	"_site/xyo/web/data-source/abstract-sql-connection.php",
];
// ---

phpSourceFiles = [
	"my-sql-table.php",
	"my-sql-query.php",
	"my-sql-connection.php"
];

processAmalgam("_site/xyo/web/data-source",
	phpSourceFiles,
	"./output/_site/xyo/web/data-source/my-sql-connection.php",
	phpSourceFilesExtra,
	true);
Shell.remove("./output/_site/xyo/web/data-source/my-sql-table.php");
Shell.remove("./output/_site/xyo/web/data-source/my-sql-query.php");

// ---

phpSourceFiles = [
	"sq-lite-table.php",
	"sq-lite-query.php",
	"sq-lite-connection.php"
];

processAmalgam("_site/xyo/web/data-source",
	phpSourceFiles,
	"./output/_site/xyo/web/data-source/sq-lite-connection.php",
	phpSourceFilesExtra,
	true);
Shell.remove("./output/_site/xyo/web/data-source/sq-lite-table.php");
Shell.remove("./output/_site/xyo/web/data-source/sq-lite-query.php");

// ---

phpSourceFiles = [
	"postgre-sql-table.php",
	"postgre-sql-query.php",
	"postgre-sql-connection.php"
];

processAmalgam("_site/xyo/web/data-source",
	phpSourceFiles,
	"./output/_site/xyo/web/data-source/postgre-sql-connection.php",
	phpSourceFilesExtra,
	true);
Shell.remove("./output/_site/xyo/web/data-source/postgre-sql-table.php");
Shell.remove("./output/_site/xyo/web/data-source/postgre-sql-query.php");

// ---

Shell.removeDirRecursively("./output/_site/xyo/web/client");
Shell.removeDirRecursively("./output/_site/xyo/web/release");
Shell.remove("./output/_site/xyo/web/autoload.php");
