/**
 * Queue-based toast notification system (max 3 visible).
 *
 * Toasts slide in from the top, auto-dismiss after 3 seconds,
 * and support success / error / warning variants.
 *
 * @since 1.0.0
 */
(function() {
    var MAX_TOASTS = 3;
    var DISMISS_MS = 3000;
    var _queue = [];
    var _id = 0;
    var _icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>'
    };

    /** Get or create the toast container element. */
    function getContainer() {
        var c = document.getElementById('toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'toast-container';
            c.className = 'toast-container';
            c.setAttribute('aria-live', 'polite');
            c.setAttribute('aria-atomic', 'false');
            document.body.appendChild(c);
        }
        return c;
    }

    /** Dismiss a toast by its internal id. */
    function dismiss(id) {
        var idx = -1;
        for (var i = 0; i < _queue.length; i++) {
            if (_queue[i].id === id) { idx = i; break; }
        }
        if (idx === -1) return;
        var item = _queue[idx];
        clearTimeout(item.timer);
        item.el.classList.add('toast-out');
        setTimeout(function() {
            if (item.el.parentNode) item.el.parentNode.removeChild(item.el);
        }, 250);
        _queue.splice(idx, 1);
    }

    /**
     * Show a toast notification.
     *
     * @since 1.0.0
     *
     * @param {string} message The message to display.
     * @param {string} [type]  Variant: 'success' (default), 'error', or 'warning'.
     */
    TinyShop.toast = function(message, type) {
        type = type || 'success';
        var container = getContainer();
        var id = ++_id;
        var icon = _icons[type] || _icons.success;

        // Evict oldest if at max
        while (_queue.length >= MAX_TOASTS) {
            dismiss(_queue[0].id);
        }

        var el = document.createElement('div');
        el.className = 'toast-item toast-' + type;
        el.setAttribute('role', 'alert');
        var safe = document.createElement('span');
        safe.textContent = message;
        el.innerHTML = '<span class="toast-icon">' + icon + '</span>' +
            '<span class="toast-msg">' + safe.innerHTML + '</span>' +
            '<button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';
        container.appendChild(el);

        // Trigger entrance
        requestAnimationFrame(function() {
            requestAnimationFrame(function() { el.classList.add('toast-show'); });
        });

        var timer = setTimeout(function() { dismiss(id); }, DISMISS_MS);

        el.querySelector('.toast-close').addEventListener('click', function() { dismiss(id); });

        _queue.push({ id: id, el: el, timer: timer });
    };
})();
