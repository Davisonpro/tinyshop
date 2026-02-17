/**
 * TinyShop — Dashboard JS
 * Product list, product form page, image uploads, categories, localStorage draft.
 */
var TinyShop = window.TinyShop || {};

/* ============================================================
   API Helper
   ============================================================ */
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
    return $.ajax(opts);
};

/* ============================================================
   File Upload
   ============================================================ */
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
