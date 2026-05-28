<?php

// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0

namespace XYO\Web\DataSource;

defined("XYO_WEB") or die("Forbidden");

class TableInfo
{
    public $name = "";
    public $primaryKey = null;
    public $fields = [];
    public $indexes = [];
}
