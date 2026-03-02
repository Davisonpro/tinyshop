/**
 * CSRF protection for jQuery $.ajax and native fetch.
 *
 * Reads the token from the <meta name="csrf-token"> tag and
 * injects it as an X-CSRF-Token header on every same-origin
 * request. The live value of TinyShop.csrfToken is used so
 * SPA navigation can refresh it without re-patching fetch.
 *
 * @since 1.0.0
 */
(function() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    TinyShop.csrfToken = token;

    if (token) {
        $.ajaxSetup({ headers: { 'X-CSRF-Token': token } });

        var _fetch = window.fetch;
        window.fetch = function(url, opts) {
            opts = opts || {};
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
