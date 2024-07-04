// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT

if (typeof XYO === "undefined") {
    XYO = {};
};

if (typeof XYO.Web === "undefined") {
    XYO.Web = {};
};

XYO.Web.OverlayScrollbars = {};
XYO.Web.OverlayScrollbars.instance = null;

/**
 * Initialize Overlay Scrollbars
 * @param {elements} elements - Selected elements
 * @param {object} options - Overlay scrollbars config options
 */
XYO.Web.OverlayScrollbars.create = function(elements, options) {
	retV = [];
	if (!options) {
		options = {scrollbars : {clickScrolling : true}};
	};
	Array.from(elements).forEach(function(item) {
		retV.push(this.instance(item, options));
	},this);
	return retV;
};

/**
 * Destroy Overlay Scrollbars
 * @param {elements} elements - Selected elements
 */
XYO.Web.OverlayScrollbars.destroy = function(elements) {
	Array.from(elements).forEach(function(item) {
		this.instance(item).destroy();
	},this);	
};

/**
 * Destroy Overlay Scrollbars
 * @param {items} instance - Instance of created scrollbars
 */
XYO.Web.OverlayScrollbars.instanceDestroy = function(items) {
	items.forEach(function(item) {
		item.destroy();
	});	
};

/**
 * Update Overlay Scrollbars
 * @param {elements} elements - Selected elements
 */
XYO.Web.OverlayScrollbars.update = function(elements) {
	Array.from(elements).forEach(function(item) {
		this.instance(item).update(true);
	},this);
};

/**
 * Update Overlay Scrollbars
 * @param {items} instance - Instance of created scrollbars
 */
XYO.Web.OverlayScrollbars.instanceUpdate = function(items) {
	items.forEach(function(item) {
		item.update(true);
	});	
};

/**
 * Initialization
 */
XYO.Web.OverlayScrollbars.init = function() {
	this.instance = OverlayScrollbarsGlobal.OverlayScrollbars;
	this.instance.plugin(OverlayScrollbarsGlobal.ClickScrollPlugin);
	this.instance.nonce(document.scripts[0].nonce);
	XYO.Web.OverlayScrollbars.create(document.querySelectorAll(".overlay-scrollbars"));
};

/**
 * On load
 */
XYO.Web.OverlayScrollbars.onLoad = function() {
	window.removeEventListener("load", XYO.Web.OverlayScrollbars.onLoad);
	XYO.Web.OverlayScrollbars.init();
};
window.addEventListener("load", XYO.Web.OverlayScrollbars.onLoad);
