/* ============================================================
   Login modal — shown when session expires (cross-tab logout)
   ============================================================ */
(function() {
    var _overlay = null;
    var _showing = false;
    var _pendingUrl = null;

    function getOverlay() {
        if (_overlay && _overlay.isConnected) return _overlay;

        var div = document.createElement('div');
        div.className = 'login-modal-overlay';
        div.id = 'loginModal';
        div.innerHTML =
            '<div class="login-modal-box">' +
                '<div class="login-modal-handle"></div>' +
                '<div class="login-modal-header">' +
                    '<h2>Session expired</h2>' +
                    '<p>Please sign in again to continue.</p>' +
                '</div>' +
                '<div class="login-modal-body">' +
                    '<form id="loginModalForm">' +
                        '<div class="login-modal-field">' +
                            '<label for="loginModalEmail">Email</label>' +
                            '<input type="email" id="loginModalEmail" placeholder="you@example.com" autocomplete="email" required>' +
                        '</div>' +
                        '<div class="login-modal-field">' +
                            '<label for="loginModalPassword">Password</label>' +
                            '<input type="password" id="loginModalPassword" placeholder="Your password" autocomplete="current-password" required>' +
                        '</div>' +
                        '<div class="login-modal-error" id="loginModalError"></div>' +
                        '<button type="submit" class="login-modal-btn" id="loginModalBtn">Sign In</button>' +
                    '</form>' +
                '</div>' +
            '</div>';
        document.body.appendChild(div);
        _overlay = div;

        // Form submission
        div.querySelector('#loginModalForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var email = div.querySelector('#loginModalEmail').value.trim();
            var password = div.querySelector('#loginModalPassword').value;
            var btn = div.querySelector('#loginModalBtn');
            var errEl = div.querySelector('#loginModalError');

            if (!email || !password) {
                errEl.textContent = 'Email and password are required';
                errEl.classList.add('visible');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Signing in...';
            errEl.classList.remove('visible');

            $.ajax({
                url: '/api/auth/login',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ email: email, password: password }),
                success: function(res) {
                    if (res.success) {
                        TinyShop.hideLoginModal();
                        // Clear SPA cache — session changed
                        if (TinyShop.spa) TinyShop.spa._cache = {};
                        // Update CSRF token if returned
                        if (res.csrf) {
                            TinyShop.csrfToken = res.csrf;
                            $.ajaxSetup({ headers: { 'X-CSRF-Token': res.csrf } });
                            var meta = document.querySelector('meta[name="csrf-token"]');
                            if (meta) meta.setAttribute('content', res.csrf);
                        }
                        // Reload the page the user was trying to reach
                        var dest = _pendingUrl || location.pathname + location.search;
                        if (TinyShop.spa && TinyShop.spa._ready) {
                            TinyShop.spa.go(dest);
                        } else {
                            window.location.reload();
                        }
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong';
                    errEl.textContent = msg;
                    errEl.classList.add('visible');
                    btn.disabled = false;
                    btn.textContent = 'Sign In';
                }
            });
        });

        return div;
    }

    TinyShop.showLoginModal = function(targetUrl) {
        if (_showing) return;
        _showing = true;
        _pendingUrl = targetUrl || null;
        var overlay = getOverlay();
        // Reset form
        overlay.querySelector('#loginModalEmail').value = '';
        overlay.querySelector('#loginModalPassword').value = '';
        overlay.querySelector('#loginModalError').classList.remove('visible');
        overlay.querySelector('#loginModalBtn').disabled = false;
        overlay.querySelector('#loginModalBtn').textContent = 'Sign In';

        // Refresh CSRF token — old session is dead, need a fresh one
        $.getJSON('/api/auth/check').done(function(res) {
            if (res.csrf) {
                TinyShop.csrfToken = res.csrf;
                $.ajaxSetup({ headers: { 'X-CSRF-Token': res.csrf } });
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', res.csrf);
            }
        });

        // Show — CSS handles visibility via .active class
        overlay.classList.add('active');
        document.body.classList.add('login-modal-open');
        // Focus email field
        setTimeout(function() {
            overlay.querySelector('#loginModalEmail').focus();
        }, 100);
    };

    TinyShop.hideLoginModal = function() {
        if (!_showing || !_overlay) return;
        _showing = false;
        _pendingUrl = null;
        _overlay.classList.remove('active');
        document.body.classList.remove('login-modal-open');
    };

    TinyShop._isLoginModalShowing = function() {
        return _showing;
    };

    // Global AJAX handler — intercept 401 on API calls to show login modal
    $(document).ajaxError(function(event, xhr, settings) {
        if (xhr.status === 401 && settings.url && settings.url.indexOf('/api/') !== -1) {
            // Don't show modal for login attempts themselves
            if (settings.url.indexOf('/api/auth/login') !== -1) return;
            if (_showing) return;
            TinyShop.showLoginModal();
        }
    });
})();
