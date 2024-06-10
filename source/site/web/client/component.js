/*!
// XYO.Web
// Copyright (c) 2024 Grigore Stefan <g_stefan@yahoo.com>
// MIT License (MIT) <http://opensource.org/licenses/MIT>
// SPDX-FileCopyrightText: 2024 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: MIT
*/

XYO.Web.Component = {};
XYO.Web.Component.AJAX = {};
XYO.Web.Component.token = "";
XYO.Web.Component.nonce = document.scripts[0].nonce;
XYO.Web.Components = [];

/**
 * Get request
 * @param {string} id - Component id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.get = function (id, payloadArray, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "?";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_component", id]);
    payloadArray.push(["_ajax", 1]);
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.get(payload, function (response) {
        XYO.Web.HTML.update(id, response, XYO.Web.Component.nonce);
    }, fnError, fnRequest, fnUpload, fnDownload);
};

/**
 * Post request
 * @param {string} id - Component id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} token - token required to post request 
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.post = function (id, payloadArray, token, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    if (typeof token === "undefined") {
        token = XYO.Web.Component.token;
    };
    payloadArray.push(["_component", id]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_token", token]);
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.post("", payload, function (response) {
        XYO.Web.HTML.update(id, response, XYO.Web.Component.nonce);
    }, fnError, fnRequest, fnUpload, fnDownload);
};

