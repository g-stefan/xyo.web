/*!
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT
*/

XYO.Web.Script = {};

/**
 * Run script
 * @param {string} script - Script ot run
 * @param {string} nonce - nonce required to run script
 * @param {string} el - attach script as child of el
 */
XYO.Web.Script.run = function (script, nonce, el) {
	el=el?el:document.body;
	var elScript = document.createElement("script");
	elScript.textContent = script;
	if (nonce) {
		elScript.setAttribute("nonce", nonce);
	};
	el.appendChild(elScript);
};
