<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

// Thrown by the SQL drivers when a query fails at the database level
// (connection lost, syntax error, constraint violation, ...). It lets
// callers tell a genuine failure apart from a successful query that simply
// returned no rows: load() returns false for an empty result but throws
// DataSourceException when the underlying query errors.
class DataSourceException extends \RuntimeException {}
