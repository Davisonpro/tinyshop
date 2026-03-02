/**
 * TinyShop — Dashboard JS
 *
 * Shared API helper and file upload used by dashboard pages.
 *
 * @since 1.0.0
 */
var TinyShop = window.TinyShop || {};

/**
 * Send a JSON API request.
 *
 * Automatically sets Content-Type for non-GET requests,
 * and clears the SPA cache after any mutation so stale
 * pages are not served from cache.
 *
 * @since 1.0.0
 *
 * @param {string} method HTTP method (GET, POST, PUT, DELETE).
 * @param {string} url    The endpoint URL.
 * @param {Object} [data] Request payload (JSON-serialised for non-GET).
 * @return {jqXHR} jQuery AJAX promise.
 */
TinyShop.api = function(method, url, data) {
    var opts = {
        method: method,
        url: url,
        dataType: 'json'
    };
    if (data && method !== 'GET') {
        opts.contentType = 'application/json';
        opts.data = JSON.stringify(data);
    }
    var xhr = $.ajax(opts);
    if (method !== 'GET' && TinyShop.spa) {
        xhr.done(function() { TinyShop.spa._cache = {}; });
    }
    return xhr;
};

/**
 * Upload a file to the server.
 *
 * @since 1.0.0
 *
 * @param {File}     file      The file to upload.
 * @param {Function} [onSuccess] Called with the uploaded file URL.
 * @param {Function} [onError]   Called with the error message.
 */
TinyShop.uploadFile = function(file, onSuccess, onError) {
    var formData = new FormData();
    formData.append('file', file);
    $.ajax({
        url: '/api/upload',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.success && onSuccess) onSuccess(res.url);
        },
        error: function(xhr) {
            var msg = xhr.responseJSON ? xhr.responseJSON.message : 'Upload failed';
            TinyShop.toast(msg, 'error');
            if (onError) onError(msg);
        }
    });
};
