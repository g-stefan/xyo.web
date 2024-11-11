/*!
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT
*/

XYO.Web.AJAX = {};

/**
 * Get request
 * @param {string} url
 * @param {function} fn - Call on success - fn(response)
 * @param {function} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.AJAX.get = function (url, fn, fnError, fnRequest, fnUpload, fnDownload) {
	var request = new XMLHttpRequest();
	request.onreadystatechange = function () {
		if (this.readyState == 4) {
			if (this.status == 200) {
				try {
					fn(this.responseText);
				} catch (e) {
					if (fnError) {
						fnError();
					};
				};
			} else {
				if (fnError) {
					fnError();
				};
			};
		};
	};
	request.open("GET", url, true);
	if (fnRequest) {
		fnRequest(request);
	};
	if (fnUpload) {
		request.upload.addEventListener("progress", function (event) {
			fnUpload(event.loaded, event.total, event.lengthComputable);
		}, false);
	};
	if (fnDownload) {
		request.addEventListener("progress", function (event) {
			fnDownload(event.loaded, event.total, event.lengthComputable);
		}, false);
	};
	request.send();
	return request;
};

/**
 * Post request
 * @param {string} url
 * @param {string} payload - Payload to send, form urlencoded
 * @param {string} fn - Call on success - fn(response)
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.AJAX.post = function (url, payload, fn, fnError, fnRequest, fnUpload, fnDownload) {
	var request = new XMLHttpRequest();
	request.onreadystatechange = function () {
		if (this.readyState == 4) {
			if (this.status == 200) {
				try {
					fn(this.responseText);
				} catch (e) {
					if (fnError) {
						fnError();
					};
				};
			} else {
				if (fnError) {
					fnError();
				};
			};
		};
	};
	request.open("POST", url, true);
	if (fnRequest) {
		fnRequest(request);
	} else {
		request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");
	};
	if (fnUpload) {
		request.upload.addEventListener("progress", function (event) {
			fnUpload(event.loaded, event.total, event.lengthComputable);
		}, false);
	};
	if (fnDownload) {
		request.addEventListener("progress", function (event) {
			fnDownload(event.loaded, event.total, event.lengthComputable);
		}, false);
	};
	request.send(payload);
	return request;
};

/**
 * Get json request
 * @param {string} url
 * @param {function} fn - Call on success with parsed result - fn(response)
 * @param {function} [fnError] - Call on error - fnError()
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.AJAX.getJson = function (url, fn, fnError, fnUpload, fnDownload) {
	return XYO.Web.AJAX.get(url, function (result) {
		fn(JSON.parse(result));
	}, fnError, null, fnUpload, fnDownload);
};

/**
 * Post json request
 * @param {string} url
 * @param {string} payload - Object to send, will be converted to json
 * @param {string} fn - Call on success with parsed result
 * @param {function} [fnError] - Call on error - fnError()
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.AJAX.postJson = function (url, payload, fn, fnError, fnUpload, fnDownload) {
	return XYO.Web.AJAX.post(url, JSON.stringify(payload), function (result) { fn(JSON.parse(result)); }, fnError, function (request) { request.setRequestHeader("Content-Type", "application/json"); }, fnUpload, fnDownload);
};

/**
 * Post form request
 * @param {string} url
 * @param {string} form - Form to send, will be converted to FormData
 * @param {string} fn - Call on success with parsed result
 * @param {function} [fnError] - Call on error - fnError()
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.AJAX.postForm = function (url, form, fn, fnError, fnUpload, fnDownload) {
	return XYO.Web.AJAX.post(url, new FormData(form), fn, fnError, function (request) { }, fnUpload, fnDownload);
};
