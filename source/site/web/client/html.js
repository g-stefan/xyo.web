/*!
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT
*/

XYO.Web.HTML = {};

/**
 * Escape RegExp
 * @param {string} str - String that will be escaped
 * @returns {string} Escaped string
 */
XYO.Web.HTML.escapeRegExp = function (str) {
	return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
};

/**
 * Extract tag from HTML
 * @param {string} inputHTML - HTML
 * @returns {object} Extracted tag {html,tag}
 */
XYO.Web.HTML.extractTag = function (inputHTML, tag) {
	var retV = {
		html: "",
		tag: ""
	};
	var tag_ = XYO.Web.HTML.escapeRegExp(tag);
	var pattern = new RegExp("<" + tag + "[^>]*>([\\S\\s]*?)</" + tag + ">", "ig");
	var matches = inputHTML.matchAll(pattern);
	for (var match_ of matches) {
		retV.tag += match_[1];
	};
	retV.html = inputHTML.replace(pattern, "");
	return retV;
};

/**
 * Extract content from HTML
 * @param {string} inputHTML - HTML
 * @returns {object} Extracted html,script and style {html,script,style}
 */
XYO.Web.HTML.extract = function (inputHTML) {
	var infoScript = XYO.Web.HTML.extractTag(inputHTML, "script");
	var infoStyle = XYO.Web.HTML.extractTag(infoScript.html, "style");
	return {
		html: infoStyle.html,
		script: infoScript.tag,
		style: infoStyle.tag
	};
};

/**
 * Update html on element with id
 * @param {string} id - Id of the element
 * @param {string} inputHTML - HTML
 * @param {string} nonce - nonce required to run script
 * @param {function} [fnError] - Call on error - fnError()
 * @returns {element} Element
 */
XYO.Web.HTML.update = function (id, inputHTML, nonce, fnError) {
	var el = document.getElementById(id);
	if (!el) {
		if (fnError) {
			fnError();
		};
		return null;
	};
	var infoHTML = XYO.Web.HTML.extract(inputHTML);
	if (infoHTML.style.length > 0) {
		XYO.Web.Style.run(infoHTML.style, nonce);
	};
	if (infoHTML.html.length > 0) {
		el.innerHTML = infoHTML.html;
	};
	if (infoHTML.script.length > 0) {
		XYO.Web.Script.run(infoHTML.script, nonce);
	};
	return el;
};
