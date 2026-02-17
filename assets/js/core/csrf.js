/* ============================================================
   CSRF protection — jQuery $.ajax + native fetch
   ============================================================ */
(function() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    TinyShop.csrfToken = token;

    if (token) {
        $.ajaxSetup({ headers: { 'X-CSRF-Token': token } });

        var _fetch = window.fetch;
        window.fetch = function(url, opts) {
            opts = opts || {};
            // Read live token — SPA navigation updates TinyShop.csrfToken
            var currentToken = TinyShop.csrfToken;
            var isSameOrigin = typeof url === 'string' && (url.startsWith('/') || url.startsWith(location.origin));
            if (isSameOrigin && currentToken) {
                if (opts.headers instanceof Headers) {
                    if (!opts.headers.has('X-CSRF-Token')) opts.headers.set('X-CSRF-Token', currentToken);
                } else {
                    opts.headers = Object.assign({ 'X-CSRF-Token': currentToken }, opts.headers || {});
                }
            }
            return _fetch.call(this, url, opts);
        };
    }
})();
