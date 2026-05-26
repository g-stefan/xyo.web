/*!
// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0
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

/**
 * Update html on multiple elements
 * @param {string} inputList - Array of [id, HTML]
 * @param {string} nonce - nonce required to run script
 * @param {function} [fnError] - Call on error - fnError()
 * @returns {element} Element
 */
XYO.Web.HTML.batchUpdate = function (inputList, nonce, fnError) {
	var elList = [];
	var i;
	for (i = 0; i < inputList.length; ++i) {
		elList[i] = document.getElementById(inputList[i][0]);
		if (!elList[i]) {
			if (fnError) {
				fnError();
			};
			return null;
		};
	};
	var elHTML = [];
	var elStyle = "";
	var elScript = "";
	for (i = 0; i < inputList.length; ++i) {
		elHTML[i] = XYO.Web.HTML.extract(inputList[i][1]);
		if (elHTML[i].style.length > 0) {
			elStyle = elStyle + elHTML[i].style;
		};
		if (elHTML[i].script.length > 0) {
			elScript = elScript + elHTML[i].script;
		};
	};

	if (elStyle.length > 0) {
		XYO.Web.Style.run(elStyle, nonce);
	};

	for (i = 0; i < inputList.length; ++i) {
		elList[i].innerHTML = elHTML[i].html;
	};

	if (elScript.length > 0) {
		XYO.Web.Script.run(elScript, nonce);
	};

	return elList;
};
