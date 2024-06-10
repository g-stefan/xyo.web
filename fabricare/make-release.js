// Created by Grigore Stefan <g_stefan@yahoo.com>
// Public domain (Unlicense) <http://unlicense.org>
// SPDX-FileCopyrightText: 2023-2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Unlicense

messageAction("make-release [" + Project.name + "]");

Fabricare.include("make");
Fabricare.include("make-amalgam-js");
Fabricare.include("make-amalgam-php");
