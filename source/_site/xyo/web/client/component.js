/*!
// XYO.Web
// SPDX-FileCopyrightText: 2024-2026 Grigore Stefan <g_stefan@yahoo.com>
// SPDX-License-Identifier: Apache-2.0
*/

XYO.Web.Component = {};
XYO.Web.Component.AJAX = {};
XYO.Web.Component.nonce = document.currentScript.nonce;
XYO.Web.Component.tabId = (function () {
    var key = "__xyoWebTab";
    var value = sessionStorage.getItem(key);
    if (!value) {
        value = (Date.now().toString(36) + Math.random().toString(36).slice(2, 10));
        sessionStorage.setItem(key, value);
    }
    return value;
})();
XYO.Web.Components = [];

/**
 * Get request
 * @param {string} componentId - Component id
 * @param {string} elementId - Element id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.get = function (componentId, elementId, payloadArray, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "?";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_component", componentId]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);
    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.get(payload, function (response) {
        XYO.Web.HTML.update(elementId, response, XYO.Web.Component.nonce, fnError);
    }, fnError, fnRequest, fnUpload, fnDownload);
};

/**
 * Post request
 * @param {string} componentId - Component id
 * @param {string} elementId - Element id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} token - token required to post request 
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.post = function (componentId, elementId, payloadArray, token, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_component", componentId]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_token", token]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);

    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.post("", payload, function (response) {
        XYO.Web.HTML.update(elementId, response, XYO.Web.Component.nonce, fnError);
    }, fnError, fnRequest, fnUpload, fnDownload);
};

/**
 * Post form request
 * @param {string} componentId - Component id
 * @param {string} formId - Form id
 * @param {string} elementId - Element id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} token - token required to post request 
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.postForm = function (componentId, formId, elementId, payloadArray, token, fnError, fnUpload, fnDownload) {
    var el = document.getElementById(formId);
    if (!el) {
        if (fnError) {
            fnError();
        };
        return null;
    };

    var payloadForm = new FormData(el);

    var payload = "";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_component", componentId]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_token", token]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);

    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payloadForm.set(payloadArray[i][0], payloadArray[i][1]);
    };

    XYO.Web.AJAX.post("", payloadForm, function (response) {
        XYO.Web.HTML.update(elementId, response, XYO.Web.Component.nonce, fnError);
    }, fnError, function (request) { }, fnUpload, fnDownload);
};

/**
 * Batch get request
 * @param {string} componentIdList - Component id list, "id1,id2,id3"
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.batchGet = function (componentIdList, payloadArray, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "?";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_batch", componentIdList]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);

    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.get(payload, function (response) {
        try {
            var inputList = JSON.parse(response);
            XYO.Web.HTML.batchUpdate(inputList, XYO.Web.Component.nonce, fnError);
        } catch (e) {
            if (fnError) {
                fnError();
            };
        };
    }, fnError, fnRequest, fnUpload, fnDownload);
};

/**
  * Batch post request
 * @param {string} componentIdList - Component id list, "id1,id2,id3"
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} token - token required to post request 
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnRequest] - Customize request - fnRequest(request)
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.batchPost = function (componentIdList, payloadArray, token, fnError, fnRequest, fnUpload, fnDownload) {
    var payload = "";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_batch", componentIdList]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_token", token]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);

    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payload += encodeURIComponent(payloadArray[i][0]) + "=" + encodeURIComponent(payloadArray[i][1]) + "&";
    };
    XYO.Web.AJAX.post("", payload, function (response) {
        try {
            var inputList = JSON.parse(response);
            XYO.Web.HTML.batchUpdate(inputList, XYO.Web.Component.nonce, fnError);
        } catch (e) {
            if (fnError) {
                fnError();
            };
        };
    }, fnError, fnRequest, fnUpload, fnDownload);
};

/**
 * Batch post form request
 * @param {string} componentIdList - Component id list, "id1,id2,id3"
 * @param {string} formId - Form id
 * @param {string} payloadArray - Payload to send, array of ["name","value"]
 * @param {string} token - token required to post request 
 * @param {string} [fnError] - Call on error - fnError()
 * @param {function} [fnUpload] - Upload progress - fnUpload(loaded,total,isComputable)
 * @param {function} [fnDownload] - Download progress - fnDownload(loaded,total,isComputable)
 * @returns {XMLHttpRequest} Request
 */
XYO.Web.Component.AJAX.batchPostForm = function (componentIdList, formId, payloadArray, token, fnError, fnUpload, fnDownload) {
    var el = document.getElementById(formId);
    if (!el) {
        if (fnError) {
            fnError();
        };
        return null;
    };

    var payloadForm = new FormData(el);

    var payload = "";
    if (!Array.isArray(payloadArray)) {
        payloadArray = [];
    };
    payloadArray.push(["_batch", componentIdList]);
    payloadArray.push(["_ajax", 1]);
    payloadArray.push(["_token", token]);
    payloadArray.push(["_tab", XYO.Web.Component.tabId]);

    var i;
    for (i = 0; i < payloadArray.length; ++i) {
        payloadForm.set(payloadArray[i][0], payloadArray[i][1]);
    };

    XYO.Web.AJAX.post("", payloadForm, function (response) {
        try {
            var inputList = JSON.parse(response);
            XYO.Web.HTML.batchUpdate(inputList, XYO.Web.Component.nonce, fnError);
        } catch (e) {
            if (fnError) {
                fnError();
            };
        };
    }, fnError, function (request) { }, fnUpload, fnDownload);
};
