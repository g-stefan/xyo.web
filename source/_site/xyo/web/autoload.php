<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web;

defined("XYO_WEB") or die("Forbidden");

// Class names do not match their file paths (kebab-case files, dir/namespace
// casing differences, names like 301.php => HTTP301), so an explicit map is used.
\spl_autoload_register(function ($class) {
    static $map = [
        "XYO\\Web\\Authorization" => "_site/xyo/web/authorization.php",
        "XYO\\Web\\Client" => "_site/xyo/web/client.php",
        "XYO\\Web\\Component" => "_site/xyo/web/component.php",
        "XYO\\Web\\ComponentForm" => "_site/xyo/web/component-form.php",
        "XYO\\Web\\Config" => "_site/xyo/web/config.php",
        "XYO\\Web\\Firewall" => "_site/xyo/web/firewall.php",
        "XYO\\Web\\GroupedList" => "_site/xyo/web/grouped-list.php",
        "XYO\\Web\\Info" => "_site/xyo/web/info.php",
        "XYO\\Web\\Language" => "_site/xyo/web/language.php",
        "XYO\\Web\\Layout" => "_site/xyo/web/layout.php",
        "XYO\\Web\\Log" => "_site/xyo/web/log.php",
        "XYO\\Web\\Module" => "_site/xyo/web/module.php",
        "XYO\\Web\\Page" => "_site/xyo/web/page.php",
        "XYO\\Web\\Registry" => "_site/xyo/web/registry.php",
        "XYO\\Web\\Request" => "_site/xyo/web/request.php",
        "XYO\\Web\\Router" => "_site/xyo/web/router.php",
        "XYO\\Web\\Session" => "_site/xyo/web/session.php",
        "XYO\\Web\\UniqueList" => "_site/xyo/web/unique-list.php",
        "XYO\\Web\\View" => "_site/xyo/web/view.php",

        "XYO\\Web\\DataSource\\Connection" => "_site/xyo/web/datasource/connection.php",
        "XYO\\Web\\DataSource\\DataSourceException" => "_site/xyo/web/datasource/datasource-exception.php",
        "XYO\\Web\\DataSource\\EmptyField" => "_site/xyo/web/datasource/empty-field.php",
        "XYO\\Web\\DataSource\\Order" => "_site/xyo/web/datasource/order.php",
        "XYO\\Web\\DataSource\\Query" => "_site/xyo/web/datasource/query.php",
        "XYO\\Web\\DataSource\\QueryInfo" => "_site/xyo/web/datasource/query-info.php",
        "XYO\\Web\\DataSource\\Table" => "_site/xyo/web/datasource/table.php",
        "XYO\\Web\\DataSource\\TableInfo" => "_site/xyo/web/datasource/table-info.php",

        "XYO\\Web\\DataSource\\Type\\AbstractSQLConnection" => "_site/xyo/web/datasource/type/abstract-sql-connection.php",
        "XYO\\Web\\DataSource\\Type\\AbstractSQLQuery" => "_site/xyo/web/datasource/type/abstract-sql-query.php",
        "XYO\\Web\\DataSource\\Type\\AbstractSQLTable" => "_site/xyo/web/datasource/type/abstract-sql-table.php",
        "XYO\\Web\\DataSource\\Type\\MySQL\\Connection" => "_site/xyo/web/datasource/type/mysql-connection.php",
        "XYO\\Web\\DataSource\\Type\\MySQL\\Query" => "_site/xyo/web/datasource/type/mysql-query.php",
        "XYO\\Web\\DataSource\\Type\\MySQL\\Table" => "_site/xyo/web/datasource/type/mysql-table.php",
        "XYO\\Web\\DataSource\\Type\\PostgreSQL\\Connection" => "_site/xyo/web/datasource/type/postgresql-connection.php",
        "XYO\\Web\\DataSource\\Type\\PostgreSQL\\Query" => "_site/xyo/web/datasource/type/postgresql-query.php",
        "XYO\\Web\\DataSource\\Type\\PostgreSQL\\Table" => "_site/xyo/web/datasource/type/postgresql-table.php",
        "XYO\\Web\\DataSource\\Type\\SQLite\\Connection" => "_site/xyo/web/datasource/type/sqlite-connection.php",
        "XYO\\Web\\DataSource\\Type\\SQLite\\Query" => "_site/xyo/web/datasource/type/sqlite-query.php",
        "XYO\\Web\\DataSource\\Type\\SQLite\\Table" => "_site/xyo/web/datasource/type/sqlite-table.php",

        "XYO\\Web\\Library\\XYOWebCSS" => "_site/xyo/web/library/xyo-web-css.php",
        "XYO\\Web\\Library\\XYOWebLogo" => "_site/xyo/web/library/xyo-web-logo.php",

        "XYO\\Web\\_Default\\HTTP301" => "_site/xyo/web/default/301.php",
        "XYO\\Web\\_Default\\HTTP400" => "_site/xyo/web/default/400.php",
        "XYO\\Web\\_Default\\HTTP401" => "_site/xyo/web/default/401.php",
        "XYO\\Web\\_Default\\HTTP404" => "_site/xyo/web/default/404.php",
        "XYO\\Web\\_Default\\HTTP501" => "_site/xyo/web/default/501.php",
        "XYO\\Web\\_Default\\Layout" => "_site/xyo/web/default/layout.php",
        "XYO\\Web\\_Default\\Page" => "_site/xyo/web/default/page.php",
    ];
    if (isset($map[$class])) {
        require_once(XYO_WEB_PATH . $map[$class]);
    }
});
